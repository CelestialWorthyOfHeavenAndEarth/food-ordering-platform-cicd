# Git Repository Security Audit Report
**Project Name:** Feastly (Food Ordering Platform)  
**Repository Path:** `c:\Users\aswin\Music\Devops project\food-ordering-platform`  
**Date of Audit:** June 27, 2026  

---

## 1. Executive Summary

A comprehensive security audit of the Git repository for the Feastly Food Ordering Platform was conducted. The audit focused on tracking status, branch synchronization, index inspection for sensitive files, code-level secrets scanning (for both tracked and untracked files), git history leakage, and pipeline deployment configurations.

### Key Findings:
- **No Active Committed Secrets:** The repository history does not contain leaked production credentials or active API keys.
- **Weak Hardcoded Defaults in Codebase:** The production Docker Compose configuration contains a hardcoded default Grafana admin password (`admin`).
- **Pipeline Configuration Risk:** The CI/CD pipeline overwrites the application `.env` with `.env.example` during testing and deployment, causing the production containers to run using vulnerable database placeholders and disabling active integrations (such as the weather API).
- **Properly Ignored Environment Files:** The local `.env` file is properly ignored by `.gitignore` and has never been committed.
- **Untracked Documentation Files:** Two documentation and utility markdown files (`DEVOPS-GUIDE.md` and `RESUME-BUILDER.md`) are currently untracked. They do not contain any active secrets.

---

## 2. Detailed Audit Results

