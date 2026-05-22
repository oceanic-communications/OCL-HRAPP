<?php

namespace App\Support;

use App\Models\InductionChangeLog;
use App\Models\InductionPolicy;
use App\Models\InductionSection;
use App\Models\InductionSubClause;

final class InductionChangeLogPresenter
{
    public function __construct(
        private readonly InductionChangeLog $log,
    ) {}

    public function actionLabel(): string
    {
        return match ($this->log->action) {
            'induction_policy.created' => 'Policy created',
            'induction_policy.updated' => 'Policy amended',
            'induction_section.created' => 'Clause created',
            'induction_section.updated' => 'Clause amended',
            'induction_section.archived' => 'Clause archived',
            'induction_sub_clause.created' => 'Sub-clause created',
            'induction_sub_clause.updated' => 'Sub-clause amended',
            'induction_sub_clause.archived' => 'Sub-clause archived',
            default => str_replace('_', ' ', $this->log->action),
        };
    }

    public function subjectLabel(): string
    {
        $meta = $this->log->metadata ?? [];

        if (is_string($meta['subject_label'] ?? null)) {
            return $meta['subject_label'];
        }

        return match ($this->log->subject_type) {
            InductionPolicy::class => 'Policy',
            InductionSection::class => 'Clause',
            InductionSubClause::class => 'Sub-clause',
            default => 'Record',
        };
    }

    /**
     * @return list<array{field: string, label: string, from: string|null, to: string|null}>
     */
    public function changes(): array
    {
        $stored = $this->log->changes;
        if (is_array($stored) && $stored !== []) {
            return $stored;
        }

        $meta = $this->log->metadata ?? [];
        $before = is_array($meta['before'] ?? null) ? $meta['before'] : [];
        $after = is_array($meta['after'] ?? null) ? $meta['after'] : [];

        $fieldMap = match ($this->log->subject_type) {
            InductionPolicy::class => InductionChangeLogDiff::POLICY_FIELDS,
            InductionSection::class => InductionChangeLogDiff::SECTION_FIELDS,
            InductionSubClause::class => InductionChangeLogDiff::SUB_CLAUSE_FIELDS,
            default => [],
        };

        if ($fieldMap === []) {
            return [];
        }

        return InductionChangeLogDiff::between($before, $after, $fieldMap);
    }

    public function actorName(): string
    {
        return $this->log->actor?->name ?? 'Unknown user';
    }

    public function policyLabel(): string
    {
        if ($this->log->policy !== null) {
            return $this->log->policy->abbreviation.' · '.$this->log->policy->name;
        }

        return '—';
    }
}
