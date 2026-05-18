<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InductionPolicy;
use App\Models\InductionPolicyVersion;
use App\Models\InductionSection;
use App\Services\Induction\InductionPolicyAdminChangeService;
use App\Support\PortalPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    private function validateStaffRepeatChoice(Request $request): bool
    {
        $request->validate([
            'staff_must_repeat_induction' => ['required', 'in:0,1'],
        ]);

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

    public function index(): View
    {
        $this->authorizeManage();

        $policies = InductionPolicy::query()
            ->with(['versions' => fn ($q) => $q->orderByDesc('published_at')->orderByDesc('id')])
            ->orderBy('name')
            ->get();

        return view('admin.induction.index', compact('policies'));
    }

    public function storePolicy(Request $request): RedirectResponse
    {
        $this->authorizeManage();
        $data = $request->validate([
            'create_name' => ['required', 'string', 'max:255'],
            'create_slug' => ['nullable', 'string', 'max:64', Rule::unique('induction_policies', 'slug')],
        ]);
        $repeat = $this->validateStaffRepeatChoice($request);

        $slug = $data['create_slug'] ?? null;
        if (! is_string($slug) || $slug === '') {
            $slug = $this->uniqueSlugFromName($data['create_name']);
        }

        $policy = null;
        $ctx = $this->auditRequestContext($request);

        DB::transaction(function () use (&$policy, $data, $slug, $repeat, $ctx): void {
            $policy = InductionPolicy::query()->create([
                'name' => $data['create_name'],
                'slug' => $slug,
                'is_active' => true,
            ]);

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_policy.created',
                subjectType: InductionPolicy::class,
                subjectId: $policy->id,
                policyId: $policy->id,
                versionId: null,
                metadata: ['after' => $policy->only(['name', 'slug', 'is_active'])],
                staffRepeatRequested: $repeat,
                versionForRepeat: null,
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
        $repeat = $this->validateStaffRepeatChoice($request);

        $before = $policy->only(['name', 'slug', 'is_active']);
        $ctx = $this->auditRequestContext($request);

        DB::transaction(function () use ($policy, $data, $repeat, $before, $ctx): void {
            $policy->forceFill([
                'name' => $data['policy'][$policy->id]['name'],
                'is_active' => $data['policy'][$policy->id]['is_active'] === '1',
            ])->save();

            $published = $policy->publishedVersion();
            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_policy.updated',
                subjectType: InductionPolicy::class,
                subjectId: $policy->id,
                policyId: $policy->id,
                versionId: $published?->id,
                metadata: ['before' => $before, 'after' => $policy->only(['name', 'slug', 'is_active'])],
                staffRepeatRequested: $repeat,
                versionForRepeat: $published,
                complianceContext: $ctx,
            );
        });

        return redirect()->route('admin.induction.index')->with('success', 'Policy saved.');
    }

    public function storeVersion(Request $request, InductionPolicy $policy): RedirectResponse
    {
        $this->authorizeManage();
        $data = $request->validate([
            "version.{$policy->id}.version_label" => ['required', 'string', 'max:64'],
            "version.{$policy->id}.effective_date" => ['nullable', 'date'],
        ]);
        $repeat = $this->validateStaffRepeatChoice($request);

        $version = null;
        $ctx = $this->auditRequestContext($request);

        DB::transaction(function () use (&$version, $policy, $data, $repeat, $ctx): void {
            $row = $data['version'][$policy->id];
            $version = InductionPolicyVersion::query()->create([
                'induction_policy_id' => $policy->id,
                'version_label' => $row['version_label'],
                'effective_date' => $row['effective_date'] ?? null,
                'published_at' => null,
                'created_by' => auth()->id(),
            ]);

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_policy_version.created',
                subjectType: InductionPolicyVersion::class,
                subjectId: $version->id,
                policyId: $policy->id,
                versionId: $version->id,
                metadata: ['after' => $version->only(['version_label', 'effective_date', 'published_at'])],
                staffRepeatRequested: $repeat,
                versionForRepeat: $version,
                complianceContext: $ctx,
            );
        });

        return redirect()->route('admin.induction.versions.show', $version)->with('success', 'Draft version created.');
    }

    public function showVersion(InductionPolicyVersion $version): View
    {
        $this->authorizeManage();
        $version->load(['policy', 'sections' => fn ($q) => $q->orderBy('sort_order')]);

        return view('admin.induction.version', compact('version'));
    }

    public function storeSection(Request $request, InductionPolicyVersion $version): RedirectResponse
    {
        $this->authorizeManage();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'requires_signature' => ['sometimes', 'boolean'],
            'acknowledgement_hint' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        $repeat = $this->validateStaffRepeatChoice($request);

        $section = null;
        $ctx = $this->auditRequestContext($request);

        DB::transaction(function () use (&$section, $request, $version, $data, $repeat, $ctx): void {
            $max = (int) $version->sections()->max('sort_order');
            $section = InductionSection::query()->create([
                'induction_policy_version_id' => $version->id,
                'sort_order' => $data['sort_order'] ?? ($max + 1),
                'title' => $data['title'],
                'body' => $data['body'],
                'requires_signature' => $request->boolean('requires_signature'),
                'acknowledgement_hint' => $data['acknowledgement_hint'] ?? null,
            ]);

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_section.created',
                subjectType: InductionSection::class,
                subjectId: $section->id,
                policyId: $version->induction_policy_id,
                versionId: $version->id,
                metadata: ['after' => $section->only(['title', 'sort_order', 'requires_signature'])],
                staffRepeatRequested: $repeat,
                versionForRepeat: $version,
                complianceContext: $ctx,
            );
        });

        return redirect()->route('admin.induction.versions.show', $version)->with('success', 'Section added.');
    }

    public function updateSection(Request $request, InductionPolicyVersion $version, InductionSection $section): RedirectResponse
    {
        $this->authorizeManage();
        abort_unless($section->induction_policy_version_id === $version->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'requires_signature' => ['sometimes', 'boolean'],
            'acknowledgement_hint' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);
        $repeat = $this->validateStaffRepeatChoice($request);

        $before = $section->only(['title', 'body', 'sort_order', 'requires_signature', 'acknowledgement_hint']);
        $ctx = $this->auditRequestContext($request);

        DB::transaction(function () use ($request, $section, $version, $data, $repeat, $before, $ctx): void {
            $section->update([
                'title' => $data['title'],
                'body' => $data['body'],
                'requires_signature' => $request->boolean('requires_signature'),
                'acknowledgement_hint' => $data['acknowledgement_hint'] ?? null,
                'sort_order' => $data['sort_order'],
            ]);

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_section.updated',
                subjectType: InductionSection::class,
                subjectId: $section->id,
                policyId: $version->induction_policy_id,
                versionId: $version->id,
                metadata: ['before' => $before, 'after' => $section->fresh()->only(['title', 'body', 'sort_order', 'requires_signature', 'acknowledgement_hint'])],
                staffRepeatRequested: $repeat,
                versionForRepeat: $version,
                complianceContext: $ctx,
            );
        });

        return redirect()->route('admin.induction.versions.show', $version)->with('success', 'Section updated.');
    }

    public function destroySection(Request $request, InductionPolicyVersion $version, InductionSection $section): RedirectResponse
    {
        $this->authorizeManage();
        abort_unless($section->induction_policy_version_id === $version->id, 404);
        $repeat = $this->validateStaffRepeatChoice($request);

        $snapshot = $section->only(['id', 'title', 'sort_order', 'requires_signature']);
        $ctx = $this->auditRequestContext($request);

        DB::transaction(function () use ($section, $version, $repeat, $snapshot, $ctx): void {
            $sectionId = $section->id;
            $section->delete();

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_section.deleted',
                subjectType: InductionSection::class,
                subjectId: $sectionId,
                policyId: $version->induction_policy_id,
                versionId: $version->id,
                metadata: ['deleted' => $snapshot],
                staffRepeatRequested: $repeat,
                versionForRepeat: $version,
                complianceContext: $ctx,
            );
        });

        return redirect()->route('admin.induction.versions.show', $version)->with('success', 'Section removed.');
    }

    public function publishVersion(Request $request, InductionPolicyVersion $version): RedirectResponse
    {
        $this->authorizeManage();
        $repeat = $this->validateStaffRepeatChoice($request);
        $ctx = $this->auditRequestContext($request);

        DB::transaction(function () use ($version, $repeat, $ctx): void {
            InductionPolicyVersion::query()
                ->where('induction_policy_id', $version->induction_policy_id)
                ->whereKeyNot($version->id)
                ->update(['published_at' => null]);

            $version->forceFill(['published_at' => now()])->save();
            $version->refresh();

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_policy_version.published',
                subjectType: InductionPolicyVersion::class,
                subjectId: $version->id,
                policyId: $version->induction_policy_id,
                versionId: $version->id,
                metadata: ['after' => $version->only(['version_label', 'published_at'])],
                staffRepeatRequested: $repeat,
                versionForRepeat: $version,
                complianceContext: $ctx,
            );
        });

        return redirect()->route('admin.induction.versions.show', $version)->with('success', 'This version is now the published induction.');
    }

    public function uploadMasterPdf(Request $request, InductionPolicyVersion $version): RedirectResponse
    {
        $this->authorizeManage();
        $request->validate([
            'policy_pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);
        $repeat = $this->validateStaffRepeatChoice($request);

        $before = $version->only(['policy_pdf_disk', 'policy_pdf_path']);
        $ctx = $this->auditRequestContext($request);

        DB::transaction(function () use ($request, $version, $repeat, $before, $ctx): void {
            $path = $request->file('policy_pdf')->store('induction/policy-masters/'.$version->id, 'local');
            $version->forceFill([
                'policy_pdf_disk' => 'local',
                'policy_pdf_path' => $path,
            ])->save();

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_policy_version.master_pdf_uploaded',
                subjectType: InductionPolicyVersion::class,
                subjectId: $version->id,
                policyId: $version->induction_policy_id,
                versionId: $version->id,
                metadata: ['before' => $before, 'after' => $version->only(['policy_pdf_disk', 'policy_pdf_path'])],
                staffRepeatRequested: $repeat,
                versionForRepeat: $version,
                complianceContext: $ctx,
            );
        });

        return redirect()->route('admin.induction.versions.show', $version)->with('success', 'Policy PDF stored.');
    }

    public function destroyMasterPdf(Request $request, InductionPolicyVersion $version): RedirectResponse
    {
        $this->authorizeManage();
        $repeat = $this->validateStaffRepeatChoice($request);

        $before = $version->only(['policy_pdf_disk', 'policy_pdf_path']);
        $ctx = $this->auditRequestContext($request);

        DB::transaction(function () use ($version, $repeat, $before, $ctx): void {
            if ($version->policy_pdf_disk && $version->policy_pdf_path) {
                Storage::disk($version->policy_pdf_disk)->delete($version->policy_pdf_path);
            }
            $version->forceFill([
                'policy_pdf_disk' => null,
                'policy_pdf_path' => null,
            ])->save();

            $this->adminChangeService->record(
                actor: auth()->user(),
                action: 'induction_policy_version.master_pdf_removed',
                subjectType: InductionPolicyVersion::class,
                subjectId: $version->id,
                policyId: $version->induction_policy_id,
                versionId: $version->id,
                metadata: ['before' => $before],
                staffRepeatRequested: $repeat,
                versionForRepeat: $version,
                complianceContext: $ctx,
            );
        });

        return redirect()->route('admin.induction.versions.show', $version)->with('success', 'Policy PDF removed.');
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
