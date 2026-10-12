<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PersonProfile extends Model
{
    use HasUuids;

    protected $fillable = ['full_name', 'email', 'phone'];
}
