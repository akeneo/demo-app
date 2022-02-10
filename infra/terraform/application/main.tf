terraform {
  backend "gcs" {
    bucket = "<PLACEHOLDER>"
    prefix = "tfstate/application/default"
  }
}

provider "google" {
  project = var.gcp_project_id
  region  = var.gcp_region
}
