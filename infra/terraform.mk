PWD := $(shell dirname $(realpath $(firstword $(MAKEFILE_LIST))))
TERRAFORM_MODULES := $(shell find $(PWD)/infra/terraform -maxdepth 1 -mindepth 1 -type d -exec basename {} \;)

##
## GCP values to override
##

GCP_DOCKER_REGISTRY ?= eu.gcr.io
GCP_REGION ?= europe-west1
GCP_APP_NAME ?= demo-app
GCP_APP_VERSION ?= latest
GCP_DOCKER_IMAGE_NAME = $(GCP_DOCKER_REGISTRY)/$(GCP_PROJECT)/$(GCP_APP_NAME)

##
## Define generic targets for all terraform modules
##

define terraform-module

.PHONY: terraform.lint.$1
terraform.lint.$1:
	cd $(PWD)/infra/terraform/$1 && terraform init -reconfigure -backend=false
	cd $(PWD)/infra/terraform/$1 && terraform validate

.PHONY: terraform.destroy.$1
terraform.destroy.$1: TF_VAR_gcp_project_id = $(GCP_PROJECT)
terraform.destroy.$1: TF_VAR_gcp_region = $(GCP_REGION)
terraform.destroy.$1: deploy.check
terraform.destroy.$1:
	cd $(PWD)/infra/terraform/$1 && terraform apply -destroy -auto-approve

endef
$(foreach module,$(TERRAFORM_MODULES),$(eval $(call terraform-module,$(module))))

##
## Linter
##

.PHONY: terraform.lint
terraform.lint: $(addprefix terraform.lint.,$(TERRAFORM_MODULES))
	terraform fmt --diff --check --recursive infra/

.PHONY: deploy.check
deploy.check:
ifndef GCP_PROJECT
	$(error GCP_PROJECT is undefined)
endif
ifndef GCP_TERRAFORM_BUCKET
	$(error GCP_TERRAFORM_BUCKET is undefined)
endif
ifndef GOOGLE_APPLICATION_CREDENTIALS
	$(error GOOGLE_APPLICATION_CREDENTIALS is undefined)
endif
ifndef AKENEO_CLIENT_ID
	$(error AKENEO_CLIENT_ID is undefined)
endif
ifndef AKENEO_CLIENT_SECRET
	$(error AKENEO_CLIENT_SECRET is undefined)
endif

.PHONY: terraform.deploy
terraform.deploy: deploy.check
	$(MAKE) terraform.deploy.infrastructure
	$(MAKE) terraform.deploy.application

.PHONY: terraform.deploy.infrastructure
terraform.deploy.infrastructure: TF_VAR_gcp_project_id = $(GCP_PROJECT)
terraform.deploy.infrastructure: TF_VAR_gcp_region = $(GCP_REGION)
terraform.deploy.infrastructure: deploy.check
	terraform -chdir=infra/terraform/infrastructure/ init -reconfigure -backend-config="bucket=$(GCP_TERRAFORM_BUCKET)"
	terraform -chdir=infra/terraform/infrastructure/ apply -auto-approve

.PHONY: terraform.deploy.application
terraform.deploy.application: TF_VAR_gcp_project_id = $(GCP_PROJECT)
terraform.deploy.application: TF_VAR_gcp_region = $(GCP_REGION)
terraform.deploy.application: TF_VAR_app_name = $(GCP_APP_NAME)
terraform.deploy.application: TF_VAR_app_version = $(GCP_APP_VERSION)
terraform.deploy.application: TF_VAR_app_client_id = $(AKENEO_CLIENT_ID)
terraform.deploy.application: TF_VAR_app_client_secret = $(AKENEO_CLIENT_SECRET)
terraform.deploy.application: export DOCKER_IMAGE_NAME ?= $(GCP_DOCKER_IMAGE_NAME)
terraform.deploy.application: export DOCKER_IMAGE_VERSION ?= $(GCP_APP_VERSION)
terraform.deploy.application: deploy.check
	$(MAKE) docker-image
	cat $(GOOGLE_APPLICATION_CREDENTIALS) | docker login -u _json_key --password-stdin https://$(GCP_DOCKER_REGISTRY)
	$(MAKE) docker-push
	terraform -chdir=infra/terraform/application/ init -reconfigure -backend-config="bucket=$(GCP_TERRAFORM_BUCKET)" -backend-config="prefix=tfstate/application/$(GCP_APP_NAME)"
	terraform -chdir=infra/terraform/application/ apply -auto-approve

.PHONY: terraform.destroy
terraform.deploy: TF_VAR_gcp_project_id = $(GCP_PROJECT)
terraform.deploy: TF_VAR_gcp_region = $(GCP_REGION)
terraform.deploy: deploy.check
terraform.destroy:
	$(MAKE) terraform.destroy.application
	$(MAKE) terraform.destroy.infrastructure
