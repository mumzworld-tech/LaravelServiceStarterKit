# Infrastructure

The infrastructure is Docker-based. A `docker` folder will house individual subfolders for each service node.

## Services

The following services will be used:

- MySQL
- DynamoDB
- Redis
- Horizon
- Redis Commander
- phpMyAdmin

Separate Dockerfiles and nodes will be maintained for:

- The main application (using FrankenPHP)
- Horizon workers  (using FrankenPHP)

## Logging

Application logs will be directed to `stdout` in JSON format. 

## Environments

The Docker-based infrastructure supports distinct "development" and "production" configurations, primarily for the application and worker services. This is managed via separate Dockerfiles and environment variables.

*   **`docker/application/Dockerfile.dev`**: Tailored for development, includes Xdebug, development Composer dependencies, and development-friendly PHP settings (e.g., opcache configured for frequent file changes via `docker/application/opcache-dev.ini`). Also copies `docker/application/xdebug.ini`.
*   `docker/application/Dockerfile.prod`**: Optimized for production, installs only necessary Composer dependencies (no dev), and should have production-hardened PHP settings.
*   **`docker/horizon/Dockerfile.dev`**: For Horizon development, includes development Composer dependencies.
*   **`docker/horizon/Dockerfile.prod`**: For Horizon production, installs only necessary Composer dependencies.

The choice of Dockerfile for the `app` service is controlled by the `DOCKER_APP_DOCKERFILE` variable in the `.env` file. Similarly, `DOCKER_HORIZON_DOCKERFILE` controls the Dockerfile for the `horizon` service.

A `docker-compose.yml` file, located in the root of the project, will be used to define and manage the services. It reads the `.env` file to configure itself.

### Environment Configuration with `.env`

The Docker Compose setup sources its configuration from a `.env` file located in the project root. This file is used to set environment-specific variables such as external ports, database credentials, and other service parameters without hardcoding them into `docker-compose.yml`.

An `example.env` file is provided as a template. You should copy this to `.env` and customize it for your local setup or production environment.

Key Docker-specific variables in `.env` (prefixed with `DOCKER_`) include:

*   `DOCKER_APP_DOCKERFILE`: Specifies the Dockerfile to use for the main application service (e.g., `Dockerfile.dev` or `Dockerfile.prod`). Default: `Dockerfile.dev`.
*   `DOCKER_HORIZON_DOCKERFILE`: Specifies the Dockerfile for the Horizon service (e.g., `Dockerfile.dev` or `Dockerfile.prod`). Default: `Dockerfile.dev`.
*   `DOCKER_APP_HTTP_PORT`: External HTTP port for the application (default: 80).
*   `DOCKER_APP_HTTPS_PORT`: External HTTPS port for the application (default: 443).
*   `DOCKER_MYSQL_HOST_PORT`: External port for MySQL (default: 3306).
*   `DOCKER_MYSQL_ROOT_PASSWORD`: Root password for MySQL.
*   `DOCKER_MYSQL_DATABASE`: Database name for the application.
*   `DOCKER_MYSQL_USER`: MySQL user for the application.
*   `DOCKER_MYSQL_PASSWORD`: Password for the MySQL application user.
*   `DOCKER_REDIS_HOST_PORT`: External port for Redis (default: 6379).
*   `DOCKER_DYNAMODB_HOST_PORT`: External port for DynamoDB (default: 8000).
*   `DOCKER_PHPMYADMIN_HOST_PORT`: External port for phpMyAdmin (default: 8080).
*   `DOCKER_REDIS_COMMANDER_HOST_PORT`: External port for Redis Commander (default: 8081).

**Important for Laravel Configuration:**
When running the application within this Docker setup, ensure your Laravel `.env` variables (which are also typically in the same `.env` file) point to the Docker service names for hosts and use appropriate credentials:

*   `DB_HOST=mysql`
*   `REDIS_HOST=redis`
*   `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`: These should be set in your `.env` file. For local development against the Dockerized DynamoDB, you can use dummy values (e.g., `dummykey`, `dummysecret`, `us-local-1`). For production, use your actual AWS credentials and region.
*   `DYNAMODB_ENDPOINT`: For local development, set this to `http://dynamodb:8000` to point to the local DynamoDB container. For production AWS DynamoDB, this variable should typically be empty or not set, so the AWS SDK uses the default endpoint for the specified `AWS_DEFAULT_REGION`.

The `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in Laravel's section of the `.env` should also correspond to the values set for `DOCKER_MYSQL_DATABASE`, `DOCKER_MYSQL_USER`, and `DOCKER_MYSQL_PASSWORD` respectively. 