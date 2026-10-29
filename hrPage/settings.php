<?php
session_start();
require "../functions.php";
require "../connection.php";
redirectToLogin('HR');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Company Name - HR Settings</title>
  <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../css/Global.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    .settings-card {
      transition: all 0.3s ease;
      border: none;
      border-radius: 15px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      height: 100%;
    }
    .settings-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    .settings-icon {
      width: 70px;
      height: 70px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      font-size: 1.8rem;
    }
    .settings-link {
      text-decoration: none;
      color: inherit;
    }
    .settings-link:hover {
      color: inherit;
    }
    .card-title {
      font-weight: 600;
      margin-bottom: 10px;
    }
    .card-description {
      color: #6c757d;
      font-size: 0.9rem;
      line-height: 1.4;
    }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="row flex-nowrap">

      <!-- Sidebar -->
      <?php include 'nav.php' ?>

      <!-- Main Content -->
      <div class="col py-4 px-5">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
          <h2 class="fw-bold">Settings & Configuration</h2>
          <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width:45px; height:45px;">HR</div>
            <div>
              <h6 class="mb-0"><?php echo $_SESSION['employeeName']; ?></h6>
              <small><?php echo $_SESSION['employeePosition']; ?></small>
            </div>
          </div>
        </div>

        <!-- Welcome Message -->
        <div class="alert alert-info mb-4">
          <i class="fas fa-cog me-2"></i>
          <strong>System Configuration Center</strong> - Manage all HR system settings and configurations from one place.
        </div>

        <!-- Settings Grid -->
        <div class="row g-4">
          <!-- Leave Management -->
          <div class="col-xl-4 col-lg-6 col-md-6">
            <a href="settings/leaveTypes.php" class="settings-link">
              <div class="card settings-card">
                <div class="card-body text-center p-4">
                  <div class="settings-icon bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-calendar-alt"></i>
                  </div>
                  <h5 class="card-title">Leave Management</h5>
                  <p class="card-description">
                    Configure leave types, policies, and manage employee leave settings and entitlements.
                  </p>
                  <div class="mt-3">
                    <span class="badge bg-primary">Manage Leaves</span>
                  </div>
                </div>
              </div>
            </a>
          </div>

          <!-- Report Management -->
          <div class="col-xl-4 col-lg-6 col-md-6">
            <a href="settings/reportTypes.php" class="settings-link">
              <div class="card settings-card">
                <div class="card-body text-center p-4">
                  <div class="settings-icon bg-danger bg-opacity-10 text-danger">
                    <i class="fas fa-flag"></i>
                  </div>
                  <h5 class="card-title">Report Management</h5>
                  <p class="card-description">
                    Manage incident report types, categories, and configure reporting workflows.
                  </p>
                  <div class="mt-3">
                    <span class="badge bg-danger">Configure Reports</span>
                  </div>
                </div>
              </div>
            </a>
          </div>

          <!-- Performance Management -->
          <div class="col-xl-4 col-lg-6 col-md-6">
            <a href="settings/managePerformance.php" class="settings-link">
              <div class="card settings-card">
                <div class="card-body text-center p-4">
                  <div class="settings-icon bg-success bg-opacity-10 text-success">
                    <i class="fas fa-chart-line"></i>
                  </div>
                  <h5 class="card-title">Performance Management</h5>
                  <p class="card-description">
                    Set up performance metrics, evaluation criteria, and review cycles.
                  </p>
                  <div class="mt-3">
                    <span class="badge bg-success">Track Performance</span>
                  </div>
                </div>
              </div>
            </a>
          </div>

          <!-- Department & Positions -->
          <div class="col-xl-4 col-lg-6 col-md-6">
            <a href="settings/departmentPos.php" class="settings-link">
              <div class="card settings-card">
                <div class="card-body text-center p-4">
                  <div class="settings-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-sitemap"></i>
                  </div>
                  <h5 class="card-title">Department & Positions</h5>
                  <p class="card-description">
                    Manage organizational structure, departments, job positions, and hierarchies.
                  </p>
                  <div class="mt-3">
                    <span class="badge bg-warning text-dark">Organize Structure</span>
                  </div>
                </div>
              </div>
            </a>
          </div>

          <!-- Backup & Restore -->
          <div class="col-xl-4 col-lg-6 col-md-6">
            <a href="settings/backupRes.php" class="settings-link">
              <div class="card settings-card">
                <div class="card-body text-center p-4">
                  <div class="settings-icon bg-info bg-opacity-10 text-info">
                    <i class="fas fa-database"></i>
                  </div>
                  <h5 class="card-title">Backup & Restore</h5>
                  <p class="card-description">
                    Create system backups, restore data, and manage database maintenance tasks.
                  </p>
                  <div class="mt-3">
                    <span class="badge bg-info">Data Safety</span>
                  </div>
                </div>
              </div>
            </a>
          </div>
      </div>
    </div>
  </div>

  <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    // Add some interactive effects
    document.addEventListener('DOMContentLoaded', function() {
      const cards = document.querySelectorAll('.settings-card');
      
      cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
          this.style.cursor = 'pointer';
        });
        
        card.addEventListener('click', function() {
          const link = this.closest('a');
          if (link) {
            window.location.href = link.href;
          }
        });
      });
    });
  </script>
</body>
</html>