# Chapter 11: Testing

## What is Testing?

Testing is a crucial part of software development that helps ensure your code works correctly and continues to work as you make changes. Laravel provides excellent testing support with PHPUnit and additional testing helpers that make it easy to test your applications.

## Types of Tests

### Unit Tests
Test individual components in isolation (models, services, helpers).

### Feature Tests
Test complete features from the user's perspective (HTTP requests, database interactions).

### Integration Tests
Test how different components work together.

## Setting Up Testing

### PHPUnit Configuration

```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory suffix=".php">./app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
    </php>
</phpunit>
```

### Test Database Configuration

```php
// config/database.php
'testing' => [
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
],
```

## Running Tests

### Basic Commands

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/UserTest.php

# Run specific test method
php artisan test --filter test_user_can_register

# Run tests with coverage
php artisan test --coverage

# Run tests in parallel
php artisan test --parallel

# Run tests with verbose output
php artisan test --verbose
```

### Using PHPUnit Directly

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit --testsuite=Feature

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage
```

## Feature Tests

### Creating Feature Tests

```bash
# Create feature test
php artisan make:test UserTest

# Create test for specific model
php artisan make:test UserTest --model=User
```

### Basic Feature Test Structure

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/home');
        
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/home');
        
        $this->assertAuthenticated();
    }

    public function test_user_cannot_access_protected_route(): void
    {
        $response = $this->get('/profile');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
}
```

### HTTP Testing

```php
class HttpTest extends TestCase
{
    public function test_get_request(): void
    {
        $response = $this->get('/users');

        $response->assertStatus(200);
        $response->assertViewIs('users.index');
    }

    public function test_post_request(): void
    {
        $response = $this->post('/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/users');
    }

    public function test_put_request(): void
    {
        $user = User::factory()->create();

        $response = $this->put("/users/{$user->id}", [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $response->assertStatus(302);
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Jane Doe',
        ]);
    }

    public function test_delete_request(): void
    {
        $user = User::factory()->create();

        $response = $this->delete("/users/{$user->id}");

        $response->assertStatus(302);
        
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }
}
```

### Response Assertions

```php
class ResponseTest extends TestCase
{
    public function test_response_assertions(): void
    {
        $response = $this->get('/users');

        // Status assertions
        $response->assertStatus(200);
        $response->assertOk();
        $response->assertNotFound();
        $response->assertForbidden();
        $response->assertUnauthorized();

        // Redirect assertions
        $response->assertRedirect('/login');
        $response->assertRedirect(route('login'));

        // View assertions
        $response->assertViewIs('users.index');
        $response->assertViewHas('users');
        $response->assertViewHas('users', $users);

        // JSON assertions
        $response->assertJson(['name' => 'John Doe']);
        $response->assertJsonFragment(['name' => 'John Doe']);
        $response->assertJsonMissing(['password']);

        // Header assertions
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertHeaderMissing('X-Custom-Header');

        // Cookie assertions
        $response->assertCookie('name', 'value');
        $response->assertCookieMissing('name');

        // Session assertions
        $response->assertSessionHas('key');
        $response->assertSessionHas('key', 'value');
        $response->assertSessionMissing('key');
        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasNoErrors();
    }
}
```

### Authentication Testing

```php
class AuthenticationTest extends TestCase
{
    public function test_guest_cannot_access_protected_route(): void
    {
        $response = $this->get('/profile');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_protected_route(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $this->assertAuthenticated();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }
}
```

## Unit Tests

### Creating Unit Tests

```bash
# Create unit test
php artisan make:test UserTest --unit

# Create test for specific class
php artisan make:test UserServiceTest --unit
```

### Testing Models

```php
<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_full_name(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('John Doe', $user->full_name);
    }

    public function test_user_can_be_admin(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($user->isAdmin());
    }

    public function test_user_has_many_posts(): void
    {
        $user = User::factory()->create();
        $posts = Post::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->posts);
        $this->assertInstanceOf(Post::class, $user->posts->first());
    }
}
```

### Testing Services

```php
<?php

namespace Tests\Unit;

