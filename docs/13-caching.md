# Caching

## Overview

Caching is essential for improving application performance. Laravel provides a powerful caching system that supports multiple cache drivers and offers various caching strategies. This chapter covers everything you need to know about implementing effective caching in your Laravel applications.

## What is Caching?

Caching is the process of storing frequently accessed data in memory or fast storage to reduce response times and server load. Laravel's caching system provides a unified API for working with different cache backends.

## Cache Drivers

Laravel supports multiple cache drivers, each with different characteristics:

### Available Drivers

```php
// config/cache.php
'default' => env('CACHE_DRIVER', 'file'),

'stores' => [
    'apc' => [
        'driver' => 'apc',
    ],
    'array' => [
        'driver' => 'array',
    ],
    'file' => [
        'driver' => 'file',
        'path' => storage_path('framework/cache/data'),
    ],
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
    ],
    'memcached' => [
        'driver' => 'memcached',
        'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
        'sasl' => [
            env('MEMCACHED_USERNAME'),
            env('MEMCACHED_PASSWORD'),
        ],
        'options' => [
            // Memcached::OPT_CONNECT_TIMEOUT => 2000,
        ],
        'servers' => [
            [
                'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                'port' => env('MEMCACHED_PORT', 11211),
                'weight' => 100,
            ],
        ],
    ],
],
```

### Driver Comparison

| Driver | Speed | Persistence | Memory Usage | Use Case |
|--------|-------|-------------|--------------|----------|
| Array | Fastest | No | High | Testing |
| File | Slow | Yes | Low | Development |
| Redis | Fast | Yes | Medium | Production |
| Memcached | Fast | No | Low | Production |
| APC | Fastest | No | High | Single Server |

## Basic Caching Operations

### Storing Items

```php
use Illuminate\Support\Facades\Cache;

// Store a simple value
Cache::put('key', 'value', $seconds = 60);

// Store forever
Cache::forever('key', 'value');

// Store if not exists
Cache::add('key', 'value', $seconds = 60);

// Store with helper function
cache(['key' => 'value'], $seconds = 60);
```

### Retrieving Items

```php
// Get value
$value = Cache::get('key');

// Get with default
$value = Cache::get('key', 'default');

// Get and delete
$value = Cache::pull('key');

// Check if exists
if (Cache::has('key')) {
    // Key exists
}

// Get multiple items
$values = Cache::many(['key1', 'key2', 'key3']);
```

### Removing Items

```php
// Remove single item
Cache::forget('key');

// Remove multiple items
Cache::forget(['key1', 'key2']);

// Clear all cache
Cache::flush();

// Remove by pattern (Redis only)
Cache::flush('user:*');
```

## Cache Tags

Cache tags allow you to group related cache items and flush them together:

```php
// Store with tags
Cache::tags(['users', 'posts'])->put('user_posts', $posts, 3600);

// Retrieve with tags
$posts = Cache::tags(['users', 'posts'])->get('user_posts');

// Flush specific tags
Cache::tags('users')->flush();

// Flush multiple tags
Cache::tags(['users', 'posts'])->flush();
```

## Cache Helpers

### Remember Pattern

```php
// Remember pattern - get from cache or store
$users = Cache::remember('users', 3600, function () {
    return User::all();
});

// Remember forever
$users = Cache::rememberForever('users', function () {
    return User::all();
});

// Remember with tags
$users = Cache::tags('users')->remember('all_users', 3600, function () {
    return User::all();
});
```

### Increment/Decrement

```php
// Increment counter
Cache::increment('counter');
Cache::increment('counter', 5);

// Decrement counter
Cache::decrement('counter');
Cache::decrement('counter', 5);
```

## Model Caching

### Cache Model Queries

```php
// Cache query results
$users = Cache::remember('users.active', 3600, function () {
    return User::where('active', true)->get();
});

// Cache with model events
class User extends Model
{
    protected static function boot()
    {
        parent::boot();
        
        static::saved(function ($user) {
            Cache::tags('users')->flush();
        });
        
        static::deleted(function ($user) {
            Cache::tags('users')->flush();
        });
    }
}
```

### Cache Model Relationships

```php
class User extends Model
{
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
    
    public function getCachedPostsAttribute()
    {
        return Cache::remember("user.{$this->id}.posts", 3600, function () {
            return $this->posts()->with('comments')->get();
        });
    }
}

// Usage
$user = User::find(1);
$posts = $user->cached_posts;
```

## Route Caching

### Cache Routes

```bash
# Cache all routes
php artisan route:cache

# Clear route cache
php artisan route:clear

# List cached routes
php artisan route:list
```

### Cache Configuration

```bash
# Cache configuration
php artisan config:cache

# Clear config cache
php artisan config:clear

# Cache views
php artisan view:cache

# Clear view cache
php artisan view:clear
```

## Database Query Caching

### Cache Query Results

```php
// Cache database queries
$users = DB::table('users')
    ->where('active', true)
    ->remember(60)
    ->get();

// Cache with tags
$users = DB::table('users')
    ->where('active', true)
    ->remember(60, 'users.active')
    ->get();
```

### Cache Eloquent Queries

```php
// Cache Eloquent queries
$users = User::where('active', true)
    ->remember(60)
    ->get();

// Cache with relationships
$users = User::with('posts')
    ->where('active', true)
    ->remember(60, 'users.with_posts')
    ->get();
```

## Cache Events

### Listen to Cache Events

