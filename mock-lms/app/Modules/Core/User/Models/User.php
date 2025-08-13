<?php

namespace App\Modules\Core\User\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'status'
    ];

    protected $hidden = ['password'];
}
