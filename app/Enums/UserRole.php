<?php

namespace App\Enums;

enum UserRole: string
{
    case STAF_OPERASIONAL = 'staf_operasional';
    case FIRST_LEVEL = 'first_level';
    case MIDDLE_LEVEL = 'middle_level';
    case TOP_LEVEL = 'top_level';

    /**
     * Get human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::STAF_OPERASIONAL => 'Staf Operasional',
            self::FIRST_LEVEL => 'First-Level Management',
            self::MIDDLE_LEVEL => 'Middle Management',
            self::TOP_LEVEL => 'Top-Level Management / Owner',
        };
    }

    /**
     * Get hierarchy level order.
     */
    public function level(): int
    {
        return match ($this) {
            self::STAF_OPERASIONAL => 1,
            self::FIRST_LEVEL => 2,
            self::MIDDLE_LEVEL => 3,
            self::TOP_LEVEL => 4,
        };
    }
}
