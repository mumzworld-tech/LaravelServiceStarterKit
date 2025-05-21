# Use an official MySQL image
FROM mysql:8.0

# Copy custom MySQL configuration
COPY my.cnf /etc/mysql/conf.d/my.cnf

# Optional: Add custom initialization scripts
# COPY init.sql /docker-entrypoint-initdb.d/

# Environment variables are typically set in docker-compose.yml 