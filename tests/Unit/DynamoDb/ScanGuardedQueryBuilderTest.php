<?php

namespace Tests\Unit\DynamoDb;

use App\DynamoDb\ScanGuardedQueryBuilder;
use App\Models\ExampleModelDynamoDB;
use BaoPham\DynamoDb\RawDynamoDbQuery;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class ScanGuardedQueryBuilderTest extends TestCase
{
    /**
     * Test that ScanGuardedQueryBuilder can be instantiated.
     */
    public function test_can_instantiate_scan_guarded_query_builder(): void
    {
        $model = $this->createMock(ExampleModelDynamoDB::class);
        $model->method('getClient')->willReturn(null);
        $model->method('getTable')->willReturn('test_table');

        $builder = new ScanGuardedQueryBuilder($model);

        $this->assertInstanceOf(ScanGuardedQueryBuilder::class, $builder);
    }

    /**
     * Test failOnScan method returns self for chaining.
     */
    public function test_fail_on_scan_returns_self_for_chaining(): void
    {
        $model = $this->createMock(ExampleModelDynamoDB::class);
        $model->method('getClient')->willReturn(null);
        $model->method('getTable')->willReturn('test_table');

        $builder = new ScanGuardedQueryBuilder($model);
        $result = $builder->failOnScan(true);

        $this->assertSame($builder, $result);
    }

    /**
     * Test allowScan method returns self for chaining.
     */
    public function test_allow_scan_returns_self_for_chaining(): void
    {
        $model = $this->createMock(ExampleModelDynamoDB::class);
        $model->method('getClient')->willReturn(null);
        $model->method('getTable')->willReturn('test_table');

        $builder = new ScanGuardedQueryBuilder($model);
        $result = $builder->allowScan();

        $this->assertSame($builder, $result);
    }

    /**
     * Test withScanContext method returns self for chaining.
     */
    public function test_with_scan_context_returns_self_for_chaining(): void
    {
        $model = $this->createMock(ExampleModelDynamoDB::class);
        $model->method('getClient')->willReturn(null);
        $model->method('getTable')->willReturn('test_table');

        $builder = new ScanGuardedQueryBuilder($model);
        $result = $builder->withScanContext('test context');

        $this->assertSame($builder, $result);
    }

    /**
     * Test withoutScanLogging method returns self for chaining.
     */
    public function test_without_scan_logging_returns_self_for_chaining(): void
    {
        $model = $this->createMock(ExampleModelDynamoDB::class);
        $model->method('getClient')->willReturn(null);
        $model->method('getTable')->willReturn('test_table');

        $builder = new ScanGuardedQueryBuilder($model);
        $result = $builder->withoutScanLogging();

        $this->assertSame($builder, $result);
    }

    /**
     * Test newQuery preserves failOnScan setting.
     */
    public function test_new_query_preserves_fail_on_scan_setting(): void
    {
        $model = $this->createMock(ExampleModelDynamoDB::class);
        $model->method('getClient')->willReturn(null);
        $model->method('getTable')->willReturn('test_table');

        $builder = new ScanGuardedQueryBuilder($model);
        $builder->failOnScan(true);

        $newBuilder = $builder->newQuery();

        $this->assertInstanceOf(ScanGuardedQueryBuilder::class, $newBuilder);
    }

    /**
     * Test scan detection logs warning when log_scans is enabled.
     */
    public function test_scan_detection_logs_warning(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'DynamoDB SCAN operation detected'
                    && isset($context['operation'])
                    && $context['operation'] === 'Scan';
            });

        $model = $this->createMock(ExampleModelDynamoDB::class);
        $model->method('getClient')->willReturn(null);
        $model->method('getTable')->willReturn('test_table');
        $model->method('getDynamoDbIndexKeys')->willReturn([]);

        $builder = new TestableScanGuardedQueryBuilder($model);

        // Simulate a scan operation
        $rawQuery = new RawDynamoDbQuery('Scan', ['TableName' => 'test_table']);
        $builder->simulateScanDetection($rawQuery);
    }

    /**
     * Test scan detection throws exception when failOnScan is enabled.
     */
    public function test_scan_detection_throws_exception_when_fail_on_scan_enabled(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DynamoDB Scan operation detected');

        Log::shouldReceive('warning')->once();

        $model = $this->createMock(ExampleModelDynamoDB::class);
        $model->method('getClient')->willReturn(null);
        $model->method('getTable')->willReturn('test_table');
        $model->method('getDynamoDbIndexKeys')->willReturn([]);

        $builder = new TestableScanGuardedQueryBuilder($model);
        $builder->failOnScan(true);

        // Simulate a scan operation
        $rawQuery = new RawDynamoDbQuery('Scan', ['TableName' => 'test_table']);
        $builder->simulateScanDetection($rawQuery);
    }

    /**
     * Test scan detection does not throw when allowScan is called.
     */
    public function test_scan_detection_does_not_throw_when_allow_scan_called(): void
    {
        // allowScan disables both logging and exceptions
        Log::shouldReceive('warning')->never();

        $model = $this->createMock(ExampleModelDynamoDB::class);
        $model->method('getClient')->willReturn(null);
        $model->method('getTable')->willReturn('test_table');
        $model->method('getDynamoDbIndexKeys')->willReturn([]);

        $builder = new TestableScanGuardedQueryBuilder($model);
        $builder->failOnScan(true);
        $builder->allowScan(); // This should override failOnScan

        // Simulate a scan operation - should not throw
        $rawQuery = new RawDynamoDbQuery('Scan', ['TableName' => 'test_table']);
        $builder->simulateScanDetection($rawQuery);

        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    /**
     * Test scan detection respects global config.
     */
    public function test_scan_detection_respects_global_config(): void
    {
        $this->expectException(RuntimeException::class);

        Log::shouldReceive('warning')->once();

        // Set global config to fail on scan
        config(['dynamodb.fail_on_scan' => true]);

        $model = $this->createMock(ExampleModelDynamoDB::class);
        $model->method('getClient')->willReturn(null);
        $model->method('getTable')->willReturn('test_table');
        $model->method('getDynamoDbIndexKeys')->willReturn([]);

        $builder = new TestableScanGuardedQueryBuilder($model);
        // Note: not calling failOnScan() - relying on global config

        $rawQuery = new RawDynamoDbQuery('Scan', ['TableName' => 'test_table']);
        $builder->simulateScanDetection($rawQuery);
    }

    /**
     * Test error message includes available indexes.
     */
    public function test_error_message_includes_available_indexes(): void
    {
        Log::shouldReceive('warning')->once();

        $model = $this->createMock(ExampleModelDynamoDB::class);
        $model->method('getClient')->willReturn(null);
        $model->method('getTable')->willReturn('test_table');
        $model->method('getDynamoDbIndexKeys')->willReturn([
            'name-index' => ['hash' => 'name'],
            'status-index' => ['hash' => 'status'],
        ]);

        $builder = new TestableScanGuardedQueryBuilder($model);
        $builder->failOnScan(true);

        try {
            $rawQuery = new RawDynamoDbQuery('Scan', ['TableName' => 'test_table']);
            $builder->simulateScanDetection($rawQuery);
            $this->fail('Expected RuntimeException was not thrown');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('name-index', $e->getMessage());
            $this->assertStringContainsString('status-index', $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        // Reset config
        config(['dynamodb.fail_on_scan' => false]);
        parent::tearDown();
    }
}

/**
 * Testable version that exposes protected methods for testing.
 */
class TestableScanGuardedQueryBuilder extends ScanGuardedQueryBuilder
{
    public function simulateScanDetection(RawDynamoDbQuery $raw): void
    {
        $this->handleScanDetected($raw);
    }
}
