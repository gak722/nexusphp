# Copilot Spec: Phase 10 — Event Dispatcher & Realtime Interfaces

## Objective
Implement event pub/sub dispatcher, realtime event broadcasting over Redis Pub/Sub, and Server-Sent Events (`SseResponse`) streaming interface.

## Target Files to Create / Modify
- `framework/Events/Event.php`
- `framework/Events/Dispatcher.php`
- `framework/Events/ListenerInterface.php`
- `framework/Events/BroadcastManager.php`
- `framework/Http/SseResponse.php`

---

## Detailed Specifications

### 1. `framework/Events/Dispatcher.php`
- `listen(string $eventClass, callable|string $listener): void`
- `dispatch(object $event): void` — invokes registered listeners or serializes to queue if listener implements queued contract.

### 2. `framework/Http/SseResponse.php`
- Inherits `Response`, setting headers:
  - `Content-Type: text/event-stream`
  - `Cache-Control: no-cache`
  - `Connection: keep-alive`
- Flushes data using `flush()` after every message yield.

---

## Copilot Validation Rules
- [ ] `SseResponse` MUST clear output buffers (`ob_end_flush()`) before streaming events.
- [ ] Events MUST support both closure listeners and class-based listeners.
