# Chapter 9: Authentication & Authorization

## What is Authentication?

Authentication is the process of verifying who a user is, while authorization is the process of determining what a user can access. Laravel provides a complete authentication system that includes user registration, login, password reset, email verification, and more.

## Laravel Authentication Packages

### Laravel Breeze

Laravel Breeze provides a minimal and simple authentication implementation:

```bash
# Install Laravel Breeze
composer require laravel/breeze --dev

# Install Breeze with default stack
php artisan breeze:install

# Install with specific stack
php artisan breeze:install blade
php artisan breeze:install react
php artisan breeze:install vue
php artisan breeze:install api
```

### Laravel Jetstream

Laravel Jetstream provides a more robust authentication system with additional features:

```bash
# Install Laravel Jetstream
composer require laravel/jetstream

# Install with Livewire
php artisan jetstream:install livewire

# Install with Inertia.js
php artisan jetstream:install inertia
```

### Laravel Fortify

Laravel Fortify provides backend authentication services:

```bash
# Install Laravel Fortify
composer require laravel/fortify

# Publish configuration
php artisan vendor:publish --provider="Laravel\Fortify\FortifyServiceProvider"
```

## Basic Authentication Setup

### User Model

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
```

### Authentication Configuration

```php
// config/auth.php
return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],
];
```

## Authentication Controllers

### Login Controller

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function authenticated(Request $request, $user)
    {
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->intended($this->redirectPath());
    }

    protected function loggedOut(Request $request)
    {
        return redirect()->route('login')
            ->with('success', 'You have been successfully logged out.');
    }
}
```

### Register Controller

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest');
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }
}
```

## Authentication Routes

### Web Routes

```php
// routes/web.php
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
    
    Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [RegisterController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('profile', [ProfileController::class, 'show'])->name('profile');
});
```

### API Routes

```php
// routes/api.php
use App\Http\Controllers\Auth\AuthController;

Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
});
```

## Manual Authentication

### Logging In

```php
use Illuminate\Support\Facades\Auth;

// Attempt to log in
if (Auth::attempt(['email' => $email, 'password' => $password])) {
    // Authentication successful
    return redirect()->intended('/dashboard');
}

// Attempt with additional conditions
if (Auth::attempt(['email' => $email, 'password' => $password, 'active' => 1])) {
    // User is active and credentials are correct
}

// Remember user
if (Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
    // User will be remembered
}
```

### Logging Out

```php
use Illuminate\Support\Facades\Auth;

// Log out the current user
Auth::logout();

// Log out and invalidate session
Auth::logout();
$request->session()->invalidate();
$request->session()->regenerateToken();
```

### Checking Authentication Status

```php
use Illuminate\Support\Facades\Auth;

// Check if user is authenticated
if (Auth::check()) {
    // User is logged in
}

// Get current user
$user = Auth::user();

// Get user ID
$userId = Auth::id();

// Check if user is guest
if (Auth::guest()) {
    // User is not logged in
}
```

## Password Reset

### Password Reset Controller

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Foundation\Auth\ResetsPasswords;

class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function __construct()
    {
        $this->middleware('guest');
    }
}

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest');
    }
}
```

### Password Reset Routes

```php
// routes/web.php
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;

Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])
    ->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->name('password.email');

Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])
    ->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset'])
    ->name('password.update');
```

### Custom Password Reset

```php
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;

// Send password reset link
$status = Password::sendResetLink($request->only('email'));

// Reset password
$status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function ($user, $password) {
    $user->forceFill([
        'password' => Hash::make($password)
    ])->save();
});
```

## Email Verification

### Enabling Email Verification

```php
// User model
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    // ...
}
```

### Email Verification Controller

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\VerifiesEmails;

class VerificationController extends Controller
{
    use VerifiesEmails;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }
}
```

### Email Verification Routes

```php
// routes/web.php
use App\Http\Controllers\Auth\VerificationController;

Route::get('email/verify', [VerificationController::class, 'show'])
    ->name('verification.notice');
