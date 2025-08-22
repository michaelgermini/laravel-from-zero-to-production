# Chapter 5: Blade Templates

## What is Blade?

Blade is Laravel's powerful templating engine. It provides a clean, simple syntax for creating dynamic views with PHP. Blade templates are compiled into plain PHP code and cached for optimal performance.

## Basic Blade Syntax

### Echoing Data

```php
{{ $variable }}

{{ $user->name }}

{{ $array['key'] }}

{{ $object->method() }}
```

### Unescaped Data

```php
{!! $html !!}
```

**Warning**: Only use `{!! !!}` for trusted content to prevent XSS attacks.

### Comments

```php
{{-- This is a Blade comment --}}
{{-- 
    Multi-line
    comments
--}}
```

## Blade Directives

### Conditional Statements

#### @if, @elseif, @else

```php
@if($user->isAdmin())
    <h1>Welcome, Administrator!</h1>
@elseif($user->isModerator())
    <h1>Welcome, Moderator!</h1>
@else
    <h1>Welcome, User!</h1>
@endif
```

#### @unless

```php
@unless($user->isVerified())
    <p>Please verify your email address.</p>
@endunless
```

#### @isset and @empty

```php
@isset($user->profile)
    <p>Profile: {{ $user->profile->bio }}</p>
@endisset

@empty($posts)
    <p>No posts found.</p>
@endempty
```

### Loops

#### @foreach

```php
@foreach($users as $user)
    <div class="user">
        <h3>{{ $user->name }}</h3>
        <p>{{ $user->email }}</p>
    </div>
@endforeach
```

#### @for

```php
@for($i = 0; $i < 10; $i++)
    <p>Item {{ $i }}</p>
@endfor
```

#### @while

```php
@while($condition)
    <p>Looping...</p>
@endwhile
```

#### @forelse

```php
@forelse($posts as $post)
    <div class="post">
        <h2>{{ $post->title }}</h2>
        <p>{{ $post->excerpt }}</p>
    </div>
@empty
    <p>No posts available.</p>
@endforelse
```

### Loop Variables

```php
@foreach($users as $user)
    @if($loop->first)
        <p>First user: {{ $user->name }}</p>
    @endif
    
    @if($loop->last)
        <p>Last user: {{ $user->name }}</p>
    @endif
    
    <p>User {{ $loop->iteration }} of {{ $loop->count }}: {{ $user->name }}</p>
    
    @if($loop->even)
        <p>Even row</p>
    @endif
    
    @if($loop->odd)
        <p>Odd row</p>
    @endif
@endforeach
```

## Layouts and Sections

### Creating a Layout

```php
<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'My Laravel App')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">My App</a>
            <div class="navbar-nav">
                <a class="nav-link" href="{{ route('users.index') }}">Users</a>
                <a class="nav-link" href="{{ route('posts.index') }}">Posts</a>
            </div>
        </div>
    </nav>

    <main class="container mt-4">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-light mt-5 py-3">
        <div class="container text-center">
            <p>&copy; {{ date('Y') }} My Laravel App. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
```

### Extending a Layout

```php
<!-- resources/views/users/index.blade.php -->
@extends('layouts.app')

@section('title', 'Users - My Laravel App')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <h1>Users</h1>
            <a href="{{ route('users.create') }}" class="btn btn-primary mb-3">Create User</a>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->created_at->format('M d, Y') }}</td>
                                <td>
                                    <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-info">View</a>
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
```

### Stacks

Stacks allow you to push content to named stacks that can be rendered elsewhere in the layout:

```php
<!-- In your view -->
@push('styles')
    <link rel="stylesheet" href="/css/custom.css">
@endpush

@push('scripts')
    <script src="/js/custom.js"></script>
@endpush

<!-- Or prepend content -->
@prepend('scripts')
    <script src="/js/analytics.js"></script>
@endprepend
```

## Components

### Creating Components

```bash
php artisan make:component Alert
php artisan make:component UserCard --view
```

### Class-Based Components

```php
<!-- app/View/Components/Alert.php -->
<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Alert extends Component
{
    public $type;
    public $message;

    public function __construct($type = 'info', $message = '')
    {
        $this->type = $type;
        $this->message = $message;
    }

    public function render()
    {
        return view('components.alert');
    }

    public function isDismissible()
    {
        return in_array($this->type, ['success', 'warning', 'danger']);
    }
}
```

```php
<!-- resources/views/components/alert.blade.php -->
<div class="alert alert-{{ $type }} {{ $isDismissible() ? 'alert-dismissible fade show' : '' }}" role="alert">
    {{ $message }}
    {{ $slot }}
    
    @if($isDismissible())
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    @endif
</div>
```

### Anonymous Components

```php
<!-- resources/views/components/alert.blade.php -->
@props(['type' => 'info', 'dismissible' => false])

<div class="alert alert-{{ $type }} {{ $dismissible ? 'alert-dismissible fade show' : '' }}" role="alert">
    {{ $slot }}
    
    @if($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    @endif
</div>
```

### Using Components

