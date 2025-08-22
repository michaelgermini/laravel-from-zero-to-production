# REST API - Laravel

A complete REST API built with Laravel, featuring authentication, data transformation, and comprehensive documentation.

## 🚀 Features

### ✅ **API Authentication**
- **Laravel Sanctum** : Token-based authentication
- **User registration** : Secure account creation
- **Login/Logout** : Session management
- **Password reset** : Email-based recovery

### ✅ **Data Management**
- **Complete CRUD** : Create, Read, Update, Delete operations
- **API Resources** : Data transformation and formatting
- **Pagination** : Efficient data browsing
- **Filtering** : Advanced query capabilities

### ✅ **Advanced Features**
- **Rate limiting** : API usage protection
- **Validation** : Request data validation
- **Error handling** : Comprehensive error responses
- **Documentation** : Auto-generated API docs
- **Testing** : Complete test suite

### ✅ **Developer Experience**
- **Consistent responses** : Standardized API format
- **Status codes** : Proper HTTP status codes
- **CORS support** : Cross-origin requests
- **Versioning** : API version management

## 📁 Project Structure

```
rest-api/
├── app/
│   ├── Http/Controllers/Api/
│   │   └── PostController.php      # API controller
│   ├── Http/Resources/
│   │   ├── PostResource.php       # Data transformation
│   │   └── PostCollection.php     # Collection wrapper
│   ├── Http/Requests/
│   │   └── StorePostRequest.php   # Validation rules
│   └── Models/
│       └── Post.php               # Eloquent model
├── database/
│   └── migrations/
│       └── create_posts_table.php # Database structure
├── routes/
│   └── api.php                   # API routes
├── tests/
│   └── Feature/
│       └── ApiTest.php           # API tests
└── README.md                     # This file
```

## 🛠️ Installation

### 1. **Prerequisites**
- PHP 8.1+
- Composer
- MySQL/PostgreSQL/SQLite
- Laravel 10+

### 2. **Installation**
```bash
# Clone the project
git clone <repository-url>
cd rest-api

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rest_api
DB_USERNAME=root
DB_PASSWORD=

# Run migrations
php artisan migrate

# Install Laravel Sanctum
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate

# Start server
php artisan serve
```

### 3. **Access the API**
- **Base URL** : http://localhost:8000/api
- **Documentation** : http://localhost:8000/api/documentation

## 📚 Laravel Concepts Used

### **API Controllers**
```php
class PostController extends Controller
{
    public function index(): PostCollection
    {
        $posts = Post::with(['user', 'category'])
                    ->latest()
                    ->paginate(15);
        
        return new PostCollection($posts);
    }

    public function store(StorePostRequest $request): PostResource
    {
        $post = Post::create($request->validated());
        
        return new PostResource($post->load(['user', 'category']));
    }

    public function show(Post $post): PostResource
    {
        return new PostResource($post->load(['user', 'category', 'comments']));
    }
}
```

### **API Resources**
```php
class PostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'status' => $this->status,
            'published_at' => $this->published_at?->toISOString(),
            'user' => new UserResource($this->whenLoaded('user')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'comments_count' => $this->comments_count,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'links' => [
                'self' => route('api.posts.show', $this->id),
                'comments' => route('api.posts.comments.index', $this->id),
            ],
        ];
    }
}
```

### **Form Requests**
```php
class StorePostRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:100',
            'category_id' => 'required|exists:categories,id',
            'status' => 'required|in:draft,published',
            'published_at' => 'nullable|date|after:now',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The post title is required.',
            'content.min' => 'The post content must be at least 100 characters.',
        ];
    }
}
```

### **API Routes**
```php
Route::prefix('v1')->group(function () {
    // Public routes
    Route::get('posts', [PostController::class, 'index']);
    Route::get('posts/{post}', [PostController::class, 'show']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('posts', [PostController::class, 'store']);
        Route::put('posts/{post}', [PostController::class, 'update']);
        Route::delete('posts/{post}', [PostController::class, 'destroy']);
    });
});
```

## 🎯 API Endpoints

