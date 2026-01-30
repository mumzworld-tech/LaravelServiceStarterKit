<?php

namespace App\Models;

use BaoPham\DynamoDb\DynamoDbModel;
use Illuminate\Support\Str;

class ExampleModelDynamoDB extends DynamoDbModel
{
    /**
     * The DynamoDB table associated with the model.
     *
     * @var string
     */
    protected $table = 'example_model_dynamodb';

    /**
     * The primary key for the model (DynamoDB Hash Key).
     *
     * @var string
     */
    protected $primaryKey = 'example_id';

    /**
     * The "type" of the primary key ID.
     * For DynamoDB, this is typically 'string' (S), 'number' (N), or 'binary' (B).
     *
     * @var string
     */
    protected $keyType = 'string'; // Or 'number', 'binary'

    /**
     * Indicates if the model's ID is auto-incrementing.
     * DynamoDB does not support auto-incrementing keys.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped (created_at/updated_at).
     * Set to true if you want DynamoDB to automatically handle these as attributes.
     * We will manage them manually for this example.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'example_id',
        'name',
        'item_count',
        'is_enabled',
        'settings',
        'created_at',
        'last_processed_at',
    ];

    /**
     * The attributes that should be cast to native types or custom classes.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'item_count' => 'integer',
        'is_enabled' => 'boolean',
        'settings' => 'array', // For JSON string to array casting
        'created_at' => 'datetime', // For ISO8601 string to Carbon instance
        'last_processed_at' => 'datetime',
    ];

    /**
     * Defines the Global Secondary Indexes (GSIs) for the DynamoDB table.
     * Example:
     * 'index-name' => [
     *     'hash' => 'gsi_hash_key_attribute',
     *     // 'range' => 'gsi_range_key_attribute', // Optional range key
     * ],
     *
     * @var array<string, array<string, string>>
     */
    protected $dynamoDbIndexKeys = [
        'name-index' => [
            'hash' => 'name',
        ]
    ];

    /**
     * Create a new model instance.
     *
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->table = config('app.dynamodb_example_table', 'example_model_dynamodb');
        
        parent::__construct($attributes);

        // Set default for primary key if it's not auto-incrementing and not provided
        if (!$this->incrementing && !isset($attributes[$this->getKeyName()])) {
            $this->setAttribute($this->getKeyName(), (string) Str::uuid());
        }

        // If managing timestamps manually (when $timestamps = false):
        if (!isset($attributes['created_at'])) {
            $this->setAttribute('created_at', now()->toIso8601String());
        }
        // last_processed_at can be null by default or set when an action occurs
    }

    // Example Accessor for settings to ensure it's always an array even if null in DB
    public function getSettingsAttribute($value): array
    {
        // The 'array' cast already handles JSON decoding.
        // This accessor is an example if you needed more complex logic or default.
        return is_array($value) ? $value : (json_decode($value, true) ?: []);
    }

    // Example Mutator for settings to ensure it's stored as JSON
    public function setSettingsAttribute($value): void
    {
        // The 'array' cast already handles JSON encoding.
        // This mutator is an example if you needed more complex logic.
        $this->attributes['settings'] = is_array($value) ? json_encode($value) : $value;
    }

    // Example Scope:
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
} 