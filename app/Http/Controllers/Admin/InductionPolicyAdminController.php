<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesPortalAccess;
use App\Http\Controllers\Controller;
use App\Models\InductionPolicy;
use App\Models\InductionPolicyVersion;
use App\Models\InductionSection;
use App\Models\InductionSubClause;
use App\Services\Induction\InductionPolicyAdminChangeService;
use App\Support\InductionAcknowledgementMode;
use App\Support\InductionChangeLogDiff;
use App\Support\InductionPolicyChangeNotification;
use App\Support\PortalAccessRules;
use App\Support\RichHtmlPurifier;
use App\Support\RichTextHelper;
use App\Support\RichTextLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InductionPolicyAdminController extends Controller
{
    use AuthorizesPortalAccess;

    public function __construct(
        private readonly InductionPolicyAdminChangeService $adminChangeService,
    ) {}

    private function staffRepeatFromRequest(Request $request, bool $required = false): bool
    {
        if ($required) {
            $request->validate([
                'staff_must_repeat_induction' => ['required', 'in:0,1'],
            ]);
        }

        return $request->input('staff_must_repeat_induction') === '1';
    }

    /**
     * @return array{ip: string|null, user_agent: string|null, correlation_id: string}
     */
    private function auditRequestContext(Request $request): array
    {
        return [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'correlation_id' => (string) Str::uuid(),
        ];
    }

    private function resolveSection(InductionPolicy $policy, InductionSection $section): InductionSection
    {
        $version = $policy->ensureEditableVersion();
        abort_unless($section->induction_policy_version_id === $version->id, 404);

        return $section;
    }

    private function resolveSubClause(InductionPolicy $policy, InductionSection $section, InductionSubClause $subClause): InductionSubClause
    {
        $section = $this->resolveSection($policy, $section);
        abort_unless($subClause->induction_section_id === $section->id, 404);

        return $subClause;
    }

    public function index(): View|RedirectResponse
    {
        $this->authorizeInductionAdminIndex();

        $user = $this->portalUser();
        $canReadPolicies = PortalAccessRules::canReadInductionPolicies($user);

        if (! $canReadPolicies && PortalAccessRules::canReadInductionEnrollment($user)) {
            return redirect()->route('admin.induction.progress.index');
        }

        $policies = $canReadPolicies
            ? InductionPolicy::query()
                ->with([
                    'versions' => fn ($q) => $q
                        ->whereNotNull('published_at')
                        ->orderByDesc('published_at')
                        ->limit(1)
                        ->withCount('sections'),
                ])
                ->ordered()
                ->get()
            : collect();

        return view('admin.induction.index', compact('policies', 'canReadPolicies'));
    }

    public function reorderPolicy(Request $request, InductionPolicy $policy): RedirectResponse
    {
        $this->authorizeUpdateInductionPolicies();

        $data = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ]);

        $policies = InductionPolicy::query()->ordered()->get();
        $index = $policies->search(fn (InductionPolicy $p): bool => $p->id === $policy->id);
        if ($index === false) {
            return back()->withErrors(['policy' => 'Policy not found.']);
        }

        $swapIndex = $data['direction'] === 'up' ? $index - 1 : $index + 1;
        $other = $policies->get($swapIndex);
        if ($other === null) {
            return back();
        }

        DB::transaction(function () use ($policy, $other): void {
            $policyOrder = $policy->sort_order;
            $policy->update(['sort_order' => $other->sort_order]);
            $other->update(['sort_order' => $policyOrder]);
        });

        return back()->with('success', 'Policy display order updated.');
    }

    public function showPolicy(InductionPolicy $policy): View
    {
        $this->authorizeReadInductionPolicies();

        $policy->load([
            'versions' => fn ($q) => $q
                ->whereNotNull('published_at')
                ->orderByDesc('published_at')
                ->limit(1),
            'versions.sections' => fn ($q) => $q
                ->orderBy('sort_order')
                ->withCount(['subClauses as active_sub_clauses_count' => fn ($q) => $q->whereNull('archived_at')]),
        ]);

        return view('admin.induction.policies.show', compact('policy'));
    }

    public function storePolicy(Request $request): RedirectResponse
    {
        $this->authorizeCreateInductionPolicies();
        $this->mergeNormalizedAbbreviation($request, 'create_abbreviation');
        $data = $request->validate([
            'create_name' => ['required', 'string', 'max:255'],
            'create_abbreviation' => $this->policyAbbreviationRules(),
            'acknowledgement_mode' => InductionAcknowledgementMode::validationRules(),
            'notify_employees' => ['sometimes', 'boolean'],
        ]);

        $slug = $this->uniqueSlugFromName($data['create_name']);
        $abbreviation = $data['create_abbreviation'];
        $ctx = $this->auditRequestContext($request);
        $policy = null;

        $notify = $this->shouldNotifyEmployees($request);
        $ackMode = $data['acknowledgement_mode'];

        DB::transaction(function () use ($data, $slug, $abbreviation, $ctx, $notify, $ackMode, &$policy): void {
            $policy = InductionPolicy::query()->create([
                'name' => $data['create_name'],
                'abbreviation' => $abbreviation,
                'slug' => $slug,
                'is_active' => true,
                'sort_order' => (int) InductionPolicy::query()->max('sort_order') + 1,
                'acknowledgement_mode' => $ackMode,
            ]);

            $version = $policy->versions()->create([
                'version_label' => 'Current',
                'published_at' => now(),
                'created_by' => auth()->id(),
            ]);

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_policy.created',
                subjectType: InductionPolicy::class,
                subjectId: $policy->id,
                policyId: $policy->id,
                versionId: $version->id,
                metadata: ['subject_label' => $policy->name],
                changes: InductionChangeLogDiff::created(
                    $policy->only(['name', 'abbreviation', 'is_active', 'acknowledgement_mode']),
                    InductionChangeLogDiff::POLICY_FIELDS,
                ),
                staffRepeatRequested: false,
                versionForRepeat: $version,
                complianceContext: $ctx,
                notifyEmployeesRequested: $notify,
                changeNotification: InductionPolicyChangeNotification::policyNew($policy),
            );
        });

        return redirect()
            ->route('admin.induction.policies.show', $policy)
            ->with('success', 'Policy created.');
    }

    public function updatePolicy(Request $request, InductionPolicy $policy): RedirectResponse
    {
        $this->authorizeUpdateInductionPolicies();
        $this->mergeNormalizedAbbreviation($request, "policy.{$policy->id}.abbreviation");
        $data = $request->validate([
            "policy.{$policy->id}.name" => ['required', 'string', 'max:255'],
            "policy.{$policy->id}.abbreviation" => $this->policyAbbreviationRules($policy),
            "policy.{$policy->id}.is_active" => ['required', 'in:0,1'],
            'acknowledgement_mode' => InductionAcknowledgementMode::validationRules(),
            'notify_employees' => ['sometimes', 'boolean'],
        ]);

        $before = $policy->only(['name', 'abbreviation', 'slug', 'is_active', 'acknowledgement_mode']);
        $version = $policy->ensureEditableVersion();
        $ctx = $this->auditRequestContext($request);
        $abbreviation = $data['policy'][$policy->id]['abbreviation'];
        $notify = $this->shouldNotifyEmployees($request);

        DB::transaction(function () use ($policy, $data, $abbreviation, $before, $version, $ctx, $notify): void {
            $policy->forceFill([
                'name' => $data['policy'][$policy->id]['name'],
                'abbreviation' => $abbreviation,
                'is_active' => $data['policy'][$policy->id]['is_active'] === '1',
                'acknowledgement_mode' => $data['acknowledgement_mode'],
            ])->save();

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_policy.updated',
                subjectType: InductionPolicy::class,
                subjectId: $policy->id,
                policyId: $policy->id,
                versionId: $version->id,
                metadata: ['subject_label' => $policy->name],
                changes: InductionChangeLogDiff::between(
                    $before,
                    $policy->only(['name', 'abbreviation', 'is_active', 'acknowledgement_mode']),
                    InductionChangeLogDiff::POLICY_FIELDS,
                ),
                staffRepeatRequested: false,
                versionForRepeat: $version,
                complianceContext: $ctx,
                notifyEmployeesRequested: $notify,
                changeNotification: InductionPolicyChangeNotification::policyAmendment($policy),
            );
        });

        return redirect()
            ->route('admin.induction.policies.show', $policy)
            ->with('success', 'Policy saved.');
    }

    public function createSection(InductionPolicy $policy): View
    {
        $this->authorizeCreateInductionPolicies();
        $policy->ensureEditableVersion();

        return view('admin.induction.clauses.create', compact('policy'));
    }

    public function storeSection(Request $request, InductionPolicy $policy): RedirectResponse
    {
        $this->authorizeCreateInductionPolicies();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'acknowledgement_mode' => InductionAcknowledgementMode::validationRules(),
            'notify_employees' => ['sometimes', 'boolean'],
        ]);
        $body = $this->validatedSectionBody($request);
        $ack = $this->acknowledgementAttributesFromMode($data['acknowledgement_mode']);

        $version = $policy->ensureEditableVersion();
        $ctx = $this->auditRequestContext($request);
        $notify = $this->shouldNotifyEmployees($request);
        $section = null;

        DB::transaction(function () use (&$section, $version, $data, $body, $ack, $ctx, $notify, $policy): void {
            $section = InductionSection::query()->create([
                'induction_policy_version_id' => $version->id,
                'sort_order' => $this->nextSectionSortOrder($version),
                'title' => $data['title'],
                'body' => $body,
                'requires_signature' => $ack['requires_signature'],
                'acknowledgement_mode' => $ack['acknowledgement_mode'],
                'acknowledgement_hint' => null,
            ]);

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_section.created',
                subjectType: InductionSection::class,
                subjectId: $section->id,
                policyId: $version->induction_policy_id,
                versionId: $version->id,
                metadata: ['subject_label' => 'Clause: '.$section->title],
                changes: InductionChangeLogDiff::created(
                    array_merge(
                        $section->only(['title', 'requires_signature', 'acknowledgement_mode']),
                        ['body' => $section->body],
                    ),
                    InductionChangeLogDiff::SECTION_FIELDS,
                ),
                staffRepeatRequested: false,
                versionForRepeat: $version,
                complianceContext: $ctx,
                notifyEmployeesRequested: $notify,
                changeNotification: InductionPolicyChangeNotification::clauseNew($policy, $section->title),
            );
        });

        return redirect()
            ->route('admin.induction.policies.show', $policy)
            ->with('success', 'Clause created.');
    }

    public function showSection(InductionPolicy $policy, InductionSection $section): View
    {
        $this->authorizeReadInductionPolicies();
        $section = $this->resolveSection($policy, $section);
        $section->load(['subClauses' => fn ($q) => $q->orderBy('sort_order')]);

        return view('admin.induction.clauses.show', compact('policy', 'section'));
    }

    public function editSection(InductionPolicy $policy, InductionSection $section): View
    {
        $this->authorizeUpdateInductionPolicies();
        $section = $this->resolveSection($policy, $section);

        return view('admin.induction.clauses.edit', compact('policy', 'section'));
    }

    public function updateSection(Request $request, InductionPolicy $policy, InductionSection $section): RedirectResponse
    {
        $this->authorizeUpdateInductionPolicies();
        $section = $this->resolveSection($policy, $section);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'acknowledgement_mode' => InductionAcknowledgementMode::validationRules(),
            'notify_employees' => ['sometimes', 'boolean'],
        ]);
        $body = $this->validatedSectionBody($request);
        $repeat = $this->staffRepeatFromRequest($request, required: true);
        $ack = $this->acknowledgementAttributesFromMode($data['acknowledgement_mode']);
        $notify = $this->shouldNotifyEmployees($request);

        $version = $policy->ensureEditableVersion();
        $before = $section->only(['title', 'body', 'sort_order', 'archived_at', 'requires_signature', 'acknowledgement_mode']);
        $ctx = $this->auditRequestContext($request);

        DB::transaction(function () use ($section, $version, $data, $body, $repeat, $before, $ack, $ctx, $notify, $policy): void {
            $section->update([
                'title' => $data['title'],
                'body' => $body,
                'requires_signature' => $ack['requires_signature'],
                'acknowledgement_mode' => $ack['acknowledgement_mode'],
            ]);

            $after = $section->fresh()->only(['title', 'body', 'requires_signature', 'acknowledgement_mode', 'archived_at']);

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_section.updated',
                subjectType: InductionSection::class,
                subjectId: $section->id,
                policyId: $version->induction_policy_id,
                versionId: $version->id,
                metadata: ['subject_label' => 'Clause: '.$section->title],
                changes: InductionChangeLogDiff::between($before, $after, InductionChangeLogDiff::SECTION_FIELDS),
                staffRepeatRequested: $repeat,
                versionForRepeat: $version,
                complianceContext: $ctx,
                notifyEmployeesRequested: $notify,
                changeNotification: InductionPolicyChangeNotification::clauseAmendment($policy, $section->title),
            );
        });

        return redirect()
            ->route('admin.induction.policies.clauses.show', [$policy, $section])
            ->with('success', 'Clause saved.');
    }

    public function archiveSection(Request $request, InductionPolicy $policy, InductionSection $section): RedirectResponse
    {
        $this->authorizeArchiveInductionPolicies();
        $section = $this->resolveSection($policy, $section);

        $version = $policy->ensureEditableVersion();
        $ctx = $this->auditRequestContext($request);

        DB::transaction(function () use ($section, $version, $ctx): void {
            $section->forceFill(['archived_at' => now()])->save();
            $section->subClauses()->whereNull('archived_at')->update(['archived_at' => now()]);

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_section.archived',
                subjectType: InductionSection::class,
                subjectId: $section->id,
                policyId: $version->induction_policy_id,
                versionId: $version->id,
                metadata: ['subject_label' => 'Clause: '.$section->title],
                changes: [
                    [
                        'field' => 'status',
                        'label' => 'Status',
                        'from' => 'Active',
                        'to' => 'Archived',
                    ],
                ],
                staffRepeatRequested: false,
                versionForRepeat: $version,
                complianceContext: $ctx,
            );
        });

        return redirect()
            ->route('admin.induction.policies.show', $policy)
            ->with('success', 'Clause archived.');
    }

    public function storeSubClause(Request $request, InductionPolicy $policy, InductionSection $section): RedirectResponse
    {
        $this->authorizeCreateInductionPolicies();
        $section = $this->resolveSection($policy, $section);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'acknowledgement_mode' => InductionAcknowledgementMode::validationRules(),
            'notify_employees' => ['sometimes', 'boolean'],
        ]);
        $body = $this->validatedSubClauseBody($request);
        $ack = $this->acknowledgementAttributesFromMode($data['acknowledgement_mode']);

        $version = $policy->ensureEditableVersion();
        $ctx = $this->auditRequestContext($request);
        $notify = $this->shouldNotifyEmployees($request);
        $subClause = null;

        DB::transaction(function () use (&$subClause, $section, $version, $data, $body, $ack, $ctx, $notify, $policy): void {
            $subClause = InductionSubClause::query()->create([
                'induction_section_id' => $section->id,
                'sort_order' => $this->nextSubClauseSortOrder($section),
                'title' => $data['title'],
                'body' => $body,
                'acknowledgement_mode' => $ack['acknowledgement_mode'],
            ]);

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_sub_clause.created',
                subjectType: InductionSubClause::class,
                subjectId: $subClause->id,
                policyId: $version->induction_policy_id,
                versionId: $version->id,
                metadata: [
                    'induction_section_id' => $section->id,
                    'subject_label' => 'Sub-clause: '.$subClause->title.' (clause: '.$section->title.')',
                ],
                changes: InductionChangeLogDiff::created(
                    array_merge(
                        $subClause->only(['title', 'acknowledgement_mode']),
                        ['body' => $subClause->body],
                    ),
                    InductionChangeLogDiff::SUB_CLAUSE_FIELDS,
                ),
                staffRepeatRequested: false,
                versionForRepeat: $version,
                complianceContext: $ctx,
                notifyEmployeesRequested: $notify,
                changeNotification: InductionPolicyChangeNotification::subClauseNew($policy, $section, $subClause->title),
            );
        });

        return redirect()
            ->route('admin.induction.policies.clauses.show', [$policy, $section])
            ->with('success', 'Sub-clause created.');
    }

    public function showSubClause(InductionPolicy $policy, InductionSection $section, InductionSubClause $sub_clause): View
    {
        $this->authorizeReadInductionPolicies();
        $subClause = $this->resolveSubClause($policy, $section, $sub_clause);

        return view('admin.induction.sub-clauses.show', [
            'policy' => $policy,
            'section' => $section,
            'subClause' => $subClause,
        ]);
    }

    public function editSubClause(InductionPolicy $policy, InductionSection $section, InductionSubClause $sub_clause): View
    {
        $this->authorizeUpdateInductionPolicies();
        $subClause = $this->resolveSubClause($policy, $section, $sub_clause);

        return view('admin.induction.sub-clauses.edit', [
            'policy' => $policy,
            'section' => $section,
            'subClause' => $subClause,
        ]);
    }

    public function updateSubClause(Request $request, InductionPolicy $policy, InductionSection $section, InductionSubClause $sub_clause): RedirectResponse
    {
        $this->authorizeUpdateInductionPolicies();
        $subClause = $this->resolveSubClause($policy, $section, $sub_clause);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'acknowledgement_mode' => InductionAcknowledgementMode::validationRules(),
            'notify_employees' => ['sometimes', 'boolean'],
        ]);
        $body = $this->validatedSubClauseBody($request);
        $repeat = $this->staffRepeatFromRequest($request, required: true);
        $ack = $this->acknowledgementAttributesFromMode($data['acknowledgement_mode']);
        $notify = $this->shouldNotifyEmployees($request);

        $version = $policy->ensureEditableVersion();
        $before = $subClause->only(['title', 'body', 'sort_order', 'archived_at', 'acknowledgement_mode']);
        $ctx = $this->auditRequestContext($request);

        DB::transaction(function () use ($subClause, $section, $version, $data, $body, $repeat, $before, $ack, $ctx, $notify, $policy): void {
            $subClause->update([
                'title' => $data['title'],
                'body' => $body,
                'acknowledgement_mode' => $ack['acknowledgement_mode'],
            ]);

            $after = $subClause->fresh()->only(['title', 'body', 'acknowledgement_mode', 'archived_at']);

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_sub_clause.updated',
                subjectType: InductionSubClause::class,
                subjectId: $subClause->id,
                policyId: $version->induction_policy_id,
                versionId: $version->id,
                metadata: [
                    'induction_section_id' => $section->id,
                    'subject_label' => 'Sub-clause: '.$subClause->title.' (clause: '.$section->title.')',
                ],
                changes: InductionChangeLogDiff::between($before, $after, InductionChangeLogDiff::SUB_CLAUSE_FIELDS),
                staffRepeatRequested: $repeat,
                versionForRepeat: $version,
                complianceContext: $ctx,
                notifyEmployeesRequested: $notify,
                changeNotification: InductionPolicyChangeNotification::subClauseAmendment($policy, $section, $subClause->title),
            );
        });

        return redirect()
            ->route('admin.induction.policies.clauses.sub-clauses.show', [$policy, $section, $subClause])
            ->with('success', 'Sub-clause saved.');
    }

    public function archiveSubClause(Request $request, InductionPolicy $policy, InductionSection $section, InductionSubClause $sub_clause): RedirectResponse
    {
        $this->authorizeArchiveInductionPolicies();
        $subClause = $this->resolveSubClause($policy, $section, $sub_clause);

        $version = $policy->ensureEditableVersion();
        $ctx = $this->auditRequestContext($request);

        DB::transaction(function () use ($subClause, $section, $version, $ctx): void {
            $subClause->forceFill(['archived_at' => now()])->save();

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_sub_clause.archived',
                subjectType: InductionSubClause::class,
                subjectId: $subClause->id,
                policyId: $version->induction_policy_id,
                versionId: $version->id,
                metadata: [
                    'induction_section_id' => $section->id,
                    'subject_label' => 'Sub-clause: '.$subClause->title.' (clause: '.$section->title.')',
                ],
                changes: [
                    [
                        'field' => 'status',
                        'label' => 'Status',
                        'from' => 'Active',
                        'to' => 'Archived',
                    ],
                ],
                staffRepeatRequested: false,
                versionForRepeat: $version,
                complianceContext: $ctx,
            );
        });

        return redirect()
            ->route('admin.induction.policies.clauses.show', [$policy, $section])
            ->with('success', 'Sub-clause archived.');
    }

    private function validatedSectionBody(Request $request): string
    {
        return $this->validatedRichBody($request, InductionSection::BODY_MAX_WORDS);
    }

    private function validatedSubClauseBody(Request $request): string
    {
        return $this->validatedRichBody($request, InductionSubClause::BODY_MAX_WORDS);
    }

    private function validatedRichBody(Request $request, int $maxWords): string
    {
        $maxChars = RichTextLimits::maxStoredCharsForWords($maxWords);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:'.$maxChars],
        ]);

        $raw = $data['body'];

        if (RichTextHelper::wordCountFromHtml($raw) > $maxWords) {
            throw ValidationException::withMessages([
                'body' => ["Maximum {$maxWords} words."],
            ]);
        }

        $html = RichHtmlPurifier::purify($raw);

        if (! RichTextHelper::hasTextContent($html)) {
            throw ValidationException::withMessages([
                'body' => ['Content is required.'],
            ]);
        }

        if (RichTextHelper::wordCountFromHtml($html) > $maxWords) {
            throw ValidationException::withMessages([
                'body' => ['Too long after sanitising.'],
            ]);
        }

        return $html;
    }

    private function nextSectionSortOrder(InductionPolicyVersion $version): int
    {
        return (int) $version->sections()->max('sort_order') + 1;
    }

    private function nextSubClauseSortOrder(InductionSection $section): int
    {
        return (int) $section->subClauses()->max('sort_order') + 1;
    }

    /**
     * @return array<int, mixed>
     */
    private function policyAbbreviationRules(?InductionPolicy $policy = null): array
    {
        return [
            'required',
            'string',
            'min:2',
            'max:'.InductionPolicy::ABBREVIATION_MAX_LENGTH,
            'regex:/^[A-Za-z0-9]+$/',
            Rule::unique('induction_policies', 'abbreviation')->ignore($policy?->id),
        ];
    }

    private function mergeNormalizedAbbreviation(Request $request, string $key): void
    {
        if (! $request->has($key)) {
            return;
        }

        $request->merge([$key => $this->normalizeAbbreviation((string) $request->input($key))]);
    }

    private function normalizeAbbreviation(string $value): string
    {
        return strtoupper(trim($value));
    }

    private function uniqueSlugFromName(string $name): string
    {
        $base = Str::slug($name) ?: 'policy';
        $slug = $base;
        $i = 1;
        while (InductionPolicy::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return Str::limit($slug, 64, '');
    }

    private function shouldNotifyEmployees(Request $request): bool
    {
        return $request->boolean('notify_employees');
    }

    /**
     * @return array{acknowledgement_mode: string, requires_signature: bool}
     */
    private function acknowledgementAttributesFromMode(string $mode): array
    {
        return [
            'acknowledgement_mode' => $mode,
            'requires_signature' => InductionAcknowledgementMode::requiresSignature($mode),
        ];
    }
}
