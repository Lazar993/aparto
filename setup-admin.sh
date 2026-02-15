#!/bin/bash

# Script to set up admin user with proper roles and permissions

echo "Setting up admin user with Spatie roles..."

php artisan shield:install --fresh

php artisan shield:generate --all

echo ""
echo "Now run this in tinker to assign super_admin role to your user:"
echo ""
echo "php artisan tinker"
echo ""
echo "Then run:"
echo "\$user = App\Models\User::where('email', 'your@email.com')->first();"
echo "\$user->assignRole('super_admin');"
echo ""
