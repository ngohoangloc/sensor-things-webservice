pipeline {

  agent any

  environment {
    DOCKER_IMAGE = "nhloc/sensor-things-webservice"
  }

  stages {
    stage('Test') {
        agent {
            docker {
                image 'php:8.0-cli'
                args '-u 0:0 -v /tmp:/root/.cache -v $HOME/.composer:/root/.composer'
            }
        }
        steps {
            // sh 'apt update && apt install -y curl'
            // sh 'curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer'
            // sh 'composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader'
            // sh 'php artisan clear-compiled'
            // sh 'php artisan optimize'
            // sh 'vendor/bin/phpunit'

            sh 'apt-get update && apt-get install -y wget zip unzip'
            sh 'wget -O composer-setup.php https://getcomposer.org/installer'
            sh 'php composer-setup.php --install-dir=/usr/local/bin --filename=composer'
            sh 'composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader'
        }
    }
    stage("Build") {
      agent { node {label 'master'}}
      environment {
        DOCKER_TAG="${GIT_BRANCH.tokenize('/').pop()}-${GIT_COMMIT.substring(0,7)}"
      }
      steps {
        sh "docker build -t ${DOCKER_IMAGE}:${DOCKER_TAG} . "
        sh "docker tag ${DOCKER_IMAGE}:${DOCKER_TAG} ${DOCKER_IMAGE}:latest"
        sh "docker image ls | grep ${DOCKER_IMAGE}"
        withCredentials([usernamePassword(credentialsId: 'docker-hub', usernameVariable: 'DOCKER_USERNAME', passwordVariable: 'DOCKER_PASSWORD')]) {
            sh 'echo $DOCKER_PASSWORD | docker login --username $DOCKER_USERNAME --password-stdin'
            sh "docker push ${DOCKER_IMAGE}:${DOCKER_TAG}"
            sh "docker push ${DOCKER_IMAGE}:latest"
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






// pipeline {
//     agent any
//     stages {
//         stage("Verify tooling") {
//             steps {
//                 sh '''
//                     docker info
//                     docker version
//                     docker compose version
//                 '''
//             }
//         }
//         stage("Verify SSH connection to server") {
//             steps {
//                 sshagent(credentials: ['aws-ec2']) {
//                     sh '''
//                         ssh -o StrictHostKeyChecking=no ec2-user@13.40.116.143 whoami
//                     '''
//                 }
//             }
//         }
//         stage("Clear all running docker containers") {
//             steps {
//                 script {
//                     try {
//                         sh 'docker rm -f $(docker ps -a -q)'
//                     } catch (Exception e) {
//                         echo 'No running container to clear up...'
//                     }
//                 }
//             }
//         }
//         stage("Start Docker") {
//             steps {
//                 sh 'make up'
//                 sh 'docker compose ps'
//             }
//         }
//         stage("Run Composer Install") {
//             steps {
//                 sh 'docker compose run --rm composer install'
//             }
//         }
//         stage("Populate .env file") {
//             steps {
//                 dir("/var/lib/jenkins/workspace/envs/laravel-test") {
//                     fileOperations([fileCopyOperation(excludes: '', flattenFiles: true, includes: '.env', targetLocation: "${WORKSPACE}")])
//                 }
//             }
//         }
//         stage("Run Tests") {
//             steps {
//                 sh 'docker compose run --rm artisan test'
//             }
//         }
//     }
//     post {
//         success {
//             sh 'cd "/var/lib/jenkins/workspace/LaravelTest"'
//             sh 'rm -rf artifact.zip'
//             sh 'zip -r artifact.zip . -x "*node_modules**"'
//             withCredentials([sshUserPrivateKey(credentialsId: "aws-ec2", keyFileVariable: 'keyfile')]) {
//                 sh 'scp -v -o StrictHostKeyChecking=no -i ${keyfile} /var/lib/jenkins/workspace/LaravelTest/artifact.zip ec2-user@13.40.116.143:/home/ec2-user/artifact'
//             }
//             sshagent(credentials: ['aws-ec2']) {
//                 sh 'ssh -o StrictHostKeyChecking=no ec2-user@13.40.116.143 unzip -o /home/ec2-user/artifact/artifact.zip -d /var/www/html'
//                 script {
//                     try {
//                         sh 'ssh -o StrictHostKeyChecking=no ec2-user@13.40.116.143 sudo chmod 777 /var/www/html/storage -R'
//                     } catch (Exception e) {
//                         echo 'Some file permissions could not be updated.'
//                     }
//                 }
//             }
//         }
//         always {
//             sh 'docker compose down --remove-orphans -v'
//             sh 'docker compose ps'
//         }
//     }
// }
