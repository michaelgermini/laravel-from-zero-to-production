# Chapter 7: Database Migrations

## What are Migrations?

Migrations are like version control for your database. They allow you to define and modify your database schema in a structured and organized way. Each migration file represents a change to your database structure, and Laravel keeps track of which migrations have been run.

## Creating Migrations

### Using Artisan Command

```bash
# Basic migration
php artisan make:migration create_users_table

# Migration for existing table
php artisan make:migration add_email_to_users_table

# Migration with model
php artisan make:model User -m

# Migration with model and factory
php artisan make:model Post -mf

# Migration with model, factory, and seeder
php artisan make:model Category -mfs
```

### Migration File Structure

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

## Column Types

### Basic Column Types

```php
Schema::create('users', function (Blueprint $table) {
    // String columns
    $table->string('name');
    $table->string('email', 100); // With length
    $table->text('description');
    $table->longText('content');
    
    // Numeric columns
    $table->integer('age');
    $table->bigInteger('phone');
    $table->decimal('price', 8, 2); // 8 digits, 2 decimal places
    $table->float('rating', 3, 2);
    $table->double('amount', 10, 2);
    
    // Boolean columns
    $table->boolean('is_active');
    $table->boolean('is_admin')->default(false);
    
    // Date and time columns
    $table->date('birth_date');
    $table->dateTime('last_login');
    $table->timestamp('email_verified_at')->nullable();
    $table->time('start_time');
    $table->year('birth_year');
    
    // JSON columns
    $table->json('settings');
    $table->jsonb('metadata'); // PostgreSQL only
    
    // Binary columns
    $table->binary('avatar');
    
    // UUID columns
    $table->uuid('id')->primary();
    $table->uuidMorphs('taggable');
    
    // Enum columns
    $table->enum('status', ['active', 'inactive', 'pending']);
    $table->set('options', ['option1', 'option2', 'option3']);
});
```

### Column Modifiers

```php
Schema::create('users', function (Blueprint $table) {
    // Nullable columns
    $table->string('middle_name')->nullable();
    
    // Default values
    $table->boolean('is_active')->default(true);
    $table->string('role')->default('user');
    
    // Auto-incrementing
    $table->id(); // Same as $table->bigIncrements('id');
    $table->increments('id'); // Integer auto-increment
    $table->smallIncrements('id'); // Small integer auto-increment
    $table->mediumIncrements('id'); // Medium integer auto-increment
    
    // Unsigned columns
    $table->unsignedInteger('user_id');
    $table->unsignedBigInteger('post_id');
    
    // Comment
    $table->string('email')->comment('User email address');
    
    // After column
    $table->string('last_name')->after('first_name');
    
    // First column
    $table->string('priority')->first();
});
```

## Indexes

### Creating Indexes

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('email')->unique(); // Unique index
    $table->string('username')->unique();
    $table->string('name');
    $table->string('city');
    $table->integer('age');
    
    // Single column indexes
    $table->index('name');
    $table->index(['name', 'email']);
    
    // Named indexes
    $table->index(['name', 'email'], 'users_name_email_index');
    
    // Unique indexes
    $table->unique('email');
    $table->unique(['name', 'email']);
    
    // Sparse indexes (nullable columns)
    $table->string('phone')->nullable()->index();
    
    // Full-text indexes (MySQL)
    $table->text('content');
    $table->fullText('content');
    
    // Spatial indexes
    $table->point('location');
    $table->spatialIndex('location');
});
```

### Adding Indexes to Existing Tables

```php
Schema::table('users', function (Blueprint $table) {
    $table->index('email');
    $table->unique('username');
    $table->index(['name', 'email']);
});
```

### Dropping Indexes

```php
Schema::table('users', function (Blueprint $table) {
    $table->dropIndex(['email']);
    $table->dropUnique(['username']);
    $table->dropIndex('users_name_email_index');
});
```

## Foreign Keys

### Creating Foreign Keys

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content');
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('category_id');
    
    // Foreign key constraints
    $table->foreign('user_id')->references('id')->on('users');
    $table->foreign('category_id')->references('id')->on('categories');
    
    // With cascade options
    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->onDelete('cascade')
        ->onUpdate('cascade');
    
    // With custom constraint name
    $table->foreign('user_id', 'posts_user_id_foreign')
        ->references('id')
        ->on('users');
});
```

