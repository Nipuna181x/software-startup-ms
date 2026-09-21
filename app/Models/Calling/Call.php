<?php

namespace App\Models\Calling;

use App\Concerns\BelongsToOrganization;
use App\Enums\Calling\CallOutcome;
use App\Models\User;
use Database\Factories\Calling\CallFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single call attempt against a business.
 *
 * Every attempt is its own row and nothing is ever overwritten or updated in
 * place; changing a business's status later is recorded as a new Call.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $business_id
 * @property int|null $business_phone_id
 * @property int $user_id
 * @property CallOutcome $outcome
 * @property string|null $note
 * @property Carbon|null $follow_up_at
 * @property Carbon $called_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Business $business
 * @property-read BusinessPhone|null $phone
 * @property-read User $user
 */
#[Fillable(['organization_id', 'business_id', 'business_phone_id', 'user_id', 'outcome', 'note', 'follow_up_at', 'called_at'])]
class Call extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<CallFactory> */
    use HasFactory;

    /**
     * Get the business this call was made to.
     *
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the phone number that was called, if recorded.
     *
     * @return BelongsTo<BusinessPhone, $this>
     */
    public function phone(): BelongsTo
    {
        return $this->belongsTo(BusinessPhone::class, 'business_phone_id');
    }

    /**
     * Get the user who made this call.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'outcome' => CallOutcome::class,
            'follow_up_at' => 'date',
            'called_at' => 'datetime',
        ];
    }
}
