# Performance Optimization

## Overview

Performance optimization is crucial for building scalable Laravel applications. This chapter covers various techniques and best practices for improving application performance, from database optimization to application profiling and monitoring.

## Performance Fundamentals

### What Affects Performance?

1. **Database Queries**: Slow queries, N+1 problems, missing indexes
2. **Memory Usage**: Large datasets, memory leaks, inefficient data structures
3. **CPU Usage**: Complex calculations, inefficient algorithms
4. **Network**: Slow external API calls, large response sizes
5. **Caching**: Missing cache opportunities, inefficient cache strategies

### Performance Metrics

```php
// Measure execution time
$start = microtime(true);
// Your code here
$executionTime = microtime(true) - $start;

// Measure memory usage
$memoryUsage = memory_get_usage(true);
$peakMemory = memory_get_peak_usage(true);
```

## Database Optimization

### Query Optimization

#### N+1 Problem

```php
// Bad: N+1 problem
$users = User::all();
foreach ($users as $user) {
    echo $user->posts->count(); // Executes N queries
}

// Good: Eager loading
$users = User::with('posts')->get();
foreach ($users as $user) {
    echo $user->posts->count(); // No additional queries
}

// Better: Count with relationship
$users = User::withCount('posts')->get();
foreach ($users as $user) {
    echo $user->posts_count; // Pre-calculated
}
```

#### Select Specific Columns

```php
// Bad: Select all columns
$users = User::all();

// Good: Select only needed columns
$users = User::select('id', 'name', 'email')->get();

// Better: Use resource classes
class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
```

#### Use Database Indexes

```php
// Migration with indexes
public function up()
{
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('title');
        $table->text('content');
        $table->string('slug')->unique();
        $table->boolean('is_published')->default(false);
        $table->timestamps();
        
        // Add indexes for frequently queried columns
        $table->index(['user_id', 'is_published']);
        $table->index('created_at');
        $table->fullText(['title', 'content']);
    });
}
```

### Query Scopes

```php
class Post extends Model
{
    // Local scopes for common queries
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
    
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
    
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
    
    // Usage
    $posts = Post::published()->byUser($userId)->recent(30)->get();
}
```

### Database Connection Optimization

```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => null,
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        PDO::ATTR_PERSISTENT => true, // Enable persistent connections
    ]) : [],
],
```

## Application Optimization

### Route Caching

```bash
# Cache routes for production
php artisan route:cache

# Cache configuration
php artisan config:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

### Service Container Optimization

```php
// Use singleton for expensive services
$this->app->singleton(ExpensiveService::class, function ($app) {
    return new ExpensiveService();
});

// Use lazy loading for services
$this->app->when(PostController::class)
    ->needs(PostService::class)
    ->give(function () {
        return new PostService();
    });
```

### Memory Management

```php
// Use generators for large datasets
function getLargeDataset()
{
    $query = DB::table('posts')->orderBy('id');
    
    foreach ($query->cursor() as $post) {
        yield $post;
    }
}

// Usage
foreach (getLargeDataset() as $post) {
    // Process each post
}
```

### Chunk Processing

```php
// Process large datasets in chunks
User::chunk(1000, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});

// Chunk by ID for better performance
User::where('id', '>', 0)->chunkById(1000, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});
```

## Caching Strategies

### Model Caching

```php
class User extends Model
{
    protected static function boot()
    {
        parent::boot();
        
        // Cache frequently accessed data
        static::saved(function ($user) {
            Cache::tags('users')->flush();
        });
    }
    
    public function getCachedPostsAttribute()
    {
        return Cache::remember("user.{$this->id}.posts", 3600, function () {
            return $this->posts()->with('comments')->get();
        });
    }
}
```

### Query Result Caching

```php
// Cache expensive queries
$popularPosts = Cache::remember('popular_posts', 3600, function () {
    return Post::with('user')
        ->where('views', '>', 1000)
        ->orderBy('views', 'desc')
        ->take(10)
        ->get();
});
```

### Response Caching

```php
// Cache entire responses
Route::get('/posts', function () {
    return Cache::remember('posts_page', 1800, function () {
        return view('posts.index', [
            'posts' => Post::with('user')->paginate(20)
        ]);
    });
});
```

## Profiling and Monitoring

### Laravel Telescope

```bash
# Install Laravel Telescope
composer require laravel/telescope --dev

# Publish configuration
php artisan telescope:install

# Access dashboard at /telescope
```

### Query Logging

```php
// Enable query logging
DB::enableQueryLog();

// Your code here
$users = User::with('posts')->get();

// Get executed queries
$queries = DB::getQueryLog();

// Log slow queries
DB::listen(function ($query) {
    if ($query->time > 100) {
        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'time' => $query->time,
            'bindings' => $query->bindings,
        ]);
    }
});
```

### Performance Monitoring

```php
// Custom performance monitoring
class PerformanceMonitor
{
    public static function measure($name, callable $callback)
    {
        $start = microtime(true);
        $startMemory = memory_get_usage();
        
        $result = $callback();
        
        $executionTime = microtime(true) - $start;
        $memoryUsed = memory_get_usage() - $startMemory;
        
        Log::info("Performance: {$name}", [
            'execution_time' => $executionTime,
            'memory_used' => $memoryUsed,
        ]);
        
        return $result;
    }
}

