# OpenTelemetry Setup Guide

This guide explains how to set up and use OpenTelemetry in the Laravel Service Starter Kit using the `mumzworld/laravel-opentelemetry` package.

## 📦 Package Integration

We use the **`mumzworld/laravel-opentelemetry`** package which provides:
- ✅ Auto-configured OpenTelemetry service provider
- ✅ Complete Docker observability stack (Collector, Tempo, Grafana)
- ✅ TracerService for custom business logic tracing
- ✅ Test endpoints for validation
- ✅ Production-ready configuration

### Installation

The package is already included in `composer.json`:

```json
{
    "require": {
        "mumzworld/laravel-opentelemetry": "^1.0"
    }
}
```

> **Note**: The package is publicly available and requires no GitHub authentication.

## 🔧 Configuration

### Required Environment Variables

Add these variables to your `.env` file:

```env
# OpenTelemetry Configuration
OTEL_SERVICE_NAME=laravel-starter-kit-service
OTEL_TRACES_EXPORTER=otlp
OTEL_EXPORTER_OTLP_ENDPOINT=http://otel-collector:4318
OTEL_PROPAGATORS=baggage,tracecontext
OTEL_PHP_AUTOLOAD_ENABLED=true
```

### Environment-Specific Configuration

**Local Development (using Tempo):**
```env
OTEL_EXPORTER_OTLP_ENDPOINT=http://otel-collector:4318
```

**Production (update with your actual URLs):**
```env
OTEL_EXPORTER_OTLP_ENDPOINT=https://your-production-collector.example.com
```

## 🐳 Docker Setup

### Required PHP Extensions

The following PHP extensions are automatically installed in Docker containers:

```dockerfile
# Install OpenTelemetry extension via PECL
RUN pecl install redis opentelemetry && docker-php-ext-enable redis opentelemetry
```

### Starting Services

```bash
# Build and start all services
docker-compose up --build -d

# Check service status
docker-compose ps
```

## 🧪 Testing & Validation

### Test Endpoints

The package provides built-in test endpoints:

```bash
# Basic functionality test
curl http://localhost/api/opentelemetry/test

# Configuration verification
curl http://localhost/api/opentelemetry/config

# Nested spans test
curl http://localhost/api/opentelemetry/nested

# Error handling test
curl http://localhost/api/opentelemetry/error
```

### Generating Different Types of Traces

**1. HTTP Request Traces (Automatic)**
```bash
# Generate HTTP traces
curl http://localhost/
curl http://localhost/api/v1/health
curl http://localhost/nonexistent-page
```

**2. DynamoDB Operation Traces (Automatic)**
```bash
# Generate DynamoDB traces
docker-compose exec app php artisan tinker --execute="
use App\Models\ExampleModelDynamoDB;
\$model = new ExampleModelDynamoDB();
\$model->id = 'trace_test_' . time();
\$model->name = 'OpenTelemetry Test';
\$model->save();
echo 'DynamoDB trace generated: ' . \$model->id;
"
```

**3. Cache Operation Traces (Automatic)**
```bash
# Generate cache traces
docker-compose exec app php artisan tinker --execute="
use Illuminate\Support\Facades\Cache;
Cache::put('otel_test_key', 'test_value', 300);
\$value = Cache::get('otel_test_key');
echo 'Cache trace generated: ' . \$value;
"
```

**4. Custom Business Logic Traces**
```php
use Mumzworld\LaravelOpenTelemetry\Services\TracerService;

class UserService 
{
    public function __construct(private TracerService $tracer) {}
    
    public function createUser(array $data): User
    {
        return $this->tracer->trace('user.create', function() use ($data) {
            // Your business logic here
            return User::create($data);
        }, [
            'user.email' => $data['email'],
            'user.type' => $data['type'] ?? 'regular'
        ]);
    }
}
```

## 📊 Viewing Traces in Grafana

### Access Grafana Dashboard

1. **Open Grafana**: http://localhost:3001
2. **Login**: admin/admin
3. **Navigate**: Explore → Tempo datasource

### Sample Trace Queries

```
# Find all traces for your service
{service.name="laravel-starter-kit-service"}

# Find traces with errors
{service.name="laravel-starter-kit-service" && status=error}

# Find slow traces (duration > 100ms)
{service.name="laravel-starter-kit-service" && duration>100ms}

# Find HTTP GET requests
{service.name="laravel-starter-kit-service" && name=~".*GET.*"}

# Find DynamoDB operations
{service.name="laravel-starter-kit-service" && name=~".*DynamoDB.*"}
```

### Trace Analysis

**What to Look For:**
- **Service Map**: Visual representation of service dependencies
- **Trace Timeline**: Request flow and timing
- **Span Details**: Individual operation metrics
- **Error Traces**: Failed operations and stack traces

## 🔧 Troubleshooting

### Common Issues

**1. No Traces Appearing**

Check if services are running:
```bash
docker-compose ps otel-collector tempo grafana
```

Verify OpenTelemetry extension:
```bash
docker-compose exec app php -m | grep opentelemetry
```

Check collector logs:
```bash
docker-compose logs otel-collector
```

**2. Grafana Datasource Errors**

Restart Grafana to reload configuration:
```bash
docker-compose restart grafana
```

Check Tempo connectivity from Grafana:
```bash
docker-compose exec grafana wget -qO- http://tempo:3200/ready
```

**4. Extension Not Loading**

Verify PHP configuration:
```bash
docker-compose exec app php --ini
docker-compose exec app php -i | grep opentelemetry
```

**5. Port Conflicts**

Check if ports are available:
```bash
# Check OpenTelemetry service ports
curl http://localhost:3001  # Grafana
curl http://localhost:3201/ready  # Tempo
curl http://localhost:13134  # Collector health
```

### Debug Mode

Enable debug logging in `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

Check application logs:
```bash
docker-compose logs app | grep -i otel
```

### Validation Checklist

- [ ] OpenTelemetry extension loaded: `php -m | grep opentelemetry`
- [ ] Package test endpoints respond: `curl http://localhost/api/opentelemetry/test`
- [ ] Grafana accessible: `curl http://localhost:3001`
- [ ] Tempo ready: `curl http://localhost:3201/ready`
- [ ] Traces visible in Grafana Explore
- [ ] No errors in collector logs

## 🚀 Production Deployment

### Environment Configuration

For production, update these variables in your `.env`:

```env
# Production OpenTelemetry Configuration
OTEL_SERVICE_NAME=your-production-service-name
OTEL_EXPORTER_OTLP_ENDPOINT=https://your-production-collector.example.com
OTEL_TRACES_SAMPLER_ARG=0.1  # Sample 10% of traces for performance
```

### Performance Considerations

1. **Sampling**: Reduce sampling rate for high-traffic applications
2. **Batching**: Configure batch processors in collector
3. **Resource Limits**: Set appropriate memory and CPU limits
4. **Network**: Use gRPC for better performance if available

## 📚 Additional Resources

- **Package Repository**: https://github.com/mumzworld-tech/laravel-opentelemetry
- **OpenTelemetry PHP Documentation**: https://opentelemetry.io/docs/languages/php/
- **Grafana Tempo Documentation**: https://grafana.com/docs/tempo/
- **TraceQL Query Language**: https://grafana.com/docs/tempo/latest/traceql/

---

**Need Help?** Check the troubleshooting section above or review the package documentation for advanced configuration options.