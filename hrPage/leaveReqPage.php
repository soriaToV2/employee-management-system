<?php
session_start();
require "../functions.php";
require "../connection.php";
redirectToLogin('HR');

$currentEmployeeID = $_SESSION['employeeID'];

// Get filter parameters
$departmentFilter = isset($_GET['department']) ? $_GET['department'] : '';
$employeeIdFilter = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';

// Build the base query
$employeeLeavesQuery = "SELECT * FROM employee_leave_info 
                       WHERE employee_id != '$currentEmployeeID' 
                       AND leave_status = 'Pending'";

// Add filters if provided
if (!empty($departmentFilter)) {
    $employeeLeavesQuery .= " AND department_id = '" . mysqli_real_escape_string($con, $departmentFilter) . "'";
}

if (!empty($employeeIdFilter)) {
    $employeeLeavesQuery .= " AND employee_id LIKE '%" . mysqli_real_escape_string($con, $employeeIdFilter) . "%'";
}

$employeeLeavesQuery .= " ORDER BY employee_id";

$leaveResult = mysqli_query($con, $employeeLeavesQuery);

// Get all departments for the filter dropdown
$departmentsQuery = "SELECT department_id, department_name FROM departments ORDER BY department_name";
$departmentsResult = mysqli_query($con, $departmentsQuery);
$departments = [];
while ($dept = mysqli_fetch_assoc($departmentsResult)) {
    $departments[] = $dept;
}

$declineErr = null;
$approveErr = null;
$successMsg = null;

// Handle file viewing for HR
if (isset($_GET['view_proof'])) {
    $leaveId = mysqli_real_escape_string($con, $_GET['view_proof']);
    
    // Get the proof from leave_requests table
    $query = "SELECT lr.proof 
              FROM leave_requests lr
              JOIN employee_leave_info eli ON lr.leave_id = eli.leave_id
              WHERE lr.leave_id = '$leaveId'";
    $result = mysqli_query($con, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        if ($row['proof']) {
            // Detect file type from the binary data
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $fileType = $finfo->buffer($row['proof']);
            
            // For images, display directly in browser
            if (strpos($fileType, 'image/') === 0) {
                header('Content-Type: ' . $fileType);
                echo $row['proof'];
                exit();
            } 
            // For PDFs, display in browser
            else if ($fileType === 'application/pdf') {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="proof.pdf"');
                echo $row['proof'];
                exit();
            }
        }
    }
    // If no proof found, redirect back
    header("Location: leaveReqPage.php?error=proof_not_found");
    exit();
}

if(isset($_POST["declineSubmit"])){
    $leaveID = $_POST["leaveID"];
    $declineReason = mysqli_real_escape_string($con, $_POST["declineReason"]);

    $declineQuery = "UPDATE leave_requests SET status = 'Denied', status_reason = '$declineReason', approved_by = '$currentEmployeeID' WHERE leave_id = '$leaveID'";

    mysqli_begin_transaction($con);
    if(mysqli_query($con, $declineQuery)){
        mysqli_commit($con);
        $successMsg = "Leave request declined successfully!";
        header("Location: leaveReqPage.php?success=" . urlencode($successMsg));
        exit();
    } else {
        mysqli_rollback($con);
        $declineErr = "Failed to decline the leave request!";
    }
}

if(isset($_POST["approveSubmit"])){
    $leaveID = $_POST["leaveID"];

    $approveQuery = "UPDATE leave_requests SET status = 'Approved', approved_by = '$currentEmployeeID' WHERE leave_id = '$leaveID'";

    mysqli_begin_transaction($con);
    if(mysqli_query($con, $approveQuery)){
        mysqli_commit($con);
        $successMsg = "Leave request approved successfully!";
        header("Location: leaveReqPage.php?success=" . urlencode($successMsg));
        exit();
    } else {
        mysqli_rollback($con);
        $approveErr = "Failed to approve the leave request!";
    }
}

