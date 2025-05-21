<?php

namespace App\Services\Telemetry;

use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\API\Globals;
use OpenTelemetry\Context\ScopeInterface;

class TracerService
{
    private TracerInterface $tracer;
    private ?SpanInterface $currentSpan = null;
    private ?ScopeInterface $currentScope = null;
    private ?float $spanStartTime = null;

    public function __construct(TracerProviderInterface $provider)
    {
        $this->tracer = $provider->getTracer('my-tracer');
    }

    /**
     * Start a new trace span
     *
     * @param string $name Span name
     * @param array $attributes Initial attributes
     * @param int $kind Span kind (default: SERVER)
     * @return SpanInterface
     */
    public function startSpan(string $name, array $attributes = [], int $kind = SpanKind::KIND_SERVER): SpanInterface
    {
        // Clean up any existing span and scope
        $this->cleanupCurrentSpan();

        $parent = Context::getCurrent();
        $this->currentSpan = $this->tracer->spanBuilder($name)
            ->setParent($parent)
            ->setSpanKind($kind)
            ->setAttributes($attributes)
            ->startSpan();

        // Activate the span and store the scope
        $this->currentScope = $this->currentSpan->activate();

        // Store start time
        $this->spanStartTime = microtime(true);

        // Add standard attributes
        $this->addAttributes([
            'span.kind' => $kind,
        ]);

        return $this->currentSpan;
    }

    /**
     * Add attributes to the current span
     *
     * @param array $attributes
     * @return void
     */
    public function addAttributes(array $attributes): void
    {
        if ($this->currentSpan) {
            foreach ($attributes as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    // For arrays and objects, only store essential information
                    if (is_array($value)) {
                        $value = count($value) . ' items';
                    } else {
                        $value = get_class($value);
                    }
                } elseif (is_string($value) && strlen($value) > 1024) {
                    // Truncate long strings
                    $value = substr($value, 0, 1024) . '... (truncated)';
                }
                $this->currentSpan->setAttribute($key, $value);
            }
        }
    }

    /**
     * Record an exception in the current span
     *
     * @param \Throwable $exception
     * @return void
     */
    public function recordException(\Throwable $exception): void
    {
        if ($this->currentSpan) {
            $this->currentSpan->recordException($exception, [
                'exception.type' => get_class($exception),
                'exception.message' => $exception->getMessage(),
                'exception.stack_trace' => $exception->getTraceAsString(),
                'exception.code' => $exception->getCode(),
                'exception.file' => $exception->getFile(),
                'exception.line' => $exception->getLine(),
            ]);
            $this->currentSpan->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
        }
    }

    /**
     * End the current span
     *
     * @param array $finalAttributes Additional attributes to add before ending
     * @return void
     */
    public function endSpan(array $finalAttributes = []): void
    {
        if ($this->currentSpan) {
            // Calculate duration
            $endTime = microtime(true);
            $duration = $this->spanStartTime ? ($endTime - $this->spanStartTime) * 1000 : null;

            $this->addAttributes([
                'duration_ms' => $duration,
            ]);

            // Add any final attributes
            if (!empty($finalAttributes)) {
                $this->addAttributes($finalAttributes);
            }

            $this->cleanupCurrentSpan();
        }
    }

    /**
     * Create a child span and execute a callback within its context
     *
     * @param string $name
     * @param callable $callback
     * @param array $attributes
     * @param int $kind
     * @return mixed
     */
    public function trace(string $name, callable $callback, array $attributes = [], int $kind = SpanKind::KIND_INTERNAL)
    {
        $span = $this->startSpan($name, $attributes, $kind);

        try {
            $result = $callback($span);
            return $result;
        } catch (\Throwable $e) {
            $this->recordException($e);
            throw $e;
        } finally {
            $this->endSpan();
        }
    }

    /**
     * Trace an HTTP request
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @param callable|null $callback
     * @return mixed
     */
    public function traceHttpRequest(string $method, string $url, array $options = [], ?callable $callback = null)
    {
        $spanName = "HTTP $method $url";
        $attributes = [
            'http.method' => $method,
            'http.url' => $url,
        ];

        return $this->trace($spanName, function (SpanInterface $span) use ($callback, $method, $url, $options) {
            $startTime = microtime(true);

            try {
                $result = $callback ? $callback($span) : null;

                // Record only essential response information
                if ($result) {
                    $this->addAttributes([
                        'http.status' => $result['status'] ?? null,
                        'http.duration_ms' => (microtime(true) - $startTime) * 1000,
                    ]);
                }

                return $result;
            } catch (\Throwable $e) {
                $this->addAttributes([
                    'http.error' => true,
                    'error.type' => get_class($e),
                    'error.message' => $e->getMessage(),
                ]);
                throw $e;
            }
        }, $attributes, SpanKind::KIND_CLIENT);
    }

    /**
     * Clean up the current span and its scope
     */
    private function cleanupCurrentSpan(): void
    {
        if ($this->currentScope) {
            $this->currentScope->detach();
            $this->currentScope = null;
        }

        if ($this->currentSpan) {
            $this->currentSpan->end();
            $this->currentSpan = null;
        }

        $this->spanStartTime = null;
    }

    /**
     * Get the current span
     */
    public function getCurrentSpan(): ?SpanInterface
    {
        return $this->currentSpan;
    }

    /**
     * Get the current trace ID
     */
    public function getCurrentTraceId(): ?string
    {
        return $this->currentSpan ? $this->currentSpan->getContext()->getTraceId() : null;
    }
}
