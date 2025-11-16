#!/bin/bash

# B2B Wholesale Platform - Automated Installation Script
# Usage: ./install.sh

set -e

echo "=================================="
echo "B2B Wholesale Platform Installer"
echo "=================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -eq 0 ]; then 
    echo -e "${RED}Please do not run this script as root${NC}"
    exit 1
fi

# Check PHP version
echo "Checking PHP version..."
PHP_VERSION=$(php -r "echo PHP_VERSION;" 2>/dev/null || echo "0")
if [ "$(printf '%s\n' "8.2" "$PHP_VERSION" | sort -V | head -n1)" != "8.2" ]; then
    echo -e "${RED}PHP 8.2 or higher is required. Current version: $PHP_VERSION${NC}"
    exit 1
fi
echo -e "${GREEN}✓ PHP version OK ($PHP_VERSION)${NC}"

# Check Composer
echo "Checking Composer..."
if ! command -v composer &> /dev/null; then
    echo -e "${RED}Composer is not installed${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Composer found${NC}"

# Install dependencies
echo ""
echo "Installing dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

# Environment setup
if [ ! -f .env ]; then
    echo ""
    echo "Setting up environment file..."
    cp .env.example .env
    php artisan key:generate
    echo -e "${GREEN}✓ Environment file created${NC}"
else
    echo -e "${YELLOW}⚠ .env file already exists, skipping...${NC}"
fi

# Database configuration
echo ""
read -p "Configure database now? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    read -p "Database name: " DB_NAME
    read -p "Database user: " DB_USER
    read -sp "Database password: " DB_PASS
    echo
    
    # Update .env
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=$DB_USER/" .env
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" .env
    
    echo -e "${GREEN}✓ Database configured${NC}"
fi

# Run migrations
echo ""
read -p "Run database migrations? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate --force
    echo -e "${GREEN}✓ Migrations completed${NC}"
fi

# Seed demo data
echo ""
read -p "Seed demo data? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan db:seed --class=DemoDataSeeder
    echo -e "${GREEN}✓ Demo data seeded${NC}"
    echo ""
    echo "Demo credentials:"
    echo "  Admin: admin@b2b-platform.com / password"
    echo "  Vendor 1: vendor1@example.com / password"
fi

# Storage link
echo ""
echo "Creating storage link..."
php artisan storage:link
echo -e "${GREEN}✓ Storage linked${NC}"

# Generate AI data
echo ""
read -p "Generate AI recommendations and predictions? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Calculating recommendations..."
    php artisan recommendations:calculate
    echo "Generating predictions..."
    php artisan predictions:generate
    echo -e "${GREEN}✓ AI data generated${NC}"
fi

# Set permissions
echo ""
echo "Setting permissions..."
chmod -R 775 storage bootstrap/cache
echo -e "${GREEN}✓ Permissions set${NC}"

# Installation complete
echo ""
echo "=================================="
echo -e "${GREEN}Installation completed!${NC}"
echo "=================================="
echo ""
echo "Next steps:"
echo "  1. Review .env configuration"
echo "  2. Configure web server (see DEPLOYMENT_GUIDE.md)"
echo "  3. Set up cron jobs (see DEPLOYMENT_GUIDE.md)"
echo "  4. Start development server: php artisan serve"
echo ""
echo "Documentation:"
echo "  - README.md - Project overview"
echo "  - DEPLOYMENT_GUIDE.md - Production deployment"
echo "  - API_DOCUMENTATION.md - API reference"
echo ""
