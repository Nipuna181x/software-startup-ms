<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Gives a model an auto-generated slug that is unique within a scope.
 *
 * Implementing models define {@see self::slugSourceColumn()} (the attribute
 * the slug is derived from) and {@see self::slugUniqueScope()} (how to narrow
 * the uniqueness check, e.g. to one organization or one parent category).
 */
trait GeneratesUniqueSlug
{
    /**
     * Boot the concern.
     */
    public static function bootGeneratesUniqueSlug(): void
    {
        static::creating(function ($model): void {
            if (blank($model->slug)) {
                $model->slug = $model->generateUniqueSlug($model->{$model->slugSourceColumn()});
            }
        });

        static::updating(function ($model): void {
            if ($model->isDirty($model->slugSourceColumn()) && ! $model->isDirty('slug')) {
                $model->slug = $model->generateUniqueSlug($model->{$model->slugSourceColumn()}, $model->getKey());
            }
        });
    }

    /**
     * Generate a slug that is unique within this model's scope.
     */
    public function generateUniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'item';
        $slug = $base;
        $suffix = 2;

        while (
            $this->slugUniqueScope(static::query())
                ->where('slug', $slug)
                ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * The attribute the slug is derived from. Defaults to `name`.
     */
    protected function slugSourceColumn(): string
    {
        return 'name';
    }

    /**
     * Narrow the query used to check for a colliding slug.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function slugUniqueScope(Builder $query): Builder
    {
        return $query;
    }
}
