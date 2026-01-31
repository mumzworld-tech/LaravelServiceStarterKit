<?php

namespace Tests\Unit\Models;

use App\Models\ExampleModelDynamoDB;
use Tests\TestCase;
use Illuminate\Support\Str;

class ExampleModelDynamoDBTest extends TestCase
{
    /**
     * Test that model has correct table name.
     */
    public function test_model_has_correct_table_name(): void
    {
        $model = new ExampleModelDynamoDB();

        $this->assertNotEmpty($model->getTable());
    }

    /**
     * Test that model has correct primary key.
     */
    public function test_model_has_correct_primary_key(): void
    {
        $model = new ExampleModelDynamoDB();

        $this->assertEquals('example_id', $model->getKeyName());
    }

    /**
     * Test that model primary key type is string.
     */
    public function test_model_primary_key_type_is_string(): void
    {
        $model = new ExampleModelDynamoDB();

        $this->assertEquals('string', $model->getKeyType());
    }

    /**
     * Test that model is not auto-incrementing.
     */
    public function test_model_is_not_auto_incrementing(): void
    {
        $model = new ExampleModelDynamoDB();

        $this->assertFalse($model->getIncrementing());
    }

    /**
     * Test that model does not use timestamps.
     */
    public function test_model_does_not_use_timestamps(): void
    {
        $model = new ExampleModelDynamoDB();

        $this->assertFalse($model->usesTimestamps());
    }

    /**
     * Test that model has correct fillable attributes.
     */
    public function test_model_has_correct_fillable_attributes(): void
    {
        $model = new ExampleModelDynamoDB();
        $fillable = $model->getFillable();

        $expectedFillable = [
            'example_id',
            'name',
            'item_count',
            'is_enabled',
            'settings',
            'created_at',
            'last_processed_at',
        ];

        $this->assertEquals($expectedFillable, $fillable);
    }

    /**
     * Test that model has correct casts.
     */
    public function test_model_has_correct_casts(): void
    {
        $model = new ExampleModelDynamoDB();
        $casts = $model->getCasts();

        $this->assertArrayHasKey('item_count', $casts);
        $this->assertArrayHasKey('is_enabled', $casts);
        $this->assertArrayHasKey('settings', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('last_processed_at', $casts);

        $this->assertEquals('integer', $casts['item_count']);
        $this->assertEquals('boolean', $casts['is_enabled']);
        $this->assertEquals('array', $casts['settings']);
        $this->assertEquals('datetime', $casts['created_at']);
        $this->assertEquals('datetime', $casts['last_processed_at']);
    }

    /**
     * Test settings accessor returns array when value is array.
     */
    public function test_settings_accessor_returns_array_when_value_is_array(): void
    {
        $model = new ExampleModelDynamoDB();
        $testSettings = ['key' => 'value', 'nested' => ['a' => 1]];

        $result = $model->getSettingsAttribute($testSettings);

        $this->assertIsArray($result);
        $this->assertEquals($testSettings, $result);
    }

    /**
     * Test settings accessor returns array when value is JSON string.
     */
    public function test_settings_accessor_returns_array_when_value_is_json_string(): void
    {
        $model = new ExampleModelDynamoDB();
        $testSettings = '{"key":"value","nested":{"a":1}}';

        $result = $model->getSettingsAttribute($testSettings);

        $this->assertIsArray($result);
        $this->assertEquals(['key' => 'value', 'nested' => ['a' => 1]], $result);
    }

    /**
     * Test settings accessor returns empty array when value is null.
     */
    public function test_settings_accessor_returns_empty_array_when_value_is_null(): void
    {
        $model = new ExampleModelDynamoDB();

        $result = $model->getSettingsAttribute(null);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test settings accessor returns empty array when value is invalid JSON.
     */
    public function test_settings_accessor_returns_empty_array_when_value_is_invalid_json(): void
    {
        $model = new ExampleModelDynamoDB();

        $result = $model->getSettingsAttribute('invalid json');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test settings mutator stores array as JSON.
     */
    public function test_settings_mutator_stores_array_as_json(): void
    {
        $model = new ExampleModelDynamoDB();
        $testSettings = ['key' => 'value'];

        $model->setSettingsAttribute($testSettings);

        $this->assertJson($model->getAttributes()['settings']);
        $this->assertEquals(json_encode($testSettings), $model->getAttributes()['settings']);
    }

    /**
     * Test settings mutator stores string as-is.
     */
    public function test_settings_mutator_stores_string_as_is(): void
    {
        $model = new ExampleModelDynamoDB();
        $testSettings = '{"key":"value"}';

        $model->setSettingsAttribute($testSettings);

        $this->assertEquals($testSettings, $model->getAttributes()['settings']);
    }

    /**
     * Test model generates UUID for primary key when not provided.
     */
    public function test_model_generates_uuid_for_primary_key_when_not_provided(): void
    {
        $model = new ExampleModelDynamoDB();

        $this->assertNotEmpty($model->getAttribute('example_id'));
        $this->assertTrue(Str::isUuid($model->getAttribute('example_id')));
    }

    /**
     * Test model uses provided primary key when given.
     */
    public function test_model_uses_provided_primary_key_when_given(): void
    {
        $customId = 'custom-id-123';
        $model = new ExampleModelDynamoDB(['example_id' => $customId]);

        $this->assertEquals($customId, $model->getAttribute('example_id'));
    }

    /**
     * Test model sets created_at when not provided.
     */
    public function test_model_sets_created_at_when_not_provided(): void
    {
        $model = new ExampleModelDynamoDB();

        $this->assertNotEmpty($model->getAttribute('created_at'));
    }

    /**
     * Test model uses provided created_at when given.
     */
    public function test_model_uses_provided_created_at_when_given(): void
    {
        $customCreatedAt = '2024-01-01T00:00:00+00:00';
        $model = new ExampleModelDynamoDB(['created_at' => $customCreatedAt]);

        $createdAt = $model->getAttribute('created_at');

        // The model casts created_at to datetime, so it becomes a Carbon instance
        if ($createdAt instanceof \Carbon\Carbon) {
            $this->assertEquals('2024-01-01 00:00:00', $createdAt->format('Y-m-d H:i:s'));
        } else {
            $this->assertEquals($customCreatedAt, $createdAt);
        }
    }
}
