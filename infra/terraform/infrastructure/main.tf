terraform {
  backend "gcs" {
    bucket = "<PLACEHOLDER>"
    prefix = "tfstate/infrastructure"
  }
}

provider "google" {
  project = var.gcp_project_id
  region  = var.gcp_region
}

resource "google_project_service" "services" {
  project                    = var.gcp_project_id
  for_each                   = toset(local.gcp_services)
  service                    = each.key
  disable_dependent_services = true
}
