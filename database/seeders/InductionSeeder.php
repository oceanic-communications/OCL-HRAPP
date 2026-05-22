<?php

namespace Database\Seeders;

use App\Models\InductionPolicy;
use App\Models\InductionPolicyVersion;
use App\Models\InductionSection;
use App\Support\InductionAcknowledgementMode;
use Illuminate\Database\Seeder;

class InductionSeeder extends Seeder
{
    public function run(): void
    {
        if (InductionPolicy::query()->where('slug', 'productivity-policies')->exists()) {
            return;
        }

        $policy = InductionPolicy::query()->create([
            'name' => 'Productivity principles & policies',
            'abbreviation' => 'PP',
            'slug' => 'productivity-policies',
            'is_active' => true,
            'acknowledgement_mode' => InductionAcknowledgementMode::READ_ONLY,
        ]);

        $version = InductionPolicyVersion::query()->create([
            'induction_policy_id' => $policy->id,
            'version_label' => 'Jan 2026',
            'effective_date' => '2026-01-01',
            'published_at' => now(),
            'created_by' => null,
        ]);

        $sections = [
            [
                'sort_order' => 1,
                'title' => 'Productivity principles & scope',
                'requires_signature' => false,
                'body' => "Oceanic Communications — Productivity Principles & Policies (Version update Jan 2026).\n\nIt is productivity that measures the performance of an organisation. Oceanic defines productivity as using available resources well to deliver profitable goods and services that meet customer and client expectations.\n\nThis policy applies to all employees at all times.\n\nBy continuing through this induction you confirm you will read each section carefully before acknowledging it.",
            ],
            [
                'sort_order' => 2,
                'title' => 'Section A — Attendance & time management',
                'requires_signature' => false,
                'body' => "Operating hours: Monday to Friday 8:00am to 5:00pm (client engagement window).\n\nNormal working hours: 45 hours per week as described in the full policy. Punctuality, late arrival rules, breaks, and Time in Lieu (TOIL) are governed by the official PDF.\n\nRead the master policy document for full conditions that apply to your employment.",
            ],
            [
                'sort_order' => 3,
                'title' => 'Section B — Leave entitlement & conditions',
                'requires_signature' => false,
                'body' => "Leave types, sick leave, maternity, paternity, bereavement, application rules, and unauthorised absence are set out in the master policy.\n\nPlanned leave uses the prescribed Leave Application Form; leave is not approved until formally authorised.",
            ],
            [
                'sort_order' => 4,
                'title' => 'Section C — Communication, confidentiality & social media',
                'requires_signature' => true,
                'acknowledgement_hint' => 'Sign in the box below to confirm you will follow approved channels and confidentiality rules.',
                'body' => "Approved channels: external contact uses company email with manager in copy unless management approves otherwise.\n\nBitrix is mandatory for project and task communication.\n\nNon-disclosure and confidentiality obligations continue after employment ends.\n\nSocial media: do not post client work or confidential information without clearance.",
            ],
            [
                'sort_order' => 5,
                'title' => 'Final acknowledgement',
                'requires_signature' => true,
                'acknowledgement_hint' => 'Sign to confirm you have completed induction in order and understand your obligations.',
                'body' => "By signing this final step you confirm that you:\n\n• Have read each induction section in order.\n• Will follow Oceanic policies as updated from time to time.\n• Understand that the portal records your name, date and time, device and network metadata, and the policy version displayed.\n• Will direct questions to your manager or HR.\n\nThe official Productivity Principles & Policies PDF remains the controlling document if there is any inconsistency with this onboarding summary.",
            ],
        ];

        foreach ($sections as $row) {
            $mode = $row['requires_signature']
                ? InductionAcknowledgementMode::READ_AND_SIGN
                : InductionAcknowledgementMode::READ_ONLY;

            InductionSection::query()->create([
                'induction_policy_version_id' => $version->id,
                'sort_order' => $row['sort_order'],
                'title' => $row['title'],
                'body' => $row['body'],
                'requires_signature' => $row['requires_signature'],
                'acknowledgement_mode' => $mode,
                'acknowledgement_hint' => $row['acknowledgement_hint'] ?? null,
            ]);
        }
    }
}
