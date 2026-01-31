# Mumzworld Laravel Service Starter Kit

This project serves as the Mumzworld Dockerized starter kit for building Laravel-based services. It comes pre-configured with a comprehensive Docker environment to support local development and provides a solid foundation for building robust API and worker services.

The aim is to provide a quick and consistent setup for Mumzworld developers, including common services like MySQL, Redis, DynamoDB (local), and Horizon for background jobs, along with useful development tools.

## Dockerized Development Environment

This starter kit includes a multi-container Docker setup managed by `docker-compose.yml`. The environment is configurable via a `.env` file (copy `.env.example` to `.env` to get started).

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
*   **`otel-collector`**: OpenTelemetry Collector for receiving and processing traces.
*   **`tempo`**: Grafana Tempo for storing and querying distributed traces.
*   **`grafana`**: Grafana dashboard for visualizing traces and monitoring.
*   **`phpmyadmin`**: Web UI for managing the MySQL database.
*   **`redis-commander`**: Web UI for managing the Redis data store.
*   **`dynamodb-admin`**: Web UI for managing the local DynamoDB data.

### Admin Interfaces

Once the containers are running, you can access the following admin interfaces:

*   **Main Application**: http://localhost:80 (default port, configurable via `DOCKER_APP_HTTP_PORT`)
*   **Grafana Dashboard**: http://localhost:3001 (admin/admin) - for viewing distributed traces
*   **phpMyAdmin**: http://localhost:8080 (configurable via `DOCKER_PHPMYADMIN_HOST_PORT`)
*   **Redis Commander**: http://localhost:8081 (configurable via `DOCKER_REDIS_COMMANDER_HOST_PORT`)
*   **DynamoDB Admin**: http://localhost:8001 (configurable via `DOCKER_DYNAMODB_ADMIN_HOST_PORT`)
*   **Laravel Horizon**: http://localhost:80/horizon (requires setting up Horizon routes)

### Getting Started with Docker:

1.  Ensure Docker Desktop (or Docker Engine with Compose V2) is installed and running.
2.  Copy `.env.example` to `.env` and customize as needed:
    ```bash
    cp .env.example .env
    ```
    **Important**: Add your GitHub token to access private repositories:
    ```env
    GITHUB_TOKEN=your_github_token_here
    ```
    The OpenTelemetry configuration is pre-configured but can be customized:
    ```env
    OTEL_SERVICE_NAME=laravel-starter-kit-service
    OTEL_TRACES_EXPORTER=otlp
    OTEL_EXPORTER_OTLP_ENDPOINT=http://otel-collector:4318
    OTEL_PROPAGATORS=baggage,tracecontext
    OTEL_PHP_AUTOLOAD_ENABLED=true
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

## Log Configuration

This starter kit is configured to output all logs in JSON format to stdout, making it compatible with container orchestration platforms and centralized logging systems.

### Logging Features:

- JSON-formatted logs for structured parsing
- All application logs sent to stdout
- Both Laravel and PHP native errors captured in the same format
- Log configuration for both main application and Horizon workers

### PHP Error Configuration:

The PHP configuration is set up to ensure all errors are properly logged:

```ini
log_errors = On
error_log = /dev/stdout
display_errors = Off
display_startup_errors = Off
html_errors = Off
error_reporting = E_ALL
```

## Debug Tools

The starter kit includes a Debug Controller that helps test error handling, logging configurations, and monitoring integrations.

### Debug Controller Features:

The Debug Controller (`app/Http/Controllers/DebugController.php`) provides endpoints to trigger various types of errors and exceptions:

- **/debug**: Triggers all error types at once in a "chaos error" scenario
- **/debug/division-by-zero**: Triggers a division by zero error
- **/debug/undefined-variable**: Triggers an undefined variable error
- **/debug/type-error**: Triggers a type error
- **/debug/out-of-bounds**: Triggers an array out of bounds error
- **/debug/logic-exception**: Throws a LogicException
- **/debug/runtime-exception**: Throws a RuntimeException
- **/debug/query-exception**: Triggers a database query exception
- **/debug/http-exception**: Throws an HTTP exception
- **/debug/memory-limit**: Simulates hitting memory limit
- **/debug/parse-error-example**: Shows example of parse error
- **/debug/fatal-error**: Triggers a fatal error
- **/debug/custom-exception**: Throws a custom exception
- **/debug/random-error**: Randomly triggers one of the above errors

### Using the Debug Controller:

Use these endpoints to:
1. Test your logging configuration
2. Verify error capture in monitoring systems
3. Check exception handling middleware
4. Ensure errors are properly formatted in JSON

**Warning**: These endpoints intentionally trigger errors and should only be used in development/testing environments.

## Working with DynamoDB

This starter kit includes AWS DynamoDB Local for development, along with the necessary tools to create models and run migrations.

### Environment-Specific Table Names

DynamoDB table names can be configured via environment variables to support different table names across environments (staging, production, etc.).

**Configuration:**

Add table name variables to your `.env` file:
```env
DYNAMODB_EXAMPLE_TABLE=example_model_dynamodb
```

The starter kit includes an example model that demonstrates this pattern. When creating your own models, follow this approach:

1. **Add to `config/app.php`:**
```php
'dynamodb_your_table' => env('DYNAMODB_YOUR_TABLE', 'default_table_name'),
```

2. **Update your model constructor:**
```php
public function __construct(array $attributes = [])
{
    $this->table = config('app.dynamodb_your_table', 'default_table_name');
    parent::__construct($attributes);
}
```

3. **Update migrations:**
```php
$tableName = config('app.dynamodb_your_table', 'default_table_name');
```

This allows you to use different table names per environment:
- Development: `dev_your_table`
- Staging: `staging_your_table`
- Production: `prod_your_table`

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

## OpenTelemetry & Distributed Tracing

This starter kit includes comprehensive OpenTelemetry integration using the `mumzworld/laravel-opentelemetry` package for distributed tracing and observability.

### Features

- ✅ **Automatic Tracing**: HTTP requests, DynamoDB operations, cache operations
- ✅ **Custom Tracing**: TracerService for business logic tracing
- ✅ **Complete Observability Stack**: Collector, Tempo, Grafana
- ✅ **Production Ready**: Configurable sampling and performance optimizations

### Quick Start

1. **Ensure GitHub token is set** in your `.env` file (required for private package access)
2. **Start services** with `docker compose up --build -d`
3. **Access Grafana** at http://localhost:3001 (admin/admin)
4. **Test tracing** with built-in endpoints:

```bash
# Test OpenTelemetry functionality
curl http://localhost/api/opentelemetry/test

