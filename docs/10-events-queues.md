# Chapter 10: Events & Queues

## What are Events?

Events in Laravel provide a simple observer pattern implementation that allows you to subscribe and listen for various events that occur in your application. Events are great for decoupling various aspects of your application, since a single event can have multiple listeners that do not depend on each other.

## Creating Events

### Using Artisan Command

```bash
# Create event
php artisan make:event UserRegistered

# Create event with model
php artisan make:event UserRegistered --model=User
```

### Basic Event Structure

```php
<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
```

### Broadcasting Events

```php
<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $status;

    public function __construct(User $user, $status)
    {
        $this->user = $user;
        $this->status = $status;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'status' => $this->status,
            'timestamp' => now()->toISOString(),
        ];
    }
}
```

## Creating Listeners

### Using Artisan Command

```bash
# Create listener
php artisan make:listener SendWelcomeEmail

# Create listener for specific event
php artisan make:listener SendWelcomeEmail --event=UserRegistered
```

### Basic Listener Structure

```php
<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendWelcomeEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;
    public $timeout = 120;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserRegistered $event): void
    {
        // Send welcome email to the user
        Mail::to($event->user->email)->send(new WelcomeEmail($event->user));
    }

    /**
     * Handle a job failure.
     */
    public function failed(UserRegistered $event, \Throwable $exception): void
    {
        // Handle the failure
        Log::error('Failed to send welcome email', [
            'user_id' => $event->user->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### Queueable Listeners

```php
<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendWelcomeEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public $connection = 'redis';
    public $queue = 'emails';
    public $delay = 60; // Delay in seconds

    public function handle(UserRegistered $event): void
    {
        // This will be queued
        Mail::to($event->user->email)->send(new WelcomeEmail($event->user));
    }
}
```

## Registering Events and Listeners

### In EventServiceProvider

```php
<?php

namespace App\Providers;

use App\Events\UserRegistered;
use App\Listeners\SendWelcomeEmail;
use App\Listeners\CreateUserProfile;
use App\Listeners\SendAdminNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        UserRegistered::class => [
            SendWelcomeEmail::class,
            CreateUserProfile::class,
            SendAdminNotification::class,
        ],
        
        'Illuminate\Auth\Events\Login' => [
            'App\Listeners\LogSuccessfulLogin',
        ],
        
        'Illuminate\Auth\Events\Failed' => [
            'App\Listeners\LogFailedLogin',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();

        // Register events using wildcards
        Event::listen('user.*', function ($eventName, array $data) {
            Log::info("User event: {$eventName}", $data);
        });
    }
}
```

## Dispatching Events

### Using Event Facade

```php
use App\Events\UserRegistered;
use Illuminate\Support\Facades\Event;

// Dispatch event
Event::dispatch(new UserRegistered($user));

// Or use the event class directly
UserRegistered::dispatch($user);
```

### In Controllers

```php
<?php

namespace App\Http\Controllers;

use App\Events\UserRegistered;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $user = User::create($request->validated());

        // Dispatch event
        UserRegistered::dispatch($user);

        return redirect()->route('users.show', $user);
    }
}
```

### In Models

```php
<?php

namespace App\Models;

use App\Events\UserRegistered;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $dispatchesEvents = [
        'created' => UserRegistered::class,
    ];

    // Or manually in the model
    protected static function booted()
    {
        static::created(function ($user) {
            UserRegistered::dispatch($user);
        });

        static::updated(function ($user) {
            UserStatusChanged::dispatch($user, $user->status);
        });
    }
}
```

## Event Subscribers

### Creating Subscribers

```bash
php artisan make:listener UserEventSubscriber
```

```php
<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Events\UserUpdated;
use App\Events\UserDeleted;
use Illuminate\Events\Dispatcher;

class UserEventSubscriber
{
    public function handleUserRegistered($event): void
    {
        // Handle user registered event
    }

    public function handleUserUpdated($event): void
    {
        // Handle user updated event
    }

    public function handleUserDeleted($event): void
    {
        // Handle user deleted event
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            UserRegistered::class,
            [UserEventSubscriber::class, 'handleUserRegistered']
        );

        $events->listen(
            UserUpdated::class,
            [UserEventSubscriber::class, 'handleUserUpdated']
        );

        $events->listen(
            UserDeleted::class,
            [UserEventSubscriber::class, 'handleUserDeleted']
        );
    }
}
```

### Registering Subscribers

```php
// In EventServiceProvider
protected $subscribe = [
    UserEventSubscriber::class,
];
```

## Queues

### What are Queues?

Queues allow you to defer the processing of a time-consuming task, such as sending an email, until a later time. This drastically speeds up web requests to your application.

### Queue Configuration

```php
// config/queue.php
return [
    'default' => env('QUEUE_CONNECTION', 'sync'),

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => false,
        ],
    ],
];
```

### Creating Jobs

```bash
# Create job
php artisan make:job SendWelcomeEmail

