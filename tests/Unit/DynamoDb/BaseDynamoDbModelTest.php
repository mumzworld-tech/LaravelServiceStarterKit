<?php

namespace Tests\Unit\DynamoDb;

use App\DynamoDb\BaseDynamoDbModel;
use App\DynamoDb\PreventsDynamoDbScans;
use App\DynamoDb\ScanGuardedQueryBuilder;
use Tests\TestCase;

class BaseDynamoDbModelTest extends TestCase
{
    /**
     * Test that BaseDynamoDbModel uses PreventsDynamoDbScans trait.
     */
    public function test_base_model_uses_prevents_dynamodb_scans_trait(): void
    {
        $traits = class_uses_recursive(BaseDynamoDbModel::class);

        $this->assertContains(PreventsDynamoDbScans::class, $traits);
    }

    /**
     * Test that BaseDynamoDbModel is not auto-incrementing.
     */
    public function test_base_model_is_not_auto_incrementing(): void
    {
        $model = new ConcreteBaseDynamoDbModel();

        $this->assertFalse($model->getIncrementing());
    }

    /**
     * Test that BaseDynamoDbModel key type is string.
     */
    public function test_base_model_key_type_is_string(): void
    {
        $model = new ConcreteBaseDynamoDbModel();

        $this->assertEquals('string', $model->getKeyType());
    }

    /**
     * Test that BaseDynamoDbModel does not use timestamps.
     */
    public function test_base_model_does_not_use_timestamps(): void
    {
        $model = new ConcreteBaseDynamoDbModel();

        $this->assertFalse($model->usesTimestamps());
    }

    /**
     * Test getAvailableIndexes returns index names.
     */
    public function test_get_available_indexes_returns_index_names(): void
    {
        $model = new ConcreteBaseDynamoDbModel();

        $indexes = $model->getAvailableIndexes();

        $this->assertIsArray($indexes);
        $this->assertContains('test-index', $indexes);
    }

    /**
     * Test isIndexedAttribute returns true for primary key.
     */
    public function test_is_indexed_attribute_returns_true_for_primary_key(): void
    {
        $model = new ConcreteBaseDynamoDbModel();

        $this->assertTrue($model->isIndexedAttribute('test_id'));
    }

    /**
     * Test isIndexedAttribute returns true for GSI hash key.
     */
    public function test_is_indexed_attribute_returns_true_for_gsi_hash_key(): void
    {
        $model = new ConcreteBaseDynamoDbModel();

        $this->assertTrue($model->isIndexedAttribute('name'));
    }

    /**
     * Test isIndexedAttribute returns false for non-indexed attribute.
     */
    public function test_is_indexed_attribute_returns_false_for_non_indexed_attribute(): void
    {
        $model = new ConcreteBaseDynamoDbModel();

        $this->assertFalse($model->isIndexedAttribute('some_random_field'));
    }

    /**
     * Test getIndexForAttribute returns index name for indexed attribute.
     */
    public function test_get_index_for_attribute_returns_index_name(): void
    {
        $model = new ConcreteBaseDynamoDbModel();

        $indexName = $model->getIndexForAttribute('name');

        $this->assertEquals('test-index', $indexName);
    }

    /**
     * Test getIndexForAttribute returns null for non-indexed attribute.
     */
    public function test_get_index_for_attribute_returns_null_for_non_indexed(): void
    {
        $model = new ConcreteBaseDynamoDbModel();

        $indexName = $model->getIndexForAttribute('some_random_field');

        $this->assertNull($indexName);
    }

    /**
     * Test newQuery returns ScanGuardedQueryBuilder.
     */
    public function test_new_query_returns_scan_guarded_query_builder(): void
    {
        $model = new ConcreteBaseDynamoDbModel();

        $query = $model->newQuery();

        $this->assertInstanceOf(ScanGuardedQueryBuilder::class, $query);
    }
}

/**
 * Concrete implementation of BaseDynamoDbModel for testing.
 */
class ConcreteBaseDynamoDbModel extends BaseDynamoDbModel
{
    protected $table = 'test_table';
    protected $primaryKey = 'test_id';

    protected $fillable = [
        'test_id',
        'name',
        'created_at',
    ];

    protected $dynamoDbIndexKeys = [
        'test-index' => [
            'hash' => 'name',
        ],
    ];
}
