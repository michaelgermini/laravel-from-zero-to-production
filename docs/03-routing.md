# Routing

## Overview

Routing is the foundation of any web application. In Laravel, routes define how your application responds to HTTP requests. This chapter covers everything you need to know about Laravel's powerful routing system.

## What is Routing?

Routing is the process of defining how your application responds to different HTTP requests. When a user visits a URL, Laravel's router determines which controller method should handle that request.

## Basic Routes

### Defining Routes

Routes are defined in the `routes` directory. The main file is `routes/web.php` for web routes and `routes/api.php` for API routes.

**routes/web.php**
```php
<?php

use Illuminate\Support\Facades\Route;

// Basic GET route
Route::get('/', function () {
    return 'Hello, World!';
});

// Basic POST route
Route::post('/submit', function () {
    return 'Form submitted!';
});

// Multiple HTTP methods
Route::match(['get', 'post'], '/contact', function () {
    return 'Contact page';
});

// Any HTTP method
Route::any('/any', function () {
    return 'Any method accepted';
});
```

### HTTP Methods

Laravel supports all standard HTTP methods:

```php
Route::get($uri, $callback);
Route::post($uri, $callback);
Route::put($uri, $callback);
Route::patch($uri, $callback);
Route::delete($uri, $callback);
Route::options($uri, $callback);
```

### Route Parameters

You can capture URL segments as parameters:

```php
// Single parameter
Route::get('/user/{id}', function ($id) {
    return "User ID: {$id}";
});

// Multiple parameters
Route::get('/posts/{post}/comments/{comment}', function ($post, $comment) {
    return "Post: {$post}, Comment: {$comment}";
});

// Optional parameters
Route::get('/user/{name?}', function ($name = 'Guest') {
    return "Hello, {$name}!";
});
```

### Route Parameters with Constraints

You can add constraints to ensure parameters match specific patterns:

```php
// Numeric constraint
Route::get('/user/{id}', function ($id) {
    return "User ID: {$id}";
})->where('id', '[0-9]+');

// Alpha constraint
Route::get('/user/{name}', function ($name) {
    return "User: {$name}";
})->where('name', '[a-zA-Z]+');

// Multiple constraints
Route::get('/user/{id}/{name}', function ($id, $name) {
    return "User ID: {$id}, Name: {$name}";
})->where(['id' => '[0-9]+', 'name' => '[a-zA-Z]+']);

// Using whereNumber, whereAlpha, etc.
Route::get('/user/{id}', function ($id) {
    return "User ID: {$id}";
})->whereNumber('id');

Route::get('/user/{name}', function ($name) {
    return "User: {$name}";
})->whereAlpha('name');

Route::get('/user/{id}/{slug}', function ($id, $slug) {
    return "User ID: {$id}, Slug: {$slug}";
})->whereNumber('id')->whereAlphaNumeric('slug');
```

## Named Routes

Named routes allow you to generate URLs without hardcoding them:

```php
// Define a named route
Route::get('/user/profile', function () {
    return 'User Profile';
})->name('profile');

// Generate URL using route name
$url = route('profile'); // /user/profile

// Generate URL with parameters
Route::get('/user/{id}', function ($id) {
    return "User ID: {$id}";
})->name('user.show');

$url = route('user.show', ['id' => 123]); // /user/123

// Generate URL with query parameters
$url = route('user.show', ['id' => 123, 'tab' => 'settings']); // /user/123?tab=settings
```

### Using Named Routes in Views

```php
<!-- In Blade templates -->
<a href="{{ route('profile') }}">Profile</a>
<a href="{{ route('user.show', ['id' => $user->id]) }}">View User</a>

<!-- In forms -->
<form action="{{ route('user.update', ['id' => $user->id]) }}" method="POST">
    @csrf
    @method('PUT')
    <!-- form fields -->
</form>
```

## Route Groups

Route groups allow you to apply common attributes to multiple routes:

### Prefix Groups

```php
Route::prefix('admin')->group(function () {
    Route::get('/users', function () {
        return 'Admin Users';
    });
    
    Route::get('/posts', function () {
        return 'Admin Posts';
    });
});

// These routes will be accessible at:
// /admin/users
// /admin/posts
```

### Middleware Groups

```php
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', function () {
        return 'Admin Dashboard';
    });
    
    Route::get('/settings', function () {
        return 'Admin Settings';
    });
});
```

### Name Prefix Groups

```php
Route::name('admin.')->group(function () {
    Route::get('/users', function () {
        return 'Admin Users';
    })->name('users');
    
    Route::get('/posts', function () {
        return 'Admin Posts';
    })->name('posts');
});

// Route names will be:
// admin.users
// admin.posts
```

### Combined Groups

```php
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        Route::get('/dashboard', function () {
            return 'Admin Dashboard';
        })->name('dashboard');
        
        Route::get('/users', function () {
            return 'Admin Users';
        })->name('users');
    });
```

## Route Model Binding

Laravel automatically injects model instances based on route parameters:

### Basic Route Model Binding

```php
// Laravel will automatically fetch the User model
Route::get('/user/{user}', function (App\Models\User $user) {
    return $user;
});

// This will automatically fetch the user with ID 123
// /user/123
```

### Custom Route Model Binding

You can customize how models are resolved:

```php
// In App\Providers\RouteServiceProvider
public function boot()
{
    Route::model('user', App\Models\User::class);
    
    // Custom resolution logic
    Route::bind('user', function ($value) {
        return App\Models\User::where('username', $value)->firstOrFail();
    });
}

// Now you can use username instead of ID
Route::get('/user/{user}', function (App\Models\User $user) {
    return $user;
});

// /user/john-doe (will find user with username 'john-doe')
```

