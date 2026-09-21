<?php

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property Role $role
 * @property bool $is_active
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 */
#[Fillable(['organization_id', 'name', 'email', 'email_verified_at', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use BelongsToOrganization;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Determine if the user is a Super Admin of their organization.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === Role::SuperAdmin;
    }

    /**
     * Determine if the user may sign in.
     *
     * Both the user and their organization must be active.
     */
    public function canAuthenticate(): bool
    {
        return $this->is_active && $this->organization->is_active;
    }

    /**
     * Get the user's initials.
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }
}
