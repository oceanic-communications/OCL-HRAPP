<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesPortalAccess;
use App\Http\Controllers\Controller;
use App\Models\InductionPolicy;
use App\Models\InductionSection;
use App\Services\Induction\InductionPolicyAdminChangeService;
use App\Support\PortalAccessRules;
use App\Support\RichHtmlPurifier;
use App\Support\RichTextHelper;
use App\Support\RichTextLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
                ->orderBy('name')
                ->get()
            : collect();

        return view('admin.induction.index', compact('policies', 'canReadPolicies'));
    }

    public function showPolicy(InductionPolicy $policy): View
    {
        $this->authorizeReadInductionPolicies();

        $policy->load([
            'versions' => fn ($q) => $q
                ->whereNotNull('published_at')
                ->orderByDesc('published_at')
                ->limit(1),
            'versions.sections' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        return view('admin.induction.policies.show', compact('policy'));
    }

    public function storePolicy(Request $request): RedirectResponse
    {
        $this->authorizeCreateInductionPolicies();
        $data = $request->validate([
            'create_name' => ['required', 'string', 'max:255'],
        ]);

        $slug = $this->uniqueSlugFromName($data['create_name']);
        $ctx = $this->auditRequestContext($request);
        $policy = null;

        DB::transaction(function () use ($data, $slug, $ctx, &$policy): void {
            $policy = InductionPolicy::query()->create([
                'name' => $data['create_name'],
                'slug' => $slug,
                'is_active' => true,
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
                metadata: ['after' => $policy->only(['name', 'slug', 'is_active'])],
                staffRepeatRequested: false,
                versionForRepeat: $version,
                complianceContext: $ctx,
            );
        });

        return redirect()
            ->route('admin.induction.policies.show', $policy)
            ->with('success', 'Policy created.');
    }

    public function updatePolicy(Request $request, InductionPolicy $policy): RedirectResponse
    {
        $this->authorizeUpdateInductionPolicies();
        $data = $request->validate([
            "policy.{$policy->id}.name" => ['required', 'string', 'max:255'],
            "policy.{$policy->id}.is_active" => ['required', 'in:0,1'],
        ]);

        $before = $policy->only(['name', 'slug', 'is_active']);
        $version = $policy->ensureEditableVersion();
        $ctx = $this->auditRequestContext($request);

        DB::transaction(function () use ($policy, $data, $before, $version, $ctx): void {
            $policy->forceFill([
                'name' => $data['policy'][$policy->id]['name'],
                'is_active' => $data['policy'][$policy->id]['is_active'] === '1',
            ])->save();

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_policy.updated',
                subjectType: InductionPolicy::class,
                subjectId: $policy->id,
                policyId: $policy->id,
                versionId: $version->id,
                metadata: ['before' => $before, 'after' => $policy->only(['name', 'slug', 'is_active'])],
                staffRepeatRequested: false,
                versionForRepeat: $version,
                complianceContext: $ctx,
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
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'requires_signature' => ['sometimes', 'boolean'],
        ]);
        $body = $this->validatedSectionBody($request);

        $version = $policy->ensureEditableVersion();
        $ctx = $this->auditRequestContext($request);
        $section = null;

        DB::transaction(function () use (&$section, $version, $data, $body, $request, $ctx): void {
            $max = (int) $version->sections()->max('sort_order');
            $section = InductionSection::query()->create([
                'induction_policy_version_id' => $version->id,
                'sort_order' => $data['sort_order'] ?? ($max + 1),
                'title' => $data['title'],
                'body' => $body,
                'requires_signature' => $request->boolean('requires_signature'),
                'acknowledgement_hint' => null,
            ]);

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_section.created',
                subjectType: InductionSection::class,
                subjectId: $section->id,
                policyId: $version->induction_policy_id,
                versionId: $version->id,
                metadata: ['after' => $section->only(['title', 'sort_order', 'requires_signature'])],
                staffRepeatRequested: false,
                versionForRepeat: $version,
                complianceContext: $ctx,
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
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'requires_signature' => ['sometimes', 'boolean'],
        ]);
        $body = $this->validatedSectionBody($request);
        $repeat = $this->staffRepeatFromRequest($request, required: true);

        $version = $policy->ensureEditableVersion();
        $before = $section->only(['title', 'body', 'sort_order', 'archived_at', 'requires_signature']);
        $ctx = $this->auditRequestContext($request);

        DB::transaction(function () use ($section, $version, $data, $body, $repeat, $before, $request, $ctx): void {
            $section->update([
                'title' => $data['title'],
                'body' => $body,
                'sort_order' => $data['sort_order'],
                'requires_signature' => $request->boolean('requires_signature'),
            ]);

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_section.updated',
                subjectType: InductionSection::class,
                subjectId: $section->id,
                policyId: $version->induction_policy_id,
                versionId: $version->id,
                metadata: ['before' => $before, 'after' => $section->fresh()->only(['title', 'body', 'sort_order', 'archived_at', 'requires_signature'])],
                staffRepeatRequested: $repeat,
                versionForRepeat: $version,
                complianceContext: $ctx,
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

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_section.archived',
                subjectType: InductionSection::class,
                subjectId: $section->id,
                policyId: $version->induction_policy_id,
                versionId: $version->id,
                metadata: ['archived_at' => $section->archived_at?->toIso8601String()],
                staffRepeatRequested: false,
                versionForRepeat: $version,
                complianceContext: $ctx,
            );
        });

        return redirect()
            ->route('admin.induction.policies.show', $policy)
            ->with('success', 'Clause archived.');
    }

    private function validatedSectionBody(Request $request): string
    {
        $maxWords = InductionSection::BODY_MAX_WORDS;
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
}
