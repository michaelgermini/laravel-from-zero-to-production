# Chapter 8: Middleware

## What is Middleware?

Middleware provides a mechanism for filtering HTTP requests entering your application. Middleware can be used for a variety of tasks such as authentication, logging, CORS, and more. Each middleware handles a specific concern and can be applied to routes, route groups, or globally.

## How Middleware Works

Middleware acts as a layer between the HTTP request and your application. When a request comes in, it passes through middleware in the order they are defined. Each middleware can:

1. **Modify the request** before it reaches your application
2. **Modify the response** before it's sent back to the client
3. **Terminate the request** early (e.g., redirect or return an error)
4. **Pass the request** to the next middleware

## Built-in Middleware

Laravel comes with several built-in middleware:

### Authentication Middleware

```php
// Check if user is authenticated
Route::get('/profile', function () {
    // Only authenticated users can access this
})->middleware('auth');

// Check if user is authenticated and email is verified
Route::get('/settings', function () {
    // Only verified users can access this
})->middleware(['auth', 'verified']);

// Check if user is authenticated and has specific guard
Route::get('/admin', function () {
    // Only admin guard users can access this
})->middleware('auth:admin');
```

### CSRF Protection

```php
// CSRF protection is automatically applied to web routes
Route::post('/users', function () {
    // CSRF token is automatically validated
});
```

### Rate Limiting

```php
// Limit requests to 60 per minute
Route::get('/api/users', function () {
    // Rate limited to 60 requests per minute
})->middleware('throttle:60,1');

// Limit requests with custom key
Route::get('/api/users', function () {
    // Rate limited by user ID
})->middleware('throttle:60,1')->middleware('throttle:60,1,user_id');
```

### CORS Middleware

```php
// Handle CORS for API routes
Route::middleware('cors')->group(function () {
    Route::get('/api/users', function () {
        // CORS headers are automatically added
    });
});
```

## Creating Custom Middleware

### Using Artisan Command

```bash
# Create middleware
php artisan make:middleware CheckAge

# Create middleware with specific path
php artisan make:middleware AdminMiddleware
```

### Basic Middleware Structure

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAge
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $minimumAge = 18): Response
    {
        if ($request->user()->age < $minimumAge) {
            return redirect('home')->with('error', 'You must be at least ' . $minimumAge . ' years old.');
        }

        return $next($request);
    }
}
```

### Middleware with Parameters

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user() || !in_array($request->user()->role, $roles)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
```

### Terminating Middleware

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Log after response is sent
        \Log::info('Request processed', [
            'url' => $request->url(),
            'method' => $request->method(),
            'status' => $response->getStatusCode(),
        ]);
        
        return $response;
    }
}
```

## Registering Middleware

### Global Middleware

Register middleware that runs on every request in `app/Http/Kernel.php`:

```php
protected $middleware = [
    // \App\Http\Middleware\TrustHosts::class,
    \App\Http\Middleware\TrustProxies::class,
    \Illuminate\Http\Middleware\HandleCors::class,
    \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
    \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
    \App\Http\Middleware\TrimStrings::class,
    \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    \App\Http\Middleware\LogRequests::class, // Custom middleware
];
```

### Route Middleware

Register middleware that can be assigned to routes in `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    'auth' => \App\Http\Middleware\Authenticate::class,
    'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
    'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
    'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
    'can' => \Illuminate\Auth\Middleware\Authorize::class,
    'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
    'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
    'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
    'signed' => \App\Http\Middleware\ValidateSignature::class,
    'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    'check.age' => \App\Http\Middleware\CheckAge::class, // Custom middleware
    'check.role' => \App\Http\Middleware\CheckRole::class, // Custom middleware
];
```

### Middleware Groups

Group related middleware in `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],

    'api' => [
        \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],

    'admin' => [
        'auth',
        'check.role:admin',
        \App\Http\Middleware\LogAdminActions::class,
    ],
];
```

## Applying Middleware

### To Individual Routes

```php
// Single middleware
Route::get('/profile', function () {
    return view('profile');
})->middleware('auth');

// Multiple middleware
Route::get('/admin/users', function () {
    return view('admin.users');
})->middleware(['auth', 'check.role:admin']);

// Middleware with parameters
Route::get('/adult-content', function () {
    return view('adult.content');
})->middleware('check.age:21');
```

### To Route Groups

```php
// Apply middleware to group
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', function () {
        return view('profile');
    });
    
    Route::get('/settings', function () {
        return view('settings');
    });
});

