<?php

session_start();
require "../functions.php";
require "../connection.php";
redirectToLogin('Manager');

$currentEmployeeID = $_SESSION['employeeID'];

// FIXED: Removed applications table join
$manager_dept_query = "
    SELECT p.department_id 
    FROM employees e
    JOIN positions p ON e.position_id = p.position_id
    WHERE e.employee_id = '$currentEmployeeID'
";
$manager_dept_result = mysqli_query($con, $manager_dept_query); 
$manager_department = null;
if ($manager_dept_result && mysqli_num_rows($manager_dept_result) > 0) {
    $manager_row = mysqli_fetch_assoc($manager_dept_result);
    $manager_department = $manager_row['department_id'];
}

// Handle forwarding resignation to HR
if (isset($_POST['forward_to_hr'])) {
    $resignation_id = mysqli_real_escape_string($con, $_POST['resignation_id']);
    
    $update_query = "UPDATE resignations SET is_forwarded = 1 WHERE resignation_id = '$resignation_id'";
    
    if (mysqli_query($con, $update_query)) {
        $success_message = "Resignation forwarded to HR successfully!";
    } else {
        $error_message = "Error forwarding resignation: " . mysqli_error($con);
    }
}

// Fetch pending resignations for the manager's department
if ($manager_department) {
    // FIXED: Removed applications table join
    $resignations_query = "
        SELECT r.*, 
               c.first_name, c.last_name, 
               p.position_name as position, 
               e.employment_status as working_status, 
               p.department_id
        FROM resignations r 
        JOIN employees e ON r.employee_id = e.employee_id 
        JOIN candidates c ON e.candidate_id = c.candidate_id
        JOIN positions p ON e.position_id = p.position_id
        WHERE r.resignation_status = 'Pending' 
        AND p.department_id = '$manager_department'
        AND r.is_forwarded = 0
        ORDER BY r.resignation_date DESC
    ";

    $resignations_result = mysqli_query($con, $resignations_query);
    $pending_resignations = [];
    if ($resignations_result && mysqli_num_rows($resignations_result) > 0) {
        while ($row = mysqli_fetch_assoc($resignations_result)) {
            $pending_resignations[] = $row;
        }
    }

    // Fetch processed resignations for the manager's department
    // FIXED: Removed applications table join
    $processed_query = "
        SELECT r.*, 
               c.first_name, c.last_name, 
               p.position_name as position, 
               e.employment_status as working_status, 
               p.department_id,
               approver_candidate.first_name as approver_first, 
               approver_candidate.last_name as approver_last
        FROM resignations r 
        JOIN employees e ON r.employee_id = e.employee_id 
        JOIN candidates c ON e.candidate_id = c.candidate_id
        JOIN positions p ON e.position_id = p.position_id
        LEFT JOIN employees approver ON r.approved_by = approver.employee_id
        LEFT JOIN candidates approver_candidate ON approver.candidate_id = approver_candidate.candidate_id
        WHERE r.resignation_status != 'Pending' 
        AND p.department_id = '$manager_department'
        ORDER BY r.resignation_date DESC
    ";

    $processed_result = mysqli_query($con, $processed_query);
    $processed_resignations = [];
    if ($processed_result && mysqli_num_rows($processed_result) > 0) {
        while ($row = mysqli_fetch_assoc($processed_result)) {
            $processed_resignations[] = $row;
        }
    }
} else {
    $pending_resignations = [];
    $processed_resignations = [];
    $error_message = "Unable to determine your department. Please contact administrator.";
}

