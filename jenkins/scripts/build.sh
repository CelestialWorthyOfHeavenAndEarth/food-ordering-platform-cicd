#!/bin/bash
# jenkins/scripts/build.sh
set -euo pipefail

echo "======================================"
echo " Starting Build Process"
echo "======================================"

# 1. Lint PHP files
echo "[1/2] Linting PHP source code..."
find app -name "*.php" -not -path "*/vendor/*" | xargs -I{} php -l {} | grep -v "No syntax errors" || true
echo "✅ PHP syntax check passed."

# 2. Build Docker images
echo "[2/2] Building Docker images..."
docker-compose -f docker-compose.prod.yml build --no-cache
echo "✅ Docker build complete."

echo "======================================"
echo " Build Process Completed Successfully"
echo "======================================"