// Apply middleware group
Route::middleware('admin')->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard');
    });
    
    Route::get('/admin/users', function () {
        return view('admin.users');
    });
});
```

### To Controllers

```php
class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('check.role:admin')->only(['index', 'store']);
        $this->middleware('check.age:18')->except(['index']);
    }
    
    public function index()
    {
        // Requires auth and admin role
    }
    
    public function show($id)
    {
        // Requires auth only
    }
    
    public function store()
    {
        // Requires auth and admin role
    }
}
```

### Conditional Middleware

```php
class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->only(['profile', 'settings']);
        $this->middleware('check.role:admin')->when(function ($request) {
            return $request->is('admin/*');
        });
    }
}
```

## Common Middleware Examples

### Authentication Middleware

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSubscribed
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->subscribed()) {
            return redirect()->route('subscription.create')
                ->with('error', 'You need an active subscription to access this feature.');
        }

        return $next($request);
    }
}
```

### Logging Middleware

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        if ($request->user()) {
            \Log::info('User activity', [
                'user_id' => $request->user()->id,
                'url' => $request->url(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
        
        return $response;
    }
}
```

### Maintenance Mode Middleware

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isDownForMaintenance() && !$request->user()?->isAdmin()) {
            return response()->view('maintenance', [], 503);
        }

        return $next($request);
    }
}
```

### API Rate Limiting Middleware

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ApiRateLimit
{
    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle(Request $request, Closure $next, $maxAttempts = 60): Response
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($key, $maxAttempts);
        }

        $this->limiter->hit($key);

        $response = $next($request);

        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    protected function resolveRequestSignature(Request $request): string
    {
        return sha1(implode('|', [
            $request->user()?->id ?? $request->ip(),
            $request->route()?->getDomain() ?? $request->getHost(),
        ]));
    }

    protected function buildResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'error' => 'Too many requests.',
            'retry_after' => $retryAfter,
        ], 429)->withHeaders([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
        ]);
    }

    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);
    }

    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $maxAttempts - $this->limiter->attempts($key) + 1;
    }
}
```

## Middleware Best Practices

### 1. Keep Middleware Focused

```php
// Good - Single responsibility
class CheckAge
{
    public function handle(Request $request, Closure $next, $minimumAge = 18): Response
    {
        if ($request->user()->age < $minimumAge) {
            return redirect('home');
        }
        return $next($request);
    }
}

// Bad - Multiple responsibilities
class CheckAgeAndRoleAndSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        // Too many checks in one middleware
        if ($request->user()->age < 18) {
            return redirect('home');
        }
        if ($request->user()->role !== 'admin') {
            return redirect('home');
        }
        if (!$request->user()->subscribed()) {
            return redirect('subscription');
        }
        return $next($request);
    }
}
```

### 2. Use Descriptive Names

```php
// Good
'check.age' => \App\Http\Middleware\CheckAge::class,
'ensure.subscribed' => \App\Http\Middleware\EnsureUserIsSubscribed::class,
'log.activity' => \App\Http\Middleware\LogUserActivity::class,

// Bad
'middleware1' => \App\Http\Middleware\Middleware1::class,
'check' => \App\Http\Middleware\Check::class,
```

### 3. Handle Errors Gracefully

```php
class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        if (!in_array($request->user()->role, $roles)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
```

### 4. Use Middleware Groups for Common Patterns

```php
// In Kernel.php
protected $middlewareGroups = [
    'web' => [
        // Web-specific middleware
    ],
    'api' => [
        // API-specific middleware
    ],
    'admin' => [
        'auth',
        'check.role:admin',
        'log.activity',
    ],
    'premium' => [
        'auth',
        'ensure.subscribed',
        'check.role:premium',
    ],
];
```

### 5. Test Your Middleware

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Middleware\CheckAge;
use Illuminate\Http\Request;

class CheckAgeMiddlewareTest extends TestCase
{
    public function test_middleware_allows_users_above_minimum_age()
    {
        $middleware = new CheckAge();
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () {
            return (object) ['age' => 25];
        });

        $response = $middleware->handle($request, function ($request) {
            return response('OK');
        }, 18);

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_middleware_redirects_users_below_minimum_age()
    {
        $middleware = new CheckAge();
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () {
            return (object) ['age' => 16];
        });

        $response = $middleware->handle($request, function ($request) {
            return response('OK');
        }, 18);

        $this->assertEquals(302, $response->getStatusCode());
    }
}
```

## Summary

In this chapter, we covered:

- ✅ Understanding middleware and how it works
- ✅ Built-in Laravel middleware
- ✅ Creating custom middleware
- ✅ Registering and applying middleware
- ✅ Middleware groups and conditional middleware
- ✅ Common middleware examples
- ✅ Best practices for writing middleware
- ✅ Testing middleware

Middleware provides a powerful way to filter and modify HTTP requests in your Laravel application. In the next chapter, we'll explore authentication and authorization.
