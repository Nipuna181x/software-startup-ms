<?php

namespace App\Models\Scopes;

use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Constrains every query to the organization of the authenticated user.
 *
 * This is the single enforcement point for tenant isolation. Any model using
 * the BelongsToOrganization concern is filtered automatically, so a module can
 * never accidentally read another organization's rows.
 *
 * @implements Scope<Model>
 */
class OrganizationScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  Builder<covariant Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $organizationId = Tenancy::currentOrganizationId();

        if ($organizationId === null) {
            return;
        }

        $builder->where(
            $model->qualifyColumn('organization_id'),
            $organizationId,
        );
    }
}
