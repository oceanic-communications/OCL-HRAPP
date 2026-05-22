<?php

namespace App\Support;

final class InductionChangeLogDiff
{
    /** @var array<string, string> */
    public const POLICY_FIELDS = [
        'name' => 'Policy name',
        'abbreviation' => 'Abbreviation',
        'is_active' => 'Status',
        'acknowledgement_mode' => 'Acknowledgement mode',
    ];

    /** @var array<string, string> */
    public const SECTION_FIELDS = [
        'title' => 'Clause title',
        'body' => 'Clause content',
        'requires_signature' => 'Requires signature',
        'acknowledgement_mode' => 'Acknowledgement mode',
        'archived_at' => 'Archived',
    ];

    /** @var array<string, string> */
    public const SUB_CLAUSE_FIELDS = [
        'title' => 'Sub-clause title',
        'body' => 'Sub-clause content',
        'acknowledgement_mode' => 'Acknowledgement mode',
        'archived_at' => 'Archived',
    ];

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, string>  $fieldLabels
     * @return list<array{field: string, label: string, from: string|null, to: string|null}>
     */
    public static function between(?array $before, ?array $after, array $fieldLabels): array
    {
        $before ??= [];
        $after ??= [];
        $changes = [];

        foreach ($fieldLabels as $field => $label) {
            $fromRaw = array_key_exists($field, $before) ? $before[$field] : null;
            $toRaw = array_key_exists($field, $after) ? $after[$field] : null;

            if (! array_key_exists($field, $before) && ! array_key_exists($field, $after)) {
                continue;
            }

            $from = self::formatValue($field, $fromRaw);
            $to = self::formatValue($field, $toRaw);

            if ($from === $to) {
                continue;
            }

            $changes[] = [
                'field' => $field,
                'label' => $label,
                'from' => $from,
                'to' => $to,
            ];
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, string>  $fieldLabels
     * @return list<array{field: string, label: string, from: string|null, to: string|null}>
     */
    public static function created(array $snapshot, array $fieldLabels): array
    {
        return self::between([], $snapshot, $fieldLabels);
    }

    public static function formatValue(string $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($field) {
            'is_active' => filter_var($value, FILTER_VALIDATE_BOOLEAN) || $value === 1 || $value === '1'
                ? 'Active'
                : 'Inactive',
            'requires_signature' => filter_var($value, FILTER_VALIDATE_BOOLEAN) || $value === 1 || $value === '1'
                ? 'Yes'
                : 'No',
            'acknowledgement_mode' => InductionAcknowledgementMode::label(is_string($value) ? $value : null),
            'body' => self::summarizeHtml(is_string($value) ? $value : (string) $value),
            'archived_at' => 'Archived at '.$value,
            default => is_bool($value) ? ($value ? 'Yes' : 'No') : (string) $value,
        };
    }

    private static function summarizeHtml(string $html): string
    {
        $text = trim(strip_tags($html));
        if ($text === '') {
            return '(empty content)';
        }

        if (mb_strlen($text) > 120) {
            return mb_substr($text, 0, 120).'…';
        }

        return $text;
    }
}
