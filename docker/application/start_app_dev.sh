#!/bin/sh

# Function to cleanup processes on exit
cleanup() {
    echo "Stopping processes..."
    # Get PIDs of all background jobs and kill them
    # Then kill the main Octane process which is the parent of this script
    if [ -n "$(jobs -p)" ]; then
        kill $(jobs -p)
    fi
    # The exec call replaces the shell process with octane, 
    # so a SIGTERM to this script's original PID (if it were not exec'd)
    # or to the parent of the actual octane process is needed.
    # The trap is inherited by child processes, but killing jobs -p is most direct.
    exit 0
}

# Trap SIGTERM and SIGINT to call the cleanup function
trap cleanup SIGTERM SIGINT

# Ensure storage/logs directory exists
mkdir -p storage/logs

echo "Starting Horizon..."
php artisan horizon &

# Start Octane with FrankenPHP in the foreground
# This will be the main process for the container
echo "Starting Octane with FrankenPHP..."
exec php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=80 --admin-port=2019 --workers=auto --task-workers=auto --log-level=debug 