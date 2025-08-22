# Chapter 6: Eloquent ORM

## What is Eloquent?

Eloquent is Laravel's Object-Relational Mapping (ORM) system. It provides an elegant, simple ActiveRecord implementation for working with your database. Each database table has a corresponding "Model" which is used to interact with that table.

## Creating Models

### Using Artisan Command

```bash
# Basic model
php artisan make:model User

# Model with migration
php artisan make:model Post -m

# Model with migration and factory
php artisan make:model Comment -mf

# Model with migration, factory, and seeder
php artisan make:model Category -mfs

# Model with controller
php artisan make:model Product -c

# Model with resource controller
php artisan make:model Order -cr
```

### Basic Model Structure

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
```

## Model Configuration

### Table Name

By default, Eloquent assumes the table name is the plural form of the model name:

```php
class User extends Model
{
    // Table: users
}

class Post extends Model
{
    // Table: posts
}

// Custom table name
class Post extends Model
{
    protected $table = 'blog_posts';
}
```

### Primary Key

```php
class User extends Model
{
    // Default: 'id'
    
    // Custom primary key
    protected $primaryKey = 'user_id';
    
    // Disable auto-incrementing
    public $incrementing = false;
    
    // Custom key type
    protected $keyType = 'string';
}
```

### Timestamps

```php
class User extends Model
{
    // Default: true
    
    // Disable timestamps
    public $timestamps = false;
    
    // Custom timestamp column names
    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';
}
```

## Mass Assignment

### Fillable Attributes

```php
class User extends Model
{
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
}
```

### Guarded Attributes

```php
class User extends Model
{
    // Guard all attributes except specified ones
    protected $guarded = ['id'];
    
    // Or guard specific attributes
    protected $guarded = ['id', 'admin'];
}
```

### Assignment Methods

```php
// Using fillable/guarded
$user = new User();
$user->fill([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

// Force assignment (bypasses fillable/guarded)
$user->forceFill([
    'admin' => true,
]);
```

## Attribute Casting

### Basic Casting

```php
class User extends Model
{
    protected $casts = [
        'is_active' => 'boolean',
        'age' => 'integer',
        'height' => 'float',
        'settings' => 'array',
        'metadata' => 'object',
        'birth_date' => 'date',
        'last_login' => 'datetime',
        'email_verified_at' => 'datetime',
    ];
}
```

### Custom Casting

```php
class User extends Model
{
    protected $casts = [
        'settings' => 'array',
        'preferences' => 'object',
        'birth_date' => 'date:Y-m-d',
        'last_login' => 'datetime:Y-m-d H:i:s',
    ];
}
```

### Value Object Casting

```php
class User extends Model
{
    protected $casts = [
        'address' => Address::class,
        'money' => Money::class,
    ];
}
```

## Accessors and Mutators

### Accessors

```php
class User extends Model
{
    // Get full name attribute
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
    
    // Get formatted email attribute
    public function getFormattedEmailAttribute()
    {
        return strtolower($this->email);
    }
    
    // Get age attribute
    public function getAgeAttribute()
    {
        return $this->birth_date->age;
    }
}
```

### Mutators

```php
class User extends Model
{
    // Set name attribute
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = strtolower($value);
    }
    
    // Set email attribute
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }
    
    // Set password attribute
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }
}
```

### Attribute Casting with Accessors/Mutators

```php
class User extends Model
{
    protected $casts = [
        'settings' => 'array',
    ];
    
    public function getSettingsAttribute($value)
    {
        $settings = json_decode($value, true);
        return array_merge([
            'theme' => 'light',
            'notifications' => true,
        ], $settings ?? []);
    }
    
    public function setSettingsAttribute($value)
    {
        $this->attributes['settings'] = json_encode($value);
    }
}
```

## Eloquent Relationships

### One-to-One

```php
// User has one Profile
class User extends Model
{
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
}

// Profile belongs to User
class Profile extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### One-to-Many