// Get resignation details for modals
$resignation_details = null;
if (isset($_GET['view_resignation'])) {
    $resignation_id = mysqli_real_escape_string($con, $_GET['view_resignation']);
    // FIXED: Removed applications table join
    $detail_query = "
        SELECT r.*, 
               c.first_name, c.last_name, 
               p.position_name as position, 
               e.employment_status as working_status,
               approver_candidate.first_name as approver_first, 
               approver_candidate.last_name as approver_last
        FROM resignations r 
        JOIN employees e ON r.employee_id = e.employee_id 
        JOIN candidates c ON e.candidate_id = c.candidate_id
        JOIN positions p ON e.position_id = p.position_id
        LEFT JOIN employees approver ON r.approved_by = approver.employee_id
        LEFT JOIN candidates approver_candidate ON approver.candidate_id = approver_candidate.candidate_id
        WHERE r.resignation_id = '$resignation_id'
    ";
    $detail_result = mysqli_query($con, $detail_query);
    if ($detail_result && mysqli_num_rows($detail_result) > 0) {
        $resignation_details = mysqli_fetch_assoc($detail_result);
    }
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
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Pending Resignations Table -->
        <div class="card shadow-sm mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-user-minus me-2"></i>Pending Resignations</h5>
          </div>
          <div class="card-body">
            <?php if (empty($pending_resignations)): ?>
              <div class="alert alert-info mb-0">
                No pending resignations in your department.
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-bordered align-middle">
                  <thead>
                    <tr>
                      <th>Employee Name</th>
                      <th>ID</th>
                      <th>Position</th>
                      <th>Working Status</th>
                      <th>Reason</th>
                      <th>Resignation Date</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($pending_resignations as $resignation): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($resignation['first_name'] . ' ' . $resignation['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($resignation['employee_id']); ?></td>
                        <td><?php echo htmlspecialchars($resignation['position']); ?></td>
                        <td>
                          <span class="badge <?php echo $resignation['working_status'] == 'Full Time' ? 'bg-success' : 'bg-info text-dark'; ?>">
                            <?php echo htmlspecialchars($resignation['working_status']); ?>
                          </span>
                        </td>
                        <td><?php echo htmlspecialchars($resignation['reason']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($resignation['resignation_date'])); ?></td>
                        <td>
                          <button class="btn btn-outline-primary btn-sm review-btn" 
                                  data-resignation-id="<?php echo $resignation['resignation_id']; ?>"
                                  data-emp-name="<?php echo htmlspecialchars($resignation['first_name'] . ' ' . $resignation['last_name']); ?>"
                                  data-emp-id="<?php echo htmlspecialchars($resignation['employee_id']); ?>"
                                  data-position="<?php echo htmlspecialchars($resignation['position']); ?>"
                                  data-status="<?php echo htmlspecialchars($resignation['working_status']); ?>"
                                  data-reason="<?php echo htmlspecialchars($resignation['reason']); ?>"
                                  data-date="<?php echo date('M d, Y', strtotime($resignation['resignation_date'])); ?>">
                            Review
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Processed Resignations Table -->
        <div class="card shadow-sm">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Processed Resignations</h5>
          </div>
          <div class="card-body">
            <?php if (empty($processed_resignations)): ?>
              <div class="alert alert-info mb-0">
                No processed resignations in your department.
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-bordered align-middle">
                  <thead>
                    <tr>
                      <th>Employee Name</th>
                      <th>ID</th>
                      <th>Position</th>
                      <th>Working Status</th>
                      <th>Reason</th>
                      <th>Resignation Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($processed_resignations as $resignation): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($resignation['first_name'] . ' ' . $resignation['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($resignation['employee_id']); ?></td>
                        <td><?php echo htmlspecialchars($resignation['position']); ?></td>
                        <td>
                          <span class="badge <?php echo $resignation['working_status'] == 'Full Time' ? 'bg-success' : 'bg-info text-dark'; ?>">
                            <?php echo htmlspecialchars($resignation['working_status']); ?>
                          </span>
                        </td>
                        <td><?php echo htmlspecialchars($resignation['reason']); ?></td>
                        <td>
                          <span class="badge <?php 
                            echo $resignation['resignation_status'] == 'Approved' ? 'bg-success' : 
                                 ($resignation['resignation_status'] == 'Rejected' ? 'bg-danger' : 'bg-warning text-dark'); 
                          ?>">
                            <?php echo htmlspecialchars($resignation['resignation_status']); ?>
                          </span>
                        </td>
                        <td>
                          <button class="btn btn-outline-primary btn-sm view-btn" 
                                  data-resignation-id="<?php echo $resignation['resignation_id']; ?>"
                                  data-emp-name="<?php echo htmlspecialchars($resignation['first_name'] . ' ' . $resignation['last_name']); ?>"
                                  data-emp-id="<?php echo htmlspecialchars($resignation['employee_id']); ?>"
                                  data-position="<?php echo htmlspecialchars($resignation['position']); ?>"
                                  data-status="<?php echo htmlspecialchars($resignation['working_status']); ?>"
                                  data-reason="<?php echo htmlspecialchars($resignation['reason']); ?>"
                                  data-resign-status="<?php echo htmlspecialchars($resignation['resignation_status']); ?>"
                                  data-processed-by="<?php echo htmlspecialchars($resignation['approver_first'] . ' ' . $resignation['approver_last']); ?>"
                                  data-status-reason="<?php echo htmlspecialchars($resignation['status_reason']); ?>"
                                  data-date="<?php echo date('M d, Y', strtotime($resignation['resignation_date'])); ?>">
                            View
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== REVIEW RESIGNATION MODAL ===== -->
  <div class="modal fade" id="reviewResignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <form method="POST" action="">
          <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-user-check me-2"></i>Review Resignation Request</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="resignation_id" id="reviewResignationId">
            <p><strong>Employee Name:</strong> <span id="reviewEmpName"></span></p>
            <p><strong>Employee ID:</strong> <span id="reviewEmpID"></span></p>
            <p><strong>Position:</strong> <span id="reviewPosition"></span></p>
            <p><strong>Reason for Resignation:</strong> <span id="reviewReason"></span></p>
            <p><strong>Working Status:</strong> <span id="reviewStatus"></span></p>
            <p><strong>Resignation Date:</strong> <span id="reviewDate"></span></p>
          </div>
          <div class="modal-footer">
            <button type="submit" name="forward_to_hr" class="btn btn-success">
              <i class="fas fa-paper-plane me-1"></i> Forward to HR
            </button>
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
              <i class="fas fa-times me-1"></i> Close
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ===== VIEW PROCESSED RESIGNATION MODAL ===== -->
  <div class="modal fade" id="viewResignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-user-check me-2"></i>Processed Resignation Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p><strong>Employee Name:</strong> <span id="viewEmpName"></span></p>
          <p><strong>Employee ID:</strong> <span id="viewEmpID"></span></p>
          <p><strong>Position:</strong> <span id="viewPosition"></span></p>
          <p><strong>Working Status:</strong> <span id="viewStatus"></span></p>
          <p><strong>Reason for Resignation:</strong> <span id="viewReason"></span></p>
          <p><strong>Resignation Status:</strong> <span id="viewResignStatus"></span></p>
          <p><strong>Processed By:</strong> <span id="viewProcessedBy"></span></p>
          <p><strong>Resignation Date:</strong> <span id="viewDate"></span></p>
          <p><strong>Status Reason:</strong> <span id="viewStatusReason">No additional notes.</span></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i> Close
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    // ===== REVIEW RESIGNATION =====
    const reviewButtons = document.querySelectorAll('.review-btn');
    
    reviewButtons.forEach(btn => {
      btn.addEventListener('click', function() {
        const resignationId = this.getAttribute('data-resignation-id');
        const empName = this.getAttribute('data-emp-name');
        const empID = this.getAttribute('data-emp-id');
        const position = this.getAttribute('data-position');
        const status = this.getAttribute('data-status');
        const reason = this.getAttribute('data-reason');
        const date = this.getAttribute('data-date');

        // Populate review modal
        document.getElementById('reviewResignationId').value = resignationId;
        document.getElementById('reviewEmpName').textContent = empName;
        document.getElementById('reviewEmpID').textContent = empID;
        document.getElementById('reviewPosition').textContent = position;
        document.getElementById('reviewStatus').textContent = status;
        document.getElementById('reviewReason').textContent = reason;
        document.getElementById('reviewDate').textContent = date;

        const reviewModal = new bootstrap.Modal(document.getElementById('reviewResignModal'));
        reviewModal.show();
      });
    });

    // ===== VIEW PROCESSED RESIGNATION =====
    const viewButtons = document.querySelectorAll('.view-btn');
    
    viewButtons.forEach(btn => {
      btn.addEventListener('click', function() {
        const empName = this.getAttribute('data-emp-name');
        const empID = this.getAttribute('data-emp-id');
        const position = this.getAttribute('data-position');
        const status = this.getAttribute('data-status');
        const reason = this.getAttribute('data-reason');
        const resignStatus = this.getAttribute('data-resign-status');
        const processedBy = this.getAttribute('data-processed-by');
        const statusReason = this.getAttribute('data-status-reason');
        const date = this.getAttribute('data-date');

        // Populate view modal
        document.getElementById('viewEmpName').textContent = empName;
        document.getElementById('viewEmpID').textContent = empID;
        document.getElementById('viewPosition').textContent = position;
        document.getElementById('viewStatus').textContent = status;
        document.getElementById('viewReason').textContent = reason;
        document.getElementById('viewResignStatus').textContent = resignStatus;
        document.getElementById('viewProcessedBy').textContent = processedBy || 'N/A';
        document.getElementById('viewStatusReason').textContent = statusReason || 'No additional notes.';
        document.getElementById('viewDate').textContent = date;

        const viewModal = new bootstrap.Modal(document.getElementById('viewResignModal'));
        viewModal.show();
      });
    });
  });
  </script>
</body>
</html>