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

    private static bool $resolvingUser = false;

    /**
     * Get the organization id that queries should be constrained to.
     *
     * Resolving the authenticated user runs a User query, which re-enters this
     * method through the global scope. The re-entrancy guard lets that inner
     * query run unscoped: it looks the user up by primary key from the session,
     * so it needs no tenant filter of its own.
     */
    public static function currentOrganizationId(): ?int
    {
        if (self::$disabled || self::$resolvingUser) {
            return null;
        }

        if (self::$organizationId !== null) {
            return self::$organizationId;
        }

        self::$resolvingUser = true;

        try {
            return Auth::user()?->organization_id;
        } finally {
            self::$resolvingUser = false;
        }
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
        self::$resolvingUser = false;
    }
}
