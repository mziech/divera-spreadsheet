node {
    checkout scm

    docker.withRegistry(env.LOCAL_DOCKER_REGISTRY, env.LOCAL_DOCKER_CREDS) {
        def customImage = docker.build("thw-ofrk/divera-spreadsheet:${env.BUILD_ID}")
        customImage.push()
        customImage.push("latest")
    }
}