### Implicit Route Model Binding

```php
// Laravel automatically resolves the model
Route::get('/posts/{post}', function (App\Models\Post $post) {
    return $post;
});

// You can also specify the column to use
Route::get('/posts/{post:slug}', function (App\Models\Post $post) {
    return $post;
});

// This will find the post by slug instead of ID
```

## Route Caching

For production applications, you can cache your routes for better performance:

```bash
# Cache routes
php artisan route:cache

# Clear route cache
php artisan route:clear

# List all routes
php artisan route:list
```

## Route Lists

View all registered routes:

```bash
# List all routes
php artisan route:list

# List routes with specific method
php artisan route:list --method=GET

# List routes with specific name
php artisan route:list --name=user

# Export routes to file
php artisan route:list > routes.txt
```

## Route Parameters and Dependencies

### Type-Hinted Dependencies

Laravel's service container automatically resolves dependencies:

```php
Route::get('/users', function (App\Services\UserService $userService) {
    return $userService->getAllUsers();
});
```

### Request Injection

```php
Route::post('/users', function (Illuminate\Http\Request $request) {
    $name = $request->input('name');
    $email = $request->input('email');
    
    return "Name: {$name}, Email: {$email}";
});
```

## Route Middleware

Apply middleware to routes:

```php
// Single middleware
Route::get('/admin', function () {
    return 'Admin Area';
})->middleware('auth');

// Multiple middleware
Route::get('/admin', function () {
    return 'Admin Area';
})->middleware(['auth', 'admin']);

// Exclude middleware from specific routes in a group
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return 'Dashboard';
    });
    
    Route::get('/admin/public', function () {
        return 'Public Admin Page';
    })->withoutMiddleware(['auth']);
});
```

## Route Fallbacks

Define a fallback route for unmatched URLs:

```php
// This should be the last route defined
Route::fallback(function () {
    return response()->json(['message' => 'Not Found'], 404);
});
```

## API Routes

API routes are defined in `routes/api.php` and automatically have the `/api` prefix:

```php
// routes/api.php
Route::get('/users', function () {
    return App\Models\User::all();
});

// This route will be accessible at /api/users
```

## Route File Organization

For large applications, you can organize routes into multiple files:

```php
// routes/web.php
Route::get('/', function () {
    return view('welcome');
});

// Include other route files
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/api.php';
```

**routes/auth.php**
```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function () {
    // Login logic
})->name('login.post');
```

## Best Practices

### 1. Use Descriptive Route Names

```php
// Good
Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
Route::post('/users', [UserController::class, 'store'])->name('users.store');

// Avoid
Route::get('/u/{id}', [UserController::class, 'show'])->name('u');
```

### 2. Group Related Routes

```php
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::resource('users', AdminUserController::class);
    Route::resource('posts', AdminPostController::class);
});
```

### 3. Use Route Model Binding

```php
// Good - automatic model resolution
Route::get('/posts/{post}', [PostController::class, 'show']);

// Avoid - manual model fetching
Route::get('/posts/{id}', function ($id) {
    $post = Post::findOrFail($id);
    return view('posts.show', compact('post'));
});
```

### 4. Apply Constraints Appropriately

```php
Route::get('/user/{id}', [UserController::class, 'show'])
    ->where('id', '[0-9]+')
    ->name('users.show');
```

### 5. Use Named Routes Consistently

```php
// In controllers
return redirect()->route('users.show', ['user' => $user]);

// In views
<a href="{{ route('users.edit', ['user' => $user]) }}">Edit</a>
```

## Common Patterns

### RESTful Routes

```php
Route::resource('users', UserController::class);
// Creates:
// GET    /users          (index)
// GET    /users/create   (create)
// POST   /users          (store)
// GET    /users/{user}   (show)
// GET    /users/{user}/edit (edit)
// PUT/PATCH /users/{user} (update)
// DELETE /users/{user}   (destroy)
```

### API Resource Routes

```php
Route::apiResource('users', UserController::class);
// Creates only: index, store, show, update, destroy
// (no create or edit routes)
```

### Nested Resources

```php
Route::resource('users.posts', UserPostController::class);
// Creates:
// GET    /users/{user}/posts
// GET    /users/{user}/posts/create
// POST   /users/{user}/posts
// GET    /users/{user}/posts/{post}
// GET    /users/{user}/posts/{post}/edit
// PUT/PATCH /users/{user}/posts/{post}
// DELETE /users/{user}/posts/{post}
```

## Testing Routes

Test your routes with PHPUnit:

```php
// tests/Feature/RouteTest.php
public function test_home_page_returns_200()
{
    $response = $this->get('/');
    $response->assertStatus(200);
}

public function test_user_route_with_model_binding()
{
    $user = User::factory()->create();
    
    $response = $this->get("/user/{$user->id}");
    $response->assertStatus(200);
    $response->assertSee($user->name);
}
```

## Summary

Laravel's routing system is powerful and flexible. Key concepts covered:

- **Basic Routes**: Define routes for different HTTP methods
- **Route Parameters**: Capture URL segments and add constraints
- **Named Routes**: Generate URLs without hardcoding
- **Route Groups**: Apply common attributes to multiple routes
- **Route Model Binding**: Automatic model resolution
- **Middleware**: Apply filters to routes
- **Best Practices**: Organize and structure routes effectively

Understanding routing is fundamental to building Laravel applications. The concepts learned here will be used throughout the rest of the book as we build more complex applications.
