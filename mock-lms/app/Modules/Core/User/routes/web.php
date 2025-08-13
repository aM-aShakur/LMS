<?php

use App\Modules\Core\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/user/{id}', [UserController::class, 'UserByIdGet']);
