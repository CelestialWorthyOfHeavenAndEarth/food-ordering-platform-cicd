# 🍽️ Feastly — Automated DevOps Food Ordering Platform

![Build Status](https://img.shields.io/badge/Build-Passing-brightgreen?style=for-the-badge&logo=jenkins)
![Docker](https://img.shields.io/badge/Docker-Containerized-blue?style=for-the-badge&logo=docker)
![AWS](https://img.shields.io/badge/AWS-EC2%20Hosted-orange?style=for-the-badge&logo=amazonaws)

A production-grade, highly automated food ordering web platform. This project demonstrates modern **DevOps methodologies** including containerization, automated Continuous Integration / Continuous Deployment (CI/CD), and cloud-hosted microservice orchestration.

---

## 🏗️ Architecture & Tech Stack

This project was transformed from a traditional monolith into a resilient, containerized architecture deployed on an AWS EC2 instance.

*   **Infrastructure:** AWS EC2 (`t2.micro` Ubuntu) with Elastic IP.
*   **Orchestration:** Docker & Docker Compose.
*   **CI/CD Pipeline:** Jenkins (Automated GitHub Webhook integration).
*   **Frontend:** HTML5, CSS3 (Glassmorphism UI, Keyframe Animations), Vanilla JS.
*   **Backend:** PHP 8.2 (FPM) interacting securely with PDO.
*   **Database:** MySQL 8.0 with persistent volumes and auto-initialization via `.sql` seeds.
*   **Web Server:** Nginx 1.25 Reverse Proxy.

---

## 🚀 CI/CD Pipeline (Jenkins)

We built a "zero-touch" deployment pipeline. The moment code is pushed to the `main` branch, a GitHub Webhook triggers Jenkins to execute the following stages automatically:

1.  **📥 Checkout SCM:** Pulls the latest source code from GitHub.
2.  **🐳 Build Containers:** Utilizes Docker layer caching to build custom Nginx and PHP images efficiently.
3.  **🧪 Container Test (Staging):** 
    *   Spins up an isolated staging environment.
    *   Waits for MySQL initialization.
    *   Runs HTTP health checks against the `/health` endpoint to verify the build is stable.
4.  **🚀 Production Rollout:** 
    *   Injects dynamic `.env` configurations securely.
    *   Executes zero-downtime deployment using `docker-compose up -d --build`.
5.  **🧹 Automated Cleanup:** Cleans up orphaned images (`docker system prune`) to conserve disk space on the EC2 host.
6.  **✅ Post-Deploy Verify:** Runs a final health check on the live production containers.

---

## 🛠️ Infrastructure Solutions & DevOps Optimizations

Operating on a constrained environment (`t2.micro` with 1GB RAM) required specific architectural safeguards:

*   **OOM Panic Prevention:** Implemented a **2GB Virtual Swap File** mounted to `/etc/fstab` to prevent Linux from crashing during heavy PHP extension compilation in Docker.
*   **Self-Healing Containers:** All services are configured with `restart: always`. If the instance reboots or a container crashes, the platform automatically recovers.
*   **Zero Hardcoded Secrets:** Migrated all database credentials out of the codebase. A `.env.example` template is converted into a secure `.env` file at deploy time, dynamically injecting root passwords.
*   **Session Security Handling:** Implemented correct cookie security attributes (`SESSION_SECURE=false` for HTTP environments) to resolve CSRF token mismatches and session drops over unencrypted connections.

---

## 💻 Running the Project Locally

If you want to spin up the entire microservice architecture on your local machine, you only need Docker installed.

1. **Clone the repository:**
   ```bash
   git clone https://github.com/CelestialWorthyOfHeavenAndEarth/food-ordering-platform-cicd.git
   cd food-ordering-platform-cicd
   ```

2. **Configure Environment:**
   ```bash
   cp .env.example .env
   # Open .env and update the passwords if desired
   ```

3. **Launch Containers:**
   ```bash
   docker-compose -f docker-compose.prod.yml up -d --build
   ```

4. **Access the application:**
   Navigate to `http://localhost` in your browser. The MySQL database will automatically seed itself with menu items upon initial startup.

---

## 📝 Acknowledgements
The frontend design and backend functionality of this application were originally based on the foundation provided by [Prachi060604](https://github.com/Prachi060604/Food-Ordering-Website-using-php), heavily refactored and containerized for cloud deployment.
