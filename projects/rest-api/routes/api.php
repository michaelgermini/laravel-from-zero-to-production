<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Public post routes
Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/featured', [PostController::class, 'featured']);
Route::get('/posts/search', [PostController::class, 'search']);
Route::get('/posts/category/{categoryId}', [PostController::class, 'byCategory']);
Route::get('/posts/{post}', [PostController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User management
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::delete('/profile', [UserController::class, 'deleteProfile']);
    
    // User's posts
    Route::get('/my-posts', [PostController::class, 'userPosts']);
    
    // Post management (requires permissions)
    Route::middleware('can:manage-posts')->group(function () {
        Route::post('/posts', [PostController::class, 'store']);
        Route::put('/posts/{post}', [PostController::class, 'update']);
        Route::delete('/posts/{post}', [PostController::class, 'destroy']);
        Route::patch('/posts/{post}/toggle-featured', [PostController::class, 'toggleFeatured']);
    });
    
    // Comments
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
});

// API Documentation route
Route::get('/', function () {
    return response()->json([
        'message' => 'Laravel REST API',
        'version' => '1.0.0',
        'documentation' => [
            'endpoints' => [
                'GET /api/posts' => 'Get all posts',
                'GET /api/posts/{id}' => 'Get a specific post',
                'POST /api/posts' => 'Create a new post (authenticated)',
                'PUT /api/posts/{id}' => 'Update a post (authenticated)',
                'DELETE /api/posts/{id}' => 'Delete a post (authenticated)',
                'GET /api/posts/featured' => 'Get featured posts',
                'GET /api/posts/search?q=term' => 'Search posts',
                'GET /api/posts/category/{id}' => 'Get posts by category',
                'POST /api/register' => 'Register a new user',
                'POST /api/login' => 'Login user',
                'POST /api/logout' => 'Logout user (authenticated)',
            ],
            'authentication' => [
                'type' => 'Bearer Token (Sanctum)',
                'header' => 'Authorization: Bearer {token}',
            ],
            'pagination' => [
                'parameter' => '?page={number}&per_page={number}',
                'response' => 'Includes links, meta, and data',
            ],
            'filtering' => [
                'status' => '?status=published|draft',
                'category' => '?category_id={id}',
                'search' => '?search={term}',
                'sorting' => '?sort_by={field}&sort_order=asc|desc',
            ],
        ],
    ]);
});