```php
// In EventServiceProvider
protected $listen = [
    'Illuminate\Cache\Events\CacheHit' => [
        'App\Listeners\LogCacheHit',
    ],
    'Illuminate\Cache\Events\CacheMissed' => [
        'App\Listeners\LogCacheMissed',
    ],
    'Illuminate\Cache\Events\KeyForgotten' => [
        'App\Listeners\LogCacheForgotten',
    ],
    'Illuminate\Cache\Events\KeyWritten' => [
        'App\Listeners\LogCacheWritten',
    ],
];
```

### Custom Cache Events

```php
// Create custom cache events
class CacheCleared
{
    public $tags;
    
    public function __construct($tags = null)
    {
        $this->tags = $tags;
    }
}

// Dispatch event
event(new CacheCleared(['users', 'posts']));
```

## Cache Middleware

### Cache Response Middleware

```php
// Create cache middleware
php artisan make:middleware CacheResponse

class CacheResponse
{
    public function handle($request, Closure $next)
    {
        if ($request->isMethod('GET')) {
            $cacheKey = 'page_cache_' . sha1($request->fullUrl());
            
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }
            
            $response = $next($request);
            
            Cache::put($cacheKey, $response, 3600);
            
            return $response;
        }
        
        return $next($request);
    }
}
```

### Apply to Routes

```php
// Apply to specific routes
Route::get('/posts', [PostController::class, 'index'])
    ->middleware('cache.response');

// Apply to route groups
Route::middleware(['cache.response'])->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/users', [UserController::class, 'index']);
});
```

## Advanced Caching Patterns

### Cache-Aside Pattern

```php
class UserService
{
    public function getUser($id)
    {
        return Cache::remember("user.{$id}", 3600, function () use ($id) {
            return User::findOrFail($id);
        });
    }
    
    public function updateUser($id, $data)
    {
        $user = User::findOrFail($id);
        $user->update($data);
        
        // Invalidate cache
        Cache::forget("user.{$id}");
        
        return $user;
    }
}
```

### Write-Through Pattern

```php
class PostService
{
    public function createPost($data)
    {
        $post = Post::create($data);
        
        // Update cache immediately
        Cache::put("post.{$post->id}", $post, 3600);
        Cache::tags('posts')->flush();
        
        return $post;
    }
}
```

### Cache Warming

```php
// Warm up cache on application start
class WarmCache
{
    public function handle()
    {
        // Cache frequently accessed data
        Cache::remember('popular_posts', 3600, function () {
            return Post::popular()->take(10)->get();
        });
        
        Cache::remember('active_users', 3600, function () {
            return User::active()->take(50)->get();
        });
    }
}
```

## Performance Optimization

### Cache Key Strategies

```php
// Use descriptive cache keys
Cache::put("user.{$user->id}.profile", $profile, 3600);
Cache::put("user.{$user->id}.posts", $posts, 1800);
Cache::put("user.{$user->id}.settings", $settings, 7200);

// Use versioned cache keys
$version = Cache::get('users_version', 1);
Cache::put("users.v{$version}.all", $users, 3600);

// Invalidate by incrementing version
Cache::increment('users_version');
```

### Cache Size Management

```php
// Monitor cache size
$size = Cache::get('cache_size', 0);
Cache::put('cache_size', $size + 1);

// Implement cache size limits
if ($size > 1000) {
    Cache::flush();
    Cache::put('cache_size', 0);
}
```

## Monitoring and Debugging

### Cache Statistics

```php
// Get cache statistics
$stats = Cache::getStats();

// Monitor cache hit rate
$hits = Cache::get('cache_hits', 0);
$misses = Cache::get('cache_misses', 0);
$hitRate = $hits / ($hits + $misses) * 100;
```

### Cache Debugging

```php
// Enable cache debugging
if (config('app.debug')) {
    Cache::put('debug_key', 'value', 60);
    Log::info('Cache debug: ' . Cache::get('debug_key'));
}
```

## Best Practices

### 1. Choose Appropriate TTL

```php
// Short TTL for frequently changing data
Cache::put('user_session', $session, 300); // 5 minutes

// Long TTL for static data
Cache::put('app_config', $config, 86400); // 24 hours

// No TTL for rarely changing data
Cache::forever('app_constants', $constants);
```

### 2. Use Cache Tags Wisely

```php
// Group related data
Cache::tags(['users', 'profile'])->put("user.{$id}", $user, 3600);
Cache::tags(['users', 'posts'])->put("user.{$id}.posts", $posts, 1800);

// Flush specific groups
Cache::tags('users')->flush(); // Flushes all user-related cache
```

### 3. Implement Cache Invalidation

```php
// Invalidate cache on model changes
class Post extends Model
{
    protected static function boot()
    {
        parent::boot();
        
        static::saved(function ($post) {
            Cache::tags(['posts', "user.{$post->user_id}"])->flush();
        });
    }
}
```

### 4. Monitor Cache Performance

```php
// Track cache performance
$start = microtime(true);
$data = Cache::get('key');
$time = microtime(true) - $start;

if ($time > 0.1) {
    Log::warning("Slow cache access: {$time}s");
}
```

## Summary

Laravel's caching system provides powerful tools for improving application performance:

- **Multiple Cache Drivers**: Choose the right driver for your needs
- **Cache Tags**: Group and manage related cache items
- **Model Caching**: Cache database queries and relationships
- **Route Caching**: Cache routes and configuration
- **Advanced Patterns**: Implement cache-aside and write-through patterns
- **Performance Monitoring**: Track and optimize cache usage

Understanding and implementing effective caching strategies is crucial for building high-performance Laravel applications.
