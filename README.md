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

## Testing

This starter kit includes a comprehensive test suite with PHPUnit, covering unit tests, feature tests, and API tests.

### Running Tests

```bash
# Run all tests via Laravel's artisan command
composer test

# Run all tests via PHPUnit directly (suppresses OpenTelemetry warnings)
composer test:phpunit

# Run only unit tests
composer test:unit

# Run only feature tests
composer test:feature
```

### Test Coverage Reports

To generate code coverage reports, you need either **Xdebug** or **PCOV** PHP extension installed.

```bash
# Generate coverage with PCOV (recommended - faster)
composer test:coverage:pcov

# Generate coverage with Xdebug
composer test:coverage

# View HTML report in browser
open coverage/html/index.html
```

Coverage reports are generated in the `coverage/` directory:
- `coverage/html/index.html` - HTML report (open in browser for detailed view)
- `coverage/clover.xml` - Clover XML format (for CI/CD integration)

#### Installing PCOV (macOS with Homebrew PHP)

PCOV is faster than Xdebug for code coverage. To install on macOS:

```bash
# Install pcre2 dependency
brew install pcre2

# Build and install PCOV
cd /tmp && rm -rf pcov-build && mkdir pcov-build && cd pcov-build
curl -L https://pecl.php.net/get/pcov-1.0.12.tgz | tar xz
cd pcov-1.0.12
phpize
./configure CFLAGS="-I$(brew --prefix pcre2)/include"
make

# Copy to PHP extension directory
PHP_EXT_DIR=$(php -r "echo ini_get('extension_dir');")
mkdir -p "$PHP_EXT_DIR"
cp modules/pcov.so "$PHP_EXT_DIR/"

# Enable the extension
PHP_INI_DIR=$(php -r "echo PHP_CONFIG_FILE_SCAN_DIR;")
echo "extension=pcov.so" > "$PHP_INI_DIR/ext-pcov.ini"

# Verify installation
php -m | grep pcov
```

#### Installing Xdebug (Alternative)

```bash
pecl install xdebug

# Add to php.ini
echo "zend_extension=xdebug.so" >> $(php -r "echo php_ini_loaded_file();")
echo "xdebug.mode=coverage" >> $(php -r "echo php_ini_loaded_file();")
```

### Test Structure

```
tests/
├── TestCase.php                           # Base test class
├── Unit/
│   └── Models/
│       └── ExampleModelDynamoDBTest.php   # DynamoDB model tests
└── Feature/
    ├── HomePageTest.php                   # Home page tests
    ├── Api/
    │   └── HealthCheckTest.php            # API health endpoint tests
    ├── Http/
    │   └── Controllers/
    │       └── DebugControllerTest.php    # Debug endpoints tests
    └── Console/
        └── Commands/
            ├── DynamoDbMigrateTest.php        # Migration command tests
            ├── MakeDynamoDbMigrationTest.php  # Migration generator tests
            └── MakeDynamoDbModelTest.php      # Model generator tests
```

### Test Categories

| Category | Description | Command |
|----------|-------------|---------|
| Unit Tests | Test individual classes in isolation | `composer test:unit` |
| Feature Tests | Test HTTP endpoints and full request lifecycle | `composer test:feature` |
| API Tests | Test API endpoints (under Feature/Api) | `composer test:feature` |
| Command Tests | Test Artisan commands (under Feature/Console) | `composer test:feature` |

### Running Tests in Docker

```bash
# Run tests inside the app container
docker compose exec app composer test

# Run with coverage (requires Xdebug in Dockerfile.dev)
docker compose exec app composer test:coverage
```

### Writing New Tests

**Unit Test Example:**
```php
namespace Tests\Unit;

use Tests\TestCase;
use App\Models\YourModel;

class YourModelTest extends TestCase
{
    public function test_model_has_correct_fillable(): void
    {
        $model = new YourModel();
        $this->assertEquals(['field1', 'field2'], $model->getFillable());
    }
}
```

**Feature Test Example:**
```php
namespace Tests\Feature;

use Tests\TestCase;

class YourEndpointTest extends TestCase
{
    public function test_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/v1/your-endpoint');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'message']);
    }
}
```

### CI/CD Integration

This starter kit includes a GitHub Actions workflow for automated test coverage on pull requests.

#### Automatic Coverage on PR Review

A workflow is configured at `.github/workflows/test-coverage.yml` that:
1. **Triggers** when the `review` label is added to a PR
2. **Runs** the full test suite with PCOV coverage
3. **Posts** a coverage report as a comment on the PR

