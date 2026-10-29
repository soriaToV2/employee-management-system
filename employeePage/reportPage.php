<?php
session_start();
require "../functions.php";
require "../connection.php";
redirectToLogin('Employee');

$currentEmployeeID = $_SESSION['employeeID'];

$err = null;
$success = null;

// Handle form submission
if (isset($_POST['submit'])) {
    // Determine if reporting anonymously
    $isAnonymous = isset($_POST['reporting']) && $_POST['reporting'] === 'anonymous';
    
    // Set reporter ID to NULL if anonymous, otherwise use current user's ID
    $reporterID = $isAnonymous ? NULL : $currentEmployeeID;
    
    $reportedEmployeeID = mysqli_real_escape_string($con, $_POST['reported_employee_id']);
    $reportTypeID = mysqli_real_escape_string($con, $_POST['report_type_id']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    
    // File upload handling
    $fileData = null;
    
    if (isset($_FILES['evidence_file']) && $_FILES['evidence_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['evidence_file']['tmp_name'];
        $fileSize = $_FILES['evidence_file']['size'];
        $fileName = $_FILES['evidence_file']['name'];
        $fileType = $_FILES['evidence_file']['type'];
        
        // Basic validation - max 5MB and allowed file types
        $maxFileSize = 5 * 1024 * 1024;
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        
        if ($fileSize <= $maxFileSize) {
            if (in_array($fileType, $allowedTypes)) {
                // Read file content and store in database
                $fileData = mysqli_real_escape_string($con, file_get_contents($fileTmpPath));
            } else {
                $err = "Invalid file type. Allowed types: JPG, PNG, GIF, PDF.";
            }
        } else {
            $err = "File size too large. Maximum size is 5MB.";
        }
    } elseif (isset($_FILES['evidence_file']) && $_FILES['evidence_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other file upload errors
        $err = "File upload error. Please try again.";
    }
    
    if (empty($err)) {
        // Prepare the query based on whether we have file data or not
        if ($fileData) {
            if ($reporterID) {
                // Insert with file data and reporter ID
                $insertQuery = "INSERT INTO reports (reporter_id, reported_employee_id, report_type_id, details, file_data, status) 
                               VALUES ('$reporterID', '$reportedEmployeeID', '$reportTypeID', '$description', '$fileData', 'Pending')";
            } else {
                // Insert with file data but no reporter ID (anonymous)
                $insertQuery = "INSERT INTO reports (reporter_id, reported_employee_id, report_type_id, details, file_data, status) 
                               VALUES (NULL, '$reportedEmployeeID', '$reportTypeID', '$description', '$fileData', 'Pending')";
            }
        } else {
            if ($reporterID) {
                // Insert without file data but with reporter ID
                $insertQuery = "INSERT INTO reports (reporter_id, reported_employee_id, report_type_id, details, status) 
                               VALUES ('$reporterID', '$reportedEmployeeID', '$reportTypeID', '$description', 'Pending')";
            } else {
                // Insert without file data and no reporter ID (anonymous)
                $insertQuery = "INSERT INTO reports (reporter_id, reported_employee_id, report_type_id, details, status) 
                               VALUES (NULL, '$reportedEmployeeID', '$reportTypeID', '$description', 'Pending')";
            }
        }
        
        mysqli_begin_transaction($con);
        if (mysqli_query($con, $insertQuery)) {
            mysqli_commit($con);
            $success = "Your report has been successfully submitted!" . 
                      ($isAnonymous ? " Your report is anonymous." : "");
        } else {
            mysqli_rollback($con);
            $err = "Error submitting report: " . mysqli_error($con);
        }
    }
}

// Fetch report types
$reportTypeQuery = "SELECT * FROM report_types";
$reportTypeResult = mysqli_query($con, $reportTypeQuery);

// Fetch employees (excluding the current user) - FIXED: Removed applications table join
$employeeQuery = "SELECT e.employee_id, c.first_name, c.last_name, p.position_name, d.department_name
                 FROM employees e
                 JOIN candidates c ON e.candidate_id = c.candidate_id
                 JOIN positions p ON e.position_id = p.position_id
                 JOIN departments d ON p.department_id = d.department_id
                 WHERE e.employee_id != '$currentEmployeeID' AND e.status = 'Active'
                 ORDER BY c.first_name, c.last_name";
$employeeResult = mysqli_query($con, $employeeQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>File a Report | Employee Portal</title>
  <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../css/Global.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container-fluid">
    <div class="row flex-nowrap">

      <?php include 'nav.php'; ?>

      <!-- Main Content -->
      <div class="col py-4 px-5">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
          <h2 class="fw-bold">File a Report</h2>
          <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width:45px; height:45px;">EN</div>
            <div>
              <h6 class="mb-0"><?php echo $_SESSION['employeeName']; ?></h6>
              <small><?php echo $_SESSION['employeePosition']; ?></small>
            </div>
          </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Success!</strong> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($err)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Error!</strong> <?php echo $err; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Page Content -->
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fa-solid fa-flag" style="margin-right: 10px"></i> Report an Incident or Policy Violation</h5>
          </div>

          <div class="card-body">
            <div class="alert alert-warning">
              <strong>
                <i class="fa-solid fa-circle-info" style="color: #007bff; font-size: 24px;"></i>
                Confidential Reporting:</strong><br>
              All reports are handled confidentially. You may report anonymously if preferred.
              For emergencies, please contact security immediately at extension <strong>911</strong>.
            </div>

            <!-- ADD enctype="multipart/form-data" HERE -->
            <form method="POST" action="reportPage.php" id="reportForm" enctype="multipart/form-data">
              <!-- Report Type -->
              <div class="mb-3">
                <label for="report_type_id" class="form-label fw-semibold">Report Type</label>
                <select id="report_type_id" name="report_type_id" class="form-select" required>
                  <option value="">-- Select Report Type --</option>
                  <?php
                  if (mysqli_num_rows($reportTypeResult) > 0) {
                      while ($row = mysqli_fetch_assoc($reportTypeResult)) {
                          $reportTypeId = $row['report_type_id'];
                          $reportName = $row['report_name'];
                          echo "<option value='$reportTypeId'>$reportName</option>";
                      }
                  } else {
                      echo "<option value=''>No report types available</option>";
                  }
                  ?>
                </select>
              </div>

              <!-- Employee Being Reported -->
              <div class="mb-3">
                <label for="reported_employee_id" class="form-label fw-semibold">Employee Being Reported</label>
                <select id="reported_employee_id" name="reported_employee_id" class="form-select" required>
                  <option value="">-- Select Employee --</option>
                  <?php
                  if (mysqli_num_rows($employeeResult) > 0) {
                      while ($row = mysqli_fetch_assoc($employeeResult)) {
                          $employeeId = $row['employee_id'];
                          $fullName = $row['first_name'] . ' ' . $row['last_name'];
                          $position = $row['position_name'];
                          $department = $row['department_name'];
                          $displayText = "$fullName - $position ($department)";
                          echo "<option value='$employeeId'>$displayText</option>";
                      }
                  } else {
                      echo "<option value=''>No employees available</option>";
                  }
                  ?>
                </select>
                <div class="form-text">Select the employee you want to report from the list above.</div>
              </div>

              <!-- Description -->
              <div class="mb-3">
                <label for="description" class="form-label fw-semibold">Detailed Description</label>
                <textarea id="description" class="form-control" name="description" rows="4" placeholder="Please provide specific details about what occurred, including date, time, and location if applicable." required></textarea>
              </div>

              <!-- File Upload -->
              <div class="mb-3">
                <label class="form-label fw-semibold">Evidence Attachment (Optional)</label>
                <input type="file" class="form-control" name="evidence_file" accept=".jpg,.jpeg,.png,.gif,.pdf">
                <div class="form-text">Allowed files: JPG, PNG, GIF, PDF (Max: 5MB)</div>
              </div>

              <!-- Reporting Options -->
              <div class="mb-4">
                <label class="form-label fw-semibold">Reporting Options</label>
                <div class="form-check">
                  <input class="form-check-input" type="radio" id="named" name="reporting" value="named" checked>
                  <label class="form-check-label" for="named">Report As Employee</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" id="anonymous" name="reporting" value="anonymous">
                  <label class="form-check-label" for="anonymous">Report Anonymously</label>
                </div>
              </div>

              <div class="d-flex justify-content-end">
                <button type="submit" name="submit" class="btn btn-primary px-4">
                  <i class="fas fa-paper-plane me-1"></i> Submit Report
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const form = document.getElementById("reportForm");

      form.addEventListener("submit", function(event) {
        // Basic validation
        const reportType = document.getElementById('report_type_id').value;
        const employee = document.getElementById('reported_employee_id').value;
        const description = document.getElementById('description').value;

        if (!reportType || !employee || !description.trim()) {
          event.preventDefault();
          alert('Please fill in all required fields.');
          return false;
        }
      });
    });
  </script>
</body>
</html>