### Foreign Key Options

```php
// Cascade delete
$table->foreign('user_id')
    ->references('id')
    ->on('users')
    ->onDelete('cascade');

// Set null on delete
$table->foreign('user_id')
    ->references('id')
    ->on('users')
    ->onDelete('set null');

// Restrict delete
$table->foreign('user_id')
    ->references('id')
    ->on('users')
    ->onDelete('restrict');

// No action
$table->foreign('user_id')
    ->references('id')
    ->on('users')
    ->onDelete('no action');
```

### Dropping Foreign Keys

```php
Schema::table('posts', function (Blueprint $table) {
    $table->dropForeign(['user_id']);
    $table->dropForeign('posts_user_id_foreign');
});
```

## Common Migration Patterns

### Creating Tables

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->enum('role', ['user', 'admin', 'moderator'])->default('user');
    $table->boolean('is_active')->default(true);
    $table->json('settings')->nullable();
    $table->rememberToken();
    $table->timestamps();
    
    // Indexes
    $table->index(['name', 'email']);
    $table->index('created_at');
});
```

### Adding Columns

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('phone')->nullable()->after('email');
    $table->date('birth_date')->nullable()->after('phone');
    $table->text('bio')->nullable()->after('birth_date');
    $table->boolean('newsletter')->default(false)->after('bio');
});
```

### Modifying Columns

```php
Schema::table('users', function (Blueprint $table) {
    // Change column type
    $table->string('name', 100)->change();
    
    // Make column nullable
    $table->string('phone')->nullable()->change();
    
    // Add default value
    $table->boolean('is_active')->default(true)->change();
    
    // Rename column
    $table->renameColumn('old_name', 'new_name');
});
```

### Dropping Columns

```php
Schema::table('users', function (Blueprint $table) {
    $table->dropColumn('phone');
    $table->dropColumn(['phone', 'birth_date', 'bio']);
});
```

## Running Migrations

### Basic Commands

```bash
# Run all pending migrations
php artisan migrate

# Run migrations with output
php artisan migrate --verbose

# Run migrations in production
php artisan migrate --force

# Check migration status
php artisan migrate:status

# Rollback last batch
php artisan migrate:rollback

# Rollback specific number of steps
php artisan migrate:rollback --step=2

# Reset all migrations
php artisan migrate:reset

# Refresh migrations (reset + migrate)
php artisan migrate:refresh

# Refresh with seed
php artisan migrate:refresh --seed

# Fresh start (drop all tables + migrate)
php artisan migrate:fresh

# Fresh with seed
php artisan migrate:fresh --seed
```

### Migration Status

```bash
php artisan migrate:status
```

Output:
```
+------+------------------------------------------------+-------+
| Ran? | Migration                                       | Batch |
+------+------------------------------------------------+-------+
| Y    | 2014_10_12_000000_create_users_table           | 1     |
| Y    | 2014_10_12_100000_create_password_resets_table | 1     |
| N    | 2019_08_19_000000_create_failed_jobs_table     | -     |
+------+------------------------------------------------+-------+
```

## Migration Best Practices

### 1. Use Descriptive Names

```bash
# Good
php artisan make:migration create_users_table
php artisan make:migration add_email_verification_to_users_table
php artisan make:migration create_posts_table

# Bad
php artisan make:migration migration_1
php artisan make:migration update_table
```

### 2. Keep Migrations Small and Focused

```php
// Good - Single purpose migration
Schema::table('users', function (Blueprint $table) {
    $table->string('phone')->nullable();
});

// Bad - Multiple unrelated changes
Schema::table('users', function (Blueprint $table) {
    $table->string('phone')->nullable();
    $table->string('address')->nullable();
    $table->integer('age')->nullable();
    // ... many more changes
});
```

### 3. Always Include Down Method

```php
public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('phone');
    });
}
```

### 4. Use Proper Data Types

```php
// Good
$table->decimal('price', 8, 2); // For currency
$table->text('description'); // For long text
$table->json('settings'); // For structured data

// Bad
$table->string('price'); // Could lose precision
$table->string('description', 1000); // Limited length
$table->text('settings'); // Not structured
```

### 5. Add Indexes for Performance

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content');
    $table->unsignedBigInteger('user_id');
    $table->enum('status', ['draft', 'published', 'archived']);
    $table->timestamps();
    
    // Indexes for common queries
    $table->index('user_id');
    $table->index('status');
    $table->index('created_at');
    $table->index(['user_id', 'status']);
});
```

### 6. Use Foreign Keys for Data Integrity

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('category_id');
    
    $table->foreign('user_id')->references('id')->on('users');
    $table->foreign('category_id')->references('id')->on('categories');
});
```

## Advanced Migration Features

### Raw SQL

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    
    // Raw SQL for complex operations
    DB::statement('ALTER TABLE users ADD FULLTEXT(name)');
});
```

### Conditional Migrations

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    
    // Only add column if database supports it
    if (DB::connection()->getDriverName() === 'mysql') {
        $table->fullText('name');
    }
});
```

### Custom Migration Commands

```php
// In your migration
public function up(): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamps();
    });
    
    // Insert default data
    DB::table('users')->insert([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
```

## Migration Examples

### Users Table

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('username')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->enum('role', ['user', 'admin', 'moderator'])->default('user');
    $table->boolean('is_active')->default(true);
    $table->string('phone')->nullable();
    $table->date('birth_date')->nullable();
    $table->text('bio')->nullable();
    $table->json('settings')->nullable();
    $table->string('avatar')->nullable();
    $table->timestamp('last_login_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();
    
    // Indexes
    $table->index(['name', 'email']);
    $table->index('role');
    $table->index('is_active');
    $table->index('created_at');
});
```

### Posts Table

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('excerpt')->nullable();
    $table->longText('content');
    $table->string('featured_image')->nullable();
    $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
    $table->boolean('is_featured')->default(false);
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('category_id')->nullable();
    $table->json('meta')->nullable();
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    // Foreign keys
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
    
    // Indexes
    $table->index('slug');
    $table->index('status');
    $table->index('is_featured');
    $table->index('published_at');
    $table->index(['user_id', 'status']);
    $table->index(['category_id', 'status']);
});
```

### Comments Table

```php
Schema::create('comments', function (Blueprint $table) {
    $table->id();
    $table->text('content');
    $table->unsignedBigInteger('user_id');
    $table->morphs('commentable'); // Creates commentable_type and commentable_id
    $table->unsignedBigInteger('parent_id')->nullable(); // For nested comments
    $table->boolean('is_approved')->default(false);
    $table->timestamps();
    $table->softDeletes();
    
    // Foreign keys
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->foreign('parent_id')->references('id')->on('comments')->onDelete('cascade');
    
    // Indexes
    $table->index('commentable_type');
    $table->index('commentable_id');
    $table->index('parent_id');
    $table->index('is_approved');
    $table->index('created_at');
});
```

## Summary

In this chapter, we covered:

- ✅ Creating and running migrations
- ✅ Column types and modifiers
- ✅ Indexes and foreign keys
- ✅ Common migration patterns
- ✅ Migration commands and status
- ✅ Best practices for migrations
- ✅ Advanced migration features
- ✅ Real-world migration examples

Migrations provide a powerful way to manage your database schema. In the next chapter, we'll explore middleware for filtering HTTP requests.
