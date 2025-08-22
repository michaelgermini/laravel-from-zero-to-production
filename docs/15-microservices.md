# Microservices

## Overview

Microservices architecture involves breaking down a monolithic application into smaller, independent services. This chapter covers building microservices with Laravel, including service design, communication patterns, and deployment strategies.

## What are Microservices?

Microservices are small, autonomous services that work together to form a larger application. Each service:
- Has its own database
- Can be developed, deployed, and scaled independently
- Communicates with other services via APIs
- Has a single responsibility

## Service Design Principles

### 1. Single Responsibility

```php
// User Service - handles user management only
class UserService
{
    public function createUser($data)
    {
        return User::create($data);
    }
    
    public function updateUser($id, $data)
    {
        return User::findOrFail($id)->update($data);
    }
}

// Post Service - handles posts only
class PostService
{
    public function createPost($data)
    {
        return Post::create($data);
    }
    
    public function getPostsByUser($userId)
    {
        return Post::where('user_id', $userId)->get();
    }
}
```

### 2. Service Independence

```php
// Each service has its own database
// User Service Database
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamps();
});

// Post Service Database
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content');
    $table->unsignedBigInteger('user_id');
    $table->timestamps();
});
```

## Service Communication

### 1. Synchronous Communication (HTTP/REST)

```php
// Service-to-service communication
class PostService
{
    protected $userServiceUrl;
    
    public function __construct()
    {
        $this->userServiceUrl = config('services.user_service.url');
    }
    
    public function createPost($data)
    {
        // Verify user exists before creating post
        $user = Http::get("{$this->userServiceUrl}/api/v1/users/{$data['user_id']}");
        
        if (!$user->successful()) {
            throw new UserNotFoundException();
        }
        
        return Post::create($data);
    }
}
```

### 2. Asynchronous Communication (Events/Queues)

```php
// User Service - Publish events
class UserCreated
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $user;
    
    public function __construct($user)
    {
        $this->user = $user;
    }
}

// Post Service - Listen to events
class UserCreatedListener
{
    public function handle(UserCreated $event)
    {
        UserProfile::create([
            'user_id' => $event->user['id'],
            'name' => $event->user['name'],
            'email' => $event->user['email'],
        ]);
    }
}
```

## API Gateway

### Basic API Gateway

```php
// API Gateway Service
class ApiGateway
{
    protected $services = [
        'users' => 'http://user-service:8000',
        'posts' => 'http://post-service:8001',
        'comments' => 'http://comment-service:8002',
    ];
    
    public function route($service, $path, $method = 'GET', $data = null)
    {
        $url = $this->services[$service] . $path;
        
        $response = Http::withHeaders([
            'Authorization' => request()->header('Authorization'),
            'Content-Type' => 'application/json',
        ])->$method($url, $data);
        
        return $response->json();
    }
}

// Gateway Routes
Route::prefix('api/v1')->group(function () {
    Route::get('users', function () {
        return app(ApiGateway::class)->route('users', '/api/v1/users');
    });
    
    Route::get('posts', function () {
        return app(ApiGateway::class)->route('posts', '/api/v1/posts');
    });
});
```

## Circuit Breaker Pattern

```php
class CircuitBreaker
{
    protected $failures = 0;
    protected $threshold = 5;
    protected $timeout = 60;
    protected $state = 'CLOSED';
    
    public function call($callback)
    {
        if ($this->state === 'OPEN') {
            throw new CircuitBreakerOpenException();
        }
        
        try {
            $result = $callback();
            $this->onSuccess();
            return $result;
        } catch (Exception $e) {
            $this->onFailure();
            throw $e;
        }
    }
    
    private function onSuccess()
    {
        $this->failures = 0;
        $this->state = 'CLOSED';
    }
    
    private function onFailure()
    {
        $this->failures++;
        if ($this->failures >= $this->threshold) {
            $this->state = 'OPEN';
        }
    }
}
```

