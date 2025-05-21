# Mumzworld Laravel Service Starter Kit

This project serves as the Mumzworld Dockerized starter kit for building Laravel-based services. It comes pre-configured with a comprehensive Docker environment to support local development and provides a solid foundation for building robust API and worker services.

The aim is to provide a quick and consistent setup for Mumzworld developers, including common services like MySQL, Redis, DynamoDB (local), and Horizon for background jobs, along with useful development tools.

## Dockerized Development Environment

This starter kit includes a multi-container Docker setup managed by `docker-compose.yml`. The environment is configurable via a `.env` file (copy `example.env` to `.env` to get started).

### Included Services/Containers:

*   **`app`**: The main Laravel application container running with FrankenPHP.
    *   Serves the API.
    *   Runs Artisan commands.
    *   Configurable for development (`Dockerfile.dev` with Xdebug) and production (`Dockerfile.prod`).
*   **`horizon`**: A dedicated container for running Laravel Horizon queue workers, also using FrankenPHP.
    *   Configurable for development (`Dockerfile.dev`) and production (`Dockerfile.prod`).
*   **`mysql`**: MySQL database service.
*   **`redis`**: Redis in-memory data store (for caching, sessions, queues).
*   **`dynamodb`**: AWS DynamoDB Local instance for development purposes.
*   **`phpmyadmin`**: Web UI for managing the MySQL database.
*   **`redis-commander`**: Web UI for managing the Redis data store.
*   **`dynamodb-admin`**: Web UI for managing the local DynamoDB data.

### Admin Interfaces

Once the containers are running, you can access the following admin interfaces:

*   **Main Application**: http://localhost:80 (default port, configurable via `DOCKER_APP_HTTP_PORT`)
*   **phpMyAdmin**: http://localhost:8080 (configurable via `DOCKER_PHPMYADMIN_HOST_PORT`)
*   **Redis Commander**: http://localhost:8081 (configurable via `DOCKER_REDIS_COMMANDER_HOST_PORT`)
*   **DynamoDB Admin**: http://localhost:8001 (configurable via `DOCKER_DYNAMODB_ADMIN_HOST_PORT`)
*   **Laravel Horizon**: http://localhost:80/horizon (requires setting up Horizon routes)

### Getting Started with Docker:

1.  Ensure Docker Desktop (or Docker Engine with Compose V2) is installed and running.
2.  Copy `example.env` to `.env` and customize as needed (especially `APP_KEY` after first run, and any desired port changes).
    ```bash
    cp example.env .env
    ```
3.  Build and start the containers:
    ```bash
    docker compose up --build -d
    ```
4.  Generate the Laravel application key:
    ```bash
    docker compose exec app php artisan key:generate
    ```
5.  Run database migrations:
    ```bash
    # Run standard Laravel migrations for MySQL
    docker compose exec app php artisan migrate
    
    # Run DynamoDB migrations
    docker compose exec app php artisan migrate:dynamodb
    ```

Your Laravel API should now be accessible (typically at `http://localhost` or the port you configured for `DOCKER_APP_HTTP_PORT`). The admin UIs for databases will be on their respective configured ports.

Refer to `docs/infrastructure.md` for more detailed information on the Docker setup and environment variables.

## Working with DynamoDB

This starter kit includes AWS DynamoDB Local for development, along with the necessary tools to create models and run migrations.

### Creating DynamoDB Models

To create a new DynamoDB model class:

```bash
php artisan make:dynamodb-model YourModelName
```

This will generate a model file in `app/Models/YourModelName.php` that extends `BaoPham\DynamoDb\DynamoDbModel`. The model will include:

- Table name configuration
- Primary key definition
- Attribute definitions and casting
- Examples of accessor and mutator methods
- Index configuration options

Example model structure:
```php
class YourModelName extends DynamoDbModel
{
    protected $table = 'your_model_table_name';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    
    protected $fillable = [
        'id',
        'name',
        // other attributes
    ];
    
    // Global Secondary Indexes
    protected $dynamoDbIndexKeys = [
        'name-index' => [
            'hash' => 'name',
        ]
    ];
    
    // ... additional model methods
}
```

### Creating DynamoDB Migrations

To create a DynamoDB migration:

```bash
php artisan make:dynamodb-migration create_your_table_name
```

This will generate a migration file in `database/migrations_dynamodb/` with methods for creating and deleting a DynamoDB table:

Example migration structure:
```php
public function up(): void
{
    $client = app(DynamoDbClientService::class)->getClient();
    $tableName = 'your_table_name';

    $client->createTable([
        'TableName' => $tableName,
        'AttributeDefinitions' => [
            [
                'AttributeName' => 'id',
                'AttributeType' => 'S'
            ],
            // Define attributes used in key schema or indexes
        ],
        'KeySchema' => [
            [
                'AttributeName' => 'id',
                'KeyType' => 'HASH'
            ]
        ],
        'GlobalSecondaryIndexes' => [
            // Define any GSIs
        ],
        'BillingMode' => 'PAY_PER_REQUEST',
        // Other table settings
    ]);

    // Wait until the table is created
    $client->waitUntil('TableExists', [
        'TableName' => $tableName
    ]);
}
```

### Running DynamoDB Migrations

To run all DynamoDB migrations:

```bash
php artisan migrate:dynamodb
```

To run a specific migration:

```bash
php artisan migrate:dynamodb --file=YYYY_MM_DD_HHMMSS_create_your_table_name.php
```

To drop all tables and re-create them:

```bash
php artisan migrate:dynamodb --fresh
```

### Notes on DynamoDB Development

1. **AttributeDefinitions**: Only include attributes that are used in the table's key schema or indexes.
2. **Local Development Credentials**: DynamoDB Local uses hardcoded credentials:
   - Access Key: `dynamodblocal`
   - Secret Key: `secret`
3. **DYNAMODB_CONNECTION**: Set to `local` in your `.env` for development with DynamoDB Local.
4. **Endpoint**: For local development, the endpoint is configured as `http://dynamodb:8000` (Docker service name).

For more details on working with DynamoDB in Laravel, refer to the [baopham/laravel-dynamodb](https://github.com/baopham/laravel-dynamodb) documentation.
