#!/bin/bash
# Run as a cron job: */5 * * * * /home/ubuntu/feastly/scripts/health-check.sh
set -euo pipefail

APP_URL="http://localhost/health"
APP_DIR="/home/ubuntu/feastly"
LOG="/var/log/feastly-health.log"
COMPOSE_FILE="docker-compose.prod.yml"

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 $APP_URL || echo "000")

if [ "$HTTP_CODE" != "200" ]; then
    echo "[$(date)] ❌ Health check FAILED (HTTP $HTTP_CODE). Restarting..." >> $LOG
    cd $APP_DIR
    docker-compose -f $COMPOSE_FILE restart nginx php
    sleep 15
    HTTP_CODE_RETRY=$(curl -s -o /dev/null -w "%{http_code}" $APP_URL || echo "000")
    echo "[$(date)] Restart result: HTTP $HTTP_CODE_RETRY" >> $LOG
else
    echo "[$(date)] ✅ OK (HTTP $HTTP_CODE)" >> $LOG
fi
