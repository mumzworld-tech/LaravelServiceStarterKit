<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use BaoPham\DynamoDb\DynamoDbClientService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $client = app(DynamoDbClientService::class)->getClient();
        $tableName = 'example_model_dynamodb';

        $client->createTable([
            'TableName' => $tableName,
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'example_id', // Primary key
                    'AttributeType' => 'S' 
                ],
                [
                    'AttributeName' => 'name', // For GSI
                    'AttributeType' => 'S'
                ],
                [
                    'AttributeName' => 'item_count', // Example number attribute
                    'AttributeType' => 'N'
                ],
                [
                    'AttributeName' => 'is_enabled', // Example boolean (stored as string)
                    'AttributeType' => 'S' 
                ],
                [
                    'AttributeName' => 'last_processed_at', // Example datetime string
                    'AttributeType' => 'S'
                ]
                // 'settings' (array/JSON) does not need to be in AttributeDefinitions unless it's a key
            ],
            'KeySchema' => [
                [
                    'AttributeName' => 'example_id',
                    'KeyType' => 'HASH' 
                ]
            ],
            'GlobalSecondaryIndexes' => [
                [
                    'IndexName' => 'name-index',
                    'KeySchema' => [
                        [
                            'AttributeName' => 'name',
                            'KeyType' => 'HASH'
                        ]
                    ],
                    'Projection' => [
                        'ProjectionType' => 'ALL' 
                    ],
                    // BillingMode for GSIs defaults to that of the table if PAY_PER_REQUEST.
                    // If table is PROVISIONED, GSIs also need ProvisionedThroughput.
                ]
            ],
            'BillingMode' => 'PAY_PER_REQUEST'
        ]);

        // Wait until the table is created
        $client->waitUntil('TableExists', [
            'TableName' => $tableName
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $client = app(DynamoDbClientService::class)->getClient();
        $tableName = 'example_model_dynamodb';

        $client->deleteTable([
            'TableName' => $tableName
        ]);

        // Wait until the table is deleted
        $client->waitUntil('TableNotExists', [
            'TableName' => $tableName
        ]);
    }
}; 