# Create job with model
php artisan make:job ProcessOrder --model=Order
```

### Basic Job Structure

```php
<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $tries = 3;
    public $timeout = 120;
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Send welcome email
        Mail::to($this->user->email)->send(new WelcomeEmail($this->user));
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Handle the failure
        Log::error('Failed to send welcome email', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### Dispatching Jobs

```php
use App\Jobs\SendWelcomeEmail;

// Dispatch job
SendWelcomeEmail::dispatch($user);

// Dispatch with delay
SendWelcomeEmail::dispatch($user)->delay(now()->addMinutes(10));

// Dispatch to specific queue
SendWelcomeEmail::dispatch($user)->onQueue('emails');

// Dispatch with chain
ProcessOrder::withChain([
    new SendOrderConfirmation($order),
    new UpdateInventory($order),
])->dispatch($order);
```

### Job Batching

```php
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

$batch = Bus::batch([
    new ProcessPodcast(Podcast::find(1)),
    new ProcessPodcast(Podcast::find(2)),
    new ProcessPodcast(Podcast::find(3)),
])->then(function (Batch $batch) {
    // All jobs completed successfully
})->catch(function (Batch $batch, \Throwable $e) {
    // First batch job failure
})->finally(function (Batch $batch) {
    // Batch completed
})->dispatch();
```

## Queue Workers

### Starting Queue Workers

```bash
# Start queue worker
php artisan queue:work

# Start with specific connection
php artisan queue:work redis

# Start with specific queue
php artisan queue:work --queue=high,default,low

# Start with timeout
php artisan queue:work --timeout=60

# Start with memory limit
php artisan queue:work --memory=1024

# Start in daemon mode
php artisan queue:work --daemon
```

### Queue Monitoring

```bash
# Check queue status
php artisan queue:monitor

# Clear failed jobs
php artisan queue:flush

# Retry failed jobs
php artisan queue:retry all

# Retry specific failed job
php artisan queue:retry 5
```

## Common Event Examples

### User Registration Event

```php
<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRegistered
{
    use Dispatchable, SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
```

### User Registration Listeners

```php
<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Jobs\SendWelcomeEmail;
use App\Jobs\CreateUserProfile;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWelcomeEmail implements ShouldQueue
{
    public function handle(UserRegistered $event): void
    {
        SendWelcomeEmail::dispatch($event->user);
    }
}

class CreateUserProfile implements ShouldQueue
{
    public function handle(UserRegistered $event): void
    {
        $event->user->profile()->create([
            'bio' => '',
            'avatar' => null,
        ]);
    }
}

class SendAdminNotification implements ShouldQueue
{
    public function handle(UserRegistered $event): void
    {
        // Notify admin about new user
        Mail::to('admin@example.com')->send(new NewUserNotification($event->user));
    }
}
```

### Order Processing Event

```php
<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced
{
    use Dispatchable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }
}
```

### Order Processing Listeners

```php
<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Jobs\SendOrderConfirmation;
use App\Jobs\UpdateInventory;
use App\Jobs\ProcessPayment;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderConfirmation implements ShouldQueue
{
    public function handle(OrderPlaced $event): void
    {
        SendOrderConfirmation::dispatch($event->order);
    }
}

class UpdateInventory implements ShouldQueue
{
    public function handle(OrderPlaced $event): void
    {
        UpdateInventory::dispatch($event->order);
    }
}

class ProcessPayment implements ShouldQueue
{
    public function handle(OrderPlaced $event): void
    {
        ProcessPayment::dispatch($event->order);
    }
}
```

## Common Job Examples

### Email Job

```php
<?php

namespace App\Jobs;

use App\Models\User;
use App\Mail\WelcomeEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $tries = 3;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function handle(): void
    {
        Mail::to($this->user->email)->send(new WelcomeEmail($this->user));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send welcome email', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### File Processing Job

```php
<?php

namespace App\Jobs;

use App\Models\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessUploadedFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $file;
    public $timeout = 300; // 5 minutes

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function handle(): void
    {
        $path = Storage::path($this->file->path);
        
        // Process the file
        $this->file->update([
            'status' => 'processing',
        ]);

        // Simulate file processing
        sleep(10);

        $this->file->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }
}
```

### API Job

```php
<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SyncUserToExternalAPI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $tries = 3;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function handle(): void
    {
        $response = Http::post('https://api.example.com/users', [
            'name' => $this->user->name,
            'email' => $this->user->email,
        ]);

        if ($response->successful()) {
            $this->user->update([
                'external_id' => $response->json('id'),
                'synced_at' => now(),
            ]);
        } else {
            throw new \Exception('Failed to sync user to external API');
        }
    }
}
```

## Best Practices

### 1. Keep Events Simple

```php
// Good
class UserRegistered
{
    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
}

// Bad - Too much data
class UserRegistered
{
    public $user;
    public $ip;
    public $userAgent;
    public $timestamp;
    public $referrer;
    // ... many more properties
}
```

### 2. Use Queues for Heavy Operations

```php
// Good - Queue heavy operations
class SendWelcomeEmail implements ShouldQueue
{
    public function handle(UserRegistered $event): void
    {
        // This will be queued
        Mail::to($event->user->email)->send(new WelcomeEmail($event->user));
    }
}

// Bad - Blocking operation
class SendWelcomeEmail
{
    public function handle(UserRegistered $event): void
    {
        // This blocks the request
        Mail::to($event->user->email)->send(new WelcomeEmail($event->user));
    }
}
```

### 3. Handle Job Failures

```php
class SendWelcomeEmail implements ShouldQueue
{
    public $tries = 3;
    public $timeout = 120;

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send welcome email', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### 4. Use Job Batching for Related Tasks

```php
$batch = Bus::batch([
    new ProcessOrder($order),
    new SendOrderConfirmation($order),
    new UpdateInventory($order),
])->then(function (Batch $batch) {
    // All jobs completed successfully
})->catch(function (Batch $batch, \Throwable $e) {
    // Handle failure
})->dispatch();
```

### 5. Monitor Queue Performance

```bash
# Monitor queue status
php artisan queue:monitor

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

## Summary

In this chapter, we covered:

- ✅ Creating and dispatching events
- ✅ Creating and registering listeners
- ✅ Event subscribers
- ✅ Broadcasting events
- ✅ Creating and dispatching jobs
- ✅ Queue configuration and workers
- ✅ Job batching and monitoring
- ✅ Common event and job examples
- ✅ Best practices for events and queues

Events and queues provide powerful ways to decouple your application and handle background tasks. In the next chapter, we'll explore testing in Laravel.
