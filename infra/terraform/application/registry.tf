data "google_container_registry_repository" "default" {
  region = lower(var.gcp_registry_location)
}
