<?php

namespace App\Models\Calling;

use App\Concerns\BelongsToOrganization;
use Database\Factories\Calling\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A business that may be called, belonging to one business type.
 *
 * This is a minimal skeleton: contact details, phone numbers, screenshots
 * and the calling workflow are added in later parts of the Calling module.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $call_business_type_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CallBusinessType $type
 */
#[Fillable(['organization_id', 'call_business_type_id', 'name'])]
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
}
