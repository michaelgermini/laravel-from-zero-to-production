# Chapter 12: Deployment

## What is Deployment?

Deployment is the process of making your Laravel application available to users on a production server. This involves setting up the server environment, configuring the application, and ensuring it runs reliably in production.

## Pre-Deployment Checklist

### Environment Configuration

```bash
# Set production environment
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Cache and session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail configuration
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Security Considerations

```bash
# Generate application key
php artisan key:generate

# Set secure session configuration
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict

# Enable HTTPS
FORCE_HTTPS=true

# Set secure headers
SECURE_HEADERS=true
```

### Performance Optimization

```bash
# Enable caching
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Set proper permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Deployment Methods

### 1. Traditional Server Deployment

#### Server Requirements

```bash
# Install required packages
sudo apt update
sudo apt install nginx mysql-server php8.1-fpm php8.1-mysql php8.1-xml php8.1-mbstring php8.1-curl php8.1-zip php8.1-gd php8.1-bcmath php8.1-redis composer
```

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/your-app/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Deployment Script

```bash
#!/bin/bash

# Deployment script
set -e

# Variables
APP_DIR="/var/www/your-app"
BACKUP_DIR="/var/backups/your-app"
RELEASE_DIR="$APP_DIR/releases/$(date +%Y%m%d_%H%M%S)"

# Create release directory
mkdir -p $RELEASE_DIR

# Clone or pull latest code
git clone https://github.com/your-username/your-app.git $RELEASE_DIR

# Install dependencies
cd $RELEASE_DIR
composer install --optimize-autoloader --no-dev

# Copy environment file
cp .env.example .env

# Set permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Run migrations
php artisan migrate --force

# Optimize application
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create symlink
ln -sfn $RELEASE_DIR $APP_DIR/current

# Restart services
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx

echo "Deployment completed successfully!"
```

### 2. Laravel Forge Deployment

Laravel Forge is a server management service that simplifies deployment:

#### Setting Up Forge

1. **Connect Server**: Add your server to Forge
2. **Install PHP**: Install PHP 8.1 and required extensions
3. **Install Database**: Install MySQL or PostgreSQL
4. **Create Site**: Create a new site in Forge
5. **Deploy**: Use Forge's deployment features

#### Forge Deployment Script

```bash
# Forge deployment script
cd /home/forge/yourdomain.com
git pull origin main
composer install --no-interaction --prefer-dist --optimize-autoloader

if [ -f artisan ]; then
    $FORGE_PHP artisan migrate --force
    $FORGE_PHP artisan config:cache
    $FORGE_PHP artisan route:cache
    $FORGE_PHP artisan view:cache
fi
```

### 3. Laravel Vapor Deployment

Laravel Vapor is a serverless deployment platform:

#### Installing Vapor

```bash
composer require laravel/vapor-cli --update-with-dependencies
vapor install
```

#### Vapor Configuration

```yaml
# vapor.yml
id: your-app-id
name: your-app-name
environments:
    production:
        memory: 1024
        cli-memory: 512
        runtime: 'php-8.1'
        build:
            - 'composer install --no-dev'
            - 'php artisan event:cache'
        deploy:
            - 'php artisan migrate --force'
        env:
            APP_ENV: production
            APP_DEBUG: false
```

#### Deploying with Vapor

```bash
# Deploy to production
vapor deploy production

# Deploy with environment variables
vapor deploy production --env=production
```

### 4. Docker Deployment

#### Dockerfile

```dockerfile
FROM php:8.1-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory contents
COPY . /var/www

# Copy existing application directory permissions
COPY --chown=www-data:www-data . /var/www

# Install dependencies
RUN composer install --optimize-autoloader --no-dev

# Change current user to www
USER www-data

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
```

#### Docker Compose

```yaml
# docker-compose.yml
version: '3'
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: laravel_app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
    networks:
      - laravel_network

  nginx:
    image: nginx:alpine
    container_name: laravel_nginx
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - ./:/var/www
      - ./docker/nginx/conf.d:/etc/nginx/conf.d
    networks:
      - laravel_network

  db:
    image: mysql:8.0
    container_name: laravel_db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: laravel
      MYSQL_ROOT_PASSWORD: your_mysql_root_password
      MYSQL_PASSWORD: your_mysql_password
      MYSQL_USER: your_mysql_user
    volumes:
      - dbdata:/var/lib/mysql
    networks:
      - laravel_network

  redis:
    image: redis:alpine
    container_name: laravel_redis
    restart: unless-stopped
    networks:
      - laravel_network

networks:
  laravel_network:
    driver: bridge

volumes:
  dbdata:
    driver: local
```

