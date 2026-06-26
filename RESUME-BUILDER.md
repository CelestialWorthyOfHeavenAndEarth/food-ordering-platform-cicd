# Feastly DevOps Project — Resume Generation Context & Prompts
### A detailed overview of the Feastly DevOps & Observability architecture designed for LLM (e.g., Claude) ingestion to generate professional resume bullet points.

---

## How to Use This File
1. Copy the entire contents of this file.
2. Paste it into Claude or any LLM with the prompt: 
   *"I want to add this DevOps project to my resume. Here is the detailed technical documentation of what I built, the architecture, and the achievements. Please write 4-6 strong, high-impact resume bullet points using the Google X-Y-Z formula (Accomplished [X] as measured by [Y], by doing [Z]) tailored for a DevOps / SRE / Systems Engineer role."*

---

## 1. Project Overview & Architecture
**Project Name:** Feastly (Containerized Food Ordering Platform & DevOps Infrastructure)  
**Deployment Environment:** AWS EC2 Instance (Linux)  
**Architecture Style:** Multi-container micro-services architecture (10 containers total) isolated via dedicated Docker networks and monitored through a centralized observability stack.

### Core Stack & Container Breakdown:
*   **Web & Routing Layer:** Custom **Nginx (Alpine)** acting as a Reverse Proxy & Web Server (handling rate limiting, caching, security headers, SSL/TLS simulation, and FastCGI routing).
*   **App Runtime:** Custom **PHP 8.2-FPM (Alpine)** container configured with enabled PHP status/ping endpoints for performance inspection.
*   **Database Layer:** **MySQL 8** container with persistent storage volumes.
*   **CI/CD Pipeline Engine:** **Jenkins** host service executing a 6-stage Pipeline-as-Code configuration.
*   **DevSecOps:** **Trivy** container scanner integrated directly into the CI/CD pipeline to analyze built images for CVE vulnerabilities.
*   **Monitoring / Metrics Stack:** **Prometheus** (Time-Series DB) scraping metrics from **cAdvisor** (cgroup resource utilization) and **PHP-FPM Exporter** (application worker/request telemetry).
*   **Log Management Stack:** **Loki** (log aggregation database) and **Promtail** (log shipper with auto-discovery via Docker Socket).
*   **Visualization Layer:** **Grafana** configured with auto-provisioned data sources and custom dashboards.

---

## 2. Key Achievements & Resume Bullet Points (Ready-to-Use)

Here are high-impact resume bullet points representing this project, categorized by DevOps domains:

### **CI/CD & DevSecOps (Jenkins, Trivy, GitHub)**
*   **Automated End-to-End Pipeline:** Designed and implemented a 6-stage automated CI/CD pipeline in **Jenkins** (using Declarative Jenkinsfile Pipeline-as-Code) triggered by **GitHub Webhooks**, which automated checkout, container builds, security scans, testing, deployment, and post-deployment validation.
*   **DevSecOps Integration (Trivy):** Integrated **Trivy vulnerability scanner** within the build cycle to inspect Docker images for HIGH and CRITICAL vulnerabilities, establishing a security gate that blocks deployments of insecure builds.
*   **Staging & Staging-to-Prod Health Check:** Built a pipeline stage that dynamically spins up the container stack, runs curl-based HTTP health probes against Nginx endpoints, and verifies response codes before executing the final production deployment.
*   **Zero-Downtime Deployment Logic:** Scripted deployment logic in Jenkins that gracefully stops existing containers, launches newly built container versions using custom Docker Compose configurations, and cleans up dangling Docker cache files to maintain host disk health.

### **Containerization & Orchestration (Docker, Docker Compose, Nginx)**
*   **Container Standardization:** Package-optimized PHP 8.2-FPM and Nginx images using lightweight **Alpine Linux bases**, reducing image sizes and minimizing the vulnerability surface area.
*   **Network Isolation & Security:** Constructed an isolated Docker custom bridge network (`feastly_prod`) preventing container port exposure to the public internet, routing public traffic exclusively through an Nginx proxy on port 80.
*   **Nginx Security Hardening:** Configured custom Nginx proxy features including rate limiting (limiting general requests to 30r/m and sensitive auth endpoints to 5r/m), custom security headers (`X-Frame-Options`, `X-XSS-Protection`), Gzip compression (~70% asset footprint reduction), static caching, and explicit blocklists for configuration files (.env, .sql, .sh).
*   **Storage Persistence:** Implemented Docker Volumes for MySQL, Prometheus, and Grafana to guarantee data persistence across application updates and container restarts.

