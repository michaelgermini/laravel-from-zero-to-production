# Chapter 4: Controllers

## What are Controllers?

Controllers are the heart of your Laravel application's logic. They handle incoming HTTP requests and return responses to the user. Controllers help you organize your application logic by grouping related request handling logic into a single class.

## Creating Controllers

### Using Artisan Command

The easiest way to create a controller is using the Artisan command:

```bash
# Basic controller
php artisan make:controller UserController

# Resource controller (with CRUD methods)
php artisan make:controller PostController --resource

# API resource controller
php artisan make:controller Api/UserController --api

# Controller with model
php artisan make:controller PostController --resource --model=Post
```

### Manual Creation

You can also create controllers manually by creating a PHP file in the `app/Http/Controllers` directory:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index');
    }
}
```

## Basic Controller Structure

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        User::create($validated);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully!');
    }
}
```

## Controller Methods

### Basic Methods

```php
public function index()
{
    // Display a list of resources
    return view('users.index');
}

public function show($id)
{
    // Display a specific resource
    $user = User::findOrFail($id);
    return view('users.show', compact('user'));
}

public function create()
{
    // Show form to create a new resource
    return view('users.create');
}

public function store(Request $request)
{
    // Store a new resource
    // Validation and storage logic
}

public function edit($id)
{
    // Show form to edit a resource
    $user = User::findOrFail($id);
    return view('users.edit', compact('user'));
}

public function update(Request $request, $id)
{
    // Update an existing resource
    // Validation and update logic
}

public function destroy($id)
{
    // Delete a resource
    $user = User::findOrFail($id);
    $user->delete();
}
```

### Custom Methods

You can add custom methods to your controllers:

```php
public function profile()
{
    $user = auth()->user();
    return view('users.profile', compact('user'));
}

public function search(Request $request)
{
    $query = $request->get('q');
    $users = User::where('name', 'like', "%{$query}%")->get();
    return view('users.search', compact('users', 'query'));
}

public function export()
{
    $users = User::all();
    return response()->json($users);
}
```

## Route Model Binding

Laravel automatically injects model instances into your controller methods:

```php
// Route: Route::get('/users/{user}', [UserController::class, 'show']);

public function show(User $user)
{
    // Laravel automatically finds the User model by ID
    return view('users.show', compact('user'));
}
```

### Custom Route Model Binding

You can customize how models are resolved:

```php
// In RouteServiceProvider
public function boot()
{
    Route::model('user', User::class);
    
    // Or with custom logic
    Route::bind('user', function ($value) {
        return User::where('username', $value)->firstOrFail();
    });
}
```

## Request Handling

### Basic Request Handling

```php
public function store(Request $request)
{
    // Access request data
    $name = $request->input('name');
    $email = $request->email; // Shorthand
    
    // Check if field exists
    if ($request->has('newsletter')) {
        // Handle newsletter subscription
    }
    
    // Get all input
    $data = $request->all();
    
    // Get only specific fields
    $data = $request->only(['name', 'email']);
    
    // Get all except specific fields
    $data = $request->except(['password']);
}
```

### Form Request Validation

Create custom form request classes for validation:

```bash
php artisan make:request StoreUserRequest
```

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Or add authorization logic
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'The name field is required.',
            'email.unique' => 'This email is already taken.',
        ];
    }
}
```

Use in controller:

```php
public function store(StoreUserRequest $request)
{
    // Validation is automatically handled
    $validated = $request->validated();
    
    User::create($validated);
    
    return redirect()->route('users.index');
}
```

## Response Types

### View Responses

```php
public function index()
{
    $users = User::all();
    return view('users.index', compact('users'));
}

// With additional data
return view('users.show', [
    'user' => $user,
    'posts' => $user->posts,
    'title' => 'User Profile'
]);
```

### JSON Responses

```php
public function api()
{
    $users = User::all();
    return response()->json($users);
}

// With status code
return response()->json(['message' => 'User created'], 201);

// With headers
return response()->json($data, 200, [
    'Content-Type' => 'application/json',
    'X-Custom-Header' => 'value'
]);
```

### Redirect Responses

```php
// Simple redirect
return redirect('/users');

// Redirect to named route
return redirect()->route('users.index');

// Redirect with data
return redirect()->route('users.index')->with('success', 'User created!');

