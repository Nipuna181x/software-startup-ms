<?php

namespace App\Support;

use App\Models\Organization;
use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the organization that the current request or process belongs to.
 *
 * Normally the organization comes from the authenticated user. Seeders, queued
 * jobs and console commands have no authenticated user, so they may set the
 * organization explicitly with {@see self::actAs()}.
 */
class Tenancy
{
    private static ?int $organizationId = null;

    private static bool $disabled = false;

    /**
     * Get the organization id that queries should be constrained to.
     */
    public static function currentOrganizationId(): ?int
    {
        if (self::$disabled) {
            return null;
        }

        if (self::$organizationId !== null) {
            return self::$organizationId;
        }

        return Auth::user()?->organization_id;
    }

    /**
     * Get the current organization model, if any.
     */
    public static function current(): ?Organization
    {
        $organizationId = self::currentOrganizationId();

        return $organizationId === null
            ? null
            : Organization::find($organizationId);
    }

    /**
     * Run the given callback scoped to the given organization.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public static function actAs(Organization|int $organization, Closure $callback): mixed
    {
        $previous = self::$organizationId;

        self::$organizationId = $organization instanceof Organization
            ? $organization->id
            : $organization;

        try {
            return $callback();
        } finally {
            self::$organizationId = $previous;
        }
    }

    /**
     * Run the given callback with tenant scoping disabled.
     *
     * Reserved for platform-level work such as registration and seeding. It is
     * deliberately explicit so that bypassing isolation is always visible.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public static function withoutScoping(Closure $callback): mixed
    {
        $previous = self::$disabled;

        self::$disabled = true;

        try {
            return $callback();
        } finally {
            self::$disabled = $previous;
        }
    }

    /**
     * Forget any explicitly set organization.
     */
    public static function forget(): void
    {
        self::$organizationId = null;
        self::$disabled = false;
    }
}
