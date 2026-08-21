# Phase 10: Event Dispatcher & Realtime Interfaces

**Duration:** Week 12

---

## 1. What to Build

Phase 10 provides an event pub/sub dispatcher, realtime message broadcasting, and Server-Sent Events (SSE) / WebSocket connection adapter foundation for building reactive applications (such as chats or live notification feeds).

### Core Deliverables:

- **`framework/Events/Event.php`** — Base class for application events.
- **`framework/Events/Dispatcher.php`** — Central event manager mapping events to synchronous/asynchronous listeners.
- **`framework/Events/ListenerInterface.php`** — Contract defining `handle(Event $event): void`.
- **`framework/Events/BroadcastManager.php`** — Redis Pub/Sub integration for cross-node event distribution.
- **`framework/Http/SseResponse.php`** — Specialized HTTP response handling Server-Sent Events (SSE) streaming (`text/event-stream`).

---

## 2. How Current Implementation Fits with Previous Phase Implementation

- **Service Container Wire-up:** `Dispatcher` registered as singleton in Phase 0 `Container`.
- **Queue Subsystem Integration:** Listeners can implement `ShouldQueue`, automatically serializing event dispatches to Phase 9's Queue subsystem.
- **HTTP Pipeline Integration:** `SseResponse` directly inherits from Phase 1's `Response`.

---

## 3. How to Build

### Step-by-Step Implementation:

1. **`framework/Events/Dispatcher.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Events;

   class Dispatcher
   {
       protected array $listeners = [];

       public function listen(string $eventClass, callable|string $listener): void
       {
           $this->listeners[$eventClass][] = $listener;
       }

       public function dispatch(object $event): void
       {
           $eventClass = get_class($event);
           if (!isset($this->listeners[$eventClass])) return;

           foreach ($this->listeners[$eventClass] as $listener) {
               if ($listener instanceof ListenerInterface) {
                   $listener->handle($event);
               } elseif (is_callable($listener)) {
                   $listener($event);
               }
           }
       }
   }
   ```

2. **`framework/Http/SseResponse.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Http;

   class SseResponse extends Response
   {
       public function __construct(protected \Closure $callback)
       {
           parent::__construct('', 200, [
               'Content-Type' => 'text/event-stream',
               'Cache-Control' => 'no-cache',
               'Connection' => 'keep-alive',
               'X-Accel-Buffering' => 'no',
           ]);
       }

       public function send(): void
       {
           http_response_code($this->statusCode);
           foreach ($this->headers as $name => $value) {
               header("{$name}: {$value}");
           }

           while (ob_get_level() > 0) ob_end_flush();
           flush();

           ($this->callback)(function (string $event, mixed $data) {
               echo "event: {$event}\n";
               echo "data: " . json_encode($data) . "\n\n";
               flush();
           });
       }
   }
   ```

---

## 4. Success Criteria

- [ ] Events dispatch synchronously to subscribed listeners or queue asynchronously.
- [ ] Redis Pub/Sub broadcasts events across distributed web nodes.
- [ ] `SseResponse` flushes dynamic events cleanly to frontend clients without memory growth.
