# Deployments

## Prerequisites

- Create a GCP project
- Enable the following services API:
  - [Cloud Resource Manager API](https://console.developers.google.com/apis/api/cloudresourcemanager.googleapis.com/overview)
  - [Service Usage API](https://console.developers.google.com/apis/api/serviceusage.googleapis.com/overview)
- Create a service account

## Commands

All the commands for deployments requires this 3 environment variables:
```shell
export GOOGLE_APPLICATION_CREDENTIALS=${PWD}/example.json
export GCP_PROJECT=<your gcp project id>
export GCP_TERRAFORM_BUCKET=<your bucket id for the terraform backend>
```

Setup the infrastructure:
```shell
make terraform.deploy.infrastructure
```

Build & deploy the application:
```shell
make terraform.deploy.application
```
