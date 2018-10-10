export BASE_IMAGE="${JOB_NAME,,}_bo_base"
export BASE_IMAGE_TAG='test'

cd $WORKSPACE/.docker
docker build -t ${BASE_IMAGE}:${BASE_IMAGE_TAG} -f DockerfileBase .

rm .env
envsubst < .env-dist > .env