#!/bin/bash
# jenkins/scripts/test.sh
set -euo pipefail

echo "======================================"
echo " Starting Test Process"
echo "======================================"

if [ ! -f ".env" ]; then
    echo "Creating temporary test environment file..."
    cp .env.example .env.test
    export ENV_FILE=".env.test"
else
    export ENV_FILE=".env"
fi

echo "[1/3] Spinning up test environment..."
docker-compose -f docker-compose.prod.yml --env-file ${ENV_FILE} up -d

echo "[2/3] Waiting for services to be ready (15s)..."
sleep 15

echo "[3/3] Running HTTP Health Check..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health || echo "000")

if [ "$HTTP_CODE" != "200" ]; then
    echo "❌ Health check failed: HTTP $HTTP_CODE"
    docker-compose -f docker-compose.prod.yml logs
    echo "Tearing down test environment..."
    docker-compose -f docker-compose.prod.yml down
    rm -f .env.test
    exit 1
fi

echo "✅ Container health check passed (HTTP 200)"

echo "Tearing down test environment..."
docker-compose -f docker-compose.prod.yml down
rm -f .env.test

echo "======================================"
echo " Test Process Completed Successfully"
echo "======================================"
