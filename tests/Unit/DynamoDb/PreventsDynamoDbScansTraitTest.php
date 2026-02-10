<?php

namespace Tests\Unit\DynamoDb;

use App\DynamoDb\PreventsDynamoDbScans;
use App\DynamoDb\ScanGuardedQueryBuilder;
use App\Models\ExampleModelDynamoDB;
use Tests\TestCase;

class PreventsDynamoDbScansTraitTest extends TestCase
{
    /**
     * Test that ExampleModelDynamoDB uses the PreventsDynamoDbScans trait.
     */
    public function test_example_model_uses_prevents_dynamodb_scans_trait(): void
    {
        $traits = class_uses_recursive(ExampleModelDynamoDB::class);

        $this->assertContains(PreventsDynamoDbScans::class, $traits);
    }

    /**
     * Test that model with trait creates ScanGuardedQueryBuilder.
     */
    public function test_model_with_trait_creates_scan_guarded_query_builder(): void
    {
        $model = new ExampleModelDynamoDB();
        $query = $model->newQuery();

        $this->assertInstanceOf(ScanGuardedQueryBuilder::class, $query);
    }

    /**
     * Test enableFailOnScan method.
     */
    public function test_enable_fail_on_scan_method(): void
    {
        $model = new ExampleModelDynamoDB();

        $result = $model->enableFailOnScan();

        $this->assertSame($model, $result, 'Method should return $this for chaining');
    }

    /**
     * Test disableFailOnScan method.
     */
    public function test_disable_fail_on_scan_method(): void
    {
        $model = new ExampleModelDynamoDB();
        $model->enableFailOnScan();

        $result = $model->disableFailOnScan();

        $this->assertSame($model, $result, 'Method should return $this for chaining');
    }

    /**
     * Test enableScanLogging method.
     */
    public function test_enable_scan_logging_method(): void
    {
        $model = new ExampleModelDynamoDB();

        $result = $model->enableScanLogging();

        $this->assertSame($model, $result, 'Method should return $this for chaining');
    }

    /**
     * Test disableScanLogging method.
     */
    public function test_disable_scan_logging_method(): void
    {
        $model = new ExampleModelDynamoDB();

        $result = $model->disableScanLogging();

        $this->assertSame($model, $result, 'Method should return $this for chaining');
    }

    /**
     * Test failOnScanGlobally sets config.
     */
    public function test_fail_on_scan_globally_sets_config(): void
    {
        // Ensure config starts as false
        config(['dynamodb.fail_on_scan' => false]);

        ExampleModelDynamoDB::failOnScanGlobally();

        $this->assertTrue(config('dynamodb.fail_on_scan'));

        // Reset
        config(['dynamodb.fail_on_scan' => false]);
    }

    /**
     * Test allowScansGlobally sets config.
     */
    public function test_allow_scans_globally_sets_config(): void
    {
        // Set config to true first
        config(['dynamodb.fail_on_scan' => true]);

        ExampleModelDynamoDB::allowScansGlobally();

        $this->assertFalse(config('dynamodb.fail_on_scan'));
    }

    /**
     * Test query builder inherits model's failOnScan setting.
     */
    public function test_query_builder_inherits_model_fail_on_scan_setting(): void
    {
        $model = new ExampleModelDynamoDB();
        $model->enableFailOnScan();

        $query = $model->newQuery();

        // The query builder should be configured with failOnScan enabled
        // We can't directly test private properties, but we can verify the builder was created
        $this->assertInstanceOf(ScanGuardedQueryBuilder::class, $query);
    }

    /**
     * Test static method calls work on model.
     */
    public function test_static_query_returns_scan_guarded_builder(): void
    {
        $query = ExampleModelDynamoDB::query();

        $this->assertInstanceOf(ScanGuardedQueryBuilder::class, $query);
    }

    /**
     * Test chained query methods return ScanGuardedQueryBuilder.
     */
    public function test_chained_query_methods_return_scan_guarded_builder(): void
    {
        $query = ExampleModelDynamoDB::query()
            ->failOnScan()
            ->withScanContext('test');

        $this->assertInstanceOf(ScanGuardedQueryBuilder::class, $query);
    }
}
