#!/bin/bash

# Digital Ocean Droplet Deployment Setup Script
# Run this script ONCE on your droplet to prepare for auto-deployments

echo "╔═══════════════════════════════════════════════════════════════════╗"
echo "║                                                                   ║"
echo "║        Digital Ocean Auto-Deployment Setup                       ║"
echo "║                                                                   ║"
echo "╚═══════════════════════════════════════════════════════════════════╝"
echo ""

# Variables - EDIT THESE
PROJECT_DIR="/var/www/brightedge-assignment"
REPO_URL="https://github.com/196y1a0213/brightedge-assignment.git"
WEB_USER="www-data"  # Change if different

echo "1. Creating project directory..."
sudo mkdir -p $PROJECT_DIR
cd $PROJECT_DIR

echo "2. Cloning repository..."
if [ -d ".git" ]; then
    echo "   Repository already cloned, pulling latest..."
    git pull origin main
else
    echo "   Cloning from GitHub..."
    sudo git clone $REPO_URL .
fi

echo "3. Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

echo "4. Creating logs directory..."
mkdir -p logs
sudo chmod -R 775 logs

echo "5. Setting permissions..."
sudo chown -R $WEB_USER:$WEB_USER $PROJECT_DIR
sudo chmod -R 755 $PROJECT_DIR

echo "6. Creating .env file..."
if [ ! -f ".env" ]; then
    cp .env.example .env
    echo "   ⚠️  Please edit .env file with your configuration"
else
    echo "   .env already exists"
fi

echo ""
echo "✅ Setup complete!"
echo ""
echo "NEXT STEPS:"
echo "1. Configure your web server (Apache/Nginx) to point to: $PROJECT_DIR/public"
echo "2. Edit .env file if needed: $PROJECT_DIR/.env"
echo "3. Set up GitHub Secrets (see DEPLOYMENT_SETUP.md)"
echo ""