// Usage
$users = PerformanceMonitor::measure('get_users', function () {
    return User::with('posts')->get();
});
```

## Frontend Optimization

### Asset Optimization

```php
// Mix configuration for asset optimization
// webpack.mix.js
const mix = require('laravel-mix');

mix.js('resources/js/app.js', 'public/js')
   .sass('resources/sass/app.scss', 'public/css')
   .version() // Add versioning for cache busting
   .sourceMaps() // Enable source maps for debugging
   .minify() // Minify assets
   .extract(['vue', 'axios']); // Extract vendor libraries
```

### Blade Optimization

```php
// Use @include instead of @component for simple includes
@include('components.alert', ['type' => 'success'])

// Cache expensive blade operations
@cache('expensive_calculation', 3600)
    @php
        $result = expensiveCalculation();
    @endphp
    {{ $result }}
@endcache
```

### Response Optimization

```php
// Compress responses
Route::get('/api/posts', function () {
    return response()->json(Post::all())
        ->header('Content-Encoding', 'gzip');
});

// Use JSON resources for consistent API responses
class PostResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->when($request->show_content, $this->content),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
```

## Queue Optimization

### Job Optimization

```php
class ProcessLargeDataset implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $maxExceptions = 1;
    
    public function handle()
    {
        // Process in chunks to avoid memory issues
        User::chunk(1000, function ($users) {
            foreach ($users as $user) {
                ProcessUser::dispatch($user);
            }
        });
    }
}
```

### Queue Configuration

```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
],

// Use multiple queues for different priorities
ProcessUser::dispatch($user)->onQueue('high');
ProcessUser::dispatch($user)->onQueue('low');
```

## Server Optimization

### PHP Configuration

```ini
; php.ini optimizations
memory_limit = 512M
max_execution_time = 300
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1
```

### Web Server Configuration

```nginx
# Nginx configuration for Laravel
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/your-app/public;
    
    # Enable gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
    
    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Performance Testing

### Load Testing

```php
// Simple load testing
class LoadTest
{
    public static function test($url, $requests = 100, $concurrency = 10)
    {
        $start = microtime(true);
        $successful = 0;
        $failed = 0;
        
        for ($i = 0; $i < $requests; $i++) {
            try {
                $response = Http::get($url);
                if ($response->successful()) {
                    $successful++;
                } else {
                    $failed++;
                }
            } catch (Exception $e) {
                $failed++;
            }
        }
        
        $duration = microtime(true) - $start;
        $rps = $requests / $duration;
        
        return [
            'requests' => $requests,
            'successful' => $successful,
            'failed' => $failed,
            'duration' => $duration,
            'requests_per_second' => $rps,
        ];
    }
}
```

### Benchmark Testing

```php
// Benchmark different approaches
class BenchmarkTest
{
    public static function compare($name, array $tests)
    {
        $results = [];
        
        foreach ($tests as $testName => $test) {
            $start = microtime(true);
            $startMemory = memory_get_usage();
            
            $result = $test();
            
            $executionTime = microtime(true) - $start;
            $memoryUsed = memory_get_usage() - $startMemory;
            
            $results[$testName] = [
                'execution_time' => $executionTime,
                'memory_used' => $memoryUsed,
                'result' => $result,
            ];
        }
        
        Log::info("Benchmark: {$name}", $results);
        
        return $results;
    }
}

// Usage
BenchmarkTest::compare('User Queries', [
    'eager_loading' => function () {
        return User::with('posts')->get();
    },
    'lazy_loading' => function () {
        return User::all();
    },
]);
```

## Best Practices

### 1. Database Best Practices

```php
// Use database transactions
DB::transaction(function () {
    $user = User::create($data);
    $user->profile()->create($profileData);
});

// Use database indexes
Schema::table('posts', function (Blueprint $table) {
    $table->index(['user_id', 'created_at']);
});

// Use database constraints
Schema::table('posts', function (Blueprint $table) {
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
});
```

### 2. Code Best Practices

```php
// Use lazy loading for expensive operations
class User extends Model
{
    public function getExpensiveDataAttribute()
    {
        return $this->remember('expensive_data', 3600, function () {
            return $this->calculateExpensiveData();
        });
    }
}

// Use collections efficiently
$users = User::all();
$activeUsers = $users->filter->isActive();
$userNames = $users->pluck('name');
```

### 3. Caching Best Practices

```php
// Use appropriate cache keys
Cache::put("user.{$user->id}.profile", $profile, 3600);
Cache::put("user.{$user->id}.posts", $posts, 1800);

// Use cache tags for related data
Cache::tags(['users', 'posts'])->put("user.{$user->id}", $user, 3600);

// Implement cache warming
class CacheWarming
{
    public function warm()
    {
        Cache::remember('popular_posts', 3600, function () {
            return Post::popular()->take(10)->get();
        });
    }
}
```

## Summary

Performance optimization in Laravel involves multiple aspects:

- **Database Optimization**: Query optimization, indexing, eager loading
- **Application Optimization**: Caching, memory management, code efficiency
- **Server Optimization**: PHP configuration, web server settings
- **Monitoring**: Profiling, benchmarking, performance testing
- **Best Practices**: Following established patterns and guidelines

By implementing these optimization techniques, you can significantly improve your Laravel application's performance and scalability.
