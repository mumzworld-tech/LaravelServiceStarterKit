# Use the official FrankenPHP image
FROM dunglas/frankenphp

# Set the working directory
WORKDIR /var/www/html

# Application code (including Horizon) is typically mounted via volumes.
# If building a production image or specific assets, uncomment and adjust:
# COPY . /var/www/html

# Optional: Install PHP extensions (e.g., pcntl for Horizon)
# RUN install-php-extensions pcntl pdo_mysql zip ...

# Command to run Horizon is specified in docker-compose.yml

# The command to run Horizon is specified in docker-compose.yml
# (e.g., "php artisan horizon")
# If you were to run FrankenPHP as a server for Horizon (not typical for workers), you'd configure it here. 