<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'role_id', 'valid_from', 'valid_to',
        'assigned_by_user_id', 'ended_by_user_id', 'end_reason',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
