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
                    
                    cp .env.example .env
                    
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
