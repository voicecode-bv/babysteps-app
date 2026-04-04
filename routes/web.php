<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CircleActionController;
use App\Http\Controllers\CircleController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\PostActionController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\HandleNativeEdge;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::get('/auth/verify', [AuthController::class, 'verify'])->name('auth.verify');

Route::middleware(['auth.token', HandleNativeEdge::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', FeedController::class)->name('feed');
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show')->whereNumber('post');

    Route::post('/posts', [PostActionController::class, 'store'])->name('posts.store');
    Route::delete('/posts/{post}', [PostActionController::class, 'destroy'])->name('posts.destroy')->whereNumber('post');
    Route::post('/posts/{post}/like', [PostActionController::class, 'like'])->name('posts.like')->whereNumber('post');
    Route::delete('/posts/{post}/like', [PostActionController::class, 'unlike'])->name('posts.unlike')->whereNumber('post');
    Route::post('/posts/{post}/comments', [PostActionController::class, 'comment'])->name('posts.comments.store')->whereNumber('post');
    Route::delete('/comments/{comment}', [PostActionController::class, 'destroyComment'])->name('comments.destroy')->whereNumber('comment');

    Route::get('/circles', [CircleController::class, 'index'])->name('circles.index');
    Route::get('/circles/{circle}', [CircleController::class, 'show'])->name('circles.show')->whereNumber('circle');
    Route::post('/circles', [CircleActionController::class, 'store'])->name('circles.store');
    Route::put('/circles/{circle}', [CircleActionController::class, 'update'])->name('circles.update')->whereNumber('circle');
    Route::delete('/circles/{circle}', [CircleActionController::class, 'destroy'])->name('circles.destroy')->whereNumber('circle');
    Route::post('/circles/{circle}/members', [CircleActionController::class, 'addMember'])->name('circles.members.store')->whereNumber('circle');
    Route::post('/circles/{circle}/invitations', [CircleActionController::class, 'inviteMember'])->name('circles.invitations.store')->whereNumber('circle');
    Route::delete('/circles/{circle}/invitations/{invitation}', [CircleActionController::class, 'cancelInvitation'])->name('circles.invitations.destroy')->whereNumber(['circle', 'invitation']);
    Route::delete('/circles/{circle}/members/{user}', [CircleActionController::class, 'removeMember'])->name('circles.members.destroy');

    Route::get('/profile/{username}', [ProfileController::class, 'show'])->name('profile.show');
});
