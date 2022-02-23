resource "google_compute_region_network_endpoint_group" "default" {
  project               = var.gcp_project_id
  name                  = "${var.app_name}-default"
  network_endpoint_type = "SERVERLESS"
  region                = var.gcp_region

  cloud_run {
    service = google_cloud_run_service.app.name
  }
}
