# Events

NexusPHP provides a robust, synchronous publish/subscribe (`pub/sub`) Event Dispatcher. Events provide a great way to decouple various aspects of your application, since a single event can have multiple listeners that do not depend on each other.

---

## Defining Events

An event class is essentially a data container that holds the information related to the event.

You can create a simple Plain Old PHP Object (POJO) to represent your event:

```php
namespace App\Events;

class UserRegistered
{
    public function __construct(public int $userId) {}
}
```

## Defining Listeners

Listeners receive the event instance and perform the necessary actions in response. A listener can be any callable (like a closure), or a class implementing the `Nexus\Events\ListenerInterface`.

```php
namespace App\Listeners;

use Nexus\Events\ListenerInterface;
use App\Events\UserRegistered;

class SendWelcomeEmail implements ListenerInterface
{
    public function handle(object $event): void
    {
        if ($event instanceof UserRegistered) {
            // Send email to $event->userId...
        }
    }
}
```

---

## Registering Events and Listeners

You register listeners to specific events using the `Nexus\Events\Dispatcher` singleton. Typically, this is done within a Service Provider or the bootstrap phase of your application.

```php
use Nexus\Events\Dispatcher;
use App\Events\UserRegistered;
use App\Listeners\SendWelcomeEmail;

$dispatcher = app(Dispatcher::class);

// Registering a Class String Listener (auto-resolved via Dependency Injection)
$dispatcher->listen(UserRegistered::class, SendWelcomeEmail::class);

// Registering a Closure Listener
$dispatcher->listen(UserRegistered::class, function (UserRegistered $event) {
    // Execute logic...
});
```

---

## Dispatching Events

To trigger an event, instantiate the event class and pass it to the `dispatch` method on the `Dispatcher`.

```php
use Nexus\Events\Dispatcher;
use App\Events\UserRegistered;

$event = new UserRegistered(42);

// Dispatch the event to all registered listeners
app(Dispatcher::class)->dispatch($event);
```

### Exception Handling in Listeners

NexusPHP embraces resilience. If an exception is thrown within a specific listener, it is safely caught, and the dispatcher continues executing the remaining listeners registered for that event.

The caught exception, along with the failing listener class and event context, is securely logged to `storage/logs/events.log` for debugging.

---

## Event Broadcasting

NexusPHP provides a `BroadcastManager` for routing framework events outward to third-party endpoints or Webhook integrations. (Note: True asynchronous WebSocket daemons are omitted by design to keep the framework zero-dependency. The `BroadcastManager` is designed for webhook payload syndication).
