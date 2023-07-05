pipeline {
    agent any

    environment {
        GIT_COMMIT_SHORT = "${sh(script: 'git rev-parse --short HEAD', returnStdout: true).trim()}"
        DOCKER_IMAGE = "nhloc/sensor-things-webservice"
        DOCKER_TAG = "${GIT_BRANCH.tokenize('/').pop()}-${GIT_COMMIT.substring(0,7)}"
    }

    stages {
        stage('Checkout') {
            steps {
                git branch: 'master', url: 'https://github.com/ngohoangloc/sensor-things-webservice'
            }
        }

        stage('Test') {
          agent {
              docker {
                  image 'php:8.0-cli'
                  args '-u 0:0 -v /tmp:/root/.cache -v $HOME/.composer:/root/.composer'
              }
          }
          steps {

              sh 'apt-get update && apt-get install -y wget zip unzip'
              sh 'wget -O composer-setup.php https://getcomposer.org/installer'
              sh 'php composer-setup.php --install-dir=/usr/local/bin --filename=composer'
              sh 'composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader'
              sh 'php artisan clear-compiled'
              sh 'php artisan optimize'
          }
        }

        stage('Create Dockerfile') {
            steps {
                writeFile file: 'Dockerfile', text: '''
                  FROM php:8.0-cli

                  # Install required extensions and dependencies
                  RUN apt-get update && apt-get install -y \
                      libpng-dev \
                      libjpeg-dev \
                      libfreetype6-dev \
                      libzip-dev \
                      unzip \
                      git \
                      && docker-php-ext-configure gd --with-freetype --with-jpeg \
                      && docker-php-ext-install -j$(nproc) gd \
                      && docker-php-ext-install pdo_mysql zip

                  # Install Composer
                  COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

                  # Set working directory
                  WORKDIR /var/www/html

                  # Copy application files
                  COPY . /var/www/html

                  # Install dependencies
                  RUN composer install --no-interaction --no-dev --optimize-autoloader

                  # Set permissions
                  RUN chown -R www-data:www-data /var/www/html/storage
                  RUN chown -R www-data:www-data /var/www/html/bootstrap/cache
                  '''
            }
        }

        stage('Build Docker Image') {
            steps {
                sh "docker build -t ${DOCKER_IMAGE}:${DOCKER_TAG} . "
                sh "docker tag ${DOCKER_IMAGE}:${DOCKER_TAG} ${DOCKER_IMAGE}:latest"
                sh "docker image ls | grep ${DOCKER_IMAGE}"
            }
        }

        stage('Push Docker Image') {
          steps {
            withCredentials([usernamePassword(credentialsId: 'docker_hub', usernameVariable: 'DOCKER_USERNAME', passwordVariable: 'DOCKER_PASSWORD')]) {
            sh 'echo $DOCKER_PASSWORD | docker login --username $DOCKER_USERNAME --password-stdin'
            sh "docker image push ${DOCKER_IMAGE}:${DOCKER_TAG}"
            sh "docker image push ${DOCKER_IMAGE}:latest"
          }

            //clean to save disk
            sh "docker image rm ${DOCKER_IMAGE}:${DOCKER_TAG}"
            sh "docker image rm ${DOCKER_IMAGE}:latest"
          }
        }
    }

    post {
      success {
        echo "SUCCESSFUL"
      }
      failure {
        echo "FAILED"
      }
  }
}

