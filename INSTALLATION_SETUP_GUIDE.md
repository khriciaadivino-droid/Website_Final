# KHRICIADIVINO - Installation & Setup Guide

**Version:** 1.0.0  
**Last Updated:** May 2026  
**Project:** Pet E-Commerce Platform (Web Admin + Mobile API)

---

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Local Development Setup](#local-development-setup)
3. [Production Deployment](#production-deployment)
4. [Database Setup & Migrations](#database-setup--migrations)
5. [Authentication Configuration](#authentication-configuration)
6. [Environment Variables](#environment-variables)
7. [Running the Application](#running-the-application)
8. [Testing](#testing)
9. [Troubleshooting](#troubleshooting)

---

## System Requirements

### Software Requirements

- **PHP:** 8.1+ (8.2+ recommended)
- **Composer:** Latest version
- **Node.js:** 18+ (for frontend build tools)
- **npm:** 8+
- **Database:** PostgreSQL 12+, MySQL 8.0+, or SQLite 3
- **Git:** 2.0+
- **Docker:** 20+ (optional, for containerized deployment)

### System Resources (Recommended)

- **CPU:** 2 cores minimum
- **RAM:** 2GB minimum (4GB for production)
- **Storage:** 5GB minimum
- **Network:** Stable internet connection for package installation

---

## Local Development Setup

### Step 1: Clone the Repository

```bash
# Clone the project
git clone https://github.com/khrings/Webdev2Finals.git khriciadivino
cd khriciadivino

# Verify directory structure
ls -la
```

### Step 2: Install PHP Dependencies

```bash
# Install Composer dependencies
composer install

# Verify installation
composer --version
php --version
```

**Troubleshooting:**
- If composer fails: `composer install --no-interaction --no-progress`
- PHP memory limit issues: `php -d memory_limit=-1 composer install`

### Step 3: Install Node Dependencies

```bash
# Install npm packages
npm install

# Verify installation
npm --version
node --version
```

### Step 4: Configure Environment

```bash
# Copy environment template
cp .env.example .env

# Edit .env with your settings
# Key variables to configure:
# - DATABASE_URL
# - APP_ENV=dev
# - APP_DEBUG=1
# - JWT configuration
```

### Step 5: Generate JWT Keys

```bash
# Generate RSA keys for JWT authentication
php bin/console lexik:jwt:generate-keypair

# Verify keys created
ls -la config/jwt/
# Should show: private.pem and public.pem
```

### Step 6: Create & Migrate Database

```bash
# Create database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate

# Verify tables created
php bin/console doctrine:query:sql "SHOW TABLES"
```

### Step 7: Load Fixtures (Sample Data)

```bash
# Load sample data
php bin/console doctrine:fixtures:load --no-interaction

# Verify data loaded
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM user"
```

**Sample Credentials After Fixtures:**
- Email: `admin@example.com`
- Password: `password123`
- Role: `ROLE_ADMIN`

### Step 8: Build Frontend Assets

```bash
# Development build with watch mode
npm run dev

# Or production build
npm run build

# Verify webpack compilation
ls -la public/build/
```

### Step 9: Start Development Server

```bash
# Using Symfony CLI (recommended)
symfony server:start

# OR using PHP built-in server
php -S localhost:8000 -t public

# Access application
# Web: http://localhost:8000
# API: http://localhost:8000/api
```

### Step 10: Verify Installation

```bash
# Test API health
curl http://localhost:8000/api/categories

# Test login endpoint
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password123"
  }'
```

---

## Production Deployment

### Pre-Deployment Checklist

- [ ] Database backups configured
- [ ] Environment variables set correctly
- [ ] SSL certificate installed
- [ ] Firewall rules configured
- [ ] Email service configured
- [ ] File permissions correct
- [ ] Caching configured
- [ ] Logs directory writable

### Step 1: Prepare Server

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y php8.2-cli php8.2-fpm php8.2-pgsql \
  php8.2-mysql php8.2-curl php8.2-intl php8.2-xml \
  php8.2-mbstring composer nginx postgresql

# Verify installations
php --version
composer --version
```

### Step 2: Clone & Setup Project

```bash
# Create application directory
sudo mkdir -p /var/www/khriciadivino
cd /var/www/khriciadivino

# Clone repository
sudo git clone https://github.com/khrings/Webdev2Finals.git .

# Set permissions
sudo chown -R www-data:www-data /var/www/khriciadivino
sudo chmod -R 755 /var/www/khriciadivino
sudo chmod -R 775 /var/www/khriciadivino/var
```

### Step 3: Install Dependencies

```bash
# Install PHP dependencies
cd /var/www/khriciadivino
composer install --no-dev --optimize-autoloader

# Install Node dependencies
npm install --production

# Build frontend
npm run build
```

### Step 4: Configure Environment

```bash
# Copy environment file
sudo cp .env.example .env

# Edit production configuration
sudo nano .env

# Critical settings:
# APP_ENV=prod
# APP_DEBUG=0
# DATABASE_URL=postgresql://user:pass@localhost/khriciadivino
# JWT_PRIVATE_KEY_PATH=%kernel.project_dir%/config/jwt/private.pem
# JWT_PUBLIC_KEY_PATH=%kernel.project_dir%/config/jwt/public.pem
# CORS_ALLOW_ORIGIN=https://yourdomain.com
```

### Step 5: Generate JWT Keys

```bash
# Generate JWT keys
php bin/console lexik:jwt:generate-keypair

# Set correct permissions
sudo chmod 600 config/jwt/private.pem
sudo chmod 644 config/jwt/public.pem
```

### Step 6: Setup Database

```bash
# Create database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Warm up cache
php bin/console cache:warmup --env=prod
```

### Step 7: Configure Web Server (Nginx)

**File:** `/etc/nginx/sites-available/khriciadivino`

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name khriciadivino.example.com;

    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name khriciadivino.example.com;

    ssl_certificate /etc/letsencrypt/live/khriciadivino.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/khriciadivino.example.com/privkey.pem;

    root /var/www/khriciadivino/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Logging
    access_log /var/log/nginx/khriciadivino_access.log;
    error_log /var/log/nginx/khriciadivino_error.log;

    # Performance
    gzip on;
    gzip_vary on;
    gzip_min_length 1000;
    gzip_types text/plain text/css text/xml text/javascript application/json;

    # Symfony routing
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    # Static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    location ~ /\.git {
        deny all;
    }
}
```

### Step 8: Enable Nginx Configuration

```bash
# Create symbolic link
sudo ln -s /etc/nginx/sites-available/khriciadivino \
  /etc/nginx/sites-enabled/

# Test Nginx configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

### Step 9: Setup SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Generate certificate
sudo certbot certonly --nginx -d khriciadivino.example.com

# Auto-renewal
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer
```

### Step 10: Setup Process Manager (Supervisor - Optional)

**File:** `/etc/supervisor/conf.d/khriciadivino.conf`

```ini
[program:khriciadivino-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/khriciadivino/bin/console messenger:consume async --time-limit=3600
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/khriciadivino/worker.log
user=www-data
```

### Step 11: Verify Production Installation

```bash
# Check PHP-FPM
sudo systemctl status php8.2-fpm

# Check Nginx
sudo systemctl status nginx

# Test application
curl -I https://khriciadivino.example.com

# Check logs
tail -f /var/log/nginx/khriciadivino_error.log
```

---

## Database Setup & Migrations

### Create Database

```bash
# PostgreSQL
psql -U postgres
CREATE DATABASE khriciadivino;
CREATE USER khriciadivino WITH PASSWORD 'secure_password';
ALTER USER khriciadivino CREATEDB;
GRANT ALL PRIVILEGES ON DATABASE khriciadivino TO khriciadivino;

# MySQL
mysql -u root -p
CREATE DATABASE khriciadivino;
CREATE USER 'khriciadivino'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON khriciadivino.* TO 'khriciadivino'@'localhost';
FLUSH PRIVILEGES;
```

### Run Migrations

```bash
# Show migration status
php bin/console doctrine:migrations:status

# Execute all pending migrations
php bin/console doctrine:migrations:migrate

# Rollback last migration
php bin/console doctrine:migrations:migrate prev

# Execute specific migration
php bin/console doctrine:migrations:execute 'DoctrineMigrations\Version20260322100000' --up
```

### Create New Migration

```bash
# Generate migration from entity changes
php bin/console make:migration

# Review the migration file
cat migrations/Version*.php

# Execute the migration
php bin/console doctrine:migrations:migrate
```

### Backup Database

```bash
# PostgreSQL
pg_dump khriciadivino > backup_$(date +%Y%m%d_%H%M%S).sql

# MySQL
mysqldump -u khriciadivino -p khriciadivino > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore
psql khriciadivino < backup_20260310_153000.sql
mysql -u khriciadivino -p khriciadivino < backup_20260310_153000.sql
```

---

## Authentication Configuration

### JWT Authentication Setup

**File:** `config/packages/lexik_jwt_authentication.yaml`

```yaml
lexik_jwt_authentication:
  secret_key: '%env(resolve:JWT_SECRET_KEY)%'
  public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
  pass_phrase: '%env(JWT_PASSPHRASE)%'
  token_ttl: 3600
  clock_skew: 0
  user_identity_field: email
```

### Add Service to .env

```env
JWT_SECRET_KEY=config/jwt/private.pem
JWT_PUBLIC_KEY=config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase_here
JWT_TOKEN_TTL=3600
```

### Test JWT Token

```bash
# Get token
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password123"
  }' | jq .

# Decode token (using jwt.io or)
# Echo your token and decode at https://jwt.io

# Use token
curl http://localhost:8000/api/customers \
  -H "Authorization: Bearer <YOUR_TOKEN>"
```

---

## Environment Variables

### Required Variables

```env
# App Configuration
APP_NAME=Khriciadivino
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=your-secret-key-here

# Database
DATABASE_URL=postgresql://khriciadivino:password@localhost:5432/khriciadivino
# or MySQL: mysql://khriciadivino:password@localhost:3306/khriciadivino

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase

# CORS
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$

# Email
MAILER_DSN=smtp://localhost:25

# OAuth (Google)
OAUTH_GOOGLE_CLIENT_ID=your-client-id
OAUTH_GOOGLE_CLIENT_SECRET=your-client-secret
```

### Optional Variables

```env
# Redis caching
REDIS_URL=redis://localhost:6379

# Session
SESSION_HANDLER=redis

# Logging
MONOLOG_HANDLER_LEVEL=INFO

# File uploads
UPLOAD_DIR=public/uploads
MAX_UPLOAD_SIZE=10485760
```

---

## Running the Application

### Development Server

```bash
# Start Symfony server
symfony server:start

# Start with specific port
symfony server:start --port=8080

# Stop server
symfony server:stop

# Server in background
symfony server:start -d

# View server logs
symfony server:log
```

### Production Server

```bash
# Clear production cache
php bin/console cache:clear --env=prod

# Warm up cache
php bin/console cache:warmup --env=prod

# Start application with web server
php -S localhost:8000 -t public

# Or via systemd service
sudo systemctl start khriciadivino
sudo systemctl status khriciadivino
```

### Docker (Optional)

**Dockerfile:**

```dockerfile
FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    postgresql-client \
    mysql-client \
    git \
    curl

WORKDIR /var/www/khriciadivino

COPY composer.* ./
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader

COPY . .
RUN php bin/console cache:warmup --env=prod

CMD ["php-fpm"]
```

**docker-compose.yaml:**

```yaml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "9000:9000"
    environment:
      DATABASE_URL: postgresql://user:password@db:5432/khriciadivino
    depends_on:
      - db
    volumes:
      - .:/var/www/khriciadivino

  db:
    image: postgres:15
    environment:
      POSTGRES_DB: khriciadivino
      POSTGRES_USER: user
      POSTGRES_PASSWORD: password
    volumes:
      - db_data:/var/lib/postgresql/data

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      - .:/var/www/khriciadivino
    depends_on:
      - app

volumes:
  db_data:
```

---

## Testing

### Unit Tests

```bash
# Run all tests
php bin/phpunit

# Run specific test file
php bin/phpunit tests/Unit/EntityTest.php

# Run with coverage
php bin/phpunit --coverage-html coverage/
```

### API Tests

```bash
# Using Postman
# 1. Import Customer_API_Postman_Collection.json
# 2. Set base URL environment variable
# 3. Click "Run Collection"

# Using curl
./test-api.ps1  # PowerShell script included
```

---

## Troubleshooting

### Common Issues

#### 1. Database Connection Failed

```bash
# Check database service
sudo systemctl status postgresql
# or mysql

# Test connection
psql -U khriciadivino -d khriciadivino
# or mysql -u khriciadivino -p khriciadivino

# Fix: Update DATABASE_URL in .env
```

#### 2. JWT Keys Missing

```bash
# Regenerate keys
php bin/console lexik:jwt:generate-keypair

# Verify permissions
ls -la config/jwt/
chmod 600 config/jwt/private.pem
chmod 644 config/jwt/public.pem
```

#### 3. Cache Issues

```bash
# Clear all caches
php bin/console cache:clear

# Clear specific cache
php bin/console cache:clear --env=prod

# Rebuild cache
php bin/console cache:warmup
```

#### 4. Permission Errors

```bash
# Fix var directory permissions
chmod -R 777 var/
chmod -R 777 public/uploads/

# Fix ownership
chown -R www-data:www-data /var/www/khriciadivino
```

#### 5. 401 Unauthorized on API Calls

```bash
# Check token validity
# 1. Verify token not expired
# 2. Check Authorization header format: "Authorization: Bearer TOKEN"
# 3. Verify JWT keys exist and match

# Debug token
php bin/console lexik:jwt:token:decode <YOUR_TOKEN>
```

### Enable Debug Mode

```bash
# For development only!
echo "APP_DEBUG=1" >> .env.local
php bin/console cache:clear
```

### Check Logs

```bash
# View application logs
tail -f var/log/prod.log
tail -f var/log/dev.log

# View web server logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/php-fpm.log
```

---

## Performance Optimization

### Caching

```bash
# Configure Redis caching in .env
CACHE_PROVIDER=redis://localhost:6379/1

# Clear cache
php bin/console cache:clear --env=prod
```

### Database Optimization

```bash
# Add indexes for frequently queried fields
ALTER TABLE `customer` ADD INDEX `idx_email` (`email`);
ALTER TABLE `orders` ADD INDEX `idx_customer_id` (`customer_id`);
ALTER TABLE `orders` ADD INDEX `idx_status` (`status`);
```

### Frontend Optimization

```bash
# Production build
npm run build

# Minify CSS/JS
npm run encore production
```

---

## Backup & Recovery

### Automated Backups

**Script:** `scripts/backup.sh`

```bash
#!/bin/bash
BACKUP_DIR="/backups/khriciadivino"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Backup database
pg_dump khriciadivino > "$BACKUP_DIR/db_$TIMESTAMP.sql"

# Backup uploads
tar -czf "$BACKUP_DIR/uploads_$TIMESTAMP.tar.gz" public/uploads/

# Keep only last 7 days
find "$BACKUP_DIR" -type f -mtime +7 -delete
```

### Recovery Process

```bash
# Restore database
psql khriciadivino < backups/db_20260310_153000.sql

# Restore uploads
tar -xzf backups/uploads_20260310_153000.tar.gz -C public/

# Verify integrity
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM user"
```

---

## Support

- **Documentation:** See `API_DOCUMENTATION_COMPLETE.md`
- **Issues:** GitHub Issues tracker
- **Contact:** support@khriciadivino.example.com

---

**End of Installation & Setup Guide**
