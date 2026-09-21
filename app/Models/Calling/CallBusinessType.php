<?php

namespace App\Models\Calling;

use App\Concerns\BelongsToOrganization;
use App\Concerns\GeneratesUniqueSlug;
use Database\Factories\Calling\CallBusinessTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A business type within a category, e.g. "Phone Shops".
 *
 * @property int $id
 * @property int $organization_id
 * @property int $call_category_id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CallCategory $category
 * @property-read Collection<int, Business> $businesses
 */
#[Fillable(['organization_id', 'call_category_id', 'name', 'slug'])]
class CallBusinessType extends Model
{
    use BelongsToOrganization;
    use GeneratesUniqueSlug;

    /** @use HasFactory<CallBusinessTypeFactory> */
    use HasFactory;

    /**
     * Get the category this business type belongs to.
     *
     * @return BelongsTo<CallCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CallCategory::class, 'call_category_id');
    }

    /**
     * Get the businesses of this type.
     *
     * @return HasMany<Business, $this>
     */
    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class, 'call_business_type_id');
    }

    /**
     * Get the route key name.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Narrow slug uniqueness to this category.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function slugUniqueScope(Builder $query): Builder
    {
        return $query->where('call_category_id', $this->call_category_id);
    }
}