// Redirect back
return redirect()->back();

// Redirect with input
return redirect()->back()->withInput();
```

### File Responses

```php
public function download()
{
    $path = storage_path('app/public/file.pdf');
    return response()->download($path);
}

public function stream()
{
    $path = storage_path('app/public/file.pdf');
    return response()->file($path);
}
```

## Controller Organization

### Single Action Controllers

For simple actions, you can use single action controllers:

```bash
php artisan make:controller ShowUserProfile --invokable
```

```php
<?php

namespace App\Http\Controllers;

class ShowUserProfile extends Controller
{
    public function __invoke($id)
    {
        $user = User::findOrFail($id);
        return view('users.profile', compact('user'));
    }
}
```

Route:
```php
Route::get('/users/{id}/profile', ShowUserProfile::class);
```

### API Controllers

API controllers are optimized for API responses:

```bash
php artisan make:controller Api/UserController --api
```

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ]);

        $user = User::create($validated);
        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);
        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }
}
```

## Middleware in Controllers

### Applying Middleware

```php
class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin')->only(['destroy']);
        $this->middleware('verified')->except(['index', 'show']);
    }
}
```

### Conditional Middleware

```php
public function __construct()
{
    $this->middleware('auth')->only(['edit', 'update', 'destroy']);
    $this->middleware('admin')->when(function ($request) {
        return $request->is('admin/*');
    });
}
```

## Resource Controllers

Resource controllers provide all the CRUD operations:

```php
// Route definition
Route::resource('users', UserController::class);

// This creates the following routes:
// GET    /users              index   users.index
// GET    /users/create       create  users.create
// POST   /users              store   users.store
// GET    /users/{user}       show    users.show
// GET    /users/{user}/edit  edit    users.edit
// PUT    /users/{user}       update  users.update
// DELETE /users/{user}       destroy users.destroy
```

### Partial Resource Routes

```php
// Only specific methods
Route::resource('users', UserController::class)->only(['index', 'show']);

// All except specific methods
Route::resource('users', UserController::class)->except(['destroy']);

// Nested resources
Route::resource('users.posts', PostController::class);
```

## Best Practices

### 1. Keep Controllers Thin

Controllers should be thin and focused on handling HTTP requests. Move business logic to services or models.

```php
// Good
public function store(StoreUserRequest $request)
{
    $user = UserService::create($request->validated());
    return redirect()->route('users.show', $user);
}

// Bad
public function store(Request $request)
{
    // Too much business logic in controller
    $data = $request->all();
    $user = new User();
    $user->name = $data['name'];
    $user->email = $data['email'];
    $user->password = Hash::make($data['password']);
    $user->save();
    // ... more logic
}
```

### 2. Use Form Requests

Use form request classes for validation and authorization:

```php
public function store(StoreUserRequest $request)
{
    // Validation is handled automatically
    $user = User::create($request->validated());
    return redirect()->route('users.show', $user);
}
```

### 3. Use Route Model Binding

Let Laravel handle model resolution:

```php
// Good
public function show(User $user)
{
    return view('users.show', compact('user'));
}

// Bad
public function show($id)
{
    $user = User::findOrFail($id);
    return view('users.show', compact('user'));
}
```

### 4. Consistent Response Patterns

Use consistent response patterns throughout your application:

```php
public function store(StoreUserRequest $request)
{
    $user = User::create($request->validated());
    
    return redirect()->route('users.show', $user)
        ->with('success', 'User created successfully!');
}

public function update(UpdateUserRequest $request, User $user)
{
    $user->update($request->validated());
    
    return redirect()->route('users.show', $user)
        ->with('success', 'User updated successfully!');
}
```

### 5. Use Dependency Injection

Inject dependencies through the constructor:

```php
class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->create($request->validated());
        return redirect()->route('users.show', $user);
    }
}
```

## Summary

In this chapter, we covered:

- ✅ Creating controllers using Artisan commands
- ✅ Basic controller structure and methods
- ✅ Route model binding
- ✅ Request handling and validation
- ✅ Different response types
- ✅ Controller organization patterns
- ✅ Middleware in controllers
- ✅ Resource controllers
- ✅ Best practices for writing controllers

Controllers are essential for organizing your application logic and handling HTTP requests. In the next chapter, we'll explore Laravel's Blade templating engine for creating dynamic views.
