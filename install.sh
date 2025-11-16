#!/bin/bash

# B2B Platform Installation Script
# This script automates the installation process

set -e  # Exit on error

echo "=========================================="
echo "B2B Wholesale Platform - Installation"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo -e "${RED}Error: Composer is not installed. Please install composer first.${NC}"
    exit 1
fi

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo -e "${RED}Error: PHP is not installed. Please install PHP 8.2 or higher.${NC}"
    exit 1
fi

# Check PHP version
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
PHP_MAJOR=$(echo $PHP_VERSION | cut -d. -f1)
PHP_MINOR=$(echo $PHP_VERSION | cut -d. -f2)

if [ "$PHP_MAJOR" -lt 8 ] || ([ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -lt 2 ]); then
    echo -e "${RED}Error: PHP 8.2 or higher is required. Current version: $PHP_VERSION${NC}"
    exit 1
fi

echo -e "${GREEN}✓ PHP $PHP_VERSION detected${NC}"

# Step 1: Install composer dependencies
echo ""
echo -e "${YELLOW}Step 1: Installing Composer dependencies...${NC}"
composer install

# Step 2: Create .env file if it doesn't exist
echo ""
echo -e "${YELLOW}Step 2: Setting up environment file...${NC}"
if [ ! -f .env ]; then
    cp .env.example .env
    echo -e "${GREEN}✓ .env file created${NC}"
else
    echo -e "${YELLOW}! .env file already exists, skipping...${NC}"
fi

# Step 3: Generate application key
echo ""
echo -e "${YELLOW}Step 3: Generating application key...${NC}"
php artisan key:generate --force

# Step 4: Prompt for database configuration
echo ""
echo -e "${YELLOW}Step 4: Database configuration${NC}"
read -p "Do you want to configure the database now? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    read -p "Database name: " DB_NAME
    read -p "Database username [root]: " DB_USER
    DB_USER=${DB_USER:-root}
    read -sp "Database password: " DB_PASS
    echo

    # Update .env file
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=$DB_USER/" .env
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" .env

    echo -e "${GREEN}✓ Database configuration updated${NC}"

    # Ask to create database
    read -p "Do you want to create the database? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        mysql -u$DB_USER -p$DB_PASS -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        echo -e "${GREEN}✓ Database created${NC}"
    fi
fi

# Step 5: Run migrations
echo ""
echo -e "${YELLOW}Step 5: Running database migrations...${NC}"
read -p "Do you want to run migrations now? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate --force
    echo -e "${GREEN}✓ Migrations completed${NC}"

    # Ask to run seeders
    read -p "Do you want to seed the database with demo data? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        php artisan db:seed --force
        echo -e "${GREEN}✓ Database seeded${NC}"
    fi
fi

# Step 6: Create storage link
echo ""
echo -e "${YELLOW}Step 6: Creating storage symbolic link...${NC}"
php artisan storage:link

# Step 7: Set permissions
echo ""
echo -e "${YELLOW}Step 7: Setting permissions...${NC}"
chmod -R 775 storage bootstrap/cache
echo -e "${GREEN}✓ Permissions set${NC}"

# Step 8: Cache configuration (optional for production)
echo ""
read -p "Do you want to cache config/routes/views? (recommended for production) (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    echo -e "${GREEN}✓ Configuration cached${NC}"
fi

# Final message
echo ""
echo "=========================================="
echo -e "${GREEN}Installation completed successfully!${NC}"
echo "=========================================="
echo ""
echo "Default accounts:"
echo "  Admin: admin@b2bplatform.com / password"
echo "  Vendor 1 (VIP): vendor1@example.com / password"
echo "  Vendor 2 (Gold): vendor2@example.com / password"
echo "  Vendor 3 (Standard): vendor3@example.com / password"
echo ""
echo "Next steps:"
echo "1. Start the server: php artisan serve"
echo "2. (Optional) Start queue worker: php artisan queue:work"
echo "3. (Optional) Start broadcasting: php artisan reverb:start"
echo ""
echo "API Base URL: http://localhost:8000/api"
echo "Documentation: README.md and PROJET_B2B_DOCUMENTATION.md"
echo ""
echo -e "${GREEN}Happy coding! 🚀${NC}"