```php
// User has many Posts
class User extends Model
{
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

// Post belongs to User
class Post extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### Many-to-Many

```php
// User has many Roles
class User extends Model
{
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}

// Role has many Users
class Role extends Model
{
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
```

### Has Many Through

```php
// Country has many Posts through Users
class Country extends Model
{
    public function posts()
    {
        return $this->hasManyThrough(Post::class, User::class);
    }
}
```

### Polymorphic Relationships

```php
// Post has many Comments
class Post extends Model
{
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

// Video has many Comments
class Video extends Model
{
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

// Comment belongs to Post or Video
class Comment extends Model
{
    public function commentable()
    {
        return $this->morphTo();
    }
}
```

## Querying with Eloquent

### Basic Queries

```php
// Get all users
$users = User::all();

// Find by ID
$user = User::find(1);
$user = User::findOrFail(1);

// Find by column
$user = User::where('email', 'john@example.com')->first();
$user = User::where('email', 'john@example.com')->firstOrFail();

// Get first user
$user = User::first();

// Get last user
$user = User::latest()->first();
```

### Where Clauses

```php
// Basic where
$users = User::where('active', true)->get();

// Multiple conditions
$users = User::where('active', true)
    ->where('age', '>', 18)
    ->get();

// OR conditions
$users = User::where('active', true)
    ->orWhere('admin', true)
    ->get();

// Where in
$users = User::whereIn('id', [1, 2, 3])->get();

// Where between
$users = User::whereBetween('created_at', [
    '2023-01-01',
    '2023-12-31'
])->get();

// Where null
$users = User::whereNull('email_verified_at')->get();

// Where not null
$users = User::whereNotNull('email_verified_at')->get();
```

### Ordering and Limiting

```php
// Order by
$users = User::orderBy('name', 'asc')->get();
$users = User::orderBy('created_at', 'desc')->get();

// Latest/oldest
$users = User::latest()->get();
$users = User::oldest()->get();

// Limit
$users = User::limit(10)->get();

// Offset
$users = User::offset(10)->limit(10)->get();

// Skip and take
$users = User::skip(10)->take(10)->get();
```

### Aggregates

```php
// Count
$count = User::count();
$count = User::where('active', true)->count();

// Sum
$total = Order::sum('total');

// Average
$average = Order::avg('total');

// Min/Max
$min = Order::min('total');
$max = Order::max('total');
```

### Eager Loading

```php
// Load relationships
$users = User::with('posts')->get();

// Load multiple relationships
$users = User::with(['posts', 'profile'])->get();

// Load nested relationships
$users = User::with('posts.comments')->get();

// Conditional eager loading
$users = User::with(['posts' => function ($query) {
    $query->where('published', true);
}])->get();

// Lazy eager loading
$users = User::all();
$users->load('posts');
```

## CRUD Operations

### Creating Records

```php
// Create single record
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => Hash::make('password'),
]);

// Create multiple records
$users = User::createMany([
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com'],
]);

// Create or update
$user = User::updateOrCreate(
    ['email' => 'john@example.com'],
    ['name' => 'John Doe', 'active' => true]
);

// First or create
$user = User::firstOrCreate(
    ['email' => 'john@example.com'],
    ['name' => 'John Doe']
);
```

### Updating Records

```php
// Update single record
$user = User::find(1);
$user->update(['name' => 'Jane Doe']);

// Update multiple records
User::where('active', false)->update(['status' => 'inactive']);

// Update or create
$user = User::updateOrCreate(
    ['email' => 'john@example.com'],
    ['name' => 'John Doe']
);
```

### Deleting Records

```php
// Delete single record
$user = User::find(1);
$user->delete();

// Delete by ID
User::destroy(1);
User::destroy([1, 2, 3]);

// Delete with conditions
User::where('active', false)->delete();

// Soft delete (if model uses SoftDeletes trait)
$user->delete(); // Sets deleted_at timestamp
```

## Soft Deletes

### Using Soft Deletes

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];
}
```

### Soft Delete Operations

```php
// Soft delete
$user->delete();

// Force delete
$user->forceDelete();

// Restore soft deleted record
$user->restore();

// Get only soft deleted records
$deletedUsers = User::onlyTrashed()->get();

// Get all records including soft deleted
$allUsers = User::withTrashed()->get();
```

## Model Events

### Available Events

```php
class User extends Model
{
    protected static function booted()
    {
        // Creating, created
        static::creating(function ($user) {
            $user->slug = Str::slug($user->name);
        });
        
        // Updating, updated
        static::updating(function ($user) {
            $user->updated_count++;
        });
        
        // Deleting, deleted
        static::deleting(function ($user) {
            // Cleanup related data
        });
        
        // Saving, saved
        static::saving(function ($user) {
            // Before save
        });
        
        // Restoring, restored
        static::restoring(function ($user) {
            // Before restore
        });
    }
}
```

### Observers

```bash
php artisan make:observer UserObserver --model=User
```

```php
class UserObserver
{
    public function created(User $user)
    {
        // Send welcome email
    }
    
    public function updated(User $user)
    {
        // Log changes
    }
    
    public function deleted(User $user)
    {
        // Cleanup
    }
}
```

Register in `AppServiceProvider`:

```php
public function boot()
{
    User::observe(UserObserver::class);
}
```

## Scopes

### Local Scopes

```php
class User extends Model
{
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
    
    public function scopeOlderThan($query, $age)
    {
        return $query->where('age', '>', $age);
    }
    
    public function scopeByRole($query, $role)
    {
        return $query->whereHas('roles', function ($q) use ($role) {
            $q->where('name', $role);
        });
    }
}
```

### Global Scopes

```php
class ActiveScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->where('active', true);
    }
}

class User extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new ActiveScope);
    }
}
```

## Model Factories

### Creating Factories

```bash
php artisan make:factory UserFactory
```

### Defining Factories

```php
class UserFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }
    
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
    
    public function admin()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_admin' => true,
            ];
        });
    }
}
```

### Using Factories

```php
// Create single user
$user = User::factory()->create();

// Create user with specific attributes
$user = User::factory()->create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

// Create multiple users
$users = User::factory()->count(10)->create();

// Create user with relationships
$user = User::factory()
    ->has(Post::factory()->count(3))
    ->create();

// Create user with state
$user = User::factory()->unverified()->create();
$admin = User::factory()->admin()->create();
```

## Best Practices

### 1. Use Fillable/Guarded

```php
class User extends Model
{
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
    
    // Or use guarded
    protected $guarded = ['id', 'admin'];
}
```

### 2. Use Accessors and Mutators

```php
class User extends Model
{
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
    
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }
}
```

### 3. Use Relationships

```php
// Good
$user->posts;

// Bad
Post::where('user_id', $user->id)->get();
```

### 4. Use Eager Loading

```php
// Good
$users = User::with('posts')->get();

// Bad
$users = User::all();
foreach ($users as $user) {
    $user->posts; // N+1 problem
}
```

### 5. Use Scopes for Common Queries

```php
class User extends Model
{
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}

// Usage
$activeUsers = User::active()->get();
```

## Summary

In this chapter, we covered:

- ✅ Creating and configuring Eloquent models
- ✅ Mass assignment and attribute casting
- ✅ Accessors and mutators
- ✅ Eloquent relationships (one-to-one, one-to-many, many-to-many, etc.)
- ✅ Querying with Eloquent
- ✅ CRUD operations
- ✅ Soft deletes
- ✅ Model events and observers
- ✅ Local and global scopes
- ✅ Model factories
- ✅ Best practices for working with Eloquent

Eloquent provides a powerful and intuitive way to work with your database. In the next chapter, we'll explore database migrations for managing your database schema.
