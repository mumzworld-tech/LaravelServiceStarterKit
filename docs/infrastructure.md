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

The Docker-based infrastructure will be configurable to run in both "development" and "production" environments. This will likely involve different configurations or Docker Compose files for each environment to manage environment-specific settings, such as resource limits, debugging tools, and service endpoints.

A `docker-compose.yml` file, located in the root of the project, will be used to define and manage the services. 