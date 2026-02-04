#!/bin/bash
# QC Tools Production Deployment Script
# Run this on the PRODUCTION SERVER after git pull

set -e  # Exit immediately if any command fails

echo "[DEPLOY] QC Tools Production Deployment"
echo "===================================="
echo ""

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Ensure we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}[ERROR] Error: Must run from Laravel project root (/srv/prod/qc-tools)${NC}"
    exit 1
fi

echo -e "${YELLOW}[WARNING]  PRODUCTION DEPLOYMENT - Proceeding with caution${NC}"
echo ""

# Function to test existing sites
test_existing_sites() {
    echo "[TEST] Testing existing sites..."
    
    EXPRESS_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://express.quincycompressor.com)
    EMAIL_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://quincyemail.com)
    
    if [ "$EXPRESS_STATUS" -eq 200 ]; then
        echo -e "${GREEN}[OK] Express tool: OK${NC}"
    else
        echo -e "${RED}[ERROR] Express tool: FAILED (HTTP $EXPRESS_STATUS)${NC}"
        return 1
    fi
    
    if [ "$EMAIL_STATUS" -eq 200 ]; then
        echo -e "${GREEN}[OK] Email tool: OK${NC}"
    else
        echo -e "${RED}[ERROR] Email tool: FAILED (HTTP $EMAIL_STATUS)${NC}"
        return 1
    fi
    
    return 0
}

# Test existing sites BEFORE making changes
echo "[CHECK] Pre-deployment health check..."
if ! test_existing_sites; then
    echo -e "${RED}[ERROR] Existing sites are not healthy. Aborting deployment.${NC}"
    exit 1
fi
echo ""

# Pull latest changes
echo "[PULL] Pulling latest code from Git..."
git pull origin main

if [ $? -ne 0 ]; then
    echo -e "${RED}[ERROR] Git pull failed${NC}"
    exit 1
fi
echo -e "${GREEN}[OK] Code updated${NC}"
echo ""

# Check if composer.json or composer.lock changed
if git diff HEAD@{1} --name-only | grep -q "composer.json\|composer.lock"; then
    echo "[INSTALL] Composer files changed, updating PHP dependencies..."
    composer install --optimize-autoloader --no-dev
    echo -e "${GREEN}[OK] PHP dependencies updated${NC}"
else
    echo "[SKIP]  Composer files unchanged, skipping PHP dependencies"
fi
echo ""

# Check if package.json or package-lock.json changed
if git diff HEAD@{1} --name-only | grep -q "package.json\|package-lock.json"; then
    echo "[INSTALL] Package files changed, updating Node dependencies..."
    npm install
    echo -e "${GREEN}[OK] Node dependencies updated${NC}"
    
    # Check if Puppeteer downloaded Chrome
    if [ -d "node_modules/puppeteer/.local-chromium" ]; then
        echo -e "${GREEN}[OK] Puppeteer Chrome downloaded${NC}"
    else
        echo -e "${YELLOW}[WARNING]  Puppeteer Chrome not found - PDF generation may not work${NC}"
    fi
else
    echo "[SKIP]  Package files unchanged, skipping Node dependencies"
fi
echo ""

# Always rebuild assets
echo "[BUILD]  Building production assets..."
npm run build

if [ $? -ne 0 ]; then
    echo -e "${RED}[ERROR] Asset build failed${NC}"
    exit 1
fi
echo -e "${GREEN}[OK] Assets built${NC}"
echo ""

# Run database migrations
echo "[DATABASE]  Running database migrations..."
php artisan migrate --force

if [ $? -ne 0 ]; then
    echo -e "${RED}[ERROR] Migrations failed${NC}"
    exit 1
fi
echo -e "${GREEN}[OK] Migrations complete${NC}"
echo ""

# Clear and rebuild Laravel caches
echo "[CACHE] Clearing and rebuilding caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}[OK] Caches rebuilt${NC}"
echo ""

# Test existing sites AFTER changes
echo "[CHECK] Post-deployment health check..."
if ! test_existing_sites; then
    echo -e "${RED}[ERROR] WARNING: Existing sites failed after deployment!${NC}"
    echo -e "${YELLOW}Consider running rollback script: ~/rollback-qc-tools.sh${NC}"
    exit 1
fi
echo ""

# Reload PHP-FPM (graceful, no downtime)
echo "[RELOAD] Reloading PHP-FPM..."
sudo systemctl reload php8.2-fpm

if [ $? -ne 0 ]; then
    echo -e "${RED}[ERROR] PHP-FPM reload failed${NC}"
    exit 1
fi
echo -e "${GREEN}[OK] PHP-FPM reloaded${NC}"
echo ""

# Test Nginx configuration before reloading
echo "[TEST] Testing Nginx configuration..."
sudo nginx -t

if [ $? -ne 0 ]; then
    echo -e "${RED}[ERROR] Nginx configuration test failed${NC}"
    echo "Do NOT reload Nginx. Fix configuration first."
    exit 1
fi
echo -e "${GREEN}[OK] Nginx configuration valid${NC}"
echo ""

# Reload Nginx (graceful, no downtime)
echo "[RELOAD] Reloading Nginx..."
sudo systemctl reload nginx

if [ $? -ne 0 ]; then
    echo -e "${RED}[ERROR] Nginx reload failed${NC}"
    exit 1
fi
echo -e "${GREEN}[OK] Nginx reloaded${NC}"
echo ""

# Final health check
echo "[CHECK] Final health check..."
if ! test_existing_sites; then
    echo -e "${RED}[ERROR] CRITICAL: Existing sites failed after Nginx reload!${NC}"
    echo -e "${YELLOW}Run rollback immediately: ~/rollback-qc-tools.sh${NC}"
    exit 1
fi
echo ""

# Test QC Tools site
echo "[TEST] Testing QC Tools deployment..."
QC_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://tools.quincycompressor.com)

if [ "$QC_STATUS" -eq 200 ]; then
    echo -e "${GREEN}[OK] QC Tools: Live and responding${NC}"
else
    echo -e "${YELLOW}[WARNING]  QC Tools: HTTP $QC_STATUS (may need additional configuration)${NC}"
fi
echo ""

# Success summary
echo "=========================================="
echo -e "${GREEN}[OK] DEPLOYMENT COMPLETE${NC}"
echo "=========================================="
echo ""
echo "[WEB] Sites Status:"
echo "  - Express Tool: https://express.quincycompressor.com"
echo "  - Email Tool: https://quincyemail.com"
echo "  - QC Tools: https://tools.quincycompressor.com"
echo ""
echo "[CHECK] Monitor logs:"
echo "  sudo tail -f /var/log/nginx/qc-tools-error.log"
echo "  sudo tail -f /srv/prod/qc-tools/storage/logs/laravel.log"
echo ""
echo "[TEST] Test PDF generation through the UI"
echo ""