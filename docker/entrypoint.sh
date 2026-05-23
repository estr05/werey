#!/bin/sh

# Cache configurations for production
echo "Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Adjust Nginx port dynamically if $PORT is set by the hosting platform
if [ -n "$PORT" ]; then
  echo "Adjusting Nginx port to $PORT..."
  sed -i "s/80/${PORT}/g" /etc/nginx/http.d/default.conf
fi

# Start Supervisor
echo "Starting Supervisor..."
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
