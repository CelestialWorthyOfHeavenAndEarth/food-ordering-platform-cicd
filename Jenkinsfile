pipeline {
    agent any

    environment {
        APP_NAME     = 'feastly'
        DOCKER_IMAGE = "${APP_NAME}:${BUILD_NUMBER}"
    }

    options {
        timeout(time: 20, unit: 'MINUTES')
        disableConcurrentBuilds()
        buildDiscarder(logRotator(numToKeepStr: '10'))
    }

    stages {
        stage('📥 Checkout') {
            steps {
                echo "Checking out branch: ${env.GIT_BRANCH}"
                checkout scm
                script {
                    env.GIT_COMMIT_SHORT = sh(script: 'git rev-parse --short HEAD', returnStdout: true).trim()
                }
            }
        }



        stage('🐳 Docker Build') {
            steps {
                sh '''
                    echo "Building Docker images..."
                    docker-compose -f docker-compose.prod.yml build
                    echo "✅ Docker build complete"
                '''
            }
        }

        stage('🛡️ Vulnerability Scan') {
            steps {
                sh '''
                    echo "Running Trivy vulnerability scanner..."
                    # Check if trivy is installed, if not try to install or run via docker
                    if ! command -v trivy &> /dev/null; then
                        echo "Trivy not found. Running via Docker..."
                        docker run --rm -v /var/run/docker.sock:/var/run/docker.sock aquasec/trivy image --severity HIGH,CRITICAL feastly-pipeline-php:latest
                        docker run --rm -v /var/run/docker.sock:/var/run/docker.sock aquasec/trivy image --severity HIGH,CRITICAL feastly-pipeline-nginx:latest
                    else
                        trivy image --severity HIGH,CRITICAL feastly-pipeline-php:latest
                        trivy image --severity HIGH,CRITICAL feastly-pipeline-nginx:latest
                    fi
                    echo "✅ Scan complete"
                '''
            }
        }

        stage('🧪 Container Test') {
            steps {
                sh '''
                    cp .env.example .env
                    docker-compose -f docker-compose.prod.yml up -d
                    sleep 15

                    # Health check
                    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health)
                    if [ "$HTTP_CODE" != "200" ]; then
                        echo "❌ Health check failed: HTTP $HTTP_CODE"
                        docker-compose -f docker-compose.prod.yml logs
                        docker-compose -f docker-compose.prod.yml down
                        exit 1
                    fi
                    echo "✅ Container health check passed (HTTP 200)"
                    docker-compose -f docker-compose.prod.yml down
                '''
            }
        }

        stage('🚀 Deploy to EC2') {
            steps {
                sh '''
                    echo "Deploying directly from Jenkins workspace..."
                    
                    if [ -f /home/ubuntu/feastly/.env ]; then
                        echo "Copying production .env from host..."
                        cp /home/ubuntu/feastly/.env .env
                    else
                        echo "Warning: Production .env not found on host. Using .env.example..."
                        cp .env.example .env
                    fi
                    
                    # Stop old containers and start new ones
                    docker-compose -f docker-compose.prod.yml down || true
                    docker-compose -f docker-compose.prod.yml up -d --build
                    docker system prune -f

                    echo "✅ Deployment complete!"
                '''
            }
        }

        stage('✅ Post-Deploy Verify') {
            steps {
                sh '''
                    sleep 10
                    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health)
                    if [ "$HTTP_CODE" = "200" ]; then
                        echo "✅ Production health check passed"
                    else
                        echo "❌ Production health check failed: HTTP $HTTP_CODE"
                        exit 1
                    fi
                '''
            }
        }
    }

    post {
        success {
            echo "🎉 Pipeline ${env.BUILD_NUMBER} succeeded! Commit: ${env.GIT_COMMIT_SHORT}"
        }
        failure {
            echo "❌ Pipeline ${env.BUILD_NUMBER} failed. Check logs above."
        }
    }
}
