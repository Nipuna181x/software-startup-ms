<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case User = 'user';

    /**
     * Get the display label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => __('Super Admin'),
            self::User => __('User'),
        };
    }

    /**
     * Get every role as a value/label pair for form selects.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $role): array => ['value' => $role->value, 'label' => $role->label()],
            self::cases(),
        );
    }
}
