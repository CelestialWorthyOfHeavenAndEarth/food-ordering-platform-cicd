#!/bin/bash
# Called by Jenkinsfile on EC2 during deployment
set -euo pipefail

APP_DIR="/home/ubuntu/feastly"
COMPOSE_FILE="docker-compose.prod.yml"

cd $APP_DIR

echo "[deploy] Pulling latest code..."
git pull origin main

echo "[deploy] Building and starting containers..."
docker-compose -f $COMPOSE_FILE up -d --build --remove-orphans

echo "[deploy] Cleaning up unused images..."
docker image prune -f

echo "[deploy] Container status:"
docker-compose -f $COMPOSE_FILE ps

echo "[deploy] ✅ Deployment complete at $(date)"