**To use:**
1. Create a PR with your changes
2. Add the `review` label to the PR
3. The workflow runs and posts coverage results as a comment

The comment includes:
- Coverage summary (Lines, Methods, Classes percentages)
- Expandable full coverage report
- Link to the GitHub Actions run

#### Manual GitHub Actions Example

```yaml
# Example GitHub Actions step for custom workflows
- name: Setup PHP with PCOV
  uses: shivammathur/setup-php@v2
  with:
    php-version: '8.2'
    extensions: mbstring, xml, pcov
    coverage: pcov

- name: Install dependencies
  run: composer install --ignore-platform-req=ext-opentelemetry

- name: Run Tests
  run: composer test:phpunit

# With coverage
- name: Run Tests with Coverage
  run: php -d pcov.enabled=1 vendor/bin/phpunit --coverage-clover coverage/clover.xml

- name: Upload Coverage to Codecov
  uses: codecov/codecov-action@v4
  with:
    files: coverage/clover.xml
```

### Notes

- **OpenTelemetry Warning**: When running tests locally without the OpenTelemetry PHP extension, you may see a warning. This is expected and doesn't affect test execution.
- **Database**: Tests use SQLite in-memory database by default (configured in `phpunit.xml`).
- **Environment**: Tests run with `APP_ENV=testing` and various services disabled (Telescope, Pulse, etc.).

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

### DynamoDB Scan Prevention

DynamoDB Scan operations are expensive and can cause performance issues and high costs. This starter kit includes built-in tools to detect and prevent accidental scan operations during development.

#### Enabling Scan Prevention

**Option 1: Use the trait in your models**

```php
use App\DynamoDb\PreventsDynamoDbScans;

class YourModel extends DynamoDbModel
{
    use PreventsDynamoDbScans;

    // Optional: fail immediately on scan (default: false)
    protected bool $failOnScan = true;

    // Optional: log scan operations (default: true)
    protected bool $logScans = true;
}
```

**Option 2: Extend the base model**

```php
use App\DynamoDb\BaseDynamoDbModel;

class YourModel extends BaseDynamoDbModel
{
    // Scan prevention is built-in
}
```

#### Configuration

Add these to your `.env` file:

```env
# Throw exceptions on scan operations (recommended: true for local/testing)
DYNAMODB_FAIL_ON_SCAN=true

# Log all scan operations for monitoring
DYNAMODB_LOG_SCANS=true
```

#### Query-Level Control

```php
// Fail on scan for this specific query
User::query()->failOnScan()->where('status', 'active')->get();

// Explicitly allow scan (for rare cases like data exports)
User::query()->allowScan()->get();

// Add context for debugging scan warnings
User::query()->withScanContext('admin dashboard export')->get();
```

#### Auditing for Scan Operations

Run the audit command to find potential scan operations in your codebase:

```bash
# Basic audit
php artisan dynamodb:audit-scans

# Show fix suggestions
php artisan dynamodb:audit-scans --fix

# Audit specific path
php artisan dynamodb:audit-scans --path=app/Services

# JSON output for CI/CD integration
php artisan dynamodb:audit-scans --json

# Exit with error code if issues found (useful for CI)
php artisan dynamodb:audit-scans --strict
```

The audit command detects patterns that typically result in scans:
- `Model::all()` - Full table scan
- `Model::get()` without where clause
- `Model::first()` without key conditions
- `->count()` without key conditions
- `->chunk()` and `->each()` iterations without filters

#### What Triggers a Scan vs Query

| Operation | Result | Recommendation |
|-----------|--------|----------------|
| `Model::find($id)` | GetItem | Safe - uses primary key |
| `Model::where('primary_key', $value)->get()` | Query | Safe - uses key condition |
| `Model::where('gsi_hash', $value)->get()` | Query | Safe - uses GSI |
| `Model::where('non_indexed_attr', $value)->get()` | **Scan** | Add GSI or restructure |
| `Model::all()` | **Scan** | Avoid - use pagination with key conditions |
| `Model::get()` | **Scan** | Add where clause on indexed attribute |

#### Best Practices

1. **Always query by primary key or GSI** - Structure your data access patterns around your indexes
2. **Define GSIs for common query patterns** - Add indexes for attributes you frequently filter by
3. **Use `->withIndex('index-name')` explicitly** - Be explicit about which index to use
4. **Enable `DYNAMODB_FAIL_ON_SCAN=true` locally** - Catch scan operations during development
5. **Run `dynamodb:audit-scans` in CI** - Prevent scan-causing code from being merged

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

