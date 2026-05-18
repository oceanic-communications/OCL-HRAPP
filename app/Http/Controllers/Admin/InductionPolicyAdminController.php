<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InductionPolicy;
use App\Models\InductionSection;
use App\Services\Induction\InductionPolicyAdminChangeService;
use App\Support\PortalPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InductionPolicyAdminController extends Controller
{
    public function __construct(
        private readonly InductionPolicyAdminChangeService $adminChangeService,
    ) {}

    private function authorizeManage(): void
    {
        $u = auth()->user();
        abort_unless(
            $u && ($u->isStaffSuperUser() || $u->hasPermission(PortalPermissions::INDUCTION_POLICY_MANAGE)),
            403
        );
    }

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

    public function index(): View
    {
        $this->authorizeManage();

        $policies = InductionPolicy::query()
            ->with([
                'versions' => fn ($q) => $q->whereNotNull('published_at')->orderByDesc('published_at')->limit(1),
                'versions.sections' => fn ($q) => $q->orderBy('sort_order'),
            ])
            ->orderBy('name')
            ->get();

        return view('admin.induction.index', compact('policies'));
    }

    public function storePolicy(Request $request): RedirectResponse
    {
        $this->authorizeManage();
        $data = $request->validate([
            'create_name' => ['required', 'string', 'max:255'],
        ]);

        $slug = $this->uniqueSlugFromName($data['create_name']);
        $ctx = $this->auditRequestContext($request);

        DB::transaction(function () use ($data, $slug, $ctx): void {
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

        return redirect()->route('admin.induction.index')->with('success', 'Policy created.');
    }

    public function updatePolicy(Request $request, InductionPolicy $policy): RedirectResponse
    {
        $this->authorizeManage();
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

        return redirect()->route('admin.induction.index')->with('success', 'Policy saved.');
    }

    public function createSection(InductionPolicy $policy): View
    {
        $this->authorizeManage();
        $policy->ensureEditableVersion();

        return view('admin.induction.sections.create', compact('policy'));
    }

    public function storeSection(Request $request, InductionPolicy $policy): RedirectResponse
    {
        $this->authorizeManage();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $version = $policy->ensureEditableVersion();
        $ctx = $this->auditRequestContext($request);
        $section = null;

        DB::transaction(function () use (&$section, $version, $data, $ctx): void {
            $max = (int) $version->sections()->max('sort_order');
            $section = InductionSection::query()->create([
                'induction_policy_version_id' => $version->id,
                'sort_order' => $data['sort_order'] ?? ($max + 1),
                'title' => $data['title'],
                'body' => $data['body'],
                'requires_signature' => true,
                'acknowledgement_hint' => null,
            ]);

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_section.created',
                subjectType: InductionSection::class,
                subjectId: $section->id,
                policyId: $version->induction_policy_id,
                versionId: $version->id,
                metadata: ['after' => $section->only(['title', 'sort_order'])],
                staffRepeatRequested: false,
                versionForRepeat: $version,
                complianceContext: $ctx,
            );
        });

        return redirect()
            ->route('admin.induction.index')
            ->with('success', 'Section created.');
    }

    public function showSection(InductionPolicy $policy, InductionSection $section): View
    {
        $this->authorizeManage();
        $section = $this->resolveSection($policy, $section);

        return view('admin.induction.sections.show', compact('policy', 'section'));
    }

    public function editSection(InductionPolicy $policy, InductionSection $section): View
    {
        $this->authorizeManage();
        $section = $this->resolveSection($policy, $section);

        return view('admin.induction.sections.edit', compact('policy', 'section'));
    }

    public function updateSection(Request $request, InductionPolicy $policy, InductionSection $section): RedirectResponse
    {
        $this->authorizeManage();
        $section = $this->resolveSection($policy, $section);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);
        $repeat = $this->staffRepeatFromRequest($request, required: true);

        $version = $policy->ensureEditableVersion();
        $before = $section->only(['title', 'body', 'sort_order', 'archived_at']);
        $ctx = $this->auditRequestContext($request);

        DB::transaction(function () use ($section, $version, $data, $repeat, $before, $ctx): void {
            $section->update([
                'title' => $data['title'],
                'body' => $data['body'],
                'sort_order' => $data['sort_order'],
            ]);

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_section.updated',
                subjectType: InductionSection::class,
                subjectId: $section->id,
                policyId: $version->induction_policy_id,
                versionId: $version->id,
                metadata: ['before' => $before, 'after' => $section->fresh()->only(['title', 'body', 'sort_order', 'archived_at'])],
                staffRepeatRequested: $repeat,
                versionForRepeat: $version,
                complianceContext: $ctx,
            );
        });

        return redirect()
            ->route('admin.induction.policies.sections.show', [$policy, $section])
            ->with('success', 'Section saved.');
    }

    public function archiveSection(Request $request, InductionPolicy $policy, InductionSection $section): RedirectResponse
    {
        $this->authorizeManage();
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

        return redirect()->route('admin.induction.index')->with('success', 'Section archived.');
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