### **Authentication**
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login user
- `POST /api/auth/logout` - Logout user
- `POST /api/auth/refresh` - Refresh token

### **Posts**
- `GET /api/posts` - List all posts
- `POST /api/posts` - Create new post
- `GET /api/posts/{id}` - Get specific post
- `PUT /api/posts/{id}` - Update post
- `DELETE /api/posts/{id}` - Delete post

### **Comments**
- `GET /api/posts/{id}/comments` - Get post comments
- `POST /api/posts/{id}/comments` - Add comment
- `PUT /api/comments/{id}` - Update comment
- `DELETE /api/comments/{id}` - Delete comment

## 🧪 Testing the API

### **Using cURL**
```bash
# Register a user
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'

# Create a post (with token)
curl -X POST http://localhost:8000/api/posts \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "title": "My First Post",
    "content": "This is the content of my first post.",
    "category_id": 1,
    "status": "published"
  }'
```

### **Using Postman**
1. Import the API collection
2. Set the base URL: `http://localhost:8000/api`
3. Use the authentication endpoints to get a token
4. Add the token to the Authorization header
5. Test all endpoints

## 🔧 Customization

### **Add new endpoints**
1. Create new API controllers
2. Define API resources for data transformation
3. Add validation rules
4. Update routes
5. Write tests

### **Customize responses**
```php
// Custom API Resource
class CustomPostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'data' => [
                'id' => $this->id,
                'title' => $this->title,
                // ... other fields
            ],
            'meta' => [
                'version' => '1.0',
                'timestamp' => now()->toISOString(),
            ],
        ];
    }
}
```

### **Add middleware**
```php
// Custom API middleware
class ApiResponseMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        return response()->json([
            'success' => true,
            'data' => $response->getData(),
            'message' => 'Request successful',
        ]);
    }
}
```

## 🧪 Testing

### **Unit Tests**
```bash
# Run tests
php artisan test

# API tests
php artisan test --filter=ApiTest
```

### **Feature Tests**
```php
class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register_user()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'user' => ['id', 'name', 'email'],
                        'token'
                    ]
                ]);
    }

    public function test_can_create_post()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/posts', [
            'title' => 'Test Post',
            'content' => 'Test content',
            'category_id' => 1,
            'status' => 'published',
        ]);

        $response->assertStatus(201);
    }
}
```

## 🚀 Deployment

### **Heroku**
```bash
# Create Heroku app
heroku create rest-api-laravel

# Configure environment variables
heroku config:set APP_KEY=base64:your-key
heroku config:set DB_CONNECTION=postgresql
heroku config:set SANCTUM_STATEFUL_DOMAINS=your-domain.com

# Deploy
git push heroku main

# Run migrations
heroku run php artisan migrate
```

### **VPS/Dedicated Server**
```bash
# Clone on server
git clone <repository-url>
cd rest-api

# Install dependencies
composer install --optimize-autoloader --no-dev

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Optimize for production
php artisan config:cache
php artisan route:cache
```

## 📝 API Documentation

### **Response Format**
```json
{
    "data": {
        "id": 1,
        "title": "Post Title",
        "content": "Post content...",
        "created_at": "2024-01-01T00:00:00.000000Z"
    },
    "meta": {
        "current_page": 1,
        "total": 10,
        "per_page": 15
    },
    "links": {
        "first": "http://localhost:8000/api/posts?page=1",
        "last": "http://localhost:8000/api/posts?page=1",
        "prev": null,
        "next": null
    }
}
```

### **Error Response**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "title": ["The title field is required."],
        "content": ["The content must be at least 100 characters."]
    }
}
```

## 🔍 Rate Limiting

The API includes rate limiting to prevent abuse:

```php
// In RouteServiceProvider
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Protected routes with 60 requests per minute
});
```

## 🤝 Contributing

1. Fork the project
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License. See the `LICENSE` file for details.

## 🆘 Support

For any questions or issues:
1. Check this README
2. Consult Laravel documentation
3. Open an issue on GitHub

---

**REST API** - Complete Laravel REST API with authentication and documentation 🔌
