# 13. Event Dispatcher & Server-Sent Events (SSE)

NexusPHP includes an Event Dispatcher (`Nexus\Events\Dispatcher`) for decoupled domain logic and native support for Server-Sent Events (`Nexus\Http\SseResponse`) for real-time browser streaming.

---

## 1. Event Dispatcher Pattern

### Defining an Event Class (`Nexus\Events\Event`):

```php
namespace App\Events;

use Nexus\Events\Event;
use App\Models\User;

class UserRegisteredEvent extends Event
{
    public function __construct(public User $user) {}
}
```

### Registering Listeners:

```php
use Nexus\Events\Dispatcher;
use App\Events\UserRegisteredEvent;

$dispatcher = app(Dispatcher::class);

$dispatcher->listen(UserRegisteredEvent::class, function (UserRegisteredEvent $event) {
    // Log user registration
    app('log')->info("User registered: {$event->user->email}");
});
```

---

## 2. Real-Time Server-Sent Events (SSE)

NexusPHP provides `Nexus\Http\SseResponse` to stream events to browsers over HTTP without requiring heavy WebSocket servers.

```php
namespace App\Http\Controllers;

use Nexus\Http\Controller;
use Nexus\Http\SseResponse;

class LiveMetricsController extends Controller
{
    public function stream(): SseResponse
    {
        return new SseResponse(function () {
            // Send periodic updates over persistent HTTP stream
            while (true) {
                $data = [
                    'cpu_usage' => sys_getloadavg()[0],
                    'memory' => memory_get_usage(true),
                    'timestamp' => date('H:i:s'),
                ];

                SseResponse::sendEvent('metrics', json_encode($data));

                sleep(2); // Wait 2 seconds before next broadcast
            }
        });
    }
}
```

---

## 3. Frontend JS EventSource Integration

Clients connect using standard browser JavaScript:

```html
<script>
    const evtSource = new EventSource("/api/metrics/stream");
    
    evtSource.addEventListener("metrics", function(event) {
        const data = JSON.parse(event.data);
        console.log("CPU Load:", data.cpu_usage);
        document.getElementById("memoryVal").innerText = data.memory;
    });
</script>
```

---

## 4. Next Steps

Learn how to write custom CLI commands in [14. CLI Commands & Console Tooling](14-cli-commands.md).
