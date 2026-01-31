<?php

namespace App\DynamoDb;

use BaoPham\DynamoDb\DynamoDbQueryBuilder;
use BaoPham\DynamoDb\RawDynamoDbQuery;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ScanGuardedQueryBuilder extends DynamoDbQueryBuilder
{
    /**
     * Whether to fail on scan operations.
     */
    protected bool $failOnScan = false;

    /**
     * Whether to log scan operations.
     */
    protected bool $logScans = true;

    /**
     * Context for logging/error messages.
     */
    protected string $scanContext = '';

    /**
     * Enable failing on scan operations.
     */
    public function failOnScan(bool $enable = true): static
    {
        $this->failOnScan = $enable;
        return $this;
    }

    /**
     * Disable scan logging.
     */
    public function withoutScanLogging(): static
    {
        $this->logScans = false;
        return $this;
    }

    /**
     * Set context for scan detection (useful for debugging).
     */
    public function withScanContext(string $context): static
    {
        $this->scanContext = $context;
        return $this;
    }

    /**
     * Allow scan for this specific query (explicit opt-in).
     */
    public function allowScan(): static
    {
        $this->failOnScan = false;
        $this->logScans = false;
        return $this;
    }

    /**
     * Override toDynamoDbQuery to intercept and check for scans.
     */
    public function toDynamoDbQuery($columns = [], $limit = DynamoDbQueryBuilder::MAX_LIMIT): RawDynamoDbQuery
    {
        $raw = parent::toDynamoDbQuery($columns, $limit);

        if ($raw->op === 'Scan') {
            $this->handleScanDetected($raw);
        }

        return $raw;
    }

    /**
     * Handle detected scan operation.
     */
    protected function handleScanDetected(RawDynamoDbQuery $raw): void
    {
        $table = $this->getModel()->getTable();
        $modelClass = get_class($this->getModel());

        $data = [
            'operation' => $raw->op,
            'table' => $table,
            'model' => $modelClass,
            'context' => $this->scanContext,
            'wheres' => $this->wheres,
            'has_limit' => isset($this->limit),
            'limit' => $this->limit ?? 'unlimited',
            'trace' => $this->getRelevantStackTrace(),
        ];

        if ($this->logScans) {
            Log::warning('DynamoDB SCAN operation detected', $data);
        }

        if ($this->shouldFailOnScan()) {
            $message = $this->buildScanErrorMessage($table, $modelClass);
            throw new RuntimeException($message);
        }
    }

    /**
     * Determine if we should fail on scan.
     */
    protected function shouldFailOnScan(): bool
    {
        // Check query-level setting first
        if ($this->failOnScan) {
            return true;
        }

        // Check global config
        return config('dynamodb.fail_on_scan', false);
    }

    /**
     * Build a helpful error message for scan detection.
     */
    protected function buildScanErrorMessage(string $table, string $modelClass): string
    {
        $message = "DynamoDB Scan operation detected on table '{$table}' (model: {$modelClass}).";

        if ($this->scanContext) {
            $message .= " Context: {$this->scanContext}.";
        }

        $message .= "\n\nPossible solutions:";
        $message .= "\n1. Add a where clause on the primary key (hash key)";
        $message .= "\n2. Use a Global Secondary Index (GSI) with ->withIndex('index-name')";
        $message .= "\n3. If scan is intentional, use ->allowScan() to bypass this check";

        $indexKeys = $this->getModel()->getDynamoDbIndexKeys();
        if (!empty($indexKeys)) {
            $message .= "\n\nAvailable indexes: " . implode(', ', array_keys($indexKeys));
        }

        return $message;
    }

    /**
     * Get relevant stack trace for debugging.
     */
    protected function getRelevantStackTrace(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);

        return array_values(array_filter($trace, function ($frame) {
            $file = $frame['file'] ?? '';

            // Skip vendor files except for useful context
            if (str_contains($file, '/vendor/') && !str_contains($file, '/app/')) {
                return false;
            }

            // Skip this class
            if (str_contains($file, 'ScanGuardedQueryBuilder.php')) {
                return false;
            }

            return true;
        }));
    }

    /**
     * Get a new instance of the query builder.
     */
    public function newQuery(): static
    {
        $query = new static($this->getModel());
        $query->failOnScan = $this->failOnScan;
        $query->logScans = $this->logScans;

        return $query;
    }
}