## Service Discovery

```php
// Service Registry
class ServiceRegistry
{
    protected $services = [];
    
    public function register($name, $url)
    {
        $this->services[$name] = [
            'url' => $url,
            'registered_at' => now(),
        ];
    }
    
    public function get($name)
    {
        return $this->services[$name] ?? null;
    }
}

// Service Registration
Route::post('/register', function (Request $request) {
    app(ServiceRegistry::class)->register(
        $request->name,
        $request->url
    );
    
    return response()->json(['status' => 'registered']);
});
```

## Deployment with Docker

```yaml
# docker-compose.yml
version: '3.8'

services:
  api-gateway:
    build: ./api-gateway
    ports:
      - "8000:8000"
    depends_on:
      - user-service
      - post-service
  
  user-service:
    build: ./user-service
    ports:
      - "8001:8000"
    environment:
      - DB_HOST=user-db
  
  post-service:
    build: ./post-service
    ports:
      - "8002:8000"
    environment:
      - DB_HOST=post-db
  
  user-db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: user_service
  
  post-db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: post_service
```

## Monitoring and Observability

### Health Checks

```php
// Health check endpoint for each service
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'service' => config('app.name'),
        'version' => config('app.version'),
    ]);
});
```

### Service Metrics

```php
// Prometheus metrics
class MetricsMiddleware
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = microtime(true) - $start;
        
        // Record metrics
        Log::info('request_metrics', [
            'method' => $request->method(),
            'url' => $request->url(),
            'duration' => $duration,
            'status' => $response->getStatusCode(),
        ]);
        
        return $response;
    }
}
```

## Best Practices

### 1. Service Design

```php
// Keep services small and focused
class UserService
{
    // Only user-related operations
    public function createUser($data) { }
    public function updateUser($id, $data) { }
    public function deleteUser($id) { }
    public function getUser($id) { }
}
```

### 2. API Design

```php
// Use consistent API patterns
Route::prefix('api/v1')->group(function () {
    Route::apiResource('users', UserController::class);
    Route::get('users/{user}/profile', [UserController::class, 'profile']);
});
```

### 3. Error Handling

```php
// Implement proper error handling
class ServiceException extends Exception
{
    protected $service;
    protected $statusCode;
    
    public function __construct($message, $service, $statusCode = 500)
    {
        parent::__construct($message);
        $this->service = $service;
        $this->statusCode = $statusCode;
    }
}

// Handle service failures gracefully
try {
    $user = $userService->getUser($id);
} catch (ServiceException $e) {
    return $this->getFallbackUser($id);
}
```

### 4. Security

```php
// Implement service-to-service authentication
class ServiceAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        $token = $request->header('X-Service-Token');
        
        if (!$this->validateServiceToken($token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        return $next($request);
    }
}
```

## Data Consistency

### Saga Pattern

```php
// Saga Coordinator for distributed transactions
class CreateUserSaga
{
    public function execute($userData)
    {
        try {
            // Step 1: Create user
            $user = $this->createUser($userData);
            
            // Step 2: Create user profile
            $profile = $this->createProfile($user);
            
            // Step 3: Send welcome email
            $this->sendWelcomeEmail($user);
            
            return $user;
        } catch (Exception $e) {
            // Compensate for failures
            $this->compensate($user);
            throw $e;
        }
    }
    
    private function compensate($user)
    {
        if ($user) {
            $this->deleteUser($user);
        }
    }
}
```

## Summary

Building microservices with Laravel involves:

- **Service Design**: Breaking down applications into focused services
- **Communication**: Implementing service-to-service communication patterns
- **API Gateway**: Centralizing API management and routing
- **Service Discovery**: Managing service registration and discovery
- **Circuit Breaker**: Handling service failures gracefully
- **Deployment**: Using containerization and orchestration
- **Monitoring**: Implementing observability and health checks

Microservices offer scalability and flexibility but require careful design and management to be successful.
