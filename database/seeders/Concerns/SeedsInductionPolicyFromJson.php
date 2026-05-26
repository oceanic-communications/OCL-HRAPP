<?php

namespace Database\Seeders\Concerns;

use App\Models\InductionPolicy;
use App\Models\InductionPolicyVersion;
use App\Models\InductionSection;
use App\Models\InductionSubClause;
use App\Support\InductionAcknowledgementMode;
use Illuminate\Support\Facades\DB;
use RuntimeException;

trait SeedsInductionPolicyFromJson
{
    abstract protected function dataFile(): string;

    public function seedInductionPolicyFromJson(): void
    {
        $payload = $this->loadPayload();
        $meta = $payload['policy'];
        $slug = (string) $meta['slug'];
        $marker = (string) ($meta['seed_marker_section_title'] ?? '');

        $existing = InductionPolicy::query()->where('slug', $slug)->first();
        if ($existing !== null && $this->isFullySeeded($existing, $marker)) {
            return;
        }

        DB::transaction(function () use ($payload, $meta, $existing): void {
            if ($existing !== null) {
                $existing->versions()->delete();
                $existing->delete();
            }

            $policy = InductionPolicy::query()->create([
                'name' => $meta['name'],
                'abbreviation' => $meta['abbreviation'],
                'slug' => $meta['slug'],
                'is_active' => true,
                'sort_order' => (int) InductionPolicy::query()->max('sort_order') + 1,
                'acknowledgement_mode' => InductionAcknowledgementMode::READ_ONLY,
                'numbering_scheme' => $meta['numbering_scheme'] ?? null,
            ]);

            $version = InductionPolicyVersion::query()->create([
                'induction_policy_id' => $policy->id,
                'version_label' => $meta['version_label'],
                'effective_date' => $meta['effective_date'] ?? null,
                'published_at' => now(),
                'created_by' => null,
            ]);

            foreach ($payload['sections'] as $sectionRow) {
                $section = InductionSection::query()->create([
                    'induction_policy_version_id' => $version->id,
                    'sort_order' => $sectionRow['sort_order'],
                    'title' => $sectionRow['title'],
                    'number_prefix' => $sectionRow['number_prefix'] ?? null,
                    'numbering_style' => $sectionRow['numbering_style'] ?? null,
                    'number_separator' => $sectionRow['number_separator'] ?? null,
                    'body' => $sectionRow['body'] !== '' ? $sectionRow['body'] : '<p></p>',
                    'requires_signature' => false,
                    'acknowledgement_mode' => InductionAcknowledgementMode::READ_ONLY,
                    'acknowledgement_hint' => null,
                ]);

                foreach ($sectionRow['sub_clauses'] as $subRow) {
                    InductionSubClause::query()->create([
                        'induction_section_id' => $section->id,
                        'sort_order' => $subRow['sort_order'],
                        'title' => $subRow['title'],
                        'number_prefix' => $subRow['number_prefix'] ?? null,
                        'numbering_style' => $subRow['numbering_style'] ?? null,
                        'number_separator' => $subRow['number_separator'] ?? null,
                        'body' => $subRow['body'],
                        'acknowledgement_mode' => InductionAcknowledgementMode::READ_ONLY,
                    ]);
                }
            }
        });
    }

    private function isFullySeeded(InductionPolicy $policy, string $markerTitle): bool
    {
        if ($markerTitle === '') {
            return false;
        }

        $version = $policy->publishedVersion();
        if ($version === null) {
            return false;
        }

        return $version->sections()
            ->where('title', $markerTitle)
            ->exists();
    }

    /**
     * @return array{policy: array<string, mixed>, sections: array<int, array<string, mixed>>}
     */
    private function loadPayload(): array
    {
        $path = database_path($this->dataFile());
        if (! is_readable($path)) {
            throw new RuntimeException("Policy seed data not found: {$path}");
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded) || ! isset($decoded['policy'], $decoded['sections'])) {
            throw new RuntimeException('Policy seed data is invalid or incomplete.');
        }

        return $decoded;
    }
}
