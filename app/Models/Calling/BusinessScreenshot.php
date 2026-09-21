<?php

namespace App\Models\Calling;

use App\Concerns\BelongsToOrganization;
use Database\Factories\Calling\BusinessScreenshotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A screenshot attached to a business, stored on the private disk.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $business_id
 * @property string $path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Business $business
 */
#[Fillable(['organization_id', 'business_id', 'path'])]
class BusinessScreenshot extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<BusinessScreenshotFactory> */
    use HasFactory;

    /**
     * Boot the model.
     *
     * Removes the underlying file when the record is deleted, so a removed
     * screenshot never lingers on disk.
     */
    protected static function booted(): void
    {
        static::deleted(function (self $screenshot): void {
            Storage::disk(config('startsuite.business_screenshot.disk'))->delete($screenshot->path);
        });
    }

    /**
     * Get the business this screenshot belongs to.
     *
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
