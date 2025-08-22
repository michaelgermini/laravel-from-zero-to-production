# 📝 Tutorial: Todo Application with Laravel

This tutorial guides you through the installation, configuration, and use of the Todo application built with Laravel.

## 🎯 Tutorial Objectives

By the end of this tutorial, you will know how to:
- Install and configure the Todo application
- Understand the Laravel architecture used
- Test all features
- Customize the application according to your needs
- Deploy the application to production

## 🛠️ Prerequisites

Before starting, make sure you have:
- **PHP 8.1+** installed
- **Composer** installed
- **MySQL/PostgreSQL/SQLite** configured
- **Git** installed
- **Node.js** (optional, for assets)

## 📋 Step 1: Installation

### 1.1 Clone the project
```bash
# Navigate to the project folder
cd projects/todo-app

# Verify you're in the right folder
ls -la
```

### 1.2 Install dependencies
```bash
# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 1.3 Configure database
Edit the `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=todo_app
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 1.4 Create database
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE todo_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Or with SQLite (simpler for testing)
# DB_CONNECTION=sqlite
# DB_DATABASE=/path/to/database.sqlite
```

### 1.5 Run migrations
```bash
# Run migrations
php artisan migrate

# Verify tables are created
php artisan migrate:status
```

### 1.6 Create symbolic link for storage
```bash
php artisan storage:link
```

## 🚀 Step 2: Start the application

### 2.1 Start development server
```bash
php artisan serve
```

### 2.2 Access the application
Open your browser and go to: `http://localhost:8000`

You should see the Todo application homepage!

## 🧪 Step 3: Test features

### 3.1 Create a first todo
1. Click on "New Todo"
2. Fill out the form:
   - **Title**: "Learn Laravel"
   - **Description**: "Study Laravel basic concepts"
   - **Due date**: Tomorrow
   - **Priority**: High
3. Click "Create"

### 3.2 Test filters
1. Create several todos with different statuses
2. Test the filters:
   - **All**: Shows all todos
   - **Pending**: Shows only uncompleted todos
   - **Completed**: Shows only completed todos
   - **Overdue**: Shows overdue todos

### 3.3 Test actions
1. **Mark as completed**: Click the checkbox
2. **Edit**: Click the edit icon
3. **Delete**: Click the delete icon

### 3.4 Test search
1. Use the search bar to find todos
2. Test with different keywords

## 📚 Step 4: Understand the architecture

### 4.1 File structure
```
todo-app/
├── app/
│   ├── Http/Controllers/
│   │   └── TodoController.php      # Business logic
│   └── Models/
│       └── Todo.php               # Eloquent model
├── database/
│   └── migrations/
│       └── xxxx_create_todos_table.php
├── resources/
│   └── views/
│       ├── todos/
│       │   ├── index.blade.php    # Todo list
│       │   ├── create.blade.php   # Creation form
│       │   └── edit.blade.php     # Edit form
│       └── layouts/
│           └── app.blade.php      # Main layout
└── routes/
    └── web.php                    # Route definitions
```

### 4.2 Laravel concepts used

#### **Eloquent Model (Todo.php)**
```php
class Todo extends Model
{
    protected $fillable = [
        'title', 'description', 'completed', 'due_date', 'priority'
    ];

    // Scopes for filtering
    public function scopeCompleted($query) {
        return $query->where('completed', true);
    }

    public function scopePending($query) {
        return $query->where('completed', false);
    }

    // Accessors
    public function getStatusColorAttribute() {
        return $this->completed ? 'success' : 'warning';
    }
}
```

#### **Controller (TodoController.php)**
```php
class TodoController extends Controller
{
    public function index(Request $request)
    {
        $query = Todo::query();

        // Filtering
        if ($request->has('status')) {
            switch ($request->status) {
                case 'completed':
                    $query->completed();
                    break;
                case 'pending':
                    $query->pending();
                    break;
            }
        }

        $todos = $query->orderBy('created_at', 'desc')->paginate(10);
        return view('todos.index', compact('todos'));
    }
}
```

#### **Routes (web.php)**
```php
Route::get('/', function () {
    return redirect()->route('todos.index');
});

Route::resource('todos', TodoController::class);
Route::patch('todos/{todo}/toggle', [TodoController::class, 'toggle'])->name('todos.toggle');
```