# View configuration
curl http://localhost/api/opentelemetry/config

# Test nested spans
curl http://localhost/api/opentelemetry/nested
```

### Custom Tracing Example

```php
use Mumzworld\LaravelOpenTelemetry\Services\TracerService;

class UserService 
{
    public function __construct(private TracerService $tracer) {}
    
    public function createUser(array $data): User
    {
        return $this->tracer->trace('user.create', function() use ($data) {
            return User::create($data);
        }, [
            'user.email' => $data['email'],
            'user.type' => $data['type'] ?? 'regular'
        ]);
    }
}
```

### Viewing Traces

1. **Open Grafana**: http://localhost:3001
2. **Navigate**: Explore → Tempo
3. **Query traces**: `{service.name="laravel-starter-kit-service"}`

### GitHub Token Requirement

The `mumzworld/laravel-opentelemetry` package is hosted in a private repository. **You must provide a GitHub token** in your `.env` file:

```env
GITHUB_TOKEN=your_github_token_here
```

**Fallback Behavior**: If no token is provided, Docker build will continue without the OpenTelemetry package (graceful degradation).

### Detailed Setup

For comprehensive setup instructions, configuration options, and troubleshooting, see [docs/OPENTELEMETRY_SETUP.md](docs/OPENTELEMETRY_SETUP.md).

## Architecture Notes

This starter kit is designed as a microservice template with the following architecture:

### Data Storage
- **DynamoDB**: Primary data storage (configured via environment variables)
- **Redis**: Caching, sessions, and queue management
- **MySQL**: Available but optional (not required for DynamoDB-only services)

### Authentication
This starter kit does not include user authentication by default, as it's designed for microservices that:
- Receive user context from external systems (API gateways, auth services)
- Don't manage user sessions directly
- Focus on business logic rather than authentication

If your service requires authentication, you can add Laravel Sanctum or Passport as needed.

### Removed Components
The following Laravel defaults have been removed to keep the starter kit lean:
- MySQL user migrations (users, password_resets tables)
- User model and factory
- Sanctum authentication routes
- Default database seeders

This keeps the focus on DynamoDB-based microservices while maintaining the flexibility to add these components back if needed.

