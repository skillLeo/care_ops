<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Route::post('/auth/teams', [App\Http\Controllers\TeamsAuthController::class, 'authenticate']);

// Route::post('/api/auth/teams', [App\Http\Controllers\TeamsAuthController::class, 'authenticate'])
//     ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
//     ->name('teams.auth');
