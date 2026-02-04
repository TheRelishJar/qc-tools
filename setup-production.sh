#!/bin/bash
# QC Tools - Initial Production Setup
# Run this ONCE after cloning repository to production server

set -e

echo "[SETUP] QC Tools - Initial Production Setup"
echo "========================================"
echo ""

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Verify we're in the right place
if [ ! -f "artisan" ]; then
    echo -e "${RED}[ERROR] Error: Must run from Laravel project root (/srv/prod/qc-tools)${NC}"
    exit 1
fi

echo -e "${YELLOW}[WARNING]  This is a ONE-TIME setup script${NC}"
echo "Run deploy.sh for subsequent updates"
echo ""

# Install minimal system libraries for Puppeteer's Chrome
echo "[INSTALL] Installing minimal system libraries for PDF generation..."
echo "These are safe, standard libraries that won't affect existing apps"
echo ""

sudo yum install -y \
    nss \
    atk \
    cups-libs \
    libdrm \
    libXcomposite \
    libXdamage \
    libXrandr \
    libgbm \
    alsa-lib \
    liberation-fonts \
    dejavu-sans-fonts

if [ $? -ne 0 ]; then
    echo -e "${RED}[ERROR] System library installation failed${NC}"
    exit 1
fi
echo -e "${GREEN}[OK] System libraries installed${NC}"
echo ""

# Install PHP dependencies
echo "[INSTALL] Installing PHP dependencies..."
composer install --optimize-autoloader --no-dev

if [ $? -ne 0 ]; then
    echo -e "${RED}[ERROR] Composer install failed${NC}"
    exit 1
fi
echo -e "${GREEN}[OK] PHP dependencies installed${NC}"
echo ""

# Install Node dependencies (including Puppeteer with bundled Chrome)
echo "[INSTALL] Installing Node dependencies..."
echo "This will download Puppeteer's bundled Chrome (~200MB)"
npm install

if [ $? -ne 0 ]; then
    echo -e "${RED}[ERROR] NPM install failed${NC}"
    exit 1
fi
echo -e "${GREEN}[OK] Node dependencies installed${NC}"
echo ""

# Verify Puppeteer Chrome downloaded
if [ -d "node_modules/puppeteer/.local-chromium" ]; then
    echo -e "${GREEN}[OK] Puppeteer Chrome downloaded successfully${NC}"
    
    # Test Puppeteer can launch
    echo "[TEST] Testing Puppeteer..."
    node -e "const puppeteer = require('puppeteer'); puppeteer.launch({args: ['--no-sandbox']}).then(browser => { console.log('[OK] Puppeteer test passed'); browser.close(); });" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}[OK] Puppeteer is working${NC}"
    else
        echo -e "${YELLOW}[WARNING]  Puppeteer test failed - PDF generation may have issues${NC}"
    fi
else
    echo -e "${YELLOW}[WARNING]  Puppeteer Chrome not found - run 'npm install puppeteer' manually${NC}"
fi
echo ""

# Build production assets
echo "[BUILD]  Building production assets..."
npm run build

if [ $? -ne 0 ]; then
    echo -e "${RED}[ERROR] Asset build failed${NC}"
    exit 1
fi
echo -e "${GREEN}[OK] Assets built${NC}"
echo ""

# Create .env file if it doesn't exist
if [ ! -f ".env" ]; then
    echo "[CONFIG] Creating .env file from example..."
    cp .env.example .env
    php artisan key:generate
    echo -e "${GREEN}[OK] .env file created${NC}"
    echo ""
    echo -e "${YELLOW}[WARNING]  IMPORTANT: Edit .env file now with:${NC}"
    echo "  - Database credentials"
    echo "  - APP_URL=https://tools.quincycompressor.com"
    echo "  - APP_ENV=production"
    echo "  - APP_DEBUG=false"
    echo ""
    echo "Then run:"
    echo "  php artisan migrate --force"
    echo "  php artisan db:seed --force"
    echo ""
else
    echo "[OK] .env file already exists"
    echo ""
fi

# Set proper permissions
echo "[PERMS] Setting proper permissions..."
sudo chown -R nginx:nginx /srv/prod/qc-tools
sudo chmod -R 755 /srv/prod/qc-tools
sudo chmod -R 775 /srv/prod/qc-tools/storage /srv/prod/qc-tools/bootstrap/cache
echo -e "${GREEN}[OK] Permissions set${NC}"
echo ""

# Make deploy script executable
if [ -f "deploy.sh" ]; then
    chmod +x deploy.sh
    echo -e "${GREEN}[OK] deploy.sh is executable${NC}"
fi
echo ""

echo "=========================================="
echo -e "${GREEN}[OK] INITIAL SETUP COMPLETE${NC}"
echo "=========================================="
echo ""
echo "[CONFIG] Next steps:"
echo "1. Edit .env file with production settings:"
echo "   nano /srv/prod/qc-tools/.env"
echo ""
echo "2. Run database migrations:"
echo "   php artisan migrate --force"
echo "   php artisan db:seed --force"
echo ""
echo "3. Update Nginx config to point to this app"
echo "   (Follow DEPLOYMENT_GUIDE.md)"
echo ""
echo "4. For future updates, just run:"
echo "   ./deploy.sh"
echo ""