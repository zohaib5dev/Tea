<?php

// Add to routes/web.php:   require __DIR__ . '/api_spa.php';   (BEFORE the catch-all route, see SETUP.md)

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController as ApiCommentController;
use App\Http\Controllers\Api\ConversationController as ApiConversationController;
use App\Http\Controllers\Api\CounterController;
use App\Http\Controllers\Api\FeedController as ApiFeedController;
use App\Http\Controllers\Api\FriendController as ApiFriendController;
use App\Http\Controllers\Api\PostController as ApiPostController;
use App\Http\Controllers\Api\ProfileController as ApiProfileController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// JSON API for the Vue app. Uses the normal session + CSRF cookie (the "web" group), no tokens needed.
Route::prefix('api')->group(function () {

    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');

    Route::middleware(['auth', \App\Http\Middleware\EnsureNotBanned::class])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/counters', CounterController::class);

        // Feed and posts
        Route::get('/feed', [ApiFeedController::class, 'index']);
        Route::get('/sidebar', [ApiFeedController::class, 'sidebar']);
        Route::post('/posts', [ApiPostController::class, 'store']);
        Route::get('/posts/{post}', [ApiPostController::class, 'show']);
        Route::patch('/posts/{post}', [PostController::class, 'update']);     // existing controller (JSON)
        Route::delete('/posts/{post}', [PostController::class, 'destroy']);   // existing controller (JSON)
        Route::post('/posts/{post}/like', LikeController::class);             // existing controller (JSON)

        // Comments
        Route::get('/posts/{post}/comments', [ApiCommentController::class, 'index']);
        Route::post('/posts/{post}/comments', [ApiCommentController::class, 'store']);
        Route::delete('/comments/{comment}', [ApiCommentController::class, 'destroy']);

        // Profiles
        Route::get('/users/{user}', [ApiProfileController::class, 'show']);
        Route::get('/users/{user}/posts', [ApiProfileController::class, 'posts']);
        Route::get('/users/{user}/photos', [ApiProfileController::class, 'photos']);
        Route::patch('/profile', [ProfileController::class, 'update']);           // existing controller (JSON)
        Route::post('/profile/photo', [ProfileController::class, 'updatePhoto']); // existing controller (JSON)

        // Friends
        Route::get('/friends', [ApiFriendController::class, 'index']);
        Route::post('/friends/{user}/add', [FriendController::class, 'store']);        // existing (JSON)
        Route::delete('/friends/{user}', [FriendController::class, 'destroy']);        // existing (JSON)
        Route::post('/friendships/{friendship}/accept', [FriendController::class, 'accept']);   // existing (JSON)
        Route::post('/friendships/{friendship}/decline', [FriendController::class, 'decline']); // existing (JSON)

        // Chats
        Route::get('/conversations', [ApiConversationController::class, 'index']);
        Route::post('/conversations', [ApiConversationController::class, 'store']);
        Route::get('/conversations/{conversation}', [ApiConversationController::class, 'show']);
        Route::get('/conversations/{conversation}/messages', [ApiConversationController::class, 'messages']);
        Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store']);   // existing (JSON)
        Route::get('/conversations/{conversation}/poll', [MessageController::class, 'index']);        // existing (JSON)
        Route::post('/conversations/{conversation}/read', [MessageController::class, 'read']);        // existing
        Route::post('/conversations/{conversation}/call', [CallController::class, 'signal'])->middleware('throttle:300,1');
        Route::delete('/messages/{message}', [MessageController::class, 'destroy']);                  // delete for everyone
        Route::delete('/messages/{message}/me', [MessageController::class, 'hide']);                  // delete for me
    });
});
