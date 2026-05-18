<?php

namespace App\Support;

/**
 * Append-only induction / policy application audit event codes (employee portal + system).
 */
final class InductionApplicationAuditEventCode
{
    public const ENROLLMENT_CREATED = 'induction.enrollment_created';

    public const WIZARD_SUMMARY_VIEWED = 'induction.wizard_summary_viewed';

    public const SECTION_PRESENTED = 'induction.section_presented';

    public const SECTION_ACKNOWLEDGED = 'induction.section_acknowledged';

    public const PROGRAM_COMPLETED = 'induction.program_completed';

    public const COMPLETION_PDF_STORED = 'induction.completion_certificate_stored';

    public const MASTER_POLICY_PDF_DOWNLOADED = 'induction.master_policy_pdf_downloaded';

    public const COMPLETION_CERTIFICATE_DOWNLOADED = 'induction.completion_certificate_downloaded';

    public const NOTIFICATION_REPEAT_ASSIGNED = 'induction.notification_repeat_assigned';

    public const NOTIFICATION_MARKED_READ = 'induction.notification_marked_read';

    public const ENROLLMENT_PROGRESS_RESET = 'induction.enrollment_progress_reset';
}
