#!/bin/bash
# ================================================
# EC2 Ubuntu Server Bootstrap
# Run ONCE on fresh EC2 instance as ubuntu user
# Usage: sudo bash install-server.sh
# ================================================

set -euo pipefail
LOG="/var/log/feastly-install.log"
exec > >(tee -a $LOG) 2>&1

echo "================================================"
echo "  Feastly Server Bootstrap — $(date)"
echo "================================================"

# ---- System Update ----
echo "[1/7] Updating system packages..."
apt-get update -qq && apt-get upgrade -y -qq

# ---- Essential Tools ----
echo "[2/7] Installing essential tools..."
apt-get install -y -qq \
    curl wget git unzip htop \
    ufw fail2ban \
    apt-transport-https ca-certificates \
    gnupg lsb-release

# ---- Docker ----
echo "[3/7] Installing Docker..."
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] \
    https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" \
    > /etc/apt/sources.list.d/docker.list
apt-get update -qq
apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
usermod -aG docker ubuntu
systemctl enable --now docker
echo "Docker installed: $(docker --version)"

# ---- Docker Compose ----
echo "[4/7] Installing Docker Compose..."
COMPOSE_VERSION=$(curl -s https://api.github.com/repos/docker/compose/releases/latest | grep '"tag_name"' | cut -d'"' -f4)
curl -SL "https://github.com/docker/compose/releases/download/${COMPOSE_VERSION}/docker-compose-linux-x86_64" \
    -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose
echo "Docker Compose: $(docker-compose --version)"

# ---- Java (for Jenkins) ----
echo "[5/7] Installing Java 17..."
apt-get install -y openjdk-17-jdk
java -version

# ---- Jenkins ----
echo "[6/7] Installing Jenkins..."
curl -fsSL https://pkg.jenkins.io/debian-stable/jenkins.io-2023.key | tee /usr/share/keyrings/jenkins-keyring.asc > /dev/null
echo "deb [signed-by=/usr/share/keyrings/jenkins-keyring.asc] https://pkg.jenkins.io/debian-stable binary/" \
    > /etc/apt/sources.list.d/jenkins.list
apt-get update -qq && apt-get install -y jenkins
usermod -aG docker jenkins
systemctl enable --now jenkins
echo "Jenkins installed and running on port 8080"

# ---- Firewall ----
echo "[7/7] Configuring UFW firewall..."
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh         # 22
ufw allow 80/tcp      # HTTP (app)
ufw allow 443/tcp     # HTTPS (future)
ufw allow 8080/tcp    # Jenkins
ufw --force enable
echo "Firewall configured"

# ---- App Directory ----
mkdir -p /home/ubuntu/feastly /home/ubuntu/feastly/logs
chown -R ubuntu:ubuntu /home/ubuntu/feastly

# ---- fail2ban ----
systemctl enable --now fail2ban

echo ""
echo "================================================"
echo "  ✅ Server setup complete!"
echo "  Jenkins initial password:"
cat /var/lib/jenkins/secrets/initialAdminPassword 2>/dev/null || echo "  (run: sudo cat /var/lib/jenkins/secrets/initialAdminPassword)"
echo "================================================"
