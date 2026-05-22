<?php

namespace App\Support;

use App\Models\InductionPolicy;
use App\Models\InductionSection;

final class InductionPolicyChangeNotification
{
    public const TYPE_NEW = 'new';

    public const TYPE_AMENDMENT = 'amendment';

    public const LEVEL_POLICY = 'policy';

    public const LEVEL_CLAUSE = 'clause';

    public const LEVEL_SUB_CLAUSE = 'sub_clause';

    public function __construct(
        public string $changeType,
        public string $level,
        public string $policyName,
        public string $policyAbbreviation,
        public ?string $clauseTitle = null,
        public ?string $subClauseTitle = null,
    ) {}

    public static function policyNew(InductionPolicy $policy): self
    {
        return new self(
            changeType: self::TYPE_NEW,
            level: self::LEVEL_POLICY,
            policyName: $policy->name,
            policyAbbreviation: $policy->abbreviation,
        );
    }

    public static function policyAmendment(InductionPolicy $policy): self
    {
        return new self(
            changeType: self::TYPE_AMENDMENT,
            level: self::LEVEL_POLICY,
            policyName: $policy->name,
            policyAbbreviation: $policy->abbreviation,
        );
    }

    public static function clauseNew(InductionPolicy $policy, string $clauseTitle): self
    {
        return new self(
            changeType: self::TYPE_NEW,
            level: self::LEVEL_CLAUSE,
            policyName: $policy->name,
            policyAbbreviation: $policy->abbreviation,
            clauseTitle: $clauseTitle,
        );
    }

    public static function clauseAmendment(InductionPolicy $policy, string $clauseTitle): self
    {
        return new self(
            changeType: self::TYPE_AMENDMENT,
            level: self::LEVEL_CLAUSE,
            policyName: $policy->name,
            policyAbbreviation: $policy->abbreviation,
            clauseTitle: $clauseTitle,
        );
    }

    public static function subClauseNew(InductionPolicy $policy, InductionSection $section, string $subClauseTitle): self
    {
        return new self(
            changeType: self::TYPE_NEW,
            level: self::LEVEL_SUB_CLAUSE,
            policyName: $policy->name,
            policyAbbreviation: $policy->abbreviation,
            clauseTitle: $section->title,
            subClauseTitle: $subClauseTitle,
        );
    }

    public static function subClauseAmendment(InductionPolicy $policy, InductionSection $section, string $subClauseTitle): self
    {
        return new self(
            changeType: self::TYPE_AMENDMENT,
            level: self::LEVEL_SUB_CLAUSE,
            policyName: $policy->name,
            policyAbbreviation: $policy->abbreviation,
            clauseTitle: $section->title,
            subClauseTitle: $subClauseTitle,
        );
    }

    public function changeTypeLabel(): string
    {
        return $this->changeType === self::TYPE_NEW ? 'New' : 'Amendment';
    }

    public function levelLabel(): string
    {
        return match ($this->level) {
            self::LEVEL_POLICY => 'Policy',
            self::LEVEL_CLAUSE => 'Clause',
            self::LEVEL_SUB_CLAUSE => 'Sub-clause',
            default => 'Policy',
        };
    }

    public function notificationTitle(): string
    {
        $parts = [
            $this->policyAbbreviation,
            $this->changeTypeLabel(),
            $this->levelLabel(),
        ];

        if ($this->level !== self::LEVEL_POLICY && $this->clauseTitle !== null) {
            $parts[] = $this->clauseTitle;
        }

        if ($this->level === self::LEVEL_SUB_CLAUSE && $this->subClauseTitle !== null) {
            $parts[] = $this->subClauseTitle;
        }

        return implode(' · ', $parts);
    }

    public function notificationBody(bool $requiresRepeat): string
    {
        $lines = [
            $this->policyContextLine(),
            $this->changeFocusLine(),
        ];

        if ($requiresRepeat) {
            $lines[] = 'You must complete induction again for this policy version. Open Induction and work through the required sections.';
        } else {
            $lines[] = 'Please review this change in the portal when you have a moment.';
        }

        return implode(' ', $lines);
    }

    public function emailSubject(): string
    {
        return sprintf(
            '%s – %s %s: %s – %s',
            $this->policyAbbreviation,
            $this->changeTypeLabel(),
            strtolower($this->levelLabel()),
            $this->primaryEntityName(),
            config('app.name'),
        );
    }

    public function summary(): string
    {
        return $this->policyContextLine().' '.$this->changeFocusLine();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'change_type' => $this->changeType,
            'change_type_label' => $this->changeTypeLabel(),
            'level' => $this->level,
            'level_label' => $this->levelLabel(),
            'policy_name' => $this->policyName,
            'policy_abbreviation' => $this->policyAbbreviation,
            'clause_title' => $this->clauseTitle,
            'sub_clause_title' => $this->subClauseTitle,
            'notification_title' => $this->notificationTitle(),
            'notification_body' => $this->notificationBody(false),
        ];
    }

    private function policyContextLine(): string
    {
        return sprintf('Policy: %s (%s).', $this->policyName, $this->policyAbbreviation);
    }

    private function changeFocusLine(): string
    {
        $type = $this->changeTypeLabel();

        return match ($this->level) {
            self::LEVEL_POLICY => sprintf('Change: %s policy.', $type),
            self::LEVEL_CLAUSE => sprintf('Change: %s clause — "%s".', $type, $this->clauseTitle ?? ''),
            self::LEVEL_SUB_CLAUSE => sprintf(
                'Change: %s sub-clause — "%s" (under clause "%s").',
                $type,
                $this->subClauseTitle ?? '',
                $this->clauseTitle ?? '',
            ),
            default => sprintf('Change: %s.', $type),
        };
    }

    private function primaryEntityName(): string
    {
        return match ($this->level) {
            self::LEVEL_POLICY => $this->policyName,
            self::LEVEL_CLAUSE => $this->clauseTitle ?? $this->policyName,
            self::LEVEL_SUB_CLAUSE => $this->subClauseTitle ?? $this->clauseTitle ?? $this->policyName,
            default => $this->policyName,
        };
    }
}
