<?php

namespace App\DynamoDb;

use BaoPham\DynamoDb\DynamoDbModel;

/**
 * Trait PreventsDynamoDbScans
 *
 * Use this trait in your DynamoDB models to prevent accidental scan operations.
 *
 * Configuration options (set as model properties):
 * - $failOnScan: bool - Throw exception on scan (default: uses config)
 * - $logScans: bool - Log scan operations (default: true)
 *
 * Query-level options:
 * - ->failOnScan() - Enable fail-on-scan for this query
 * - ->allowScan() - Explicitly allow scan for this query
 * - ->withScanContext('description') - Add context for debugging
 */
trait PreventsDynamoDbScans
{
    /**
     * Whether to fail on scan operations for this model.
     * Override in your model to change behavior.
     */
    protected bool $failOnScan = false;

    /**
     * Whether to log scan operations for this model.
     * Override in your model to change behavior.
     */
    protected bool $logScans = true;

    /**
     * Create a new DynamoDB query builder for the model.
     *
     * @return ScanGuardedQueryBuilder
     */
    public function newQuery(): ScanGuardedQueryBuilder
    {
        /** @var DynamoDbModel $this */
        $builder = new ScanGuardedQueryBuilder($this);

        if ($this->failOnScan) {
            $builder->failOnScan(true);
        }

        if (!$this->logScans) {
            $builder->withoutScanLogging();
        }

        // Apply global scopes
        foreach ($this->getGlobalScopes() as $identifier => $scope) {
            $builder->withGlobalScope($identifier, $scope);
        }

        return $builder;
    }

    /**
     * Enable fail-on-scan mode for this model instance.
     */
    public function enableFailOnScan(): static
    {
        $this->failOnScan = true;
        return $this;
    }

    /**
     * Disable fail-on-scan mode for this model instance.
     */
    public function disableFailOnScan(): static
    {
        $this->failOnScan = false;
        return $this;
    }

    /**
     * Enable scan logging for this model instance.
     */
    public function enableScanLogging(): static
    {
        $this->logScans = true;
        return $this;
    }

    /**
     * Disable scan logging for this model instance.
     */
    public function disableScanLogging(): static
    {
        $this->logScans = false;
        return $this;
    }

    /**
     * Static method to enable fail-on-scan globally for this model class.
     * Useful in test setup.
     */
    public static function failOnScanGlobally(): void
    {
        config(['dynamodb.fail_on_scan' => true]);
    }

    /**
     * Static method to disable fail-on-scan globally.
     */
    public static function allowScansGlobally(): void
    {
        config(['dynamodb.fail_on_scan' => false]);
    }
}
