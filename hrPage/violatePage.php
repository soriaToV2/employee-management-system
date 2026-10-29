<?php
session_start();
require "../functions.php";
require "../connection.php";
redirectToLogin('HR');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_review'])) {
        // Handle review submission
        $report_id = $_POST['report_id'];
        $status = $_POST['status'];
        $employee_status = $_POST['employee_status'];
        $remarks = $_POST['remarks'];
        $hr_id = $_SESSION['employeeId'];
        
        // Update report status and HR remarks in the same table
        $update_report_query = "UPDATE reports SET 
                                status = '$status', 
                                hr_remarks = '$remarks',
                                reviewed_at = NOW() 
                                WHERE report_id = '$report_id'";
        mysqli_query($con, $update_report_query);
        
        // If employee status is changed, update employees table
        if (in_array($employee_status, ['Terminated', 'Suspended'])) {
            $employee_id = $_POST['employee_id'];
            $update_employee_query = "UPDATE employees SET status = '$employee_status' WHERE employee_id = '$employee_id'";
            mysqli_query($con, $update_employee_query);
        }
        
        $_SESSION['message'] = "Violation review updated successfully!";
        header("Location: violatePage.php");
        exit();
    }
}

$violations_query = "
    SELECT 
        r.report_id,
        r.reporter_id,
        r.reported_employee_id,
        rt.report_name as violation_type,
        r.details,
        r.status,
        r.hr_remarks,
        r.reviewed_at,
        r.submitted_at,
        r.file_data,
        -- Reporter name from candidates table
        creporter.first_name as reporter_first_name,
        creporter.last_name as reporter_last_name,
        -- Reported employee name from candidates table
        creported.first_name as reported_first_name,
        creported.last_name as reported_last_name,
        reported.employee_id as reported_employee_id
    FROM reports r
    LEFT JOIN report_types rt ON r.report_type_id = rt.report_type_id
    -- Join for reporter (employee -> candidate)
    LEFT JOIN employees reporter ON r.reporter_id = reporter.employee_id
    LEFT JOIN candidates creporter ON reporter.candidate_id = creporter.candidate_id
    -- Join for reported employee (employee -> candidate)
    LEFT JOIN employees reported ON r.reported_employee_id = reported.employee_id
    LEFT JOIN candidates creported ON reported.candidate_id = creported.candidate_id
    ORDER BY 
        CASE 
            WHEN r.status = 'Pending' THEN 1
            WHEN r.status = 'Reviewed' THEN 2
            WHEN r.status = 'Resolved' THEN 3
            ELSE 4
        END,
        r.submitted_at DESC
";

$violations_result = mysqli_query($con, $violations_query);

// Check for query errors
if (!$violations_result) {
    die("Query failed: " . mysqli_error($con));
}

$violations = [];
while ($row = mysqli_fetch_assoc($violations_result)) {
    $violations[] = $row;
}

// Fetch additional employee details (position and department) for each violation
foreach ($violations as &$violation) {
    if (!empty($violation['reported_employee_id'])) {
        $employee_id = $violation['reported_employee_id'];
        $employee_details_query = "
            SELECT 
                a.position_id,
                p.position_name,
                d.department_name
            FROM applications a
            LEFT JOIN employees e ON a.candidate_id = e.candidate_id
            LEFT JOIN positions p ON a.position_id = p.position_id
            LEFT JOIN departments d ON p.department_id = d.department_id
            WHERE e.employee_id = '$employee_id'
            ORDER BY a.application_id DESC
            LIMIT 1
        ";
        
        $employee_details_result = mysqli_query($con, $employee_details_query);
        if ($employee_details_result && mysqli_num_rows($employee_details_result) > 0) {
            $employee_details = mysqli_fetch_assoc($employee_details_result);
            $violation['position_name'] = $employee_details['position_name'] ?? 'N/A';
            $violation['department_name'] = $employee_details['department_name'] ?? 'N/A';
        } else {
            $violation['position_name'] = 'N/A';
            $violation['department_name'] = 'N/A';
        }
    } else {
        $violation['position_name'] = 'N/A';
        $violation['department_name'] = 'N/A';
    }
}
unset($violation); // Break the reference

