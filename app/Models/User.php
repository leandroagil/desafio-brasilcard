<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'id',
        'firstName',
        'lastName',
        'email',
        'password',
        'balance',
    ];

    protected $attributes = [
        'balance' => 0,
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
