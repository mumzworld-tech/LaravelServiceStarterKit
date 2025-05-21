#!/bin/sh

# Function to cleanup processes on exit
cleanup() {
    echo "Stopping processes..."
    # Get PIDs of all background jobs and kill them
    if [ -n "$(jobs -p)" ]; then
        kill $(jobs -p)
    fi
    exit 0
}

# Trap SIGTERM and SIGINT to call the cleanup function
trap cleanup SIGTERM SIGINT

# Ensure storage/logs directory exists
mkdir -p storage/logs

echo "Starting Horizon..."
php artisan horizon &

# Start Octane with FrankenPHP in the foreground
echo "Starting Octane with FrankenPHP..."
exec php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=80 --admin-port=2019 --workers=auto --task-workers=auto --log-level=debug 