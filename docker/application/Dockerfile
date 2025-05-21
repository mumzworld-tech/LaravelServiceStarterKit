# Use the official FrankenPHP image
FROM dunglas/frankenphp

# Set the working directory
WORKDIR /var/www/html

# Copy application files (if not primarily using volumes for development)
# COPY . /var/www/html

# Optional: Install PHP extensions
# RUN install-php-extensions pdo_mysql zip ...

# Expose ports (already handled by FrankenPHP base image for 80, 443, 8080)

# Command to run FrankenPHP (this is usually handled by the base image, but can be customized)
# CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "laravel"] 