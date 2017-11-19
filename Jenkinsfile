pipeline {
    agent any

    stages {
        stage('Build') {
            steps {
                sh 'composer install'
            }
        }
        stage('QA') {
            steps {
                ansiColor('xterm') {
                    sh "bin/drush --config='.' --config='./Commands' marvin:qa:lint:phpcs"
                    sh "bin/drush --config='.' --config='./Commands' marvin:qa:phpunit"
                }
            }
        }
        stage('Report') {
            steps {
                junit 'reports/machine/unit/junit.xml'
                step([
                    $class: 'CloverPublisher',
                    cloverReportDir: 'reports/machine/coverage',
                    cloverReportFileName: 'coverage.xml',
                    healthyTarget:   [methodCoverage: 70, conditionalCoverage: 80, statementCoverage: 80],
                    unhealthyTarget: [methodCoverage: 50, conditionalCoverage: 50, statementCoverage: 50],
                    failingTarget:   [methodCoverage:  0, conditionalCoverage:  0, statementCoverage:  0]
                ])
            }
        }
    }
}

// kate: indent-width 4; tag-width 4;
