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
    docker compose exec app php artisan migrate
    ```

Your Laravel API should now be accessible (typically at `http://localhost` or the port you configured for `DOCKER_APP_HTTP_PORT`). The admin UIs for databases will be on their respective configured ports.

Refer to `docs/infrastructure.md` for more detailed information on the Docker setup and environment variables.
