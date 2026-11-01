<?php

// In routes/web.php, next to the other require:   require __DIR__ . '/api_admin.php';   (BEFORE the catch-all route)

use App\Http\Controllers\Api\Admin\CommentController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\PostController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureNotBanned;
use Illuminate\Support\Facades\Route;

Route::prefix('api/admin')->middleware(['auth', EnsureNotBanned::class, EnsureAdmin::class])->group(function () {
    Route::get('/stats', DashboardController::class);

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users/{user}/ban', [UserController::class, 'ban']);
    Route::post('/users/{user}/unban', [UserController::class, 'unban']);
    Route::post('/users/{user}/admin', [UserController::class, 'setAdmin']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);

    Route::get('/posts', [PostController::class, 'index']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);

    Route::get('/comments', [CommentController::class, 'index']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
});
