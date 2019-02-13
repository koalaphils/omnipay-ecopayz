pipeline {
    agent {
        docker { image 'registry.119.9.116.199.nip.io:445/docker-agent-for-jenkinspipe'
                 reuseNode false
                 registryUrl 'https://registry.119.9.116.199.nip.io:445'
                 registryCredentialsId 'webdeveloper_at_docker_registry'
        }
    }

    environment {
        // configure at http://119.9.74.57:8081/credentials/
        REGISTRY_PASSWORD=credentials('REGISTRY_PASSWORD')
    }
    
    stages {
        stage ('Clone Repo') {
             steps {
                ansiColor('xterm') {
                    sh 'git log --pretty=format:"%h" -n 1 > version.txt && cat version.txt'
                }
             }
        }
        
        stage ('Build base image') {
            steps {
                ansiColor('xterm') {
                   sh 'bash $WORKSPACE/.jenkins/setenv.sh'
                }
            }
        }

        stage ('Build Image') {
            steps {
                ansiColor('xterm') {
                   sh 'cd $WORKSPACE/.docker && docker-compose build --no-cache'
                }
            }
        }

        stage ('Run the containers') {
            steps {
                ansiColor('xterm') {
                    // remove for now, since we dont push anything yet
                    // sh "docker login registry.119.9.116.199.nip.io:445 -uwebdeveloper -p${env.REGISTRY_PASSWORD}"

                    script {
                            sh 'cd $WORKSPACE/.docker && (docker-compose down --volumes || true)'
                            sh 'cd $WORKSPACE/.docker &&  docker-compose up -d'
                            sh 'cd $WORKSPACE/.docker && docker cp $(docker-compose ps -q bo):/home/www/.composer ./manifest/home/www/'
                            
                            sh 'echo "Wait backoffice container to fully run"'
                            sh 'sleep 10'
                            sh 'cd $WORKSPACE/.docker && bash ./waitforbo.sh'
                    }
                }
            }

        }

        stage ('Run Static Tests and generate reports') {
            steps {
                ansiColor('xterm') {
                    // re-run the linter and save the report file
                    sh 'cd $WORKSPACE/.docker && docker exec $(docker-compose ps -q bo) sh -c "cd /backoffice && php vendor/bin/phplint src > phplintreport.txt || true"'
                    // run linter
                    // we need to re run if because saving the file causes a "silent" exit, running it plainly will cause the build to fail if syntax errors are found
                    sh 'cd $WORKSPACE/.docker && docker exec $(docker-compose ps -q bo) sh -c "cd /backoffice && php vendor/bin/phplint src"'

                    sh 'cd $WORKSPACE/.docker && docker exec $(docker-compose ps -q bo) sh -c "cd /backoffice && (php vendor/bin/phpstan analyse src > phpstanreport.txt || true )"'
                }
            }
        }

        stage ('Run tests and generate reports') {

            steps {
                ansiColor('xterm') {
                    // run PHPCS tests and create report files
                    sh 'cd $WORKSPACE/.docker && (docker exec $(docker-compose ps -q bo) bin/sh -c "cd /backoffice; vendor/bin/phpcs --standard=PSR2 --report=full --ignore=vendor,themes,testsOld,docs,web,var/cache,build ." || true) > ../psr2report.txt || true'
                    sh 'cd $WORKSPACE/.docker && (docker exec $(docker-compose ps -q bo) bin/sh -c "cd /backoffice; vendor/bin/phpcs --standard=PSR2 --report=source --ignore=vendor,themes,testsOld,docs,web,var/cache,build ." || true) > ../psr2violations_per_type.txt || true'
                    sh 'cd $WORKSPACE/.docker && (docker exec $(docker-compose ps -q bo) bin/sh -c "cd /backoffice; vendor/bin/phpcs --standard=PSR2 --report=full --ignore=vendor,themes,testsOld,docs,web,var/cache,build ." || true)'
                    sh 'cd $WORKSPACE/.docker && (docker exec $(docker-compose ps -q bo) bin/sh -c "cd /backoffice; vendor/bin/phpcs --standard=PSR2 --report=checkstyle --ignore=vendor,themes,testsOld,docs,web,var/cache,build ." || true) > ../checkstyle.xml'

                    // install xdebug (required by unit test coverage report)
                    sh 'cd $WORKSPACE/.docker && docker exec $(docker-compose ps -q bo) sh -c "apk add --no-cache --repository http://dl-3.alpinelinux.org/alpine/edge/testing vips-tools vips-dev fftw-dev glib-dev php7-dev php7-pear build-base && pecl install xdebug && echo \'zend_extension=/usr/lib/php7/modules/xdebug.so\' >> /etc/php7/php.ini && php -m | grep xdebug"'

                    // disabled generating code coverage reports for now due to "no code coverage driver available" even if XDebug is installed
                    //sh 'cd $WORKSPACE/.docker && docker exec $(docker-compose ps -q bo) sh -c "cd /backoffice && export SYMFONY_DEPRECATIONS_HELPER=disabled && chmod 777 var -Rf && composer install && php app/console cache:clear --env=test && chmod 777 var -Rf && mkdir -p tests/_output && chmod 777 tests/_output -Rf && php vendor/bin/codecept run acceptance,unit,integration,webapi --coverage-html --html -vvv"'
                    // run tests without code coverage
                    sh 'cd $WORKSPACE/.docker && docker exec $(docker-compose ps -q bo) sh -c "cd /backoffice && export SYMFONY_DEPRECATIONS_HELPER=disabled && chmod 777 var -Rf && composer install && php app/console cache:clear --env=test && chmod 777 var -Rf && mkdir -p tests/_output && chmod 777 tests/_output -Rf && php vendor/bin/codecept run acceptance,unit,integration,webapi --html -vvv"'
                }
            }
        }

        stage ('Publish Reports') {
            steps {

                publishHTML([
                    allowMissing: true,
                    alwaysLinkToLastBuild: true,
                    keepAll: true,
                    reportDir: '',
                    reportFiles: 'psr2report.txt,psr2violations_per_type.txt',
                    reportName: 'PSR2 Reports',
                    reportTitles: 'PSR-2 Checkstyle Reports'])

                checkstyle canComputeNew: false, defaultEncoding: '', healthy: '', pattern: 'checkstyle.xml', unHealthy: ''
            }
        }

        // TODO: enable these after demos
        // stage ('Push images to private registry') {
            // sh 'docker push registry.119.9.116.199.nip.io:445/ac66bo_tester:wt-356-alpine'
            // sh 'docker push registry.119.9.116.199.nip.io:445/ac66bo:wt-356-alpine'
        //}

    }

    post {
        always {
            ansiColor('xterm') {
                sh 'cd $WORKSPACE/.docker'

                // coverage reports
                // export acceptance test report files
                sh 'cd $WORKSPACE/.docker && docker cp $(docker-compose ps -q bo):/backoffice/tests/_output/ $WORKSPACE/acceptance_test_reports/'

                // export phplint / phpstan report
                sh 'cd $WORKSPACE/.docker && docker cp $(docker-compose ps -q bo):/backoffice/phplintreport.txt $WORKSPACE/phplintreport.txt'
                sh 'cd $WORKSPACE/.docker && docker cp $(docker-compose ps -q bo):/backoffice/phpstanreport.txt $WORKSPACE/phpstanreport.txt'

                publishHTML([
                    allowMissing: true,
                    alwaysLinkToLastBuild: true,
                    keepAll: true,
                    reportDir: 'acceptance_test_reports/_output/',
                    reportFiles: 'report.html',
                    reportName: 'Acceptance Test Report',
                    reportTitles: 'Acceptance Test Report'])

                publishHTML([
                    allowMissing: true,
                    alwaysLinkToLastBuild: true,
                    keepAll: true,
                    reportDir: '',
                    reportFiles: 'phplintreport.txt',
                    reportName: 'Phplint - Syntax Error Reports',
                    reportTitles: 'Phplint - Syntax Error Reports'])

                publishHTML([
                    allowMissing: true,
                    alwaysLinkToLastBuild: true,
                    keepAll: true,
                    reportDir: '',
                    reportFiles: 'phpstanreport.txt',
                    reportName: 'Phpstan - Syntax Error Reports',
                    reportTitles: 'Phpstan - Syntax Error Reports'])

                //sh 'docker cp ac66_bo:/backoffice/tests/_output/coverage/ ./coverage/'
                //coverage reports
                //publishHTML([
                //    allowMissing: true,
                //    alwaysLinkToLastBuild: true,
                //    keepAll: true,
                //    reportDir: 'coverage',
                //    reportFiles: 'index.html',
                //    reportName: 'Coverage Report',
                 //   reportTitles: 'Code Coverage Report'])

                script {
                    if ("${params.DESTROY_CONTAINERS}" == "Yes") {
                        echo "removing containers"
                        sh 'cd $WORKSPACE/.docker && (docker-compose down --volumes || true )'
                    }
                }

                // cleanup

		
		        slackSend channel: '#ac66', message: "Testing Result: ${currentBuild.currentResult} for ${GIT_BRANCH}@${GIT_COMMIT} see ${env.BUILD_URL} "
            }
        }
    }
}