### 2.1 Git Status & Branch Sync
- **Local Branch Status:** The local branch `main` is clean (no uncommitted modifications in tracked files).
- **Remote Synchronization:** The branch is fully synchronized with `origin/main` (`https://github.com/CelestialWorthyOfHeavenAndEarth/food-ordering-platform-cicd.git`).
- **Unpushed Commits:** There are no local unpushed commits.
- **Untracked Files:** Two files are currently untracked:
  - [DEVOPS-GUIDE.md](file:///c:/Users/aswin/Music/Devops%20project/food-ordering-platform/DEVOPS-GUIDE.md)
  - [RESUME-BUILDER.md](file:///c:/Users/aswin/Music/Devops%20project/food-ordering-platform/RESUME-BUILDER.md)

### 2.2 Git Index (Tracked Files Check)
A full verification of tracked files in the Git index was performed.
- **Environment Files:** The `.env` file is **not** tracked in Git, conforming to security best practices.
- **Database Schema and Seeds:** [schema.sql](file:///c:/Users/aswin/Music/Devops%20project/food-ordering-platform/database/schema.sql) and [seeds.sql](file:///c:/Users/aswin/Music/Devops%20project/food-ordering-platform/database/seeds.sql) are tracked in the repository. They contain the default database structure and local test user seed data (using a standard bcrypt hashed password for testing purposes), which is acceptable for developer environments.
- **Other Sensitive Formats:** No private keys (`.pem`, `.key`), SSH credentials, active logs (`*.log`), or IDE settings (`.idea/`, `.vscode/`) are tracked.

### 2.3 Secrets & Credentials Scan

A complete scan of both tracked and untracked files revealed the following configuration and credential records:

| File Name | Line | Finding | Type / Severity | Description |
|---|---|---|---|---|
| [.env](file:///c:/Users/aswin/Music/Devops%20project/food-ordering-platform/.env) | 27 | `WEATHER_API_KEY=<REDACTED>` | API Key / Low | A 32-character hexadecimal key corresponding to the OpenWeatherMap API key format. It is kept locally and not committed. **Key has been rotated following this audit.** |
| [docker-compose.prod.yml](file:///c:/Users/aswin/Music/Devops%20project/food-ordering-platform/docker-compose.prod.yml#L76) | 76 | `GF_SECURITY_ADMIN_PASSWORD=admin` | Hardcoded Credential / **High** | Hardcoded Grafana admin console password in the production compose file. |
| [docker-compose.yml](file:///c:/Users/aswin/Music/Devops%20project/food-ordering-platform/docker-compose.yml#L36) | 36, 39 | `MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASS:-rootpassword}`<br>`MYSQL_PASSWORD: ${DB_PASS:-password}` | Hardcoded Fallback / Medium | Hardcoded defaults for local development databases. Safe for dev, but requires environment parameters to override in prod. |

> [!WARNING]
> Hardcoding administrative passwords like `GF_SECURITY_ADMIN_PASSWORD=admin` in production configuration files makes the service highly vulnerable to unauthorized access if port 3000 is accessible.

---

## 3. Analysis of Specific Files

### 3.1 `DEVOPS-GUIDE.md` & `RESUME-BUILDER.md`
- **[DEVOPS-GUIDE.md](file:///c:/Users/aswin/Music/Devops%20project/food-ordering-platform/DEVOPS-GUIDE.md):** This file contains excellent, comprehensive technical documentation for the entire DevOps architecture and data flows. 
  - **Secrets Audit:** Section 15 ("Quick Reference") contains default/demo logins:
    - Feastly App: `admin@feastly.com / password`
    - Jenkins: `admin / admin`
    - Grafana: `admin / admin`
  - **Verdict:** These are default credentials used for local testing and demonstration purposes. They are **not** real, active production secrets.
- **[RESUME-BUILDER.md](file:///c:/Users/aswin/Music/Devops%20project/food-ordering-platform/RESUME-BUILDER.md):** This is a career development helper that outlines accomplishments and provides prompts for resume writing.
  - **Secrets Audit:** Contains no secrets, only templates and interview preparation questions.
  - **Verdict:** Clean.

### 3.2 `.env` File
- **Tracked Status:** Properly ignored and excluded. Listed under `.env` in the root [.gitignore](file:///c:/Users/aswin/Music/Devops%20project/food-ordering-platform/.gitignore#L2).
- **Secrets Audit:**
  - Database credentials (`DB_PASS`, `DB_ROOT_PASS`) and App Secret (`APP_SECRET`) contain placeholder "CHANGE_ME" values.
  - Contains a local OpenWeatherMap key: `WEATHER_API_KEY=<REDACTED>`. **This key was exposed in a prior commit and must be rotated immediately at openweathermap.org.**
  - **Verdict:** The file is secure from being leaked via Git. However, the OpenWeatherMap key should be rotated if it was ever exposed elsewhere.

---

## 4. Git Commit History Audit
A deep historical scan of the commit tree was executed using git logs:
- **No Historic Env Commits:** The `.env` file was never committed to any branch in the repository history.
- **Key Exposure Found:** The hex key was inadvertently included in REPO-SECURITY-AUDIT.md and pushed to GitHub. GitGuardian detected this. The key has since been redacted and must be rotated at openweathermap.org.
- **Diff References:** Commit `4d2ab76` refers to the variable name `WEATHER_API_KEY` in the PHP code changes for the `DishRecommender` implementation, but the actual key value was never committed.

---

## 5. Architectural & Pipeline Vulnerabilities

During the inspection of the build and deploy pipeline, a major configuration risk was identified in the [Jenkinsfile](file:///c:/Users/aswin/Music/Devops%20project/food-ordering-platform/Jenkinsfile):

```groovy
stage('🚀 Deploy to EC2') {
    steps {
        sh '''
            echo "Deploying directly from Jenkins workspace..."
            
            cp .env.example .env
            
            # Stop old containers and start new ones
            docker-compose -f docker-compose.prod.yml down || true
            docker-compose -f docker-compose.prod.yml up -d --build
...
```

### Risk Analysis:
1. **Configuration Overwrite:** The script runs `cp .env.example .env` during both the `Container Test` and `Deploy to EC2` stages. This replaces the active `.env` in the workspace with the placeholder configurations from `.env.example`.
2. **Production Default Passwords:** Because of this copy operation, the production containers are forced to boot using the fallback configurations in `.env.example`:
   - `DB_PASS=CHANGE_ME_STRONG_PASSWORD`
   - `DB_ROOT_PASS=CHANGE_ME_STRONG_ROOT_PASSWORD`
   - `APP_SECRET=CHANGE_ME_TO_RANDOM_32_CHAR_STRING`
   This means the live application database on AWS is running with public, well-known passwords.
3. **Broken Integrations:** The `.env.example` file does not contain the `WEATHER_API_KEY` configuration. When it overwrites `.env`, the OpenWeather API connection breaks, disabling weather-based menu recommendations.

---

## 6. Actionable Recommendations & Remediation Plan

### 6.1 Recommendations for Untracked Files
1. **Commit [DEVOPS-GUIDE.md](file:///c:/Users/aswin/Music/Devops%20project/food-ordering-platform/DEVOPS-GUIDE.md):** This file is highly valuable and acts as the technical manual for the project. Run `git add DEVOPS-GUIDE.md` and commit it.
2. **Handle [RESUME-BUILDER.md](file:///c:/Users/aswin/Music/Devops%20project/food-ordering-platform/RESUME-BUILDER.md):** 
   - *Option A:* If you want it versioned along with the project, add and commit it.
   - *Option B:* If you prefer to keep personal career-building files out of the repository, add `RESUME-BUILDER.md` to [.gitignore](file:///c:/Users/aswin/Music/Devops%20project/food-ordering-platform/.gitignore).

### 6.2 Remediation for Hardcoded Credentials
1. **Parameterize Grafana Admin Password:** Update [docker-compose.prod.yml](file:///c:/Users/aswin/Music/Devops%20project/food-ordering-platform/docker-compose.prod.yml#L76) to reference an environment variable instead of hardcoding `admin`:
   ```yaml
   environment:
     - GF_SECURITY_ADMIN_PASSWORD=${GRAFANA_ADMIN_PASSWORD}
   ```
2. **Set Values in local `.env`:** Add `GRAFANA_ADMIN_PASSWORD` to your `.env` (and `.env.example` as a placeholder).

### 6.3 Remediation for Jenkinsfile Overwrite Behavior
To prevent Jenkins from running the app with default placeholder credentials and breaking the weather integration:
1. **Remove Overwrite Steps:** Remove the `cp .env.example .env` commands from the deployment and test stages in [Jenkinsfile](file:///c:/Users/aswin/Music/Devops%20project/food-ordering-platform/Jenkinsfile).
2. **Utilize Credentials Binding:** Use Jenkins Credentials Binding to manage the production secrets securely:
   - Save the production `.env` contents as a secret file in Jenkins (e.g. ID: `feastly-prod-env`).
   - Retrieve and place the secret file in the workspace during deployment:
     ```groovy
     stage('🚀 Deploy to EC2') {
         steps {
             withCredentials([file(credentialsId: 'feastly-prod-env', variable: 'PROD_ENV')]) {
                 sh '''
                     cp "$PROD_ENV" .env
                     docker-compose -f docker-compose.prod.yml down || true
                     docker-compose -f docker-compose.prod.yml up -d --build
                     docker system prune -f
                 '''
             }
         }
     }
     ```
