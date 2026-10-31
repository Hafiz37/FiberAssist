#!/bin/bash

# Stop FiberAssist WhatsApp Bot

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}=== Stopping FiberAssist WhatsApp Bot ===${NC}\n"

# Stop localtunnel
if [ -f logs/tunnel.pid ]; then
    PID=$(cat logs/tunnel.pid)
    if ps -p $PID > /dev/null 2>&1; then
        kill $PID
        echo -e "${GREEN}✅ Localtunnel stopped (PID: $PID)${NC}"
        rm logs/tunnel.pid
    else
        echo -e "${YELLOW}⚠️  Localtunnel not running${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  No tunnel PID file found${NC}"
fi

# Kill any remaining localtunnel processes
pkill -f "localtunnel" 2>/dev/null && echo -e "${GREEN}✅ Killed remaining localtunnel processes${NC}"

echo ""
echo -e "${GREEN}✅ Bot stopped!${NC}"
echo ""
