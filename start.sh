#!/bin/bash

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

PROJECT_DIR="/home/gopal-ichiro/Documents/FiberAssist"
WEB_DIR="/var/www/html/FiberAssist"
DB_NAME="db_sistem_pakar"
DB_USER="root"
DB_PASS="Sembarang_37"

TUNNEL_SUBDOMAIN="polite-turkeys-take"
WEBHOOK_URL="https://${TUNNEL_SUBDOMAIN}.loca.lt/FiberAssist/api/webhook_wa.php"

echo -e "${BLUE}=== FiberAssist - NetFix WhatsApp Bot Startup ===${NC}\n"

error_exit() {
    echo -e "${RED}❌ ERROR: $1${NC}" >&2
    exit 1
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

echo "1. Checking dependencies..."

if ! command -v mysql &> /dev/null; then
    error_exit "MySQL/MariaDB not found. Please install MySQL."
fi
success "MySQL found"

if ! command -v node &> /dev/null; then
    error_exit "Node.js not found. Please install Node.js."
fi
success "Node.js found ($(node -v))"

if ! systemctl is-active --quiet apache2 && ! systemctl is-active --quiet httpd; then
    info "Starting Apache..."
    sudo systemctl start apache2 2>/dev/null || sudo systemctl start httpd 2>/dev/null || error_exit "Failed to start Apache"
fi
success "Apache is running"

if ! systemctl is-active --quiet mysql && ! systemctl is-active --quiet mariadb; then
    info "Starting MySQL..."
    sudo systemctl start mysql 2>/dev/null || sudo systemctl start mariadb 2>/dev/null || error_exit "Failed to start MySQL"
fi
success "MySQL is running"

echo ""
echo "2. Checking database..."

DB_EXISTS=$(mysql -u"$DB_USER" -p"$DB_PASS" -e "SHOW DATABASES LIKE '$DB_NAME';" 2>/dev/null | grep -c "$DB_NAME" || echo "0")

if [ "$DB_EXISTS" -eq "0" ]; then
    info "Database not found. Importing..."
    if [ -f "$PROJECT_DIR/database/dataset_sistem_pakar.sql" ]; then
        mysql -u"$DB_USER" -p"$DB_PASS" < "$PROJECT_DIR/database/dataset_sistem_pakar.sql" 2>/dev/null || error_exit "Failed to import database"
        success "Database imported successfully"
    else
        error_exit "Database file not found"
    fi
else
    success "Database already exists"
fi

echo ""
echo "3. Setting up web server..."

if [ ! -L "$WEB_DIR" ]; then
    info "Creating symbolic link..."
    sudo ln -sf "$PROJECT_DIR" "$WEB_DIR" || error_exit "Failed to create symbolic link"
    success "Symbolic link created"
else
    success "Symbolic link already exists"
fi

info "Setting permissions..."
sudo chmod -R 755 "$PROJECT_DIR" || error_exit "Failed to set permissions"
sudo chown -R www-data:www-data "$WEB_DIR" 2>/dev/null || warning "Could not change ownership"

sudo chown -R $USER:$USER "$PROJECT_DIR/logs" 2>/dev/null || true

info "Restarting Apache..."
sudo systemctl restart apache2 2>/dev/null || sudo systemctl restart httpd 2>/dev/null || error_exit "Failed to restart Apache"
success "Apache restarted"

echo ""
echo "4. Starting Localtunnel..."

mkdir -p logs
chmod 755 logs

# Kill existing localtunnel processes
pkill -f "localtunnel" 2>/dev/null || true
sleep 2

info "Starting localtunnel with subdomain: ${TUNNEL_SUBDOMAIN}"
nohup npx localtunnel --port 80 --subdomain ${TUNNEL_SUBDOMAIN} > logs/tunnel.log 2>&1 &
TUNNEL_PID=$!
sleep 5

if ps -p $TUNNEL_PID > /dev/null; then
    echo "$TUNNEL_PID" > logs/tunnel.pid
    success "Localtunnel started (PID: $TUNNEL_PID)"
else
    warning "Localtunnel may have failed. Check logs/tunnel.log"
fi

echo ""
echo -e "${GREEN}=== ✅ FiberAssist WhatsApp Bot is now running! ===${NC}\n"
echo -e "${BLUE}📍 Important URLs:${NC}"
echo -e "   Webhook URL:    ${GREEN}${WEBHOOK_URL}${NC}"
echo -e "   Dashboard:      ${GREEN}http://localhost/FiberAssist/dashboard${NC}"
echo -e "   Test Connection: ${GREEN}http://localhost/FiberAssist/test_connection.php${NC}"
echo ""
echo -e "${BLUE}🤖 WhatsApp Bot:${NC}"
echo -e "   Bot Number:     ${GREEN}+6289681858965${NC}"
echo -e "   Send message:   ${YELLOW}Halo / START / 1${NC}"
echo ""
echo -e "${BLUE}📊 Monitor Logs:${NC}"
echo -e "   Webhook:        ${YELLOW}tail -f logs/webhook_wa.log${NC}"
echo -e "   Tunnel:         ${YELLOW}tail -f logs/tunnel.log${NC}"
echo ""
echo -e "${BLUE}⚙️  Meta Console Setup:${NC}"
echo -e "   1. Go to: ${YELLOW}https://developers.facebook.com/apps${NC}"
echo -e "   2. WhatsApp → Configuration → Edit Webhook"
echo -e "   3. Callback URL: ${GREEN}${WEBHOOK_URL}${NC}"
echo -e "   4. Verify Token: ${GREEN}netfix_webhook_token_2026${NC}"
echo -e "   5. Subscribe to field: ${GREEN}messages${NC}"
echo ""
echo -e "${YELLOW}💡 To stop:${NC}"
echo -e "   Localtunnel: ${YELLOW}kill \$(cat logs/tunnel.pid)${NC}"
echo -e "   Apache: ${YELLOW}sudo systemctl stop apache2${NC}"
echo ""