### **Observability, Monitoring, & Logging (Prometheus, Grafana, Loki, Promtail, cAdvisor)**
*   **Full-Stack Observability System:** Architected a comprehensive monitoring and logging framework for 10 running containers using **Prometheus**, **Grafana**, **Loki**, **Promtail**, and **cAdvisor**, reducing mean-time-to-resolution (MTTR) for infrastructure issues.
*   **Resource Telemetry:** Deployed **cAdvisor** in privileged mode to extract granular container CPU, RAM, Network RX/TX, and Disk I/O metrics directly from Linux cgroups, scraping telemetry via Prometheus every 15 seconds.
*   **Application-Level Metrics:** Enabled PHP-FPM metrics by modifying PHP configuration (`zz-status.conf`), exposing the `/status` endpoint internally, and deploying `hipages/php-fpm_exporter` to track active worker pools, queue length, and slow requests.
*   **Automated Log Harvesting:** Configured **Promtail** with Docker Service Discovery via the Docker socket to auto-discover, label, parse, and ship logs from active containers to **Loki** in real-time, eliminating the need to manually SSH into hosts to debug logs.
*   **Centralized Dashboards:** Developed custom Grafana dashboards and provisioned data sources declaratively as code, allowing real-time inspection of hardware resource consumption, PHP process metrics, and Loki log streams using LogQL.

---

## 3. Technology Matrix (for Resume Skills Section)

If you have a "Skills" section on your resume, this project justifies listing:

*   **CI/CD:** Jenkins, Jenkinsfile (Pipeline-as-Code), Git, GitHub Webhooks
*   **Containers & Orchestration:** Docker, Docker Compose, Custom Dockerfiles, Alpine Linux optimization
*   **Observability & Monitoring:** Prometheus, Grafana, cAdvisor, PHP-FPM Exporter, PromQL
*   **Logging:** Grafana Loki, Promtail, LogQL
*   **Security (DevSecOps):** Trivy Vulnerability Scanner, Nginx Rate Limiting, Nginx Security Headers
*   **Web Servers / Proxies:** Nginx, FastCGI, Gzip compression, HTTP caching
*   **Database & OS:** MySQL 8, Linux (Ubuntu/Alpine), Shell Scripting (Bash), AWS EC2

---

## 4. Technical Details & Configuration Snippets (for Interview Prep)

If Claude or an interviewer asks *how* you did it, here are the configurations you can show:

### **Q: How did you capture PHP-FPM metrics?**
We had to enable the status endpoint in PHP-FPM by creating a custom config file (`zz-status.conf`):
```ini
[www]
pm.status_path = /status
ping.path = /ping
ping.response = pong
```
We copied this configuration into `/usr/local/etc/php-fpm.d/zz-status.conf` in the Dockerfile. Then we set up the PHP-FPM exporter in `docker-compose.prod.yml`:
```yaml
php_fpm_exporter:
  image: hipages/php-fpm_exporter:2.2
  environment:
    - PHP_FPM_SCRAPE_URI=tcp://php:9000/status
```
Prometheus scraped this exporter on port `9253`, letting us query metrics like `phpfpm_active_processes` and `phpfpm_slow_requests_total`.

### **Q: How did you implement automated log shipping?**
We used Promtail's `docker_sd_configs` to read the host's `/var/run/docker.sock`. Promtail auto-discovered containers and used regex relabeling to parse container names:
```yaml
  - job_name: docker
    docker_sd_configs:
      - host: unix:///var/run/docker.sock
        refresh_interval: 5s
    relabel_configs:
      - source_labels: ['__meta_docker_container_name']
        target_label: 'container'
      - source_labels: ['__meta_docker_container_name']
        regex: '/feastly_(.*)_prod'
        target_label: 'job'
```
This automatically tagged logs with labels like `container="feastly_nginx_prod"` and `job="nginx"`, which were sent to Loki and queried in Grafana using LogQL.

### **Q: Describe the Jenkins CI/CD pipeline workflow.**
It’s a declarative pipeline containing 6 stages:
1.  **Checkout:** Pulls code and extracts the Git short hash.
2.  **Docker Build:** Rebuilds PHP and Nginx containers.
3.  **Vulnerability Scan:** Runs a Trivy container to scan the local PHP image, failing the build if `HIGH` or `CRITICAL` issues are found.
4.  **Container Test:** Brings the stack up, uses `curl` to hit `/health` to verify Nginx and PHP are running, and takes the stack down.
5.  **Deploy to EC2:** Safely stops old containers, starts new containers with `--build` flag, and runs `docker system prune` to clear unused resources.
6.  **Post-Deploy Verify:** Waits for startup, checks `/health` again, and asserts HTTP 200 to verify a successful live deploy.
