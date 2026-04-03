<?php

use Babysteps\ApiClient\Http\Controllers\AuthController;
use Babysteps\ApiClient\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/auth/verify', [AuthController::class, 'verify'])->name('auth.verify');

    Route::middleware('auth.token')->group(function () {
        Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
        Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
        Route::post('/posts/{post}/like', [PostController::class, 'like'])->name('posts.like');
        Route::delete('/posts/{post}/like', [PostController::class, 'unlike'])->name('posts.unlike');
        Route::post('/posts/{post}/comments', [PostController::class, 'comment'])->name('posts.comments.store');
        Route::delete('/comments/{comment}', [PostController::class, 'destroyComment'])->name('comments.destroy');
    });
});
