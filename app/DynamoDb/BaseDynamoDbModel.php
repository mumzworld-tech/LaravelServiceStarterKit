<?php

namespace App\DynamoDb;

use BaoPham\DynamoDb\DynamoDbModel;
use Illuminate\Support\Str;

/**
 * Base DynamoDB Model with scan prevention built-in.
 *
 * Extend this class instead of DynamoDbModel to get automatic scan detection.
 */
abstract class BaseDynamoDbModel extends DynamoDbModel
{
    use PreventsDynamoDbScans;

    /**
     * Indicates if the model's ID is auto-incrementing.
     * DynamoDB does not support auto-incrementing keys.
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model should be timestamped.
     * Set to false as DynamoDB doesn't have built-in timestamp support.
     */
    public $timestamps = false;

    /**
     * Boot the model and set up auto-UUID generation.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            // Auto-generate UUID if primary key is not set
            if (!$model->incrementing && empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

            // Auto-set created_at if not present and the model allows it
            if ($model->isFillable('created_at') && empty($model->created_at)) {
                $model->created_at = now()->toIso8601String();
            }
        });
    }

    /**
     * Get a list of available indexes for this model.
     */
    public function getAvailableIndexes(): array
    {
        return array_keys($this->getDynamoDbIndexKeys());
    }

    /**
     * Check if a given attribute is indexed (either primary key or GSI).
     */
    public function isIndexedAttribute(string $attribute): bool
    {
        // Check primary key
        if ($attribute === $this->getKeyName()) {
            return true;
        }

        // Check GSI hash/range keys
        foreach ($this->getDynamoDbIndexKeys() as $indexConfig) {
            if (($indexConfig['hash'] ?? null) === $attribute) {
                return true;
            }
            if (($indexConfig['range'] ?? null) === $attribute) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get index name for a given attribute if it exists.
     */
    public function getIndexForAttribute(string $attribute): ?string
    {
        foreach ($this->getDynamoDbIndexKeys() as $indexName => $indexConfig) {
            if (($indexConfig['hash'] ?? null) === $attribute) {
                return $indexName;
            }
        }

        return null;
    }
}
