<?php

use App\Http\Controllers\Api\DocumentApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| LocaGed V2 — API REST (CDC §4.1, authentification Sanctum).
|--------------------------------------------------------------------------
|
| Token Sanctum (ex. Postman) : en Tinker
|   $u = \App\Models\User::find(1); $u->createToken('postman')->plainTextToken
| Puis header : Authorization: Bearer <token>
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    $user = $request->user();
    $user->loadMissing('roles');

    return response()->json([
        'id' => $user->id,
        'email' => $user->email,
        'full_name' => $user->full_name,
        'roles' => $user->roles->pluck('name')->values()->all(),
    ]);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('documents', [DocumentApiController::class, 'store']);
    Route::get('documents/search', [DocumentApiController::class, 'search']);
    Route::get('documents/{document}', [DocumentApiController::class, 'show']);
    Route::get('documents/{document}/metadata', [DocumentApiController::class, 'metadata']);
});
