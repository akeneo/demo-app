resource "google_cloud_run_service" "app" {
  name                       = var.app_name
  location                   = var.gcp_region
  autogenerate_revision_name = true

  template {
    spec {
      containers {
        image = local.app_docker_image
        ports {
          container_port = 80
        }
        env {
          name  = "AKENEO_CLIENT_ID"
          value = var.app_client_id
        }
        env {
          name  = "AKENEO_CLIENT_SECRET"
          value = var.app_client_secret
        }
      }
    }

    metadata {
      annotations = {
        "autoscaling.knative.dev/maxScale" = var.gcp_app_container_max_scale
        "client.knative.dev/user-image"    = local.app_docker_image
        "run.googleapis.com/client-name"   = "terraform"
      }
    }
  }

  traffic {
    percent         = 100
    latest_revision = true
  }
}

data "google_iam_policy" "cloud-run-noauth" {
  binding {
    role = "roles/run.invoker"

    members = [
      "allUsers",
    ]
  }
}

resource "google_cloud_run_service_iam_policy" "app-noauth" {
  location = google_cloud_run_service.app.location
  project  = google_cloud_run_service.app.project
  service  = google_cloud_run_service.app.name

  policy_data = data.google_iam_policy.cloud-run-noauth.policy_data
}
