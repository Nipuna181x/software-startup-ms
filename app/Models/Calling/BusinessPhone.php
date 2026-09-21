<?php

namespace App\Models\Calling;

use App\Concerns\BelongsToOrganization;
use App\Support\PhoneNumber;
use Database\Factories\Calling\BusinessPhoneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One phone number belonging to a business.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $business_id
 * @property string $number
 * @property string $normalized_number
 * @property bool $is_primary
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Business $business
 */
#[Fillable(['organization_id', 'business_id', 'number', 'normalized_number', 'is_primary'])]
class BusinessPhone extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<BusinessPhoneFactory> */
    use HasFactory;

    /**
     * Boot the model.
     *
     * Keeps `normalized_number` in sync with `number` whenever it changes, so
     * callers never have to remember to normalize it themselves.
     */
    protected static function booted(): void
    {
        static::saving(function (self $phone): void {
            if ($phone->isDirty('number')) {
                $phone->normalized_number = PhoneNumber::normalize($phone->number);
            }
        });
    }

    /**
     * Get the business this phone number belongs to.
     *
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the tel: link for this number.
     */
    public function telLink(): string
    {
        return 'tel:'.preg_replace('/[\s\-]+/', '', $this->number);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }
}
