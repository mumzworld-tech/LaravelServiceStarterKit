<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default DynamoDb Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the DynamoDb connections below you wish
    | to use as your default connection for all DynamoDb work.
    */

    'default' => env('DYNAMODB_CONNECTION', 'local'),

    /*
    |--------------------------------------------------------------------------
    | DynamoDb Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the DynamoDb connections setup for your application.
    |
    | Most of the connection's config will be fed directly to AwsClient
    | constructor http://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.AwsClient.html#___construct
    */

    'connections' => [
        // This is your primary connection for deployed environments (e.g., AWS Lambda, EC2)
        // It will use IAM roles or standard AWS SDK credential chain (environment variables, shared config).
        'aws' => [
            // Credentials will be automatically discovered by the AWS SDK.
            // Ensure AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_SESSION_TOKEN (if applicable)
            // and AWS_DEFAULT_REGION are set in your production environment.
            'region' => env('AWS_DEFAULT_REGION', env('AWS_REGION')),
            'debug' => env('DYNAMODB_DEBUG', false),
            // 'endpoint' => env('DYNAMODB_ENDPOINT'), // Only set this if you are NOT using AWS proper (e.g. a custom endpoint)
        ],

        // Connection for local development using DynamoDB Local
        'local' => [
            'credentials' => [
                'key'    => 'dynamodblocal',
                'secret' => 'secret',
            ],
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'endpoint' => env('DYNAMODB_LOCAL_ENDPOINT', 'http://dynamodb:8000'), // Added default for safety
            'debug' => env('DYNAMODB_DEBUG', true),
        ],

        // Kept for reference if you used these specific env vars before
        'custom_aws_vars' => [
            'credentials' => [
                'key'    => env('DYNAMODB_KEY'),
                'secret' => env('DYNAMODB_SECRET'),
                'token'  => env('AWS_SESSION_TOKEN'),
            ],
            'region' => env('DYNAMODB_REGION'),
            'debug'  => env('DYNAMODB_DEBUG', false),
        ],

        'aws_iam_role' => [
            'region' => env('AWS_DEFAULT_REGION', env('AWS_REGION')),
            'debug' => env('DYNAMODB_DEBUG', false),
        ],

        'test' => [
            'credentials' => [
                'key'    => 'dynamodblocal',
                'secret' => 'secret',
            ],
            'region' => 'test-region', // Use a distinct region for tests
            'endpoint' => env('DYNAMODB_LOCAL_ENDPOINT_TEST', 'http://localhost:8001'), // Separate endpoint for tests if needed
            'debug'  => env('DYNAMODB_DEBUG', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scan Prevention Settings
    |--------------------------------------------------------------------------
    |
    | Configure how the application handles DynamoDB Scan operations.
    | Scans are expensive and should be avoided in production.
    |
    */

    // When true, throws an exception on any scan operation
    // Recommended: set to true in local/testing environments
    'fail_on_scan' => env('DYNAMODB_FAIL_ON_SCAN', false),

    // Log all scan operations (recommended for monitoring)
    'log_scans' => env('DYNAMODB_LOG_SCANS', true),
];
