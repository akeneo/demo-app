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

locals {
  gcp_services = [
    "containerregistry.googleapis.com",
    "run.googleapis.com",
    "servicenetworking.googleapis.com",
  ]
}
