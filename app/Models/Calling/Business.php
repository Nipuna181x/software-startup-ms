<?php

namespace App\Models\Calling;

use App\Concerns\BelongsToOrganization;
use App\Enums\Calling\CallOutcome;
use App\Support\PhoneNumber;
use Carbon\CarbonInterface;
use Database\Factories\Calling\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A business that may be called.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $call_business_type_id
 * @property string $name
 * @property string|null $address
 * @property string|null $owner_name
 * @property string|null $website
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CallBusinessType $type
 * @property-read Collection<int, BusinessPhone> $phones
 * @property-read Collection<int, BusinessScreenshot> $screenshots
 * @property-read Collection<int, Call> $calls
 * @property-read BusinessPhone|null $primaryPhone
 */
#[Fillable(['organization_id', 'call_business_type_id', 'name', 'address', 'owner_name', 'website', 'notes'])]
class Business extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<BusinessFactory> */
    use HasFactory;

    /**
     * Get the business type this business belongs to.
     *
     * @return BelongsTo<CallBusinessType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(CallBusinessType::class, 'call_business_type_id');
    }

    /**
     * Get all phone numbers for this business.
     *
     * @return HasMany<BusinessPhone, $this>
     */
    public function phones(): HasMany
    {
        return $this->hasMany(BusinessPhone::class);
    }

    /**
     * Get the primary phone number for this business.
     *
     * @return HasOne<BusinessPhone, $this>
     */
    public function primaryPhone(): HasOne
    {
        return $this->phones()->one()->where('is_primary', true);
    }

    /**
     * Get all screenshots for this business.
     *
     * @return HasMany<BusinessScreenshot, $this>
     */
    public function screenshots(): HasMany
    {
        return $this->hasMany(BusinessScreenshot::class);
    }

    /**
     * Get every call attempt against this business, newest first.
     *
     * @return HasMany<Call, $this>
     */
    public function calls(): HasMany
    {
        return $this->hasMany(Call::class)->latest('called_at');
    }

    /**
     * Get this business's most recent answered call, if any.
     *
     * The business's current status comes from this call's outcome. A
     * business with only "not answered" attempts has no current status and
     * belongs in the "To Call" list.
     *
     * @return HasOne<Call, $this>
     */
    public function latestAnsweredCall(): HasOne
    {
        $answeredValues = array_map(fn (CallOutcome $outcome) => $outcome->value, CallOutcome::answeredOutcomes());

        return $this->hasOne(Call::class)->ofMany(
            ['called_at' => 'max', 'id' => 'max'],
            fn ($query) => $query->whereIn('outcome', $answeredValues),
        );
    }

    /**
     * Get this business's current status, derived from its latest answered
     * call. Null means the business has never been answered.
     */
    public function currentStatus(): ?CallOutcome
    {
        return $this->latestAnsweredCall?->outcome;
    }

    /**
     * Determine whether this business has ever been answered.
     */
    public function hasBeenAnswered(): bool
    {
        return $this->currentStatus() !== null;
    }

    /**
     * Count how many unanswered attempts have been made since the business
     * was last answered (or ever, if it has never been answered).
     */
    public function attemptsSinceLastAnswer(): int
    {
        $lastAnsweredAt = $this->latestAnsweredCall?->called_at;

        return $this->calls()
            ->where('outcome', CallOutcome::NotAnswered)
            ->when($lastAnsweredAt, fn ($query) => $query->where('called_at', '>', $lastAnsweredAt))
            ->count();
    }

    /**
     * Get the time of the most recent call attempt, of any outcome.
     */
    public function lastAttemptAt(): ?CarbonInterface
    {
        return $this->calls()->first()?->called_at;
    }

    /**
     * Find an existing business in the organization with a matching phone
     * number, comparing numbers after normalization.
     *
     * Used to warn about duplicates before creating a new business.
     */
    public static function findByPhoneNumber(string $number): ?self
    {
        $normalized = PhoneNumber::normalize($number);

        if ($normalized === '') {
            return null;
        }

        $phone = BusinessPhone::query()
            ->where('normalized_number', $normalized)
            ->first();

        return $phone?->business;
    }
}
