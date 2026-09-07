#!/bin/sh
set -e

# ===================
# Toolkit Pro Entrypoint Script
# ===================

echo "🚀 Starting Toolkit Pro Container..."
echo "================================"

# ===================
# Environment Check
# ===================
echo "📋 Checking Environment..."
echo "APP_ENV: ${APP_ENV:-production}"
echo "APP_DEBUG: ${APP_DEBUG:-false}"
echo "PHP Version: $(php -v | head -n 1)"

# ===================
# Wait for Services
# ===================
echo "⏳ Waiting for services to be ready..."

# Wait for PostgreSQL
if [ -n "$DB_HOST" ]; then
    echo "Waiting for PostgreSQL at $DB_HOST:$DB_PORT..."
    until php -r "
        try {
            new PDO('pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT', '5432') . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
            echo 'connected';
        } catch (PDOException \$e) {
            exit(1);
        }
    " > /dev/null 2>&1; do
        echo "PostgreSQL is unavailable - sleeping"
        sleep 2
    done
    echo "✅ PostgreSQL is ready"
fi

# Wait for Redis
if [ -n "$REDIS_HOST" ]; then
    echo "Waiting for Redis at $REDIS_HOST:$REDIS_PORT..."
    until php -r "
        try {
            \$redis = new Redis();
            \$redis->connect(getenv('REDIS_HOST'), getenv('REDIS_PORT', '6379'));
            echo 'connected';
        } catch (Exception \$e) {
            exit(1);
        }
    " > /dev/null 2>&1; do
        echo "Redis is unavailable - sleeping"
        sleep 2
    done
    echo "✅ Redis is ready"
fi

# Wait for Elasticsearch
if [ -n "$ELASTICSEARCH_HOST" ]; then
    echo "Waiting for Elasticsearch at $ELASTICSEARCH_HOST:$ELASTICSEARCH_PORT..."
    until curl -f "http://$ELASTICSEARCH_HOST:$ELASTICSEARCH_PORT" > /dev/null 2>&1; do
        echo "Elasticsearch is unavailable - sleeping"
        sleep 2
    done
    echo "✅ Elasticsearch is ready"
fi

# Wait for RabbitMQ
if [ -n "$RABBITMQ_HOST" ]; then
    echo "Waiting for RabbitMQ at $RABBITMQ_HOST:$RABBITMQ_PORT..."
    until php -r "
        try {
            \$connection = new \PhpAmqpLib\Connection\AMQPStreamConnection(
                getenv('RABBITMQ_HOST'),
                getenv('RABBITMQ_PORT', '5672'),
                getenv('RABBITMQ_USER', 'admin'),
                getenv('RABBITMQ_PASSWORD', 'secret')
            );
            \$connection->close();
            echo 'connected';
        } catch (Exception \$e) {
            exit(1);
        }
    " > /dev/null 2>&1; do
        echo "RabbitMQ is unavailable - sleeping"
        sleep 2
    done
    echo "✅ RabbitMQ is ready"
fi

# ===================
# Setup Application
# ===================
echo "🔧 Setting up application..."

# Storage permissions
echo "Setting storage permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Create storage directories if they don't exist
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs

# Storage link
if [ ! -L /var/www/html/public/storage ]; then
    echo "Creating storage link..."
    php artisan storage:link
fi

# ===================
# Environment Configuration
# ===================
if [ ! -f /var/www/html/.env ]; then
    echo "Creating .env file..."
    cp .env.example .env
    
    # Generate application key
    echo "Generating application key..."
    php artisan key:generate --force
fi

# ===================
# Cache Configuration
# ===================
if [ "$APP_ENV" = "production" ]; then
    echo "⚡ Optimizing for production..."
    
    # Clear all cache
    php artisan optimize:clear
    
    # Optimize
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    php artisan package:discover --ansi
    
    echo "✅ Application optimized"
else
    echo "🔧 Clearing cache for development..."
    php artisan optimize:clear
fi

# ===================
# Database Migrations
# ===================
if [ "$RUN_MIGRATIONS" = "true" ] || [ "$APP_ENV" = "production" ]; then
    echo "🗄 Running database migrations..."
    php artisan migrate --force
    
    if [ "$RUN_SEEDERS" = "true" ]; then
        echo "🌱 Running database seeders..."
        php artisan db:seed --force
    fi
    
    echo "✅ Database migrated"
fi

# ===================
# Start Services
# ===================
echo "🚀 Starting services..."

# Determine which service to run
SERVICE=${SERVICE_NAME:-"app"}

case "$SERVICE" in
    "app"|"php-fpm")
        echo "Starting PHP-FPM..."
        exec php-fpm
        ;;
        
    "queue-worker")
        echo "Starting Queue Worker..."
        exec php artisan queue:work redis --queue=high,default,low --sleep=3 --tries=3 --timeout=90
        ;;
        
    "queue-worker-high")
        echo "Starting High Priority Queue Worker..."
        exec php artisan queue:work redis --queue=high --sleep=1 --tries=3 --timeout=120
        ;;
        
    "queue-worker-default")
        echo "Starting Default Queue Worker..."
        exec php artisan queue:work redis --queue=default --sleep=3 --tries=3 --timeout=90
        ;;
        
    "queue-worker-low")
        echo "Starting Low Priority Queue Worker..."
        exec php artisan queue:work redis --queue=low --sleep=5 --tries=2 --timeout=60
        ;;
        
    "scheduler")
        echo "Starting Scheduler..."
        exec php artisan schedule:work
        ;;
        
    "websocket")
        echo "Starting WebSocket Server..."
        exec php artisan websockets:serve --host=0.0.0.0 --port=6001
        ;;
        
    "horizon")
        echo "Starting Laravel Horizon..."
        exec php artisan horizon
        ;;
        
    "horizon-worker")
        echo "Starting Horizon Worker..."
        exec php artisan horizon:work
        ;;
        
    "telescope")
        echo "Starting Telescope..."
        exec php artisan telescope:serve
        ;;
        
    "octane")
        echo "Starting Laravel Octane..."
        exec php artisan octane:start --host=0.0.0.0 --port=8000
        ;;
        
    "nginx")
        echo "Starting Nginx..."
        exec nginx -g "daemon off;"
        ;;
        
    "supervisor")
        echo "Starting Supervisor..."
        exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
        ;;
        
    *)
        echo "❌ Unknown service: $SERVICE"
        echo "Available services: app, php-fpm, queue-worker, scheduler, websocket, horizon, nginx, supervisor"
        exit 1
        ;;
esac

echo "✅ Toolkit Pro is running!"
