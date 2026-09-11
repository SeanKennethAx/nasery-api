<?php

use App\Http\Controllers\TeamMemberController;
use Illuminate\Support\Facades\Route;

Route::get('/team/dashboard', [TeamMemberController::class, 'dashboard']);
Route::put('/team/tasks/{item}/complete', [TeamMemberController::class, 'complete']);
