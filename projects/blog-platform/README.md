# Blog Platform - Laravel

A complete blog platform built with Laravel, featuring authentication, content management, and user interactions.

## 🚀 Features

### ✅ **Content Management**
- **Complete CRUD** : Create, Read, Update, Delete articles
- **Rich text editor** : WYSIWYG content creation
- **Image uploads** : Featured images and galleries
- **Categories and tags** : Content organization system

### ✅ **User Management**
- **Authentication** : Laravel Breeze integration
- **User roles** : Admin and author permissions
- **User profiles** : Personal information management
- **Registration/Login** : Secure user authentication

### ✅ **Content Features**
- **SEO-friendly URLs** : Automatic slug generation
- **Comments system** : User interactions
- **Moderation** : Comment approval system
- **Search functionality** : Full-text search
- **Pagination** : Efficient content browsing

### ✅ **Advanced Features**
- **Admin panel** : Content management interface
- **Statistics** : View counts and analytics
- **Featured posts** : Highlight important content
- **Draft system** : Save work in progress
- **Scheduled publishing** : Future post scheduling

## 📁 Project Structure

```
blog-platform/
├── app/
│   ├── Http/Controllers/
│   │   ├── PostController.php      # Post management
│   │   └── CommentController.php   # Comment handling
│   ├── Models/
│   │   ├── Post.php               # Post model
│   │   ├── User.php               # User model
│   │   └── Comment.php            # Comment model
│   └── Policies/
│       └── PostPolicy.php         # Authorization policies
├── database/
│   └── migrations/
│       ├── create_posts_table.php
│       ├── create_comments_table.php
│       └── create_categories_table.php
├── resources/
│   └── views/
│       ├── posts/
│       │   ├── index.blade.php    # Post listing
│       │   ├── show.blade.php     # Single post view
│       │   ├── create.blade.php   # Create form
│       │   └── edit.blade.php     # Edit form
│       ├── admin/
│       │   └── dashboard.blade.php # Admin panel
│       └── layouts/
│           └── app.blade.php      # Main layout
├── routes/
│   └── web.php                   # Application routes
└── README.md                     # This file
```

## 🛠️ Installation

### 1. **Prerequisites**
- PHP 8.1+
- Composer
- MySQL/PostgreSQL/SQLite
- Laravel 10+
- Node.js (for frontend assets)

### 2. **Installation**
```bash
# Clone the project
git clone <repository-url>
cd blog-platform

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
DB_DATABASE=blog_platform
DB_USERNAME=root
DB_PASSWORD=

# Run migrations
php artisan migrate

# Install Laravel Breeze
composer require laravel/breeze --dev
php artisan breeze:install blade

# Install frontend assets
npm install
npm run build

# Create storage link
php artisan storage:link

# Start server
php artisan serve
```

### 3. **Access the application**
- **URL** : http://localhost:8000
- **Admin** : http://localhost:8000/admin (after login)

## 📚 Laravel Concepts Used

### **Eloquent Models with Relationships**
```php
class Post extends Model
{
    protected $fillable = [
        'title', 'slug', 'content', 'featured_image', 
        'published_at', 'status', 'user_id', 'category_id'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->where('published_at', '<=', now());
    }
}
```

### **Resource Controllers**
```php
class PostController extends Controller
{
    public function index(): View
    {
        $posts = Post::with(['user', 'category'])
                    ->published()
                    ->latest('published_at')
                    ->paginate(12);
        
        return view('posts.index', compact('posts'));
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')
                                                 ->store('posts', 'public');
        }

        $post = auth()->user()->posts()->create($validated);
        
        return redirect()->route('posts.show', $post)
                        ->with('success', 'Post created successfully!');
    }
}
```

### **Form Requests for Validation**
```php
class StorePostRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:100',
            'category_id' => 'required|exists:categories,id',
            'featured_image' => 'nullable|image|max:2048',
            'status' => 'required|in:draft,published',
            'published_at' => 'nullable|date|after:now',
        ];
    }
}
```

### **Authorization Policies**
```php
class PostPolicy
{
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->isAdmin();
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->isAdmin();
    }
}
```

## 🎯 Detailed Features

### **Content Management**
- **Rich text editor** : CKEditor or TinyMCE integration
- **Image management** : Upload, resize, and optimize images
- **Draft system** : Save work without publishing
- **Scheduled posts** : Publish at specific dates
- **Featured posts** : Highlight important content

### **User System**
- **Role-based access** : Admin, Author, Reader roles
- **User profiles** : Personal information and avatar
- **Activity tracking** : User engagement metrics
- **Email verification** : Secure account creation

### **Comment System**
- **Nested comments** : Reply to specific comments
- **Moderation** : Approve/reject comments
- **Spam protection** : Akismet integration
- **Email notifications** : New comment alerts

### **SEO and Performance**
- **SEO optimization** : Meta tags and structured data
- **Sitemap generation** : Automatic XML sitemaps
- **RSS feeds** : Content syndication
- **Caching** : Redis/Memcached integration

## 🔧 Customization

### **Add new content types**
1. Create new models and migrations
2. Add relationships to existing models
3. Create controllers and views
4. Update routes and policies

### **Custom themes**
1. Create new Blade layouts
2. Customize CSS and JavaScript
3. Add theme configuration options
4. Implement theme switching

### **Extend functionality**
- **Newsletter system** : Email subscriptions
- **Social sharing** : Share buttons
- **Related posts** : Content recommendations
- **Analytics** : Google Analytics integration

## 🧪 Testing

### **Unit Tests**
```bash
# Run tests
php artisan test

# Specific tests
php artisan test --filter=PostTest
```

### **Feature Tests**
```php
class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_post()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/posts', [
            'title' => 'Test Post',
            'content' => 'This is a test post content.',
            'category_id' => 1,
            'status' => 'published'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('posts', ['title' => 'Test Post']);
    }
}
```

## 🚀 Deployment

### **Heroku**
```bash
# Create Heroku app
heroku create blog-platform-laravel

# Configure environment variables
heroku config:set APP_KEY=base64:your-key
heroku config:set DB_CONNECTION=postgresql
heroku config:set FILESYSTEM_DISK=s3

# Deploy
git push heroku main

# Run migrations
heroku run php artisan migrate
```

### **VPS/Dedicated Server**
```bash
# Clone on server
git clone <repository-url>
cd blog-platform

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 📝 API Endpoints

The platform can be extended with a REST API:

```php
// routes/api.php
Route::apiResource('posts', PostApiController::class);
Route::apiResource('comments', CommentApiController::class);
Route::post('posts/{post}/like', [PostApiController::class, 'like']);
```

### **Available endpoints**
- `GET /api/posts` - List posts
- `POST /api/posts` - Create post
- `GET /api/posts/{id}` - Get post details
- `PUT /api/posts/{id}` - Update post
- `DELETE /api/posts/{id}` - Delete post
- `POST /api/posts/{id}/comments` - Add comment

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

**Blog Platform** - Complete Laravel blog platform with advanced features 📝
