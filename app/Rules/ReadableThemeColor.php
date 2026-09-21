<?php

namespace App\Rules;

use App\Support\Color;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates a theme colour and refuses colours that cannot be read on white.
 *
 * The dashboard is always white plus the organization's colour, so a white or
 * near-white choice would make buttons, links and active items invisible.
 */
class ReadableThemeColor implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Color::isValidHex($value)) {
            $fail(__('Enter a valid hex colour, for example #1d4ed8.'));

            return;
        }

        if (Color::isTooLightForWhite($value)) {
            $fail(__('That colour is too light to read on a white background. Pick a deeper shade so buttons and links stay visible.'));
        }
    }
}
