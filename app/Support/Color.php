<?php

namespace App\Support;

/**
 * Small colour utility used to build an organization's theme from one hex.
 *
 * Shades are produced by mixing the base colour with white (tints) or black
 * (shades), and foreground colours are chosen by WCAG relative luminance so
 * text on a coloured button always stays readable.
 */
class Color
{
    /**
     * Normalise a hex colour to the lowercase six digit form (`#1d4ed8`).
     */
    public static function normalize(string $hex): string
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return '#'.strtolower($hex);
    }

    /**
     * Determine whether the given string is a valid 3 or 6 digit hex colour.
     */
    public static function isValidHex(string $hex): bool
    {
        return (bool) preg_match('/^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', trim($hex));
    }

    /**
     * Convert a hex colour to its red, green and blue components.
     *
     * @return array{int, int, int}
     */
    public static function toRgb(string $hex): array
    {
        $hex = ltrim(self::normalize($hex), '#');

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Convert red, green and blue components back to a hex colour.
     */
    public static function fromRgb(int $red, int $green, int $blue): string
    {
        return sprintf(
            '#%02x%02x%02x',
            max(0, min(255, $red)),
            max(0, min(255, $green)),
            max(0, min(255, $blue)),
        );
    }

    /**
     * Get the WCAG relative luminance of a colour, between 0 and 1.
     */
    public static function luminance(string $hex): float
    {
        [$red, $green, $blue] = self::toRgb($hex);

        $channels = array_map(function (int $channel): float {
            $value = $channel / 255;

            return $value <= 0.03928
                ? $value / 12.92
                : (($value + 0.055) / 1.055) ** 2.4;
        }, [$red, $green, $blue]);

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * Get the WCAG contrast ratio between two colours, between 1 and 21.
     */
    public static function contrastRatio(string $first, string $second): float
    {
        $lighter = max(self::luminance($first), self::luminance($second));
        $darker = min(self::luminance($first), self::luminance($second));

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Pick the readable foreground colour to place on top of the given colour.
     */
    public static function foregroundFor(string $hex): string
    {
        return self::contrastRatio($hex, '#ffffff') >= 4.5
            ? '#ffffff'
            : '#111827';
    }

    /**
     * Mix a colour with white by the given amount (0 to 1).
     */
    public static function tint(string $hex, float $amount): string
    {
        [$red, $green, $blue] = self::toRgb($hex);

        return self::fromRgb(
            (int) round($red + (255 - $red) * $amount),
            (int) round($green + (255 - $green) * $amount),
            (int) round($blue + (255 - $blue) * $amount),
        );
    }

    /**
     * Mix a colour with black by the given amount (0 to 1).
     */
    public static function shade(string $hex, float $amount): string
    {
        [$red, $green, $blue] = self::toRgb($hex);

        return self::fromRgb(
            (int) round($red * (1 - $amount)),
            (int) round($green * (1 - $amount)),
            (int) round($blue * (1 - $amount)),
        );
    }

    /**
     * Determine whether a colour is too light to read as a solid fill on white.
     *
     * Used to reject white and near-white theme colours at registration.
     */
    public static function isTooLightForWhite(string $hex, float $minimumRatio = 1.6): bool
    {
        return self::contrastRatio($hex, '#ffffff') < $minimumRatio;
    }

    /**
     * Build the full shade ramp used by the dashboard theme.
     *
     * Keys are the shade steps (50 through 900). PHP casts numeric string
     * keys to integers, so the array is keyed by int.
     *
     * @return array<int, string>
     */
    public static function ramp(string $hex): array
    {
        $base = self::normalize($hex);

        return [
            '50' => self::tint($base, 0.95),
            '100' => self::tint($base, 0.88),
            '200' => self::tint($base, 0.75),
            '300' => self::tint($base, 0.6),
            '400' => self::tint($base, 0.3),
            '500' => $base,
            '600' => self::shade($base, 0.12),
            '700' => self::shade($base, 0.26),
            '800' => self::shade($base, 0.4),
            '900' => self::shade($base, 0.55),
        ];
    }
}
