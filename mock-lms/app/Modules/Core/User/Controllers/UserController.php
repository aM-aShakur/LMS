<?php

namespace App\Modules\Core\User\Controllers;

use App\Foundation\ModuleController;
use App\Modules\Core\User\Models\User;
use Illuminate\Http\Request;

class UserController extends ModuleController
{
    public function UserByIdGet(int $id)
    {
        return User::findOrFail($id);
    }
}
