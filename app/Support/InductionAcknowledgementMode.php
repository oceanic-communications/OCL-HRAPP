<?php

namespace App\Support;

final class InductionAcknowledgementMode
{
    public const READ_AND_SIGN = 'read_and_sign';

    public const READ_ONLY = 'read_only';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return [self::READ_AND_SIGN, self::READ_ONLY];
    }

    /**
     * @return array<int, string>
     */
    public static function validationRules(): array
    {
        return ['required', 'in:'.implode(',', self::values())];
    }

    public static function requiresSignature(?string $mode): bool
    {
        return $mode === self::READ_AND_SIGN;
    }

    public static function label(?string $mode): string
    {
        return match ($mode) {
            self::READ_AND_SIGN => 'Read and sign',
            self::READ_ONLY => 'Read only',
            default => 'Read only',
        };
    }
}