if(isset($_GET['success'])) {
    $successMsg = $_GET['success'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Company Name - HR Leave Requests</title>
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
          <h2 class="fw-bold">Employee Management</h2>
          <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width:45px; height:45px;">HR</div>
            <div>
              <h6 class="mb-0"><?php echo $_SESSION['employeeName']; ?></h6>
              <small><?php echo $_SESSION['employeePosition']; ?></small>
            </div>
          </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if($successMsg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($successMsg); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if($declineErr): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($declineErr); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if($approveErr): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($approveErr); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
            <div>
              <i class="fas fa-filter me-2"></i>
              <h5 class="mb-0 d-inline">Filter Leave Requests</h5>
            </div>
          </div>
          <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
              <div class="col-md-5">
                <label for="department" class="form-label fw-semibold text-white">Department</label>
                <select class="form-select border-secondary" id="department" name="department">
                  <option value="">All Departments</option>
                  <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['department_id']; ?>" 
                      <?php echo ($departmentFilter == $dept['department_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($dept['department_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-5">
                <label for="employee_id" class="form-label fw-semibold text-white">Employee ID</label>
                <input type="text" class="form-control border-secondary" id="employee_id" name="employee_id" 
                  value="<?php echo htmlspecialchars($employeeIdFilter); ?>" 
                  placeholder="Enter employee ID">
              </div>
              <div class="col-md-2 d-grid gap-2">
                <button type="submit" class="btn btn-primary fw-semibold">
                  <i class="fas fa-search me-1"></i> Filter
                </button>
                <a href="?" class="btn btn-outline-primary fw-semibold">
                  <i class="fas fa-rotate me-1"></i> Clear
                </a>
              </div>
            </form>
          </div>
        </div>

        <!-- Leave Requests Table -->
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
            <div>
              <i class="fas fa-calendar-check me-2"></i>
              <h5 class="mb-0 d-inline">Leave Requests</h5>
            </div>
            <div class="text-white">
              <small>
                <?php 
                $totalRequests = mysqli_num_rows($leaveResult);
                echo "Total: " . $totalRequests . " request" . ($totalRequests != 1 ? 's' : '');
                
                if (!empty($departmentFilter) || !empty($employeeIdFilter)) {
                  echo " (Filtered)";
                }
                ?>
              </small>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Employee Name</th>
                    <th>ID</th>
                    <th>Leave Type</th>
                    <th>Date of Leave</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  if(mysqli_num_rows($leaveResult) > 0){
                    while($row = mysqli_fetch_assoc($leaveResult)){
                      $startDate = new DateTime($row['start_date']);
                      $formattedStart = $startDate->format('M. d, Y');
                      $endDate = new DateTime($row['end_date']);
                      $formattedEnd = $endDate->format('M. d, Y');
                      
                      // Check if proof exists
                      $proofQuery = "SELECT proof FROM leave_requests WHERE leave_id = '{$row['leave_id']}'";
                      $proofResult = mysqli_query($con, $proofQuery);
                      $hasProof = false;
                      if ($proofResult && mysqli_num_rows($proofResult) > 0) {
                          $proofData = mysqli_fetch_assoc($proofResult);
                          $hasProof = !empty($proofData['proof']);
                      }
                      ?>
                      <tr>
                        <td><?php echo htmlspecialchars($row['employee_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['leave_type']); ?></td>
                        <td><?php echo $formattedStart . ' - ' . $formattedEnd; ?></td>
                        <td><span class="badge bg-warning"><?php echo htmlspecialchars($row['leave_status']); ?></span></td>
                        <td>
                          <button title="Approve" class="btn btn-success btn-sm me-1 approve-btn"
                            data-employee="<?php echo htmlspecialchars($row['employee_name']); ?>"
                            data-employee-id="<?php echo htmlspecialchars($row['employee_id']); ?>"
                            data-leave-id="<?php echo htmlspecialchars($row['leave_id']); ?>"><i class="fas fa-check"></i></button>
                          <button title="Decline" class="btn btn-danger btn-sm me-1 decline-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#declineModal"
                            data-employee="<?php echo htmlspecialchars($row['employee_name']); ?>"
                            data-employee-id="<?php echo htmlspecialchars($row['employee_id']); ?>"
                            data-leave-id="<?php echo htmlspecialchars($row['leave_id']); ?>"><i class="fas fa-times"></i></button>
                          <button title="View" class="btn btn-outline-primary btn-sm view-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#viewModal"
                            data-employee="<?php echo htmlspecialchars($row['employee_name']); ?>"
                            data-employee-id="<?php echo htmlspecialchars($row['employee_id']); ?>"
                            data-position="<?php echo htmlspecialchars($row['position_name']); ?>"
                            data-department="<?php echo htmlspecialchars($row['department_name']); ?>"
                            data-leave-type="<?php echo htmlspecialchars($row['leave_type']); ?>"
                            data-start-date="<?php echo $formattedStart; ?>"
                            data-end-date="<?php echo $formattedEnd; ?>"
                            data-reason="<?php echo htmlspecialchars($row['reason']); ?>"
                            data-status="<?php echo htmlspecialchars($row['leave_status']); ?>"
                            data-proof-available="<?php echo $hasProof ? 'Yes' : 'No'; ?>"
                            data-leave-id="<?php echo htmlspecialchars($row['leave_id']); ?>"><i class="fas fa-info-circle"></i></button>
                        </td>
                      </tr>
                      <?php
                    }
                  } else {
                    echo '<tr><td colspan="9" class="text-center">No pending leave requests found' . 
                         ((!empty($departmentFilter) || !empty($employeeIdFilter)) ? ' matching the filter criteria' : '') . 
                         '.</td></tr>';
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Decline Modal -->
  <div class="modal fade" id="declineModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="post" action="leaveReqPage.php" id="declineForm">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title"><i class="fa-solid fa-ban me-2"></i>Decline Leave Request</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p><strong>Employee:</strong> <span id="declineEmpName"></span></p>
            <p><strong>Employee ID:</strong> <span id="declineEmpID"></span></p>
            <input type="hidden" name="leaveID" id="declineLeaveID">
            <div class="mb-3">
              <label for="declineReason" class="form-label">Reason for Decline</label>
              <textarea name="declineReason" id="declineReason" class="form-control" rows="3" placeholder="Enter your reason..." required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" name="declineSubmit" class="btn btn-success">
              <i class="fa-solid fa-paper-plane me-1"></i>Submit
            </button>
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
              <i class="fa-solid fa-xmark me-1"></i>Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Approve Modal -->
  <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="post" action="leaveReqPage.php" id="approveForm">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title"><i class="fa-solid fa-check-circle me-2"></i>Approve Leave Request</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to approve the leave request for:</p>
            <p><strong>Employee:</strong> <span id="approveEmpName"></span></p>
            <p><strong>Employee ID:</strong> <span id="approveEmpID"></span></p>
            <input type="hidden" name="leaveID" id="approveLeaveID">
          </div>
          <div class="modal-footer">
            <button type="submit" name="approveSubmit" class="btn btn-success">
              <i class="fa-solid fa-check me-1"></i>Approve
            </button>
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
              <i class="fa-solid fa-xmark me-1"></i>Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- View Modal -->
  <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><i class="fa-solid fa-eye me-2"></i>Leave Request Details</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="fw-bold">Employee Name:</label>
                <p id="viewEmpName" class="mb-2"></p>
              </div>
              <div class="mb-3">
                <label class="fw-bold">Employee ID:</label>
                <p id="viewEmpID" class="mb-2"></p>
              </div>
              <div class="mb-3">
                <label class="fw-bold">Department:</label>
                <p id="viewDepartment" class="mb-2"></p>
              </div>
              <div class="mb-3">
                <label class="fw-bold">Position:</label>
                <p id="viewPosition" class="mb-2"></p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="fw-bold">Leave Type:</label>
                <p id="viewLeaveType" class="mb-2"></p>
              </div>
              <div class="mb-3">
                <label class="fw-bold">Date of Leave:</label>
                <p id="viewDate" class="mb-2"></p>
              </div>
              <div class="mb-3">
                <label class="fw-bold">Status:</label>
                <p id="viewStatus" class="mb-2"></p>
              </div>
              <div class="mb-3">
                <label class="fw-bold">Proof Available:</label>
                <p id="viewProof" class="mb-2"></p>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-12">
              <div class="mb-3">
                <label class="fw-bold">Reason:</label>
                <div class="border rounded p-3">
                  <p id="viewReason" class="mb-0"></p>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-12">
              <div class="mb-3">
                <label class="fw-bold">Proof Document:</label>
                <div id="proofContainer" class="border rounded p-3 text-center">
                  <!-- Proof content will be loaded here -->
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
            <i class="fa-solid fa-xmark me-1"></i>Close
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Decline Modal
      const declineButtons = document.querySelectorAll('.decline-btn');
      const declineEmpName = document.getElementById('declineEmpName');
      const declineEmpID = document.getElementById('declineEmpID');
      const declineLeaveID = document.getElementById('declineLeaveID');

      declineButtons.forEach(btn => {
        btn.addEventListener('click', function () {
          declineEmpName.textContent = this.dataset.employee;
          declineEmpID.textContent = this.dataset.employeeId;
          declineLeaveID.value = this.dataset.leaveId;
        });
      });

      // Approve Modal
      const approveButtons = document.querySelectorAll('.approve-btn');
      const approveEmpName = document.getElementById('approveEmpName');
      const approveEmpID = document.getElementById('approveEmpID');
      const approveLeaveID = document.getElementById('approveLeaveID');

      approveButtons.forEach(btn => {
        btn.addEventListener('click', function () {
          approveEmpName.textContent = this.dataset.employee;
          approveEmpID.textContent = this.dataset.employeeId;
          approveLeaveID.value = this.dataset.leaveId;
          
          const approveModal = new bootstrap.Modal(document.getElementById('approveModal'));
          approveModal.show();
        });
      });

      // View Modal
      const viewButtons = document.querySelectorAll('.view-btn');
      const viewEmpName = document.getElementById('viewEmpName');
      const viewEmpID = document.getElementById('viewEmpID');
      const viewDepartment = document.getElementById('viewDepartment');
      const viewPosition = document.getElementById('viewPosition');
      const viewLeaveType = document.getElementById('viewLeaveType');
      const viewDate = document.getElementById('viewDate');
      const viewReason = document.getElementById('viewReason');
      const viewStatus = document.getElementById('viewStatus');
      const viewProof = document.getElementById('viewProof');
      const proofContainer = document.getElementById('proofContainer');

      viewButtons.forEach(btn => {
        btn.addEventListener('click', function () {
          viewEmpName.textContent = this.dataset.employee;
          viewEmpID.textContent = this.dataset.employeeId;
          viewDepartment.textContent = this.dataset.department;
          viewPosition.textContent = this.dataset.position;
          viewLeaveType.textContent = this.dataset.leaveType;
          viewDate.textContent = this.dataset.startDate + ' - ' + this.dataset.endDate;
          viewReason.textContent = this.dataset.reason;
          viewStatus.textContent = this.dataset.status;
          viewProof.textContent = this.dataset.proofAvailable;

          // Handle proof display
          const leaveID = this.dataset.leaveId;
          const proofAvailable = this.dataset.proofAvailable;
          
          if (proofAvailable === 'Yes') {
            proofContainer.innerHTML = `
              <div class="mb-3">
                <a href="leaveReqPage.php?view_proof=${leaveID}" class="btn btn-primary" target="_blank">
                  <i class="fas fa-external-link-alt me-1"></i>View Proof Document
                </a>
              </div>
            `;
          } else {
            proofContainer.innerHTML = '<p><i class="fas fa-times-circle me-1"></i>No proof document uploaded.</p>';
          }
        });
      });

      // Clear decline form when modal is hidden
      document.getElementById('declineModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('declineReason').value = '';
      });
    });
  </script>
</body>
</html>