<?php

use App\Http\Controllers\CallController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
  return view('spa');
}); 

require __DIR__ . '/api.php';   // the JSON API under /api/*
require __DIR__ . '/api_admin.php';   // the JSON API under /api/*

// Every other URL opens the Vue app (Vue Router shows the right page).
Route::get('/{any}', SpaController::class)->where('any', '^(?!api|storage|broadcasting|up|build).*$');



Route::middleware('auth')->group(function () {
  // Feed & posts
  Route::get('/feed', [FeedController::class, 'index'])->name('feed');
  Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
  Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
  Route::patch('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
  Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
  Route::post('/posts/{post}/like', LikeController::class)->name('posts.like');
  Route::get('/posts/{post}/comments', [CommentController::class, 'index'])->name('comments.index');
  Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('comments.store');
  Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

  // Profiles
  Route::get('/users/{user}', [ProfileController::class, 'show'])->name('users.show');
  Route::get('/profile', fn() => redirect()->route('users.show', auth()->id()))->name('profile');
  Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
  Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
  Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');
  // Friends
  Route::get('/friends', [FriendController::class, 'index'])->name('friends.index');
  Route::post('/friends/{user}/add', [FriendController::class, 'store'])->name('friends.add');
  Route::delete('/friends/{user}', [FriendController::class, 'destroy'])->name('friends.destroy');
  Route::post('/friendships/{friendship}/accept', [FriendController::class, 'accept'])->name('friendships.accept');
  Route::post('/friendships/{friendship}/decline', [FriendController::class, 'decline'])->name('friendships.decline');

  // Chats // Chats
  Route::get('/messages', [ConversationController::class, 'index'])->name('conversations.index');
  Route::post('/messages', [ConversationController::class, 'store'])->name('conversations.store');
  Route::get('/messages/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
  Route::get('/messages/{conversation}/poll', [MessageController::class, 'index'])->name('messages.poll');
  Route::post('/messages/{conversation}', [MessageController::class, 'store'])->name('messages.store');
  Route::post('/messages/{conversation}/read', [MessageController::class, 'read'])->name('messages.read');
  Route::delete('/message-items/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
  Route::delete('/message-items/{message}/me', [MessageController::class, 'hide'])->name('messages.hide');
  Route::post('/messages/{conversation}/call', [CallController::class, 'signal'])->middleware('throttle:300,1')->name('calls.signal');
});


require __DIR__ . '/auth.php';
