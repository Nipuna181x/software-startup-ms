<?php

namespace App\Enums\Calling;

/**
 * The result of a single call attempt.
 *
 * NotAnswered keeps a business in the "To Call" list; the other three move
 * it to "Answered" and become its current status until a later call changes
 * it. Adding a new outcome later (e.g. "Callback requested") means adding a
 * case here plus a label/color below — no schema change.
 */
enum CallOutcome: string
{
    case NotAnswered = 'not_answered';
    case Pending = 'pending';
    case Interested = 'interested';
    case Rejected = 'rejected';

    /**
     * Get the display label for this outcome.
     */
    public function label(): string
    {
        return match ($this) {
            self::NotAnswered => __('Not answered'),
            self::Pending => __('Pending'),
            self::Interested => __('Interested'),
            self::Rejected => __('Rejected'),
        };
    }

    /**
     * Determine whether this outcome means the call went unanswered.
     */
    public function isUnanswered(): bool
    {
        return $this === self::NotAnswered;
    }

    /**
     * Get the outcomes a business can be assigned once it has been answered.
     *
     * @return array<int, self>
     */
    public static function answeredOutcomes(): array
    {
        return [self::Pending, self::Interested, self::Rejected];
    }

    /**
     * Get every answered outcome as a value/label pair for filters and forms.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function answeredOptions(): array
    {
        return array_map(
            fn (self $outcome): array => ['value' => $outcome->value, 'label' => $outcome->label()],
            self::answeredOutcomes(),
        );
    }

    /**
     * Get the badge color for this outcome.
     *
     * Kept distinct from any organization theme color, and always paired
     * with the label text so status is never conveyed by color alone.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::NotAnswered => 'zinc',
            self::Pending => 'amber',
            self::Interested => 'green',
            self::Rejected => 'red',
        };
    }
}
