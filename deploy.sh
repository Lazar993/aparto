#!/bin/bash

# Deployment script for Stan na Dan Laravel application
# Run this on the server after git pull

echo "🚀 Starting deployment..."

# Pull latest changes
echo "📥 Pulling latest code from git..."
git pull origin master

# Install/update composer dependencies
echo "📦 Installing composer dependencies..."
composer install --no-dev --optimize-autoloader

# Install/update npm dependencies
echo "📦 Installing npm dependencies..."
npm install

# Build frontend assets
echo "🔨 Building production assets..."
npm run build

# Clear and cache Laravel configs
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Cache config for production
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations (if needed)
# Uncomment the next line if you want to auto-run migrations
# php artisan migrate --force

# Set proper permissions
echo "🔒 Setting permissions..."
chmod -R 775 storage bootstrap/cache

echo "✅ Deployment complete!"
