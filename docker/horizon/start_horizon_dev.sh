#!/bin/sh

# Function to cleanup processes on exit
cleanup() {
    echo "Stopping Horizon..."
    # Find the PID of the 'php artisan horizon' process and kill it
    HORIZON_PID=$(ps aux | grep 'php artisan horizon' | grep -v grep | awk '{print $2}')
    if [ -n "$HORIZON_PID" ]; then
        kill $HORIZON_PID
    fi
    exit 0
}

# Trap SIGTERM and SIGINT to call the cleanup function
trap cleanup SIGTERM SIGINT

# Ensure storage/logs directory exists (Horizon might write logs or other files)
mkdir -p storage/logs

echo "Starting Horizon (Dev Mode)..."
php artisan horizon 