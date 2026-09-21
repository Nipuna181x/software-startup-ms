<?php

namespace App\Models\Calling;

use App\Concerns\BelongsToOrganization;
use App\Concerns\GeneratesUniqueSlug;
use Database\Factories\Calling\CallCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A top-level grouping of business types, e.g. "Marketing Agencies".
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, CallBusinessType> $businessTypes
 */
#[Fillable(['organization_id', 'name', 'slug'])]
class CallCategory extends Model
{
    use BelongsToOrganization;
    use GeneratesUniqueSlug;

    /** @use HasFactory<CallCategoryFactory> */
    use HasFactory;

    /**
     * Get the business types that belong to this category.
     *
     * @return HasMany<CallBusinessType, $this>
     */
    public function businessTypes(): HasMany
    {
        return $this->hasMany(CallBusinessType::class);
    }

    /**
     * Get the route key name.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Narrow slug uniqueness to this organization.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function slugUniqueScope(Builder $query): Builder
    {
        return $query->where('organization_id', $this->organization_id);
    }
}
