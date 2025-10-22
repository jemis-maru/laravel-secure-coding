<?php

use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PostController::class, 'index'])->name('posts.index');

# Show create form
Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');

# Patched store
Route::post('/posts/store', [PostController::class, 'store'])->name('posts.store');

# Search functionality
Route::get('/posts/search', [PostController::class, 'searchForm'])->name('posts.search');
Route::get('/posts/search-vulnerable', [PostController::class, 'searchVulnerable'])->name('posts.search.vulnerable');
Route::get('/posts/search-secure', [PostController::class, 'searchSecure'])->name('posts.search.secure');
