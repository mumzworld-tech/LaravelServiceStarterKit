# OpenTelemetry Tracing with TracerService

The `TracerService` provides a simple way to add distributed tracing to your Laravel application using OpenTelemetry.

## Basic Usage

### 1. Inject the Service

```php
use App\Services\Telemetry\TracerService;

class YourController extends Controller
{
    private TracerService $tracer;

    public function __construct(TracerService $tracer)
    {
        $this->tracer = $tracer;
    }
}
```

### 2. Create Simple Traces

```php
$this->tracer->trace('operation.name', function ($span) {
    // Your code here
    $result = doSomething();
    
    // Add attributes to the span
    $this->tracer->addAttributes([
        'operation.result' => json_encode($result),
        'operation.timestamp' => now()->toIso8601String()
    ]);
    
    return $result;
});
```

### 3. Trace HTTP Requests

```php
$response = $this->tracer->traceHttpRequest(
    'GET',
    'https://api.example.com',
    ['timeout' => 5],
    function ($span) {
        return Http::get('https://api.example.com')->json();
    }
);
```

### 4. Handle Exceptions

Exceptions are automatically captured and recorded in the trace:

```php
$this->tracer->trace('risky.operation', function ($span) {
    try {
        // Your code that might throw an exception
        $result = riskyOperation();
        return $result;
    } catch (\Throwable $e) {
        // The exception will be automatically recorded in the span
        throw $e;
    }
});
```

## Advanced Usage

### 1. Nested Spans

You can create nested spans to track sub-operations:

```php
$this->tracer->trace('parent.operation', function ($span) {
    // Parent operation code
    
    $result = $this->tracer->trace('child.operation', function ($childSpan) {
        // Child operation code
        return processData();
    });
    
    return $result;
});
```

### 2. Custom Attributes

Add custom attributes to provide more context:

```php
$this->tracer->trace('process.user', function ($span) use ($user) {
    $this->tracer->addAttributes([
        'user.id' => $user->id,
        'user.email' => $user->email,
        'process.type' => 'registration',
        'metadata' => json_encode([
            'source' => 'web',
            'browser' => request()->header('User-Agent')
        ])
    ]);
    
    // Process user...
});
```

### 3. Timing Information

Start and end times are automatically recorded for all spans:

```php
$this->tracer->trace('long.operation', function ($span) {
    // The span will automatically record:
    // - start_time
    // - end_time
    // - Duration can be calculated from these values
    sleep(2); // Some long operation
});
```

## Best Practices

1. **Naming Spans**: Use dot notation for span names (e.g., `service.operation.sub_operation`)
2. **Attributes**: Always JSON encode complex data structures
3. **Error Handling**: Let the service handle exceptions automatically
4. **Context**: Include relevant context in span attributes
5. **Granularity**: Create spans for operations that are meaningful for debugging and monitoring

## Configuration

The service is configured to send traces to your OpenTelemetry collector. The configuration can be found in:
- `docker/application/opentelemetry.ini`
- Environment variables in your `.env` file

## Example Controller

Here's a complete example of a controller using various tracing features:

```php
use App\Services\Telemetry\TracerService;

class OrderController extends Controller
{
    private TracerService $tracer;

    public function __construct(TracerService $tracer)
    {
        $this->tracer = $tracer;
    }

    public function process(Request $request)
    {
        return $this->tracer->trace('order.process', function ($span) use ($request) {
            // Add request context
            $this->tracer->addAttributes([
                'order.id' => $request->input('order_id'),
                'user.id' => auth()->id()
            ]);

            // Process payment (nested span)
            $payment = $this->tracer->trace('order.payment', function ($paymentSpan) use ($request) {
                return $this->processPayment($request->input('payment_details'));
            });

            // External API call
            $shipping = $this->tracer->traceHttpRequest(
                'POST',
                'https://shipping-api.example.com/schedule',
                ['timeout' => 10],
                function ($span) use ($request) {
                    return Http::post('https://shipping-api.example.com/schedule', [
                        'order_id' => $request->input('order_id')
                    ])->json();
                }
            );

            return response()->json([
                'status' => 'success',
                'payment' => $payment,
                'shipping' => $shipping
            ]);
        });
    }
}
``` 