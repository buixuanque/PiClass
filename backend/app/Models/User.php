<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'person_profile_id', 'login_identifier', 'password', 'status', 'last_login_at',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function personProfile()
    {
        return $this->belongsTo(PersonProfile::class);
    }

    public function userRoles()
    {
        return $this->hasMany(UserRole::class);
    }

    public function hasActiveRole(string $roleCode): bool
    {
        return $this->userRoles()
            ->whereHas('role', fn ($q) => $q->where('code', $roleCode))
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>', now()))
            ->exists();
    }

    /**
     * Laravel's password-reset broker stores/looks up tokens by this value.
     * We don't have a column literally named "email"; login_identifier plays that role.
     */
    public function getEmailForPasswordReset(): string
    {
        return $this->login_identifier;
    }
}
