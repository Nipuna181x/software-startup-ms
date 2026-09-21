<?php

namespace App\Concerns;

use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Makes a model belong to a single organization.
 *
 * Add this trait to any module model that stores tenant data. It applies the
 * global {@see OrganizationScope} so reads are filtered, and stamps the
 * organization id on create so writes land in the right tenant. The model's
 * table needs an `organization_id` foreign key.
 *
 * @phpstan-require-extends Model
 */
trait BelongsToOrganization
{
    /**
     * Boot the concern.
     */
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('organization_id') === null) {
                $model->setAttribute('organization_id', Tenancy::currentOrganizationId());
            }
        });
    }

    /**
     * Get the organization that owns the model.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
