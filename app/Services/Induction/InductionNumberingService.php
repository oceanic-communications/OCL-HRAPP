<?php

namespace App\Services\Induction;

use App\Models\InductionPolicy;
use App\Models\InductionSection;
use App\Models\InductionSubClause;

final class InductionNumberingService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function defaultScheme(): array
    {
        return config('induction.numbering_scheme_defaults', []);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function schemeForPolicy(InductionPolicy $policy): array
    {
        $stored = $policy->numbering_scheme;
        if (! is_array($stored)) {
            return $this->defaultScheme();
        }

        return array_replace_recursive($this->defaultScheme(), $stored);
    }

    public function formatSectionLabel(int $index, ?InductionPolicy $policy = null): string
    {
        $level = $policy ? ($this->schemeForPolicy($policy)['section'] ?? []) : ($this->defaultScheme()['section'] ?? []);
        $style = (string) ($level['style'] ?? 'roman');
        $separator = (string) ($level['separator'] ?? '.');
        $value = $this->valueForStyle($index, $style, (string) ($level['start'] ?? ''));

        return $value.$separator;
    }

    public function formatClauseLabel(int $clauseIndex, InductionSection $clause, InductionPolicy $policy): string
    {
        $scheme = $this->schemeForPolicy($policy);
        $sectionPart = $this->formatSectionLabel(1, $policy);
        $level = $scheme['clause'] ?? [];
        $style = (string) ($clause->numbering_style ?? $level['style'] ?? 'alpha_upper');
        $separator = (string) ($clause->number_separator ?? $level['separator'] ?? '.');
        $prefix = (string) ($clause->number_prefix ?? $level['prefix'] ?? '');
        $value = $this->valueForStyle($clauseIndex, $style, (string) ($level['start'] ?? 'A'));

        return rtrim($sectionPart, '.').$separator.$prefix.$value;
    }

    public function formatSubClauseLabel(int $clauseIndex, int $subIndex, InductionSubClause $sub, InductionSection $clause, InductionPolicy $policy): string
    {
        $clausePart = $this->formatClauseLabel($clauseIndex, $clause, $policy);
        $scheme = $this->schemeForPolicy($policy);
        $level = $scheme['sub_clause'] ?? [];
        $style = (string) ($sub->numbering_style ?? $level['style'] ?? 'decimal');
        $separator = (string) ($sub->number_separator ?? $level['separator'] ?? '.');
        $prefix = (string) ($sub->number_prefix ?? $level['prefix'] ?? '');
        $value = $this->valueForStyle($subIndex, $style, (string) ($level['start'] ?? '1'));

        return $clausePart.$separator.$prefix.$value;
    }

    public function previewSubClauseLabel(
        string $title,
        string $clausePart,
        string $prefix,
        string $style,
        string $separator,
        int $subIndex = 2,
    ): string {
        $value = $this->valueForStyle($subIndex, $style, '1');

        return trim($prefix.$clausePart.$separator.$value.' '.$title);
    }

    private function valueForStyle(int $index, string $style, string $start): string
    {
        $i = max(1, $index);

        return match ($style) {
            'roman' => $this->toRoman($i),
            'alpha_upper' => $this->toAlpha($i, false),
            'alpha_lower' => $this->toAlpha($i, true),
            'decimal' => (string) $i,
            'roman_lower' => strtolower($this->toRoman($i)),
            default => is_numeric($start) ? (string) ((int) $start + $i - 1) : $this->toAlpha($i, $style === 'alpha_lower'),
        };
    }

    private function toRoman(int $n): string
    {
        $map = [
            1000 => 'M', 900 => 'CM', 500 => 'D', 400 => 'CD',
            100 => 'C', 90 => 'XC', 50 => 'L', 40 => 'XL',
            10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I',
        ];
        $result = '';
        foreach ($map as $value => $numeral) {
            while ($n >= $value) {
                $result .= $numeral;
                $n -= $value;
            }
        }

        return $result !== '' ? $result : (string) $n;
    }

    private function toAlpha(int $n, bool $lower): string
    {
        $n = max(1, $n);
        $letters = '';
        while ($n > 0) {
            $n--;
            $letters = chr(($lower ? 97 : 65) + ($n % 26)).$letters;
            $n = intdiv($n, 26);
        }

        return $letters;
    }
}