use App\Services\UserService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = new UserService();
    }

    public function test_create_user(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
        ];

        $user = $this->userService->createUser($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
    }

    public function test_update_user(): void
    {
        $user = User::factory()->create();
        $updateData = ['name' => 'Jane Doe'];

        $updatedUser = $this->userService->updateUser($user, $updateData);

        $this->assertEquals('Jane Doe', $updatedUser->name);
    }

    public function test_delete_user(): void
    {
        $user = User::factory()->create();

        $this->userService->deleteUser($user);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
```

## Database Testing

### Database Assertions

```php
class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_assertions(): void
    {
        $user = User::factory()->create();

        // Assert record exists
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $user->name,
        ]);

        // Assert record doesn't exist
        $this->assertDatabaseMissing('users', [
            'email' => 'nonexistent@example.com',
        ]);

        // Assert count
        $this->assertDatabaseCount('users', 1);

        // Assert table is empty
        $this->assertDatabaseEmpty('posts');
    }

    public function test_soft_deletes(): void
    {
        $user = User::factory()->create();
        $user->delete();

        // Assert record is soft deleted
        $this->assertSoftDeleted('users', ['id' => $user->id]);

        // Assert record exists in database but is soft deleted
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
```

### Database Factories

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }
}
```

### Using Factories in Tests

```php
class FactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_using_factories(): void
    {
        // Create single user
        $user = User::factory()->create();

        // Create user with specific attributes
        $admin = User::factory()->create(['role' => 'admin']);

        // Create multiple users
        $users = User::factory()->count(5)->create();

        // Create user with state
        $unverifiedUser = User::factory()->unverified()->create();

        // Create user with relationships
        $userWithPosts = User::factory()
            ->has(Post::factory()->count(3))
            ->create();

        // Create user without saving
        $user = User::factory()->make();

        // Create user with raw attributes
        $userData = User::factory()->raw();
    }
}
```

## Mocking and Stubbing

### Mocking Services

```php
use Mockery;
use App\Services\EmailService;

class MockTest extends TestCase
{
    public function test_mocking_service(): void
    {
        $mock = Mockery::mock(EmailService::class);
        $mock->shouldReceive('send')
            ->once()
            ->with('john@example.com', 'Welcome!')
            ->andReturn(true);

        app()->instance(EmailService::class, $mock);

        $user = User::factory()->create();
        
        // Your code that uses EmailService
        $this->assertTrue($mock->send('john@example.com', 'Welcome!'));
    }

    public function test_partial_mock(): void
    {
        $user = User::factory()->create();
        
        $mock = Mockery::mock($user)->makePartial();
        $mock->shouldReceive('sendEmail')
            ->once()
            ->andReturn(true);

        $this->assertTrue($mock->sendEmail());
    }
}
```

### Mocking HTTP Requests

```php
use Illuminate\Support\Facades\Http;

class HttpMockTest extends TestCase
{
    public function test_mocking_http_requests(): void
    {
        Http::fake([
            'api.example.com/*' => Http::response([
                'id' => 1,
                'name' => 'John Doe',
            ], 200),
        ]);

        $response = Http::get('api.example.com/users/1');

        $this->assertEquals(200, $response->status());
        $this->assertEquals('John Doe', $response->json('name'));

        Http::assertSent(function ($request) {
            return $request->url() == 'api.example.com/users/1' &&
                   $request->method() == 'GET';
        });
    }
}
```

## Testing Events and Jobs

### Testing Events

```php
use App\Events\UserRegistered;
use Illuminate\Support\Facades\Event;

class EventTest extends TestCase
{
    public function test_event_is_dispatched(): void
    {
        Event::fake();

        $user = User::factory()->create();

        UserRegistered::dispatch($user);

        Event::assertDispatched(UserRegistered::class);
        Event::assertDispatched(UserRegistered::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    public function test_event_is_not_dispatched(): void
    {
        Event::fake();

        $user = User::factory()->create();

        Event::assertNotDispatched(UserRegistered::class);
    }
}
```

### Testing Jobs

```php
use App\Jobs\SendWelcomeEmail;
use Illuminate\Support\Facades\Queue;

class JobTest extends TestCase
{
    public function test_job_is_dispatched(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        SendWelcomeEmail::dispatch($user);

        Queue::assertPushed(SendWelcomeEmail::class);
        Queue::assertPushed(SendWelcomeEmail::class, function ($job) use ($user) {
            return $job->user->id === $user->id;
        });
    }

    public function test_job_is_not_dispatched(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Queue::assertNotPushed(SendWelcomeEmail::class);
    }
}
```

## Testing Mail and Notifications

### Testing Mail

```php
use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Mail;

class MailTest extends TestCase
{
    public function test_mail_is_sent(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        Mail::to($user->email)->send(new WelcomeEmail($user));

        Mail::assertSent(WelcomeEmail::class);
        Mail::assertSent(WelcomeEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_mail_is_not_sent(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        Mail::assertNotSent(WelcomeEmail::class);
    }
}
```

### Testing Notifications

```php
use App\Notifications\WelcomeNotification;
use Illuminate\Support\Facades\Notification;

class NotificationTest extends TestCase
{
    public function test_notification_is_sent(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $user->notify(new WelcomeNotification());

        Notification::assertSentTo($user, WelcomeNotification::class);
    }
}
```

## Testing API Endpoints

### API Testing

```php
class ApiTest extends TestCase
{
    public function test_api_endpoint(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email']
                ]
            ]);
    }

    public function test_api_validation(): void
    {
        $response = $this->postJson('/api/users', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_api_authentication(): void
    {
        $response = $this->getJson('/api/profile');

        $response->assertStatus(401);
    }
}
```

## Test Data Providers

### Using Data Providers

```php
class DataProviderTest extends TestCase
{
    /**
     * @dataProvider userDataProvider
     */
    public function test_user_validation($name, $email, $expectedStatus): void
    {
        $response = $this->post('/users', [
            'name' => $name,
            'email' => $email,
        ]);

        $response->assertStatus($expectedStatus);
    }

    public function userDataProvider(): array
    {
        return [
            'valid user' => ['John Doe', 'john@example.com', 302],
            'invalid email' => ['John Doe', 'invalid-email', 422],
            'empty name' => ['', 'john@example.com', 422],
        ];
    }
}
```

## Testing Best Practices

### 1. Use Descriptive Test Names

```php
// Good
public function test_user_can_register_with_valid_data(): void
public function test_user_cannot_register_with_invalid_email(): void
public function test_admin_can_delete_any_user(): void

// Bad
public function test_register(): void
public function test_email(): void
public function test_delete(): void
```

### 2. Follow AAA Pattern

```php
public function test_user_can_login(): void
{
    // Arrange
    $user = User::factory()->create();

    // Act
    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    // Assert
    $response->assertRedirect('/home');
    $this->assertAuthenticated();
}
```

### 3. Test One Thing at a Time

```php
// Good - Single responsibility
public function test_user_can_register(): void
{
    // Test registration only
}

public function test_user_can_login(): void
{
    // Test login only
}

// Bad - Multiple responsibilities
public function test_user_authentication(): void
{
    // Testing registration, login, and logout in one test
}
```

### 4. Use Factories for Test Data

```php
// Good
$user = User::factory()->create();

// Bad
$user = new User();
$user->name = 'John Doe';
$user->email = 'john@example.com';
$user->save();
```

### 5. Test Edge Cases

```php
public function test_user_cannot_register_with_existing_email(): void
{
    $existingUser = User::factory()->create();

    $response = $this->post('/register', [
        'name' => 'John Doe',
        'email' => $existingUser->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors(['email']);
}
```

### 6. Use Database Transactions

```php
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TransactionTest extends TestCase
{
    use DatabaseTransactions;

    // Tests will run in transactions and be rolled back
}
```

## Summary

In this chapter, we covered:

- ✅ Setting up testing environment
- ✅ Feature tests for HTTP requests
- ✅ Unit tests for individual components
- ✅ Database testing and factories
- ✅ Mocking and stubbing
- ✅ Testing events and jobs
- ✅ Testing mail and notifications
- ✅ API testing
- ✅ Test data providers
- ✅ Testing best practices

Testing is essential for building reliable and maintainable applications. In the next chapter, we'll explore deployment strategies for Laravel applications.
