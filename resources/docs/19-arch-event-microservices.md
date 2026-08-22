# 19. Architecture Pattern: Event-Driven Microservices

Build event-driven microservice architectures using NexusPHP's Queue Workers, Event Dispatcher, and Server-Sent Events (SSE).

---

## 1. System Topology

```
 [ Service A (API Gateway) ] ──(Publishes Job)──► [ Database / Redis Queue ]
                                                              │
                                                              ▼
                                                   [ Service B (Worker) ]
                                                              │
                                                     (Dispatches Domain Event)
                                                              │
                                                              ▼
                                                   [ Real-Time SSE Stream ]
```

---

## 2. Asynchronous Task Producer Service

```php
namespace App\Http\Controllers;

use Nexus\Http\Controller;
use Nexus\Http\Request;
use Nexus\Http\Response;
use App\Jobs\ProcessOrderJob;

class OrderController extends Controller
{
    public function checkout(Request $request): Response
    {
        $orderData = $request->all();

        // Offload long-running payment processing & inventory reservation to queue
        ProcessOrderJob::dispatch($orderData);

        return $this->json([
            'status' => 'accepted',
            'message' => 'Order is being processed asynchronously.',
            'tracking_id' => $orderData['order_id'] ?? uniqid()
        ], 202);
    }
}
```

---

## 3. Dedicated Worker Processing Node

On your worker instances, launch background queue listeners:

```bash
php nexus queue:work
```

---

## 4. Next Steps

Review final deployment recommendations and production checklists in [20. Conclusion & Production Deployment](20-conclusion.md).
