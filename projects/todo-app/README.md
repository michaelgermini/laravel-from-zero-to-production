# Todo Application - Laravel

A complete Todo application built with Laravel, demonstrating fundamental web development concepts.

## 🚀 Features

### ✅ **Todo Management**
- **Complete CRUD** : Create, Read, Update, Delete todos
- **Status** : Mark as completed/pending
- **Priorities** : Low, Medium, High
- **Due dates** : Deadline management with alerts

### ✅ **User Interface**
- **Responsive design** with Bootstrap 5
- **Intuitive navigation** with filters
- **Real-time statistics** (Total, Completed, Pending, Overdue)
- **Font Awesome icons** for better UX

### ✅ **Advanced Features**
- **Filtering** : By status (All, Pending, Completed, Overdue)
- **Pagination** : Smooth navigation between pages
- **Validation** : Server-side validation with error messages
- **Eloquent Scopes** : Optimized queries for filters

## 📁 Project Structure

```
todo-app/
├── app/
│   ├── Http/Controllers/
│   │   └── TodoController.php      # Main controller
│   └── Models/
│       └── Todo.php               # Eloquent model
├── database/
│   └── migrations/
│       └── create_todos_table.php # Database migration
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php      # Main layout
│       └── todos/
│           ├── index.blade.php    # Todo list
│           └── create.blade.php   # Creation form
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

### 2. **Installation**
```bash
# Clone the project
git clone <repository-url>
cd todo-app

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
DB_DATABASE=todo_app
DB_USERNAME=root
DB_PASSWORD=

# Run migrations
php artisan migrate

# Start server
php artisan serve
```

### 3. **Access the application**
- **URL** : http://localhost:8000
- **Interface** : Complete web interface with navigation

## 📚 Laravel Concepts Used

### **Eloquent Model**
```php
class Todo extends Model
{
    protected $fillable = [
        'title', 'description', 'completed', 'due_date', 'priority'
    ];

    // Scopes for filtering
    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    public function scopePending($query)
    {
        return $query->where('completed', false);
    }
}
```

### **Resource Controller**
```php
class TodoController extends Controller
{
    public function index(): View
    {
        $todos = Todo::latest()->paginate(10);
        return view('todos.index', compact('todos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high',
        ]);

        Todo::create($validated);
        return redirect()->route('todos.index');
    }
}
```

### **Resource Routes**
```php
Route::resource('todos', TodoController::class);
Route::patch('/todos/{todo}/toggle', [TodoController::class, 'toggle']);
```

### **Blade Views**
```blade
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-tasks"></i> Todo List</h2>
        </div>
        <div class="card-body">
            @foreach($todos as $todo)
                <div class="todo-item">
                    <h5>{{ $todo->title }}</h5>
                    <span class="badge bg-{{ $todo->priority_color }}">
                        {{ ucfirst($todo->priority) }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
```

## 🎯 Detailed Features

### **Todo Management**
- **Creation** : Form with validation
- **Editing** : Modify details
- **Deletion** : Confirmation before deletion
- **Toggle** : Switch completed/pending status

### **Filtering and Sorting**
- **By status** : All, Pending, Completed, Overdue
- **By priority** : Low, Medium, High
- **Sorting** : By creation date (newest first)

### **Statistics**
- **Real-time counters** : Total, Completed, Pending, Overdue
- **Colored cards** : Statistics visualization
- **Auto-update** : After each action

### **User Interface**
- **Responsive design** : Mobile and desktop compatible
- **Intuitive navigation** : Menu with icons
- **Alerts** : Success/error messages with auto-close
- **Confirmation** : Confirmation dialogs for destructive actions

## 🔧 Customization

### **Add new fields**
1. Modify the migration `create_todos_table.php`
2. Add the field to the model's `$fillable`
3. Update views and controller
4. Run `php artisan migrate:fresh`

### **Modify styling**
Edit the file `resources/views/layouts/app.blade.php` :
```css
<style>
    .card {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        border-radius: 0.5rem;
    }
    
    .todo-item {
        border-left: 4px solid #007bff;
        padding-left: 1rem;
    }
</style>
```

### **Add new features**
- **Categories** : Group todos by category
- **Tags** : Tag system for organization
- **Sub-todos** : Nested todos
- **Notifications** : Email alerts for deadlines

## 🧪 Testing

### **Unit Tests**
```bash
# Run tests
php artisan test

# Specific tests
php artisan test --filter=TodoTest
```

### **Feature Tests**
```php
class TodoTest extends TestCase
{
    public function test_can_create_todo()
    {
        $response = $this->post('/todos', [
            'title' => 'Test Todo',
            'priority' => 'medium'
        ]);

        $response->assertRedirect('/todos');
        $this->assertDatabaseHas('todos', ['title' => 'Test Todo']);
    }
}
```

## 🚀 Deployment

### **Heroku**
```bash
# Create Heroku app
heroku create todo-app-laravel

# Configure environment variables
heroku config:set APP_KEY=base64:your-key
heroku config:set DB_CONNECTION=postgresql

# Deploy
git push heroku main

# Run migrations
heroku run php artisan migrate
```

### **VPS/Dedicated Server**
```bash
# Clone the project
git clone <repository-url>
cd todo-app

# Install dependencies
composer install --optimize-autoloader --no-dev

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Configure web server (Nginx/Apache)
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 📝 API Endpoints

The application can be extended with a REST API:

```php
// routes/api.php
Route::apiResource('todos', TodoApiController::class);
Route::patch('todos/{todo}/toggle', [TodoApiController::class, 'toggle']);
```

### **Available endpoints**
- `GET /api/todos` - List todos
- `POST /api/todos` - Create a todo
- `GET /api/todos/{id}` - Todo details
- `PUT /api/todos/{id}` - Update a todo
- `DELETE /api/todos/{id}` - Delete a todo
- `PATCH /api/todos/{id}/toggle` - Toggle status

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

**Todo Application** - Complete Laravel application for task management 🎯
