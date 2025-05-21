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
                    'AttributeName' => 'example_id',          // Table HASH Key
                    'AttributeType' => 'S' 
                ],
                [
                    'AttributeName' => 'name',                  // GSI HASH Key for name-index
                    'AttributeType' => 'S'
                ]
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
                    // For PAY_PER_REQUEST tables, ProvisionedThroughput for GSIs is not specified.
                ]
            ],
            'BillingMode' => 'PAY_PER_REQUEST',
            'StreamSpecification' => [
                'StreamEnabled' => true,
                'StreamViewType' => 'NEW_AND_OLD_IMAGES'
            ]
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