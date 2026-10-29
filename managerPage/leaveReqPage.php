<?php
session_start();
require "../functions.php";
require "../connection.php";
redirectToLogin('Manager');

$currentEmployeeID = $_SESSION['employeeID'];

// Query para malaman anong department ng manager
$managerDeptQuery = "SELECT p.department_id 
                     FROM employees e
                     JOIN positions p ON e.position_id = p.position_id
                     WHERE e.employee_id = '$currentEmployeeID'";
$managerDeptResult = mysqli_query($con, $managerDeptQuery);

if ($managerDeptResult && mysqli_num_rows($managerDeptResult) > 0) {
    $managerDept = mysqli_fetch_assoc($managerDeptResult);
    $managerDeptId = $managerDept['department_id'];
}

// manipulating the records to be displayed sa leave request table
$employeeLeavesQuery = "SELECT * FROM employee_leave_info 
                       WHERE employee_id != '$currentEmployeeID' 
                       AND leave_status = 'Pending' 
                       AND department_id = '$managerDeptId'";
$leaveResult = mysqli_query($con, $employeeLeavesQuery);

$declineErr = null;
$approveErr = null;
$successMsg = null;

// Handle file viewing for manager
if (isset($_GET['view_proof'])) {
    $leaveId = mysqli_real_escape_string($con, $_GET['view_proof']);
    
    // Get the proof from leave_requests table
    $query = "SELECT lr.proof 
              FROM leave_requests lr
              JOIN employee_leave_info eli ON lr.leave_id = eli.leave_id
              WHERE lr.leave_id = '$leaveId' AND eli.department_id = '$managerDeptId'";
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
    $declineReason = $_POST["declineReason"];

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
  <title>Manager Dashboard | Company Name</title>
  <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../css/Global.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body>
  <div class="container-fluid">
    <div class="row flex-nowrap">
      
      <!-- Sidebar -->
      <?php include 'nav.php'; ?>

      <!-- Main Content -->
      <div class="col py-4 px-5">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
          <h2 class="fw-bold">Manager Dashboard</h2>
          <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width:45px; height:45px;">MJ</div>
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

        <!-- Leave Requests Table -->
        <div class="card shadow-sm">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Pending Leave Requests</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered align-middle">
                <thead>
                  <tr>
                    <th>Employee Name</th>
                    <th>ID</th>
                    <th>Position</th>
                    <th>Department</th>
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
                          <td><?php echo $row['employee_name'] ;?></td>
                          <td><?php echo $row['employee_id'] ;?></td>
                          <td><?php echo $row['position_name'] ;?></td>
                          <td><?php echo $row['department_name'] ;?></td>
                          <td><?php echo $row['leave_type'] ;?></td>
                          <td><?php echo $formattedStart . ' - ' . $formattedEnd ;?></td>
                          <td><span class="badge bg-warning"><?php echo $row['leave_status'] ;?></span></td>
                          <td>
                            <a href="#" class="btn btn-success btn-sm approve-btn">Approve</a>
                            <a href="#" class="btn btn-danger btn-sm decline-btn">Decline</a>
                            <a href="#" class="btn btn-outline-primary btn-sm view-btn">View</a>
                          </td>
                          <td style="display: none;"><?php echo $row['reason'] ;?></td>
                          <td style="display: none;"><?php echo $row['status_reason'] ;?></td>
                          <td style="display: none;"><?php echo $row['leave_id']; ?></td>
                          <td style="display: none;"><?php echo $hasProof ? 'Yes' : 'No'; ?></td>
                        </tr>
                        <?php
                      }
                    } else {
                      echo '<tr><td colspan="9" class="text-center">No pending leave requests found.</td></tr>';
                    }
                    ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        
        <!-- Approve Leave Modal -->
        <div class="modal fade" id="approveLeaveModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                <form method="post" action="leaveReqPage.php" id="approveLeaveForm">
                    <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Approve Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                    <p>Are you sure you want to approve the leave request for <strong id="approveEmpName"></strong> (ID: <span id="approveEmpID"></span>)?</p>
                    <input type="hidden" name="leaveID" id="approveLeaveID">
                    </div>
                    <div class="modal-footer">
                    <button type="submit" name="approveSubmit" class="btn btn-success"><i class="fas fa-check me-1"></i> Approve</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Cancel</button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- Decline Leave Modal -->
        <div class="modal fade" id="declineLeaveModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                <form method="post" action="leaveReqPage.php" id="declineLeaveForm">
                    <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-ban me-2"></i>Decline Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                    <p>Are you sure you want to decline the leave request for <strong id="declineEmpName"></strong> (ID: <span id="declineEmpID"></span>)?</p>
                    <div class="mb-3">
                        <input type="hidden" name="leaveID" id="declineLeaveID">
                        <label for="declineReason" class="form-label">Reason for Decline</label>
                        <textarea name="declineReason" class="form-control" id="declineReason" rows="3" placeholder="Enter reason..." required></textarea>
                    </div>
                    </div>
                    <div class="modal-footer">
                    <button type="submit" name="declineSubmit" class="btn btn-success"><i class="fas fa-paper-plane me-1"></i> Submit</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Cancel</button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- View Leave Modal -->
        <div class="modal fade" id="viewLeaveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Leave Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                      <label class="fw-bold">Position:</label>
                      <p id="viewPosition" class="mb-2"></p>
                    </div>
                    <div class="mb-3">
                      <label class="fw-bold">Department:</label>
                      <p id="viewDepartment" class="mb-2"></p>
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
                      <label class="fw-bold">Proof Uploaded:</label>
                      <p id="viewProof" class="mb-2"></p>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-12">
                    <div class="mb-3">
                      <label class="fw-bold">Leave Reason:</label>
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
                <div class="row">
                  <div class="col-12">
                    <div class="mb-3">
                      <label class="fw-bold">Notes / Comments:</label>
                      <p id="viewNotes" class="mb-2">No additional notes.</p>
                    </div>
                  </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Close</button>
            </div>
            </div>
        </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== APPROVE LEAVE MODAL =====
    const approveButtons = document.querySelectorAll('.approve-btn');

    approveButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();

            const row = btn.closest('tr');
            const empName = row.cells[0].textContent;
            const empID = row.cells[1].textContent;
            const leaveID = row.cells[10].textContent; // Updated index (was 11, now 10)

            document.getElementById('approveEmpName').textContent = empName;
            document.getElementById('approveEmpID').textContent = empID;
            document.getElementById('approveLeaveID').value = leaveID;

            const approveModal = new bootstrap.Modal(document.getElementById('approveLeaveModal'));
            approveModal.show();
        });
    });

    // ===== DECLINE LEAVE MODAL =====
    const declineButtons = document.querySelectorAll('.decline-btn');

    declineButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();

            const row = btn.closest('tr');
            const empName = row.cells[0].textContent;
            const empID = row.cells[1].textContent;
            const leaveID = row.cells[10].textContent; // Updated index (was 11, now 10)

            document.getElementById('declineEmpName').textContent = empName;
            document.getElementById('declineEmpID').textContent = empID;
            document.getElementById('declineLeaveID').value = leaveID;

            const declineModal = new bootstrap.Modal(document.getElementById('declineLeaveModal'));
            declineModal.show();
        });
    });

    // ===== VIEW LEAVE MODAL =====
    const viewButtons = document.querySelectorAll('.view-btn');

    viewButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();

            const row = btn.closest('tr');
            const empName = row.cells[0].textContent;
            const empID = row.cells[1].textContent;
            const position = row.cells[2].textContent;
            const department = row.cells[3].textContent;
            const leaveType = row.cells[4].textContent;
            const date = row.cells[5].textContent;
            const status = row.cells[6].textContent; // Updated index (was 7, now 6)
            const reason = row.cells[8].textContent; // Updated index (was 9, now 8)
            const comments = row.cells[9].textContent; // Updated index (was 10, now 9)
            const leaveID = row.cells[10].textContent; // Updated index (was 11, now 10)
            const proofAvailable = row.cells[11].textContent; // Updated index (was 12, now 11)

            document.getElementById('viewEmpName').textContent = empName;
            document.getElementById('viewEmpID').textContent = empID;
            document.getElementById('viewPosition').textContent = position;
            document.getElementById('viewDepartment').textContent = department;
            document.getElementById('viewLeaveType').textContent = leaveType;
            document.getElementById('viewReason').textContent = reason;
            document.getElementById('viewDate').textContent = date;
            document.getElementById('viewStatus').textContent = status;
            document.getElementById('viewProof').textContent = proofAvailable;

            document.getElementById('viewNotes').textContent = comments ? comments : 'No Comments';

            // Handle proof display
            const proofContainer = document.getElementById('proofContainer');
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

            const viewModal = new bootstrap.Modal(document.getElementById('viewLeaveModal'));
            viewModal.show();
        });
    });

});
</script>
</html>