## Continuous Integration/Continuous Deployment (CI/CD)

### GitHub Actions

```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v3

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql, dom, filter, gd, iconv, json, mbstring, pdo, xml

    - name: Copy .env
      run: php -r "file_exists('.env') || copy('.env.example', '.env');"

    - name: Install Dependencies
      run: composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist

    - name: Generate key
      run: php artisan key:generate

    - name: Directory Permissions
      run: chmod -R 777 storage bootstrap/cache

    - name: Create Database
      run: |
        mkdir -p database
        touch database/database.sqlite

    - name: Execute tests (Unit and Feature tests) via PHPUnit
      env:
        DB_CONNECTION: sqlite
        DB_DATABASE: database/database.sqlite
      run: vendor/bin/phpunit

    - name: Deploy to server
      uses: appleboy/ssh-action@v0.1.5
      with:
        host: ${{ secrets.HOST }}
        username: ${{ secrets.USERNAME }}
        key: ${{ secrets.KEY }}
        script: |
          cd /var/www/your-app
          git pull origin main
          composer install --no-dev --optimize-autoloader
          php artisan migrate --force
          php artisan config:cache
          php artisan route:cache
          php artisan view:cache
          sudo systemctl restart php8.1-fpm
          sudo systemctl restart nginx
```

### GitLab CI/CD

```yaml
# .gitlab-ci.yml
stages:
  - test
  - deploy

test:
  stage: test
  image: php:8.1
  services:
    - mysql:8.0
  variables:
    MYSQL_DATABASE: laravel_test
    MYSQL_ROOT_PASSWORD: password
  before_script:
    - apt-get update -qq && apt-get install -y -qq git unzip libzip-dev
    - docker-php-ext-install pdo_mysql zip
    - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    - cp .env.example .env
    - composer install --no-interaction --no-progress --prefer-dist
    - php artisan key:generate
    - php artisan migrate --force
  script:
    - vendor/bin/phpunit

deploy:
  stage: deploy
  image: alpine:latest
  before_script:
    - apk add --no-cache openssh-client
    - eval $(ssh-agent -s)
    - echo "$SSH_PRIVATE_KEY" | tr -d '\r' | ssh-add -
    - mkdir -p ~/.ssh
    - chmod 700 ~/.ssh
  script:
    - ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_HOST "cd /var/www/your-app && git pull origin main && composer install --no-dev --optimize-autoloader && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && sudo systemctl restart php8.1-fpm && sudo systemctl restart nginx"
  only:
    - main
```

## Environment-Specific Configurations

### Production Environment

```php
// config/app.php
'debug' => env('APP_DEBUG', false),
'env' => env('APP_ENV', 'production'),

// config/database.php
'default' => env('DB_CONNECTION', 'mysql'),
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => null,
],

// config/cache.php
'default' => env('CACHE_DRIVER', 'redis'),
'redis' => [
    'driver' => 'redis',
    'connection' => 'cache',
    'lock_connection' => 'default',
],

// config/session.php
'driver' => env('SESSION_DRIVER', 'redis'),
'lifetime' => env('SESSION_LIFETIME', 120),
'expire_on_close' => false,
'secure' => env('SESSION_SECURE_COOKIE', true),
'same_site' => env('SESSION_SAME_SITE', 'strict'),
```

### Staging Environment

```bash
# .env.staging
APP_ENV=staging
APP_DEBUG=true
APP_URL=https://staging.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=staging-db-host
DB_DATABASE=staging_database
DB_USERNAME=staging_user
DB_PASSWORD=staging_password

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

## Monitoring and Logging

### Application Monitoring

```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'daily'],
        'ignore_exceptions' => false,
    ],
    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
    ],
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],
    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => env('LOG_LEVEL', 'critical'),
    ],
],
```

### Error Tracking

```php
// config/app.php
'providers' => [
    // ...
    Sentry\Laravel\ServiceProvider::class,
],