// Handle file download/view request
if (isset($_GET['download_file']) && isset($_GET['report_id'])) {
    $report_id = mysqli_real_escape_string($con, $_GET['report_id']);
    $file_query = "SELECT file_data FROM reports WHERE report_id = '$report_id'";
    $file_result = mysqli_query($con, $file_query);
    
    if ($file_result && mysqli_num_rows($file_result) > 0) {
        $file_data = mysqli_fetch_assoc($file_result);
        
        if (!empty($file_data['file_data'])) {
            // Clean any previous output
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Set appropriate headers for PDF
            header('Content-Type: application/pdf');
            
            if (isset($_GET['download']) && $_GET['download'] == '1') {
                header('Content-Disposition: attachment; filename="evidence_' . $report_id . '.pdf"');
            } else {
                header('Content-Disposition: inline; filename="evidence_' . $report_id . '.pdf"');
            }
            
            header('Content-Length: ' . strlen($file_data['file_data']));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            
            // Output the file data
            echo $file_data['file_data'];
            exit();
        } else {
            error_log("File data is empty for report_id: " . $report_id);
        }
    } else {
        error_log("No file found for report_id: " . $report_id);
    }
    
    // If no file found, return 404
    header("HTTP/1.0 404 Not Found");
    echo "File not found";
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Company Name - HR Violations</title>
  <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../css/Global.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
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
          <h2 class="fw-bold">Violations</h2>
          <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width:45px; height:45px;">HR</div>
            <div>
              <h6 class="mb-0"><?php echo $_SESSION['employeeName']; ?></h6>
              <small><?php echo $_SESSION['employeePosition']; ?></small>
            </div>
          </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($_SESSION['message'])): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <!-- Violations Card -->
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white d-flex align-items-center">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <h5 class="mb-0">Policy Violations</h5>
          </div>
          <div class="card-body">

            <!-- Employees Section -->
            <div class="mb-4">
              <h5 class="fw-bold border-bottom pb-2 mb-3"><i class="fas fa-users me-2 text-primary"></i>Reported Employees</h5>
              <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="employeesTable">
                  <thead class="table-light">
                    <tr>
                      <th>Employee Name</th>
                      <th>ID</th>
                      <th>Position</th>
                      <th>Violation</th>
                      <th>Status</th>
                      <th>Date Reported</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($violations)): ?>
                      <tr>
                        <td colspan="7" class="text-center text-muted">No violations reported</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($violations as $violation): ?>
                        <tr>
                          <td>
                            <?php 
                              $reported_name = 'Unknown';
                              if (!empty($violation['reported_first_name'])) {
                                $reported_name = htmlspecialchars($violation['reported_first_name'] . ' ' . $violation['reported_last_name']);
                              }
                              echo $reported_name;
                            ?>
                          </td>
                          <td><?php echo htmlspecialchars($violation['reported_employee_id'] ?? 'N/A'); ?></td>
                          <td><?php echo htmlspecialchars($violation['position_name'] ?? 'N/A'); ?></td>
                          <td><?php echo htmlspecialchars($violation['violation_type'] ?? 'N/A'); ?></td>
                          <td>
                            <?php 
                              $status = $violation['status'] ?? 'Pending';
                              $status_class = '';
                              switch($status) {
                                case 'Pending': $status_class = 'bg-warning text-dark'; break;
                                case 'Reviewed': $status_class = 'bg-info'; break;
                                case 'Resolved': $status_class = 'bg-success'; break;
                                default: $status_class = 'bg-secondary';
                              }
                            ?>
                            <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($status); ?></span>
                          </td>
                          <td><?php echo date('M j, Y', strtotime($violation['submitted_at'])); ?></td>
                          <td>
                            <button title="View" type="button"
                              class="btn btn-outline-primary btn-sm me-1"
                              data-bs-toggle="modal" data-bs-target="#reviewModal"
                              data-report-id="<?php echo $violation['report_id']; ?>"
                              data-emp="<?php echo htmlspecialchars($reported_name); ?>"
                              data-id="<?php echo $violation['reported_employee_id']; ?>"
                              data-violation="<?php echo htmlspecialchars($violation['violation_type'] ?? 'N/A'); ?>"
                              data-date="<?php echo date('Y-m-d', strtotime($violation['submitted_at'])); ?>"
                              data-desc="<?php echo htmlspecialchars($violation['details'] ?? 'No description'); ?>"
                              data-status="<?php echo $status; ?>"
                              onclick="openReviewModalFromBtn(this)">
                              <i class="fas fa-eye me-1"></i>
                            </button>

                            <button title="More Info" type="button"
                              class="btn btn-info btn-sm text-white"
                              data-bs-toggle="modal" data-bs-target="#infoModal"
                              data-emp="<?php echo htmlspecialchars($reported_name); ?>"
                              data-id="<?php echo $violation['reported_employee_id']; ?>"
                              data-violation="<?php echo htmlspecialchars($violation['violation_type'] ?? 'N/A'); ?>"
                              data-date="<?php echo date('Y-m-d', strtotime($violation['submitted_at'])); ?>"
                              data-desc="<?php echo htmlspecialchars($violation['details'] ?? 'No description'); ?>"
                              data-status="<?php echo $status; ?>"
                              data-department="<?php echo htmlspecialchars($violation['department_name'] ?? 'N/A'); ?>"
                              data-reporter="<?php 
                                $reporter_name = 'Unknown';
                                if (!empty($violation['reporter_first_name'])) {
                                  $reporter_name = htmlspecialchars($violation['reporter_first_name'] . ' ' . $violation['reporter_last_name']);
                                }
                                echo $reporter_name;
                              ?>"
                              data-report-id="<?php echo $violation['report_id']; ?>"
                              data-has-file="<?php echo !empty($violation['file_data']) ? '1' : '0'; ?>"
                              onclick="openInfoModalFromBtn(this)">
                              <i class="fas fa-info-circle me-1"></i>
                            </button>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- REVIEW MODAL -->
  <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="reviewForm" method="POST">
          <input type="hidden" name="save_review" value="1">
          <input type="hidden" id="reviewReportId" name="report_id">
          <input type="hidden" id="reviewEmployeeId" name="employee_id">
          
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="reviewModalLabel"><i class="fas fa-gavel me-2"></i>Review Violation</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-semibold">Reported Employee</label>
              <input type="text" id="reviewEmployeeName" class="form-control" readonly>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Employee ID</label>
              <input type="text" id="reviewEmployeeIdDisplay" class="form-control" readonly>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Violation Type</label>
              <input type="text" id="reviewViolationType" class="form-control" readonly>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Report Status</label>
              <select id="reviewStatusSelect" name="status" class="form-select" required>
                <option value="Pending">Pending</option>
                <option value="Reviewed">Reviewed</option>
                <option value="Resolved">Resolved</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Employee Status Action</label>
              <select id="reviewEmployeeStatus" name="employee_status" class="form-select">
                <option value="">No Change</option>
                <option value="Suspended">Suspend Employee</option>
                <option value="Terminated">Terminate Employee</option>
              </select>
              <small class="text-muted">Note: This will update the employee's status in the system</small>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Remarks</label>
              <textarea id="reviewRemarks" name="remarks" class="form-control" rows="3" placeholder="Enter review remarks..."></textarea>
            </div>
          </div>

          <div class="modal-footer">
            <button type="submit" class="btn btn-success">
              <i class="fas fa-save me-1"></i>Save Review
            </button>
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
              <i class="fas fa-times me-1"></i>Close
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- INFO MODAL -->
  <div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="infoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title" id="infoModalLabel"><i class="fas fa-info-circle me-2"></i>Violation Details</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <p><strong>Reported Employee:</strong> <span id="infoEmployeeName">N/A</span></p>
              <p><strong>Employee ID:</strong> <span id="infoEmployeeId">N/A</span></p>
              <p><strong>Reporter:</strong> <span id="infoReporter">N/A</span></p>
              <p><strong>Department:</strong> <span id="employeeDepartment">N/A</span></p>
            </div>
            <div class="col-md-6">
              <p><strong>Violation Type:</strong> <span id="infoViolation">N/A</span></p>
              <p><strong>Date Reported:</strong> <span id="infoDate">N/A</span></p>
              <p><strong>Status:</strong> <span id="infoStatus" class="badge bg-secondary">N/A</span></p>
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label fw-semibold">Description:</label>
            <div class="border rounded p-3 bg-dark">
              <span id="infoDesc">N/A</span>
            </div>
          </div>
          
          <!-- Evidence Section -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Evidence/Attachments:</label>
            <div id="evidenceSection" class="border rounded p-3 bg-dark">
              <div id="noEvidenceMessage" class="text-muted">
                <i class="fas fa-file-excel me-2"></i>No evidence attached
              </div>
              <div id="evidenceButtons" class="d-none">
                <a id="viewEvidenceBtn" href="#" target="_blank" class="btn btn-primary btn-sm me-2">
                  <i class="fas fa-eye me-1"></i>View Evidence
                </a>
                <a id="downloadEvidenceBtn" href="#" class="btn btn-success btn-sm">
                  <i class="fas fa-download me-1"></i>Download Evidence
                </a>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-danger" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i>Close
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="scripts/violatePage.js"></script>
</body>
</html>