```php
<!-- Class-based component -->
<x-alert type="success" message="User created successfully!" />

<!-- Anonymous component -->
<x-alert type="danger" dismissible>
    <strong>Error!</strong> Something went wrong.
</x-alert>

<!-- With slots -->
<x-card>
    <x-slot name="header">
        <h5 class="card-title">User Information</h5>
    </x-slot>
    
    <p class="card-text">This is the card content.</p>
    
    <x-slot name="footer">
        <button class="btn btn-primary">Save</button>
    </x-slot>
</x-card>
```

## Forms

### CSRF Protection

```php
<form method="POST" action="{{ route('users.store') }}">
    @csrf
    <!-- form fields -->
</form>
```

### Method Spoofing

```php
<form method="POST" action="{{ route('users.update', $user) }}">
    @csrf
    @method('PUT')
    <!-- form fields -->
</form>
```

### Form Components

```php
<!-- resources/views/components/form/input.blade.php -->
@props(['name', 'label', 'type' => 'text', 'value' => '', 'required' => false])

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    <input 
        type="{{ $type }}" 
        class="form-control @error($name) is-invalid @enderror" 
        id="{{ $name }}" 
        name="{{ $name }}" 
        value="{{ old($name, $value) }}"
        {{ $required ? 'required' : '' }}
    >
    @error($name)
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
</div>
```

### Using Form Components

```php
<!-- resources/views/users/create.blade.php -->
@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Create User</h1>
            
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                
                <x-form.input name="name" label="Name" required />
                <x-form.input name="email" label="Email" type="email" required />
                <x-form.input name="password" label="Password" type="password" required />
                <x-form.input name="password_confirmation" label="Confirm Password" type="password" required />
                
                <button type="submit" class="btn btn-primary">Create User</button>
                <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection
```

## Includes and Partials

### @include

```php
@include('partials.header')

@include('partials.footer', ['year' => date('Y')])

@includeIf('partials.custom', ['data' => $data])

@includeWhen($user->isAdmin(), 'partials.admin-panel')
```

### @includeFirst

```php
@includeFirst(['partials.custom', 'partials.default'])
```

## Custom Directives

### Creating Custom Directives

```php
<!-- app/Providers/AppServiceProvider.php -->
use Illuminate\Support\Facades\Blade;

public function boot()
{
    Blade::directive('datetime', function ($expression) {
        return "<?php echo ($expression)->format('M d, Y H:i:s'); ?>";
    });

    Blade::directive('money', function ($expression) {
        return "<?php echo '$' . number_format($expression, 2); ?>";
    });

    Blade::if('admin', function () {
        return auth()->check() && auth()->user()->isAdmin();
    });
}
```

### Using Custom Directives

```php
<p>Created: @datetime($user->created_at)</p>
<p>Price: @money($product->price)</p>

@admin
    <p>This content is only visible to admins.</p>
@endadmin
```

## Raw PHP

### @php Directive

```php
@php
    $users = App\Models\User::all();
    $count = $users->count();
@endphp

<p>Total users: {{ $count }}</p>
```

## Error Handling

### Displaying Errors

```php
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- For specific fields -->
@error('email')
    <div class="text-danger">{{ $message }}</div>
@enderror
```

### Old Input

```php
<input type="text" name="name" value="{{ old('name', $user->name ?? '') }}">
```

## Best Practices

### 1. Keep Views Simple

```php
<!-- Good -->
@foreach($users as $user)
    <x-user-card :user="$user" />
@endforeach

<!-- Bad -->
@foreach($users as $user)
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">{{ $user->name }}</h5>
            <p class="card-text">{{ $user->email }}</p>
            <!-- Lots of HTML -->
        </div>
    </div>
@endforeach
```

### 2. Use Components for Reusable UI

```php
<!-- Create reusable components -->
<x-button type="submit" variant="primary">Save</x-button>
<x-modal title="Delete User" id="deleteModal">
    Are you sure you want to delete this user?
</x-modal>
```

### 3. Organize Views Properly

```
resources/views/
├── layouts/
│   ├── app.blade.php
│   └── admin.blade.php
├── components/
│   ├── alert.blade.php
│   ├── button.blade.php
│   └── modal.blade.php
├── partials/
│   ├── header.blade.php
│   └── footer.blade.php
├── users/
│   ├── index.blade.php
│   ├── show.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
└── posts/
    ├── index.blade.php
    └── show.blade.php
```

### 4. Use View Composers for Shared Data

```php
<!-- app/Providers/AppServiceProvider.php -->
use Illuminate\Support\Facades\View;

public function boot()
{
    View::composer('*', function ($view) {
        $view->with('currentUser', auth()->user());
    });

    View::composer('layouts.app', function ($view) {
        $view->with('categories', Category::all());
    });
}
```

### 5. Cache Views in Production

```bash
php artisan view:cache
```

## Summary

In this chapter, we covered:

- ✅ Basic Blade syntax and directives
- ✅ Conditional statements and loops
- ✅ Layouts and sections
- ✅ Components (class-based and anonymous)
- ✅ Forms and CSRF protection
- ✅ Includes and partials
- ✅ Custom directives
- ✅ Error handling
- ✅ Best practices for Blade templates

Blade provides a powerful and elegant way to create dynamic views in Laravel. In the next chapter, we'll explore Eloquent ORM for working with databases.
