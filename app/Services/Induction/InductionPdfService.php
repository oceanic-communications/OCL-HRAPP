<?php

namespace App\Services\Induction;

use App\Models\InductionEnrollment;
use App\Support\InductionApplicationAuditEventCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

final class InductionPdfService
{
    public function __construct(
        private InductionApplicationAuditService $applicationAudit,
    ) {}

    public function generateAndStoreCompletionPdf(InductionEnrollment $enrollment): void
    {
        $enrollment->load([
            'user',
            'version.policy',
            'sectionCompletions.section',
        ]);

        $orderedCompletions = $enrollment->sectionCompletions
            ->sortBy(fn ($c) => $c->section->sort_order)
            ->values();

        $html = view('pdf.induction-certificate', [
            'enrollment' => $enrollment,
            'completions' => $orderedCompletions,
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        $binary = $pdf->output();

        $relative = 'induction/certificates/'.$enrollment->id.'_'.now()->format('Y-m-d_His').'.pdf';
        Storage::disk('local')->put($relative, $binary);

        $enrollment->forceFill([
            'completion_pdf_disk' => 'local',
            'completion_pdf_path' => $relative,
        ])->save();

        $user = $enrollment->user;
        if ($user !== null) {
            $this->applicationAudit->record($user, InductionApplicationAuditEventCode::COMPLETION_PDF_STORED, [
                'induction_policy_id' => $enrollment->version->induction_policy_id,
                'induction_policy_version_id' => $enrollment->induction_policy_version_id,
                'induction_enrollment_id' => $enrollment->id,
                'payload' => [
                    'completion_pdf_path' => $relative,
                ],
            ]);
        }
    }
}
