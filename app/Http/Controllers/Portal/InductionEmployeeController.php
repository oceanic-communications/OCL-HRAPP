<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\InductionPolicyVersion;
use App\Models\InductionSection;
use App\Services\Induction\InductionApplicationAuditService;
use App\Services\Induction\InductionFlowService;
use App\Support\InductionApplicationAuditEventCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InductionEmployeeController extends Controller
{
    public function __construct(
        private InductionFlowService $inductionFlow,
        private InductionApplicationAuditService $applicationAudit,
    ) {}

    public function index(Request $request): View
    {
        $versions = $this->inductionFlow->activePublishedVersions();
        if ($versions->isEmpty()) {
            return view('portal.induction.unavailable');
        }

        $user = auth()->user();
        $programmes = [];
        $totalSectionsAll = 0;
        $completedSectionsAll = 0;

        foreach ($versions as $version) {
            $version->load(['activeSections.activeSubClauses', 'policy']);
            $enrollment = $this->inductionFlow->enrollmentFor($user, $version, $request->ip(), $request->userAgent());
            if ($enrollment === null) {
                continue;
            }

            $enrollment->loadCount('sectionCompletions');
            $completedIds = $enrollment->sectionCompletions()->pluck('induction_section_id')->all();
            $sectionCount = $version->activeSections->count();
            $doneCount = count($completedIds);

            $totalSectionsAll += $sectionCount;
            $completedSectionsAll += $doneCount;

            $programmes[] = [
                'version' => $version,
                'enrollment' => $enrollment,
                'completedSectionIds' => $completedIds,
                'policyLocked' => ! $this->inductionFlow->canAccessPolicy($user, $version, $request->ip(), $request->userAgent()),
            ];
        }

        if ($programmes === []) {
            return view('portal.induction.unavailable');
        }

        $firstEnrollment = $programmes[0]['enrollment'];
        $this->applicationAudit->record($user, InductionApplicationAuditEventCode::WIZARD_SUMMARY_VIEWED, [
            'induction_policy_id' => $programmes[0]['version']->induction_policy_id,
            'induction_policy_version_id' => $programmes[0]['version']->id,
            'induction_enrollment_id' => $firstEnrollment->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $overallPct = $totalSectionsAll > 0
            ? (int) round(($completedSectionsAll / $totalSectionsAll) * 100)
            : 0;

        return view('portal.induction.index', [
            'programmes' => $programmes,
            'overallPct' => $overallPct,
            'overallDone' => $completedSectionsAll,
            'overallTotal' => $totalSectionsAll,
        ]);
    }

    public function show(Request $request, InductionSection $induction_section): View|RedirectResponse
    {
        $section = $induction_section->load('version.policy', 'version.activeSections', 'activeSubClauses');
        if ($section->isArchived()) {
            return redirect()->route('portal.induction')->withErrors(['section' => 'This section is no longer available.']);
        }
        $user = auth()->user();
        $version = $section->version;

        $enrollment = $this->inductionFlow->enrollmentFor($user, $version, $request->ip(), $request->userAgent());
        if ($enrollment === null) {
            return redirect()->route('portal.induction');
        }

        if (! $this->inductionFlow->canViewSection($user, $section, $request->ip(), $request->userAgent())) {
            return redirect()
                ->route('portal.induction')
                ->withErrors(['section' => 'Complete the previous section first, or this section is not available.']);
        }

        $sectionCompleted = $this->inductionFlow->isSectionCompleted($enrollment, $section);

        $this->applicationAudit->record($user, InductionApplicationAuditEventCode::SECTION_PRESENTED, [
            'induction_policy_id' => $section->version->induction_policy_id,
            'induction_policy_version_id' => $section->induction_policy_version_id,
            'induction_section_id' => $section->id,
            'induction_enrollment_id' => $enrollment->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $sectionsOrdered = $version->activeSections;
        $completedSectionIds = $enrollment->sectionCompletions()->pluck('induction_section_id')->all();
        $progressTotal = $sectionsOrdered->count();
        $progressDone = count($completedSectionIds);
        $progressPct = $progressTotal > 0 ? (int) round(($progressDone / $progressTotal) * 100) : 0;
        $stepIndex = $sectionsOrdered->search(fn ($s) => $s->id === $section->id);
        $progressStep = $stepIndex !== false ? $stepIndex + 1 : 0;

        return view('portal.induction.section', [
            'section' => $section,
            'enrollment' => $enrollment,
            'version' => $version,
            'sections' => $sectionsOrdered,
            'completedSectionIds' => $completedSectionIds,
            'sectionCompleted' => $sectionCompleted,
            'progressTotal' => $progressTotal,
            'progressDone' => $progressDone,
            'progressPct' => $progressPct,
            'progressStep' => $progressStep,
        ]);
    }

    public function complete(Request $request, InductionSection $induction_section): RedirectResponse
    {
        $section = $induction_section->load('version');

        try {
            $this->inductionFlow->completeSection(
                auth()->user(),
                $section,
                [
                    'acknowledge' => $request->boolean('acknowledge'),
                    'signature_data' => $request->input('signature_data'),
                ],
                $request->ip(),
                $request->userAgent(),
            );
        } catch (ValidationException $e) {
            return redirect()
                ->route('portal.induction.section', $section)
                ->withErrors($e->errors())
                ->withInput();
        }

        $enrollment = $this->inductionFlow->enrollmentFor(
            auth()->user(),
            $section->version,
            $request->ip(),
            $request->userAgent(),
        );

        if ($enrollment?->isCompleted()) {
            $policyName = $section->version->policy->abbreviation ?? $section->version->policy->name;

            return redirect()->route('portal.induction')->with(
                'success',
                "{$policyName} induction complete. A PDF acknowledgement has been generated and emailed to you".(config('induction.hr_notification_email') ? ' and HR.' : '.'),
            );
        }

        $completedIds = $enrollment?->sectionCompletions()->pluck('induction_section_id')->all() ?? [];
        $nextSection = $section->version
            ->activeSections
            ->first(fn (InductionSection $s) => ! in_array($s->id, $completedIds, true));

        if ($nextSection !== null) {
            return redirect()
                ->route('portal.induction.section', $nextSection)
                ->with('success', 'Section completed. Continue with the next section.');
        }

        return redirect()->route('portal.induction')->with('success', 'Section completed.');
    }

    public function masterPolicy(Request $request, InductionPolicyVersion $induction_policy_version): StreamedResponse|RedirectResponse
    {
        $version = $induction_policy_version;
        abort_unless($version->published_at !== null, 404);
        $disk = $version->policy_pdf_disk;
        $path = $version->policy_pdf_path;
        if ($disk === null || $path === null || ! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        $this->applicationAudit->record(auth()->user(), InductionApplicationAuditEventCode::MASTER_POLICY_PDF_DOWNLOADED, [
            'induction_policy_id' => $version->induction_policy_id,
            'induction_policy_version_id' => $version->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => [
                'storage_path' => $path,
            ],
        ]);

        return Storage::disk($disk)->download($path, 'policy-'.$version->version_label.'.pdf');
    }

    public function certificate(Request $request, InductionPolicyVersion $induction_policy_version): StreamedResponse|RedirectResponse
    {
        $version = $induction_policy_version;
        abort_unless($version->published_at !== null && $version->policy?->is_active, 404);

        $enrollment = $this->inductionFlow->enrollmentFor(
            auth()->user(),
            $version,
            $request->ip(),
            $request->userAgent(),
        );
        if ($enrollment === null || ! $enrollment->isCompleted()) {
            return redirect()->route('portal.induction')->withErrors(['certificate' => 'Certificate is available after you complete all sections for this policy.']);
        }

        $disk = $enrollment->completion_pdf_disk;
        $path = $enrollment->completion_pdf_path;
        if ($disk === null || $path === null || ! Storage::disk($disk)->exists($path)) {
            return redirect()->route('portal.induction')->withErrors(['certificate' => 'The PDF record is not available yet. Please contact HR.']);
        }

        $enrollment->loadMissing('version.policy');
        $this->applicationAudit->record(auth()->user(), InductionApplicationAuditEventCode::COMPLETION_CERTIFICATE_DOWNLOADED, [
            'induction_policy_id' => $enrollment->version?->induction_policy_id,
            'induction_policy_version_id' => $enrollment->induction_policy_version_id,
            'induction_enrollment_id' => $enrollment->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => [
                'storage_path' => $path,
            ],
        ]);

        $filename = 'induction-'.($enrollment->version?->policy?->abbreviation ?? 'acknowledgement').'.pdf';

        return Storage::disk($disk)->download($path, $filename);
    }
}