// config/sentry.php
'dsn' => env('SENTRY_LARAVEL_DSN'),
'breadcrumbs' => [
    'logs' => true,
    'sql_queries' => true,
    'sql_bindings' => true,
    'queue_info' => true,
    'command_info' => true,
],
```

## Performance Optimization

### Caching Strategies

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Cache events
php artisan event:cache
```

### Database Optimization

```sql
-- Add indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_posts_user_id ON posts(user_id);
CREATE INDEX idx_posts_created_at ON posts(created_at);

-- Optimize tables
OPTIMIZE TABLE users;
OPTIMIZE TABLE posts;
```

### Queue Configuration

```php
// config/queue.php
'default' => env('QUEUE_CONNECTION', 'redis'),
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
        'after_commit' => false,
    ],
],
```

## SSL/HTTPS Configuration

### Let's Encrypt SSL

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

### Nginx SSL Configuration

```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # HSTS
    add_header Strict-Transport-Security "max-age=63072000" always;

    root /var/www/your-app/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

## Backup Strategies

### Database Backups

```bash
#!/bin/bash
# backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/database"
DB_NAME="your_database"
DB_USER="your_username"

# Create backup directory
mkdir -p $BACKUP_DIR

# Create backup
mysqldump -u $DB_USER -p $DB_NAME > $BACKUP_DIR/backup_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/backup_$DATE.sql

# Keep only last 7 days of backups
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +7 -delete

echo "Backup completed: backup_$DATE.sql.gz"
```

### File Backups

```bash
#!/bin/bash
# file_backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/files"
APP_DIR="/var/www/your-app"

# Create backup directory
mkdir -p $BACKUP_DIR

# Create backup of storage directory
tar -czf $BACKUP_DIR/storage_backup_$DATE.tar.gz -C $APP_DIR storage

# Create backup of public directory
tar -czf $BACKUP_DIR/public_backup_$DATE.tar.gz -C $APP_DIR public

# Keep only last 7 days of backups
find $BACKUP_DIR -name "*_backup_*.tar.gz" -mtime +7 -delete

echo "File backup completed"
```

## Troubleshooting Common Issues

### Permission Issues

```bash
# Fix storage permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 755 storage bootstrap/cache

# Fix log permissions
sudo chown -R www-data:www-data storage/logs
sudo chmod -R 755 storage/logs
```

### Memory Issues

```bash
# Increase PHP memory limit
sudo nano /etc/php/8.1/fpm/php.ini
# memory_limit = 512M

# Increase PHP-FPM memory
sudo nano /etc/php/8.1/fpm/php-fpm.conf
# pm.max_children = 50
# pm.start_servers = 5
# pm.min_spare_servers = 5
# pm.max_spare_servers = 35
```

### Database Connection Issues

```bash
# Check MySQL status
sudo systemctl status mysql

# Check MySQL logs
sudo tail -f /var/log/mysql/error.log

# Test database connection
mysql -u your_username -p -h your_host your_database
```

## Deployment Best Practices

### 1. Use Environment Variables

```bash
# Never commit sensitive data
# Use .env files for configuration
APP_KEY=base64:your-key-here
DB_PASSWORD=your-secure-password
MAIL_PASSWORD=your-email-password
```

### 2. Implement Zero-Downtime Deployment

```bash
# Use blue-green deployment
# Deploy to new server
# Switch traffic when ready
# Keep old server as backup
```

### 3. Monitor Application Health

```bash
# Set up health checks
# Monitor error rates
# Track response times
# Set up alerts
```

### 4. Regular Maintenance

```bash
# Update dependencies regularly
# Monitor security patches
# Clean up old logs
# Optimize database
```

## Summary

In this chapter, we covered:

- ✅ Pre-deployment checklist and preparation
- ✅ Different deployment methods (traditional, Forge, Vapor, Docker)
- ✅ CI/CD pipeline setup
- ✅ Environment-specific configurations
- ✅ Monitoring and logging strategies
- ✅ Performance optimization techniques
- ✅ SSL/HTTPS configuration
- ✅ Backup strategies
- ✅ Troubleshooting common issues
- ✅ Deployment best practices

Deployment is the final step in bringing your Laravel application to production. With proper planning and execution, you can ensure a smooth and reliable deployment process.

Congratulations! You've completed the Laravel: From Zero to Production book. You now have a comprehensive understanding of Laravel development and are ready to build and deploy production-ready applications.