## 🔧 Step 5: Customization

### 5.1 Add new fields
1. **Modify migration**:
```bash
php artisan make:migration add_category_to_todos_table
```

2. **Edit migration**:
```php
public function up()
{
    Schema::table('todos', function (Blueprint $table) {
        $table->string('category')->nullable();
        $table->integer('estimated_hours')->nullable();
    });
}
```

3. **Update model**:
```php
protected $fillable = [
    'title', 'description', 'completed', 'due_date', 
    'priority', 'category', 'estimated_hours'
];
```

4. **Update views**:
Add new fields in forms.

### 5.2 Add new features

#### **Category system**
```php
// Create Category model
php artisan make:model Category -m

// Add relationship in Todo.php
public function category()
{
    return $this->belongsTo(Category::class);
}
```

#### **Tag system**
```php
// Create Tag model
php artisan make:model Tag -m

// Create pivot table
php artisan make:migration create_todo_tag_table
```

### 5.3 Customize design
1. **Modify CSS**: Edit `resources/css/app.css`
2. **Add icons**: Use Font Awesome
3. **Change colors**: Modify Bootstrap classes

## 🚀 Step 6: Deployment

### 6.1 Prepare for production
```bash
# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache
```

### 6.2 Deploy to Heroku
```bash
# Create Heroku app
heroku create todo-app-laravel

# Configure environment variables
heroku config:set APP_KEY=base64:your-key
heroku config:set DB_CONNECTION=postgresql
heroku config:set DB_DATABASE=your-database-url

# Deploy
git push heroku main

# Run migrations
heroku run php artisan migrate
```

### 6.3 Deploy to VPS
```bash
# Clone on server
git clone <repository-url>
cd todo-app

# Install dependencies
composer install --optimize-autoloader --no-dev

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Configure web server (Apache/Nginx)
```

## 🧪 Step 7: Testing

### 7.1 Unit tests
```bash
# Run tests
php artisan test

# Specific tests
php artisan test --filter=TodoTest
```

### 7.2 Feature tests
```php
class TodoTest extends TestCase
{
    public function test_can_create_todo()
    {
        $response = $this->post('/todos', [
            'title' => 'Test Todo',
            'description' => 'Test Description',
            'priority' => 'high',
            'due_date' => now()->addDay(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('todos', ['title' => 'Test Todo']);
    }

    public function test_can_toggle_todo()
    {
        $todo = Todo::factory()->create(['completed' => false]);

        $response = $this->patch("/todos/{$todo->id}/toggle");

        $response->assertRedirect();
        $this->assertTrue($todo->fresh()->completed);
    }
}
```

## 🔍 Step 8: Troubleshooting

### 8.1 Common problems

#### **Database error**
```bash
# Check connection
php artisan tinker
DB::connection()->getPdo();

# Reset migrations
php artisan migrate:fresh
```

#### **Permission error**
```bash
# Give permissions to storage folder
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

#### **Cache error**
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 8.2 Logs and debugging
```bash
# View logs
tail -f storage/logs/laravel.log

# Debug mode
APP_DEBUG=true in .env
```

## 📈 Step 9: Advanced improvements

### 9.1 Authentication
```bash
# Install Laravel Breeze
composer require laravel/breeze
php artisan breeze:install
```

### 9.2 REST API
```bash
# Create API controllers
php artisan make:controller Api/TodoController --api
```

### 9.3 Notifications
```bash
# Create notifications
php artisan make:notification TodoReminder
```

### 9.4 Jobs and Queues
```bash
# Create jobs
php artisan make:job ProcessTodoReminder
```

## 🎉 Conclusion

Congratulations! You now have:
- ✅ Installed and configured the Todo application
- ✅ Tested all features
- ✅ Understood the Laravel architecture
- ✅ Customized the application
- ✅ Deployed to production
- ✅ Added tests

### Next steps:
1. **Explore other projects**: Blog Platform, REST API, Shop App, Wizard Form
2. **Create your own applications** using these concepts
3. **Contribute** to the project by adding new features
4. **Share** your creations with the community

---

**Todo Application** - Your first complete Laravel application! 🎯
