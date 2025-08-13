<?php

namespace App\Modules\Core\User\Models;

use App\Modules\Core\User\Database\Factories\UserFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['email', 'password', 'first_name', 'last_name', 'status'];
    protected $hidden = ['password'];

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
