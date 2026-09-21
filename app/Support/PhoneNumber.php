<?php

namespace App\Support;

/**
 * Normalizes phone numbers for duplicate detection.
 *
 * Two numbers are considered the same if they match once spaces, dashes and
 * a leading country/trunk prefix are stripped. For example "+94 71 234 5678",
 * "0712345678" and "71-234-5678" all normalize to "712345678".
 */
class PhoneNumber
{
    /**
     * Normalize a phone number for comparison and storage.
     */
    public static function normalize(string $number): string
    {
        $digits = preg_replace('/[\s\-]+/', '', trim($number)) ?? '';

        if (str_starts_with($digits, '+94')) {
            $digits = substr($digits, 3);
        } elseif (str_starts_with($digits, '94') && strlen($digits) > 9) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }
}
