# Infra

## Prerequisites

- Create a GCP project
- Enable the following services API:
  - [Cloud Resource Manager API](https://console.developers.google.com/apis/api/cloudresourcemanager.googleapis.com/overview)
  - [Service Usage API](https://console.developers.google.com/apis/api/serviceusage.googleapis.com/overview)
- Create a GCS Bucket for terraform backend
- Create a custom Role with these permissions:
  - `iam.serviceAccounts.actAs`
  - `run.services.create`
  - `run.services.delete`
  - `run.services.setIamPolicy`
  - `run.services.update`
  - `serviceusage.services.disable`
  - `serviceusage.services.enable`
  - `storage.buckets.create`
  - `storage.buckets.delete`
  - `storage.buckets.get`
  - `storage.buckets.list`
  - `storage.buckets.update`
  - `storage.objects.create`
  - `storage.objects.delete`
  - `storage.objects.get`
  - `storage.objects.list`
  - `storage.objects.update`
- Create a service account with both your custom Role and the role `roles/viewer`
- Create a service account key and download it

## Commands

All the commands for deployments requires this 3 environment variables:
```shell
export GOOGLE_APPLICATION_CREDENTIALS=service-account-key.json
export GCP_PROJECT=<your gcp project id>
export GCP_TERRAFORM_BUCKET=<your bucket id for the terraform backend>
```

Setup the infrastructure, build and deploy the application:
```shell
make terraform.deploy
```
