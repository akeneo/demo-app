#!/bin/bash

if [ -z ${CIRCLECI_TOKEN+x} ]; then
    echo "You need to set the CIRCLECI_TOKEN environment variable."
    exit
fi

CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)

read -p "Do you want to deploy ${CURRENT_BRANCH} (y/n)? " -r
if ! [[ $REPLY =~ ^[Yy]$ ]]; then
    exit
fi

read -p "client_id: " CLIENT_ID
read -p "client_secret: " CLIENT_SECRET

curl --request POST \
  --url https://circleci.com/api/v2/project/github/akeneo/demo-app/pipeline \
  --header 'Circle-Token: '${CIRCLECI_TOKEN} \
  --header 'content-type: application/json' \
  --data '{"branch":"'${CURRENT_BRANCH}'","parameters":{"run_tests":false,"run_deploy_dev":true,"akeneo_client_id":"'${CLIENT_ID}'","akeneo_client_secret":"'${CLIENT_SECRET}'"}}'

echo # empty line
echo "To find the generated url, check your latest pipeline here:"
echo "https://app.circleci.com/pipelines/github/akeneo/demo-app?branch=${CURRENT_BRANCH}&filter=all"
