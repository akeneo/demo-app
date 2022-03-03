variable "gcp_project_id" {
  type = string
}

variable "gcp_region" {
  type    = string
  default = "europe-west1"
}

variable "gcp_registry_location" {
  type    = string
  default = "EU"
}

variable "gcp_app_container_max_scale" {
  type    = string
  default = "100"
}

variable "app_name" {
  type    = string
  default = "demo-app"
}

variable "app_version" {
  type    = string
  default = "latest"
}

variable "app_secret" {
  type    = string
  default = "28d8c8cc382a2278771b95204733f09a"
}

variable "app_client_id" {
  type = string
}

variable "app_client_secret" {
  type = string
}

locals {
  app_docker_image = format("%s/%s:%s", data.google_container_registry_repository.default.repository_url, var.app_name, var.app_version)
}
