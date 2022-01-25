resource "google_container_registry" "default" {
  project  = var.gcp_project_id
  location = var.gcp_registry_location

  depends_on = [
    google_project_service.services,
  ]
}
