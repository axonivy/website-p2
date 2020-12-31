pipeline {
  agent any

  triggers {
    cron '@midnight'
  }

  options {
    buildDiscarder(logRotator(artifactNumToKeepStr: '10'))
    skipStagesAfterUnstable()
  }

  environment {
    DIST_FILE = "website-file.tar"
  }

  stages {
    stage('build') {
      agent {
	    dockerfile {
	      dir 'docker/apache'    
	    }
	  }
      steps {
      	sh 'composer install --no-dev --no-progress'
        sh "tar -cf ${env.DIST_FILE} src vendor"
        archiveArtifacts env.DIST_FILE
        stash name: 'website-tar', includes: env.DIST_FILE
        
        sh 'composer install --no-progress'
      	sh './vendor/bin/phpunit --log-junit phpunit-junit.xml || exit 0'
      	junit 'phpunit-junit.xml'
      }
    }

    stage('deploy') {
      when {
        branch 'master'
      }
      agent {
      	docker {
	        image 'axonivy/build-container:ssh-client-1.0'
	      }
      }
      steps {
        sshagent(['zugprojenkins-ssh']) {
          script {
            unstash 'website-tar'

            def targetFolder = "/home/axonivy1/deployment/website-file-" + new Date().format("yyyy-MM-dd_HH-mm-ss-SSS");
            def targetFile =  targetFolder + ".tar"
            def host = 'axonivy1@217.26.54.241'

            // copy
            sh "scp ${env.DIST_FILE} $host:$targetFile"

            // untar
            sh "ssh $host mkdir $targetFolder"
            sh "ssh $host tar -xf $targetFile -C $targetFolder"
            sh "ssh $host rm -f $targetFile"

            // symlink
            sh "ssh $host ln -fns $targetFolder/src/web /home/axonivy1/www/file.axonivy.rocks/linktoweb"
            sh "ssh $host ln -fns /home/axonivy1/data/p2 $targetFolder/src/web/p2"
          }
        }
      }
    }
  }
}