Route::get('email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->name('verification.verify');
Route::post('email/resend', [VerificationController::class, 'resend'])
    ->name('verification.resend');
```

## Authorization

### Gates

Gates are simple closures that determine if a user is authorized to perform a given action:

```php
// In AuthServiceProvider
use Illuminate\Support\Facades\Gate;

public function boot()
{
    Gate::define('update-post', function (User $user, Post $post) {
        return $user->id === $post->user_id;
    });

    Gate::define('delete-post', function (User $user, Post $post) {
        return $user->id === $post->user_id || $user->isAdmin();
    });

    Gate::define('admin-access', function (User $user) {
        return $user->role === 'admin';
    });
}
```

### Using Gates

```php
use Illuminate\Support\Facades\Gate;

// Check if user can perform action
if (Gate::allows('update-post', $post)) {
    // User can update the post
}

// Check if user cannot perform action
if (Gate::denies('delete-post', $post)) {
    abort(403);
}

// Check for any user
if (Gate::forUser($user)->allows('update-post', $post)) {
    // Specific user can update the post
}

// In Blade templates
@can('update-post', $post)
    <a href="{{ route('posts.edit', $post) }}">Edit</a>
@endcan

@cannot('delete-post', $post)
    <p>You cannot delete this post.</p>
@endcannot
```

### Policies

Policies are classes that organize authorization logic around a particular model:

```bash
# Create policy
php artisan make:policy PostPolicy --model=Post
```

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Post;

class PostPolicy
{
    public function viewAny(User $user)
    {
        return true; // Anyone can view posts
    }

    public function view(User $user, Post $post)
    {
        return true; // Anyone can view a specific post
    }

    public function create(User $user)
    {
        return $user->isVerified(); // Only verified users can create posts
    }

    public function update(User $user, Post $post)
    {
        return $user->id === $post->user_id || $user->isAdmin();
    }

    public function delete(User $user, Post $post)
    {
        return $user->id === $post->user_id || $user->isAdmin();
    }

    public function restore(User $user, Post $post)
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Post $post)
    {
        return $user->isAdmin();
    }
}
```

### Registering Policies

```php
// In AuthServiceProvider
protected $policies = [
    Post::class => PostPolicy::class,
    Comment::class => CommentPolicy::class,
];
```

### Using Policies

```php
use Illuminate\Support\Facades\Gate;

// Check authorization
if (Gate::allows('update', $post)) {
    // User can update the post
}

// Authorize action (throws exception if not authorized)
Gate::authorize('update', $post);

// In controllers
public function update(Request $request, Post $post)
{
    $this->authorize('update', $post);
    
    // Update the post
}

// In Blade templates
@can('update', $post)
    <a href="{{ route('posts.edit', $post) }}">Edit</a>
@endcan
```

## Role-Based Authorization

### User Roles

```php
// User model
class User extends Authenticatable
{
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    public function hasAnyRole($roles)
    {
        return in_array($this->role, (array) $roles);
    }

    public function hasAllRoles($roles)
    {
        return empty(array_diff((array) $roles, [$this->role]));
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isModerator()
    {
        return $this->hasRole('moderator');
    }
}
```

### Role Middleware

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user() || !$request->user()->hasAnyRole($roles)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
```

### Using Role Middleware

```php
// routes/web.php
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});

Route::middleware(['auth', 'role:admin,moderator'])->group(function () {
    Route::get('/moderate', [ModerationController::class, 'index']);
});
```

## API Authentication with Sanctum

### Installing Sanctum

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### API Authentication Controller

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
```

### API Routes

```php
// routes/api.php
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
});
```

## Authentication Best Practices

### 1. Use Strong Passwords

```php
// In validation rules
'password' => [
    'required',
    'string',
    'min:8',
    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
    'confirmed',
],
```

### 2. Implement Rate Limiting

```php
// In routes
Route::post('login', [LoginController::class, 'login'])
    ->middleware('throttle:5,1'); // 5 attempts per minute

Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->middleware('throttle:3,1'); // 3 attempts per minute
```

### 3. Use HTTPS in Production

```php
// In AppServiceProvider
public function boot()
{
    if (app()->environment('production')) {
        \URL::forceScheme('https');
    }
}
```

### 4. Implement Two-Factor Authentication

```php
// Using Laravel Fortify
composer require laravel/fortify
php artisan vendor:publish --provider="Laravel\Fortify\FortifyServiceProvider"
```

### 5. Log Authentication Events

```php
// In EventServiceProvider
protected $listen = [
    'Illuminate\Auth\Events\Login' => [
        'App\Listeners\LogSuccessfulLogin',
    ],
    'Illuminate\Auth\Events\Failed' => [
        'App\Listeners\LogFailedLogin',
    ],
    'Illuminate\Auth\Events\Logout' => [
        'App\Listeners\LogSuccessfulLogout',
    ],
];
```

## Summary

In this chapter, we covered:

- ✅ Laravel authentication packages (Breeze, Jetstream, Fortify)
- ✅ Basic authentication setup and configuration
- ✅ Authentication controllers and routes
- ✅ Manual authentication methods
- ✅ Password reset functionality
- ✅ Email verification
- ✅ Authorization with Gates and Policies
- ✅ Role-based authorization
- ✅ API authentication with Sanctum
- ✅ Authentication best practices

Authentication and authorization are crucial for securing your Laravel application. In the next chapter, we'll explore events and queues for handling background tasks.
