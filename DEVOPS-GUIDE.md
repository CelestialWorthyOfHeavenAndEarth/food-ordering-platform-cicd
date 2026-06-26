# Feastly — Complete DevOps Tools Documentation
### How Every Tool Works, What It Does, and Why We Chose It

---

## Table of Contents
1. [Architecture Overview](#1-architecture-overview)
2. [Docker — Containerization](#2-docker--containerization)
3. [Docker Compose — Multi-Container Orchestration](#3-docker-compose--multi-container-orchestration)
4. [Nginx — Reverse Proxy & Web Server](#4-nginx--reverse-proxy--web-server)
5. [GitHub — Version Control & Webhook Trigger](#5-github--version-control--webhook-trigger)
6. [Jenkins — CI/CD Pipeline (6 Stages)](#6-jenkins--cicd-pipeline)
7. [Trivy — Container Security Scanner](#7-trivy--container-security-scanner)
8. [Prometheus — Metrics & Time-Series Database](#8-prometheus--metrics--time-series-database)
9. [cAdvisor — Container Resource Monitor](#9-cadvisor--container-resource-monitor)
10. [PHP-FPM Exporter — Application Metrics](#10-php-fpm-exporter--application-metrics)
11. [Grafana — Visualization & Dashboards](#11-grafana--visualization--dashboards)
12. [Loki — Log Aggregation Database](#12-loki--log-aggregation-database)
13. [Promtail — Log Shipping Agent](#13-promtail--log-shipping-agent)
14. [End-to-End Data Flow Diagram](#14-end-to-end-data-flow)
15. [Quick Reference — All Ports & URLs](#15-quick-reference--all-ports--urls)

---

## 1. Architecture Overview

The Feastly Food Ordering Platform is deployed on an **AWS EC2 instance** and runs entirely inside Docker containers. The entire infrastructure is managed by two complementary systems:

- **CI/CD Layer** — Jenkins + GitHub automatically build, test, scan, and deploy code on every commit.
- **Observability Layer** — Prometheus, Grafana, Loki, Promtail, cAdvisor, and PHP-FPM Exporter provide full visibility into application health, container resources, and logs in real-time.

**Total containers running in production: 10**
```
feastly_nginx_prod        — Web server (Port 80)
feastly_php_prod          — PHP application runtime
feastly_mysql_prod        — Database
feastly_prometheus        — Metrics database (Port 9090)
feastly_grafana           — Dashboards (Port 3000)
feastly_loki              — Log database (Port 3100)
feastly_promtail          — Log collector
feastly_cadvisor          — Container metrics (Port 8081)
feastly_php_fpm_exporter  — PHP metrics
Jenkins (host service)    — CI/CD server (Port 8080)
```

---

## 2. Docker — Containerization

### What is Docker?
Docker is a platform that packages an application and all of its dependencies (libraries, runtimes, configs) into a single, self-contained unit called a **container**. Containers run identically on any machine.

### Why we use it in Feastly
Without Docker, running the Feastly app would require manually installing PHP 8.2, MySQL 8, Nginx, and dozens of PHP extensions on the EC2 server. Any version mismatch would break the app. Docker eliminates this by locking the exact environment into an image.

### How it works in our project
We have **two custom Docker images** built from `Dockerfile` configurations:

**`docker/php/Dockerfile` — PHP Application Image**
```dockerfile
FROM php:8.2-fpm-alpine          # Exact PHP version locked
RUN apk add ... && docker-php-ext-install pdo pdo_mysql gd zip intl opcache
COPY php.ini /usr/local/etc/php/conf.d/custom.ini
COPY zz-status.conf /usr/local/etc/php-fpm.d/zz-status.conf  # Enables /status endpoint
EXPOSE 9000
CMD ["php-fpm"]
```

**`docker/nginx/Dockerfile` — Web Server Image**
- Uses an Nginx Alpine base image
- Bakes in our custom `nginx.conf` with security headers, rate limiting, and PHP-FPM integration

### Key Concept
Every time Jenkins builds, Docker rebuilds these images from scratch if the `Dockerfile` changed. This ensures the production image is always immutable and traceable to a specific git commit.

---

## 3. Docker Compose — Multi-Container Orchestration

### What is Docker Compose?
Docker Compose is a tool that lets you define and manage **multiple Docker containers at once** using a single YAML file (`docker-compose.prod.yml`). It handles networking, volumes, dependencies, and startup order automatically.

### Why we use it in Feastly
Starting 10 containers manually with individual `docker run` commands would be unmanageable and error-prone. Compose lets us declare the entire infrastructure as code and start everything with one command.

### How it works in our project

**`docker-compose.prod.yml`** defines the entire production stack:

```yaml
# Startup order enforced by 'depends_on'
nginx  → depends on  → php  → depends on  → mysql (must be healthy first)
grafana → depends on → prometheus + loki
php_fpm_exporter → depends on → php
```

**Networking:**
All containers are placed on a single isolated network: `feastly_prod`. This means:
- Nginx can reach PHP at `php:9000` by container name (internal DNS)
- Prometheus can reach cAdvisor at `cadvisor:8080`
- PHP-FPM exporter can reach PHP at `php:9000`
- **None of these ports are exposed to the public internet**

**Persistent Volumes (Data that survives container restarts):**
```yaml
mysql_data_prod   → MySQL database files persist even if container is destroyed
prometheus_data   → Prometheus metric history persists
grafana_data      → Grafana dashboards and settings persist
```

**Logging:**
Nginx is configured with `json-file` logging driver with rotation:
```yaml
logging:
  driver: "json-file"
  options: { max-size: "10m", max-file: "3" }
```
This prevents logs from filling up the disk while making them available to Promtail.

---

## 4. Nginx — Reverse Proxy & Web Server

### What is Nginx?
Nginx is a high-performance web server and reverse proxy. In our architecture, it sits at the front of all traffic and is the only container with a public-facing port (Port 80).

### How it works in our project (`docker/nginx/nginx.conf`)

**Traffic Flow:**
```
User Browser → EC2 IP:80 → Nginx Container → PHP-FPM Container (port 9000)
```

Nginx itself cannot run PHP. When a user hits `/menu.php`, Nginx passes the request via **FastCGI** protocol to the `php` container.

**Key configurations we implemented:**

| Feature | Config | Purpose |
|---|---|---|
| Rate Limiting | `limit_req_zone ... rate=30r/m` | API endpoints limited to 30 req/min |
| Login Protection | `limit_req_zone ... rate=5r/m` | Login endpoint limited to 5 req/min |
| Security Headers | `X-Frame-Options`, `X-XSS-Protection` | Prevents clickjacking & XSS |
| Gzip Compression | `gzip on` | Compresses CSS/JS by ~70% |
| Static Caching | `expires 30d` | Browser caches images/CSS for 30 days |
| File Blocking | `deny all` for `.env`, `.sql`, `.sh` | Prevents secret file exposure |
| Health Endpoint | `location /health { return 200 }` | Used by Jenkins to verify deployment |

---

## 5. GitHub — Version Control & Webhook Trigger

### What is GitHub?
GitHub is a cloud-based Git repository hosting service. It stores every version of the Feastly source code and acts as the **trigger point** for the entire CI/CD pipeline.

### How it works in our project

**Repository:** `github.com/CelestialWorthyOfHeavenAndEarth/food-ordering-platform-cicd`

The workflow is:
```
Developer pushes code to 'main' branch
        ↓
GitHub sends HTTP POST (Webhook) to Jenkins at http://35.169.185.9:8080
        ↓
Jenkins wakes up and starts the 6-stage pipeline automatically
```

**Webhook Setup:** GitHub is configured to notify Jenkins on every `push` event to the `main` branch. This eliminates the need to manually trigger builds — the pipeline is 100% automated.

**Branch Protection:** All infrastructure-as-code files (Dockerfiles, `docker-compose.prod.yml`, monitoring configs) are version-controlled. This means:
- Every change to the infrastructure is tracked
- You can roll back to any previous working configuration with `git revert`
- The git commit hash is tagged to each Jenkins build

---

## 6. Jenkins — CI/CD Pipeline

### What is Jenkins?
Jenkins is an open-source automation server. It is the **brain of the DevOps operation** — it listens for GitHub webhooks, then automatically runs a sequence of stages to validate, build, and deploy the application.

**Accessible at:** `http://35.169.185.9:8080`

### How it works in our project

The entire deployment process is written in a `Jenkinsfile` stored in the root of the repository. This is called **Pipeline-as-Code** — the deployment logic is version-controlled alongside the application.

```groovy
pipeline {
    agent any
    options {
        timeout(time: 20, unit: 'MINUTES')   // Kill runaway builds
        disableConcurrentBuilds()             // No parallel deployments
        buildDiscarder(logRotator(numToKeepStr: '10'))  // Keep last 10 logs
    }
```

### The 6 Pipeline Stages

---

#### Stage 1: 📥 Checkout
```groovy
stage('📥 Checkout') {
    steps {
        checkout scm
        env.GIT_COMMIT_SHORT = sh(script: 'git rev-parse --short HEAD', ...)
    }
}
```
**What it does:** Jenkins clones the latest code from GitHub into its workspace (`/var/lib/jenkins/workspace/feastly-pipeline/`). It also captures the short Git commit hash so it can be tagged to the build.

---

#### Stage 2: 🐳 Docker Build
```groovy
stage('🐳 Docker Build') {
    steps {
        sh 'docker-compose -f docker-compose.prod.yml build'
    }
}
```
**What it does:** Rebuilds the custom PHP and Nginx Docker images from the `Dockerfile` configurations. If the Dockerfile or any source file changed, Docker will rebuild the affected layers. Unchanged layers are pulled from cache, making builds fast.

---

#### Stage 3: 🛡️ Vulnerability Scan (Trivy)
```groovy
stage('🛡️ Vulnerability Scan') {
    steps {
        sh '''
            docker run --rm aquasec/trivy image \
                --severity HIGH,CRITICAL \
                feastly-pipeline-php:latest
        '''
    }
}
```
**What it does:** Runs the **Trivy** security scanner (see Section 7) against the freshly built PHP and Nginx images, scanning for known CVEs (Common Vulnerabilities and Exposures). Prevents shipping images with known security holes.

---

#### Stage 4: 🧪 Container Test
```groovy
stage('🧪 Container Test') {
    steps {
        sh '''
            docker-compose -f docker-compose.prod.yml up -d
            sleep 15
            HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health)
            if [ "$HTTP_CODE" != "200" ]; then exit 1; fi
            docker-compose -f docker-compose.prod.yml down
        '''
    }
}
```
**What it does:** Spins up the entire stack in detached mode, waits 15 seconds for everything to initialize, then performs a **health check** by hitting the `/health` endpoint. If it doesn't get HTTP 200, the pipeline fails and deployment is blocked. If it passes, the stack is brought down before production deployment.

---

#### Stage 5: 🚀 Deploy to EC2
```groovy
stage('🚀 Deploy to EC2') {
    steps {
        sh '''
            docker-compose -f docker-compose.prod.yml down || true
            docker-compose -f docker-compose.prod.yml up -d --build
            docker system prune -f
        '''
    }
}
```
**What it does:** Gracefully stops the existing production containers, starts the new ones with the freshly built images, and cleans up dangling/old Docker images to prevent disk space from filling up. The `|| true` ensures the pipeline doesn't fail if no containers were running.

---

#### Stage 6: ✅ Post-Deploy Verify
```groovy
stage('✅ Post-Deploy Verify') {
    steps {
        sh '''
            sleep 10
            HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health)
            if [ "$HTTP_CODE" = "200" ]; then echo "✅ Production health check passed"
            else exit 1; fi
        '''
    }
}
```
**What it does:** After the production containers start, Jenkins waits 10 seconds and performs a final health check against the live production endpoint. This is the **quality gate** — if the deployment broke something, this stage catches it and marks the build as FAILED.

---

## 7. Trivy — Container Security Scanner

### What is Trivy?
Trivy (by Aqua Security) is an open-source vulnerability scanner for container images. It scans Docker images against the **NVD (National Vulnerability Database)** and OS package advisories to detect known CVEs.

### Why we use it in Feastly
Without Trivy, we could unknowingly ship a PHP or Nginx image containing a critical security flaw. Trivy acts as an automated security gatekeeper — if a HIGH or CRITICAL vulnerability is found in an image, the pipeline fails and the image is never deployed.

### How it works in our project
Trivy runs inside the Jenkins pipeline as a Docker container itself (no installation needed):
```bash
docker run --rm -v /var/run/docker.sock:/var/run/docker.sock \
    aquasec/trivy image \
    --severity HIGH,CRITICAL \
    feastly-pipeline-php:latest
```
- It reads the Docker socket to access locally built images
- Scans OS packages (Alpine APK), PHP extensions, and their dependencies
- Reports only `HIGH` and `CRITICAL` severity vulnerabilities (lower severity is acceptable noise)
- Runs on **every single build**, so newly discovered vulnerabilities are caught immediately

---

## 8. Prometheus — Metrics & Time-Series Database

### What is Prometheus?
Prometheus is an open-source systems monitoring and alerting toolkit. It is a **Time-Series Database (TSDB)** — it stores numerical data points indexed by time (e.g., `memory_usage at 22:00:01 = 2.4GB`, `memory_usage at 22:00:16 = 2.5GB`).

**Accessible at:** `http://35.169.185.9:9090`

### How it works in our project (`monitoring/prometheus.yml`)

Prometheus uses a **pull-based** model. Every 15 seconds, it actively reaches out to configured endpoints and scrapes the `/metrics` page:

```yaml
global:
  scrape_interval: 15s        # Scrape every target every 15 seconds
  evaluation_interval: 15s

scrape_configs:
  - job_name: 'prometheus'
    static_configs:
      - targets: ['localhost:9090']   # Prometheus monitors itself

  - job_name: 'php-fpm'
    static_configs:
      - targets: ['feastly_php_fpm_exporter:9253']  # PHP application metrics

  - job_name: 'cadvisor'
    static_configs:
      - targets: ['cadvisor:8080']    # Container resource metrics
```

**Currently scraping 3 targets — all UP (3/3):**
- `prometheus` → self-monitoring health
- `php-fpm` → PHP-FPM Exporter (application-level metrics)
- `cadvisor` → cAdvisor (container resource metrics)

**What Prometheus stores:** Raw numeric time-series data like:
```
container_memory_usage_bytes{id="/"} = 2,476,929,024
phpfpm_active_processes{pool="www"} = 3
container_cpu_usage_seconds_total{id="/system.slice/jenkins.service"} = 92.35
```

This raw data feeds directly into Grafana for visualization.

---

## 9. cAdvisor — Container Resource Monitor

### What is cAdvisor?
cAdvisor (Container Advisor) is an open-source tool developed by Google that provides resource usage and performance metrics for running Docker containers.

**Accessible at:** `http://35.169.185.9:8081`

### How it works in our project

cAdvisor runs as a **privileged Docker container** with deep access to the host machine. The `docker-compose.prod.yml` mounts several critical directories into it:

```yaml
cadvisor:
  privileged: true
  pid: host                        # Access to host process tree
  devices:
    - /dev/kmsg:/dev/kmsg          # Kernel message access
  volumes:
    - /:/rootfs:ro                 # Full read-only access to host filesystem
    - /var/run:/var/run:ro         # Access to Docker socket
    - /sys:/sys:ro                 # Linux kernel subsystems
    - /var/lib/docker/:/var/lib/docker:ro  # Docker container data
    - /sys/fs/cgroup:/sys/fs/cgroup:ro     # cgroup v2 resource data
```

By reading Linux **cgroups** (control groups), cAdvisor knows exactly how much:
- **CPU** each container process consumes
- **RAM** each container has allocated vs. actually using
- **Disk I/O** each container is reading/writing
- **Network** bandwidth each container is sending/receiving

It exposes all of this on a `/metrics` endpoint that Prometheus scrapes every 15 seconds.

**What you can see in cAdvisor's own UI** (`http://35.169.185.9:8081/containers/`):
- Live graphs for every container
- Historical resource usage charts
- Real-time container process lists

---

## 10. PHP-FPM Exporter — Application Metrics

### What is PHP-FPM Exporter?
`hipages/php-fpm_exporter` is a Prometheus exporter that translates the PHP-FPM process manager's internal status page into the Prometheus metrics format.

### Why we added it
cAdvisor gives us *container-level* metrics (how much RAM the PHP container uses). But the PHP-FPM Exporter gives us *application-level* metrics — what is happening **inside** the PHP application itself:
- How many worker processes are active?
- How many requests are queued?
- How many slow requests are there?
- What is the total request count?

This is a much deeper, more impressive level of monitoring to demonstrate.

### How it works in our project

**Step 1:** We enabled the PHP-FPM status endpoint in `docker/php/zz-status.conf`:
```ini
[www]
pm.status_path = /status
ping.path = /ping
ping.response = pong
```
This conf is baked into the PHP Docker image so the `/status` endpoint is available internally at `php:9000/status`.

**Step 2:** The exporter container connects to PHP-FPM directly via TCP:
```yaml
php_fpm_exporter:
  image: hipages/php-fpm_exporter:2.2
  environment:
    - PHP_FPM_SCRAPE_URI=tcp://php:9000/status
```

**Step 3:** Prometheus scrapes the exporter on port `9253`, collecting metrics like:
```
phpfpm_up                         = 1
phpfpm_active_processes           = 2
phpfpm_idle_processes             = 1
phpfpm_total_processes            = 3
phpfpm_accepted_connections_total = 847
phpfpm_slow_requests_total        = 0
phpfpm_max_children_reached_total = 0
```

---

## 11. Grafana — Visualization & Dashboards

### What is Grafana?
Grafana is an open-source analytics and interactive visualization platform. It does not store any data itself — it connects to data sources (Prometheus, Loki) and renders their data as beautiful, real-time dashboards.

**Accessible at:** `http://35.169.185.9:3000` (login: `admin` / `admin`)

### How it works in our project

**Data Sources** are provisioned automatically from `monitoring/grafana-datasources.yml`:
```yaml
datasources:
  - name: Prometheus
    type: prometheus
    url: http://prometheus:9090
    isDefault: true

  - name: Loki
    type: loki
    url: http://loki:3100
```

When Grafana starts, it automatically connects to both data sources without any manual configuration needed.

**Our custom dashboard** (`monitoring/grafana-dashboard.json`) was purpose-built to use the exact metric labels our cAdvisor version produces. It shows:

| Panel | Query | What it shows |
|---|---|---|
| Total System Memory | `container_memory_usage_bytes{id="/"}` | Full server RAM usage |
| Total CPU Usage | `rate(container_cpu_usage_seconds_total{id="/"}[5m])` | CPU consumption rate |
| Docker Engine CPU | `rate(...{id="/system.slice/docker.service"}[5m])` | Docker overhead |
| Jenkins CI/CD CPU | `rate(...{id="/system.slice/jenkins.service"}[5m])` | Jenkins build load |
| Memory Over Time | Multiple memory series graphed | Historical trend |
| Network RX/TX | `rate(container_network_receive_bytes_total...)` | Live bandwidth |
| Scrape Health | `scrape_duration_seconds` | Prometheus own health |

**Grafana also provides the Loki "Explore" view** — a real-time log search tool where you can query any container's logs using LogQL:
```
{container="feastly_nginx_prod"} |= "error"
{job="nginx"} | json | status >= 500
```

---

## 12. Loki — Log Aggregation Database

### What is Loki?
Grafana Loki is a horizontally scalable log aggregation system inspired by Prometheus. It stores logs from all containers in a central, searchable database.

### Why we use it in Feastly
Without Loki, debugging a production issue requires:
1. SSHing into the EC2 server
2. Knowing which container to check
3. Running `docker logs feastly_php_prod`
4. Manually parsing through raw text

With Loki, all logs from all containers are available in one place through Grafana's UI — no SSH required.

### How it works in our project

Loki listens on port `3100` inside the Docker network. It receives log streams pushed by **Promtail** and stores them with labels (metadata tags). Unlike Elasticsearch which indexes every word of a log line (resource-intensive), **Loki only indexes the labels** (e.g., `container="feastly_nginx_prod"`, `job="nginx"`). The actual log text is stored compressed in chunks.

This makes Loki extremely lightweight and efficient for our containerized environment — perfect for running alongside the application on a single EC2 instance.

---

## 13. Promtail — Log Shipping Agent

### What is Promtail?
Promtail is a lightweight agent that collects logs from configured sources and ships them to a Loki instance. It is the equivalent of what Prometheus is for metrics — but for logs.

### How it works in our project (`monitoring/promtail.yml`)

Promtail mounts the Docker socket and uses Docker service discovery to **automatically find all running containers**:

```yaml
scrape_configs:
  # System logs
  - job_name: system
    static_configs:
      - targets: [localhost]
        labels:
          job: varlogs
          __path__: /var/log/*log    # All system logs

  # Docker container logs (auto-discovered)
  - job_name: docker
    docker_sd_configs:
      - host: unix:///var/run/docker.sock   # Reads Docker socket
        refresh_interval: 5s                # Check for new containers every 5s
    relabel_configs:
      - source_labels: ['__meta_docker_container_name']
        target_label: 'container'           # Label: container="feastly_nginx_prod"
      - source_labels: ['__meta_docker_container_name']
        regex: '/feastly_(.*)_prod'
        target_label: 'job'                 # Label: job="nginx"
```

**What this means in practice:**
- Every time a new Docker container starts with a name matching `feastly_*_prod`, Promtail **automatically starts collecting its logs** without any manual configuration
- Logs are tagged with the container name, making them instantly filterable in Grafana
- The `pipeline_stages: [docker: {}]` stage parses Docker's JSON log format properly

---

## 14. End-to-End Data Flow

### CI/CD Flow
```
Developer writes code on local machine
        ↓
git push → GitHub (main branch)
        ↓
GitHub Webhook → Jenkins (port 8080) triggered
        ↓
Stage 1: Checkout — git clone from GitHub
        ↓
Stage 2: Build — docker-compose builds PHP + Nginx images
        ↓
Stage 3: Scan — Trivy scans both images for CVEs
        ↓
Stage 4: Test — Spin up stack, hit /health, tear down
        ↓ (only if HTTP 200)
Stage 5: Deploy — down old containers, up new containers
        ↓
Stage 6: Verify — hit /health on live production
        ✅ Build marked SUCCESS or ❌ FAILURE
```

### Observability Flow
```
[All Docker Containers running]
         |
         |──── cAdvisor reads cgroup stats every 15s ──→ Prometheus (port 9090)
         |
         |──── PHP-FPM Exporter reads /status every 15s ──→ Prometheus
         |
         |──── Promtail reads docker logs via socket ──→ Loki (port 3100)
                                                              |
                                               Prometheus ───┤
                                               Loki ─────────┼──→ Grafana (port 3000)
                                                              |
                                                   [Beautiful Dashboards!]
```

---

## 15. Quick Reference — All Ports & URLs

| Tool | URL | Purpose | Credentials |
|---|---|---|---|
| **Feastly App** | `http://35.169.185.9` | Live food ordering platform | admin@feastly.com / password |
| **Jenkins** | `http://35.169.185.9:8080` | CI/CD pipeline control panel | admin / admin |
| **Prometheus** | `http://35.169.185.9:9090` | Raw metrics query & target health | None |
| **Grafana** | `http://35.169.185.9:3000` | Visual dashboards for metrics & logs | admin / admin |
| **cAdvisor** | `http://35.169.185.9:8081` | Live container resource UI | None |

### Key Pages to Show During Presentation
| What to Show | URL |
|---|---|
| All 3 Prometheus targets UP | `http://35.169.185.9:9090/targets` |
| Raw metric query (memory) | `http://35.169.185.9:9090/graph?g0.expr=container_memory_usage_bytes%7Bid%3D%22%2F%22%7D` |
| Feastly custom dashboard | `http://35.169.185.9:3000/d/feastly-monitoring` |
| Live Docker container list | `http://35.169.185.9:8081/containers/` |
| Jenkins pipeline builds | `http://35.169.185.9:8080/job/feastly-pipeline/` |
