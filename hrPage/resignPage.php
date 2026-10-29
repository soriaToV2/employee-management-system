<?php
session_start();
require "../functions.php";
require "../connection.php";
redirectToLogin('HR');

// Initialize filter variables
$department_filter = isset($_GET['department']) ? $_GET['department'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_filter = isset($_GET['search']) ? $_GET['search'] : '';

// Build the base query - FIXED: Removed applications table join
$resignationsQuery = "
    SELECT 
        r.resignation_id,
        r.employee_id,
        r.reason,
        r.resignation_date,
        r.is_forwarded,
        r.resignation_status,
        r.status_reason,
        r.approved_by,
        e.candidate_id,
        e.employment_status,
        c.first_name,
        c.last_name,
        p.position_name,
        d.department_name,
        d.department_id
    FROM resignations r
    JOIN employees e ON r.employee_id = e.employee_id
    JOIN candidates c ON e.candidate_id = c.candidate_id
    JOIN positions p ON e.position_id = p.position_id
    JOIN departments d ON p.department_id = d.department_id
    WHERE r.is_forwarded = 1
";

// Add department filter if selected
if (!empty($department_filter) && $department_filter != 'all') {
    $department_filter = mysqli_real_escape_string($con, $department_filter);
    $resignationsQuery .= " AND d.department_id = '$department_filter'";
}

// Add status filter if selected
if (!empty($status_filter) && $status_filter != 'all') {
    $status_filter = mysqli_real_escape_string($con, $status_filter);
    $resignationsQuery .= " AND r.resignation_status = '$status_filter'";
}

// Add search filter if provided
if (!empty($search_filter)) {
    $search_filter = mysqli_real_escape_string($con, $search_filter);
    $resignationsQuery .= " AND r.employee_id LIKE '%$search_filter%'";
}

// Add ordering
$resignationsQuery .= " ORDER BY r.resignation_date DESC";

$resignationsResult = mysqli_query($con, $resignationsQuery);

// Get all departments for the filter dropdown
$departmentsQuery = "SELECT department_id, department_name FROM departments ORDER BY department_name";
$departmentsResult = mysqli_query($con, $departmentsQuery);

// Get counts for each status for the filter display
$count_query = "
    SELECT 
        resignation_status,
        COUNT(*) as count
    FROM resignations 
    WHERE is_forwarded = 1
    GROUP BY resignation_status
";
$count_result = mysqli_query($con, $count_query);
$status_counts = [
    'Pending' => 0,
    'Approved' => 0,
    'Rejected' => 0
];

if ($count_result) {
    while ($row = mysqli_fetch_assoc($count_result)) {
        $status_counts[$row['resignation_status']] = $row['count'];
    }
}

// Handle resignation approval
$successMsg = null;
$errorMsg = null;

if(isset($_POST["approveResignation"])){
    $resignationID = mysqli_real_escape_string($con, $_POST["resignation_id"]);
    $currentEmployeeID = $_SESSION['employeeID'];
    
    $approveQuery = "UPDATE resignations 
                     SET resignation_status = 'Approved', 
                         approved_by = '$currentEmployeeID'
                     WHERE resignation_id = '$resignationID'";
    
    mysqli_begin_transaction($con);
    if(mysqli_query($con, $approveQuery)){
        mysqli_commit($con);
        $successMsg = "Resignation approved successfully!";
        // Refresh the page to show updated status
        echo "<script>window.location.href = 'resignPage.php?success=" . urlencode($successMsg) . "';</script>";
        exit();
    } else {
        mysqli_rollback($con);
        $errorMsg = "Failed to approve resignation: " . mysqli_error($con);
    }
}

if(isset($_POST["rejectResignation"])){
    $resignationID = mysqli_real_escape_string($con, $_POST["resignation_id"]);
    $rejectReason = mysqli_real_escape_string($con, $_POST["reject_reason"]);
    $currentEmployeeID = $_SESSION['employeeID'];
    
    // Validate reject reason
    if(empty(trim($rejectReason))) {
        $errorMsg = "Please provide a reason for rejection.";
    } else {
        $rejectQuery = "UPDATE resignations 
                        SET resignation_status = 'Rejected', 
                            status_reason = '$rejectReason',
                            approved_by = '$currentEmployeeID'
                        WHERE resignation_id = '$resignationID'";
        
        mysqli_begin_transaction($con);
        if(mysqli_query($con, $rejectQuery)){
            mysqli_commit($con);
            $successMsg = "Resignation rejected successfully!";
            // Refresh the page to show updated status
            echo "<script>window.location.href = 'resignPage.php?success=" . urlencode($successMsg) . "';</script>";
            exit();
        } else {
            mysqli_rollback($con);
            $errorMsg = "Failed to reject resignation: " . mysqli_error($con);
        }
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
  <title>Company Name - HR Resignations</title>
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
          <h2 class="fw-bold">Resignation Management</h2>
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

        <?php if($errorMsg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($errorMsg); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
            <div>
              <i class="fas fa-filter me-2"></i>
              <h5 class="mb-0 d-inline">Filter Resignation Requests</h5>
            </div>
          </div>

          <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
              <!-- Department Filter -->
              <div class="col-md-3">
                <label for="department" class="form-label fw-semibold text-white">Department</label>
                <select name="department" id="department" class="form-select border-secondary">
                  <option value="all">All Departments</option>
                  <?php mysqli_data_seek($departmentsResult, 0); // reset pointer ?>
                  <?php while($dept = mysqli_fetch_assoc($departmentsResult)): ?>
                    <option value="<?php echo $dept['department_id']; ?>" 
                      <?php echo $department_filter == $dept['department_id'] ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($dept['department_name']); ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <!-- Status Filter -->
              <div class="col-md-3">
                <label for="status" class="form-label fw-semibold text-white">Resignation Status</label>
                <select name="status" id="status" class="form-select border-secondary">
                  <option value="all">All Statuses</option>
                  <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                  <option value="Approved" <?php echo $status_filter == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                  <option value="Rejected" <?php echo $status_filter == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
              </div>

              <!-- Employee ID Search -->
              <div class="col-md-4">
                <label for="search" class="form-label fw-semibold text-white">Employee ID</label>
                <input type="text" name="search" id="search" class="form-control border-secondary" 
                      placeholder="Search by Employee ID..." 
                      value="<?php echo htmlspecialchars($search_filter); ?>">
              </div>

              <!-- Filter Buttons -->
              <div class="col-md-2 d-grid gap-2">
                <button type="submit" class="btn btn-primary fw-semibold">
                  <i class="fas fa-filter me-1"></i> Apply
                </button>
                <?php if (!empty($department_filter) || !empty($status_filter) || !empty($search_filter)): ?>
                  <a href="resignPage.php" class="btn btn-outline-primary fw-semibold">
                    <i class="fas fa-times me-1"></i> Clear
                  </a>
                <?php endif; ?>
              </div>
            </form>

            <!-- Active Filters Display -->
            <?php if (!empty($department_filter) || !empty($status_filter) || !empty($search_filter)): ?>
              <div class="mt-0 pt-0">
                <small class="text-muted fw-bold">Active Filters:</small>

                <?php if (!empty($department_filter) && $department_filter != 'all'): 
                  $dept_name = '';
                  mysqli_data_seek($departmentsResult, 0);
                  while($dept = mysqli_fetch_assoc($departmentsResult)) {
                    if ($dept['department_id'] == $department_filter) {
                      $dept_name = $dept['department_name'];
                      break;
                    }
                  }
                ?>
                  <span class="badge bg-primary ms-2">Department: <?php echo htmlspecialchars($dept_name); ?></span>
                <?php endif; ?>

                <?php if (!empty($status_filter) && $status_filter != 'all'): ?>
                  <span class="badge 
                    <?php echo $status_filter == 'Pending' ? 'bg-warning text-dark' : 
                          ($status_filter == 'Approved' ? 'bg-success' : 'bg-danger'); ?> ms-2">
                    Status: <?php echo htmlspecialchars($status_filter); ?>
                  </span>
                <?php endif; ?>

                <?php if (!empty($search_filter)): ?>
                  <span class="badge bg-info text-dark ms-2">Employee ID: <?php echo htmlspecialchars($search_filter); ?></span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Resignation Table Card -->
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
            <div>
              <i class="fas fa-sign-out-alt me-2"></i>
              <h5 class="mb-0 d-inline">Employee Resignations</h5>
            </div>
            <div class="text-white">
              <small>
                <?php 
                $totalResignations = mysqli_num_rows($resignationsResult);
                echo "Showing: " . $totalResignations . " resignation" . ($totalResignations != 1 ? 's' : '');
                if (!empty($department_filter) && $department_filter != 'all') {
                  echo " • " . htmlspecialchars($dept_name ?? '');
                }
                if (!empty($status_filter) && $status_filter != 'all') {
                  echo " • " . htmlspecialchars($status_filter);
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
                    <th>Department</th>
                    <th>Position</th>
                    <th>Working Status</th>
                    <th>Resignation Date</th>
                    <th>Reason</th>
                    <th>Resignation Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  if(mysqli_num_rows($resignationsResult) > 0){
                    // Reset pointer to beginning of result set
                    mysqli_data_seek($resignationsResult, 0);
                    while($row = mysqli_fetch_assoc($resignationsResult)){
                      // Format resignation date
                      $resignationDate = new DateTime($row['resignation_date']);
                      $formattedDate = $resignationDate->format('M. d, Y');
                      
                      // Determine badge color based on employment status
                      $employmentBadge = '';
                      switch($row['employment_status']) {
                          case 'Full Time': $employmentBadge = 'bg-success'; break;
                          case 'Part Time': $employmentBadge = 'bg-warning'; break;
                          case 'Probationary': $employmentBadge = 'bg-info'; break;
                          case 'Intern': $employmentBadge = 'bg-secondary'; break;
                          default: $employmentBadge = 'bg-secondary';
                      }
                      
                      // Determine badge color based on resignation status
                      $resignationBadge = '';
                      switch($row['resignation_status']) {
                          case 'Pending': $resignationBadge = 'bg-warning text-dark'; break;
                          case 'Approved': $resignationBadge = 'bg-success'; break;
                          case 'Rejected': $resignationBadge = 'bg-danger'; break;
                          default: $resignationBadge = 'bg-secondary';
                      }
                      ?>
                      <tr>
                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['position_name']); ?></td>
                        <td><span class="badge <?php echo $employmentBadge; ?>"><?php echo htmlspecialchars($row['employment_status']); ?></span></td>
                        <td><?php echo $formattedDate; ?></td>
                        <td><?php echo htmlspecialchars($row['reason']); ?></td>
                        <td><span class="badge <?php echo $resignationBadge; ?>"><?php echo htmlspecialchars($row['resignation_status']); ?></span></td>
                        <td>
                          <button class="btn btn-outline-primary btn-sm review-btn"
                            data-resignation-id="<?php echo $row['resignation_id']; ?>"
                            data-employee="<?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>"
                            data-employee-id="<?php echo htmlspecialchars($row['employee_id']); ?>"
                            data-department="<?php echo htmlspecialchars($row['department_name']); ?>"
                            data-position="<?php echo htmlspecialchars($row['position_name']); ?>"
                            data-status="<?php echo htmlspecialchars($row['employment_status']); ?>"
                            data-resignation-date="<?php echo $formattedDate; ?>"
                            data-reason="<?php echo htmlspecialchars($row['reason']); ?>"
                            data-resignation-status="<?php echo htmlspecialchars($row['resignation_status']); ?>"
                            data-status-reason="<?php echo htmlspecialchars($row['status_reason']); ?>">
                            <i class="fas fa-eye me-1"></i> 
                            <?php echo $row['resignation_status'] == 'Pending' ? 'Review' : 'View'; ?>
                          </button>
                        </td>
                      </tr>
                      <?php
                    }
                  } else {
                    echo '<tr><td colspan="9" class="text-center py-4">';
                    if (!empty($department_filter) || !empty($status_filter) || !empty($search_filter)) {
                      echo 'No resignation requests found matching your filters.';
                    } else {
                      echo 'No forwarded resignation requests found.';
                    }
                    echo '</td></tr>';
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ===== REVIEW RESIGNATION MODAL ===== -->
        <div class="modal fade" id="reviewResignModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
              <form method="post" action="" id="resignationForm">
                <div class="modal-header">
                  <h5 class="modal-title"><i class="fas fa-user-check me-2"></i>Review Resignation Request</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="resignation_id" id="resignationId">
                  
                  <div class="row mb-3">
                    <div class="col-md-6">
                      <label class="fw-bold">Employee Name:</label>
                      <p id="reviewEmpName" class="mb-2"></p>
                    </div>
                    <div class="col-md-6">
                      <label class="fw-bold">Employee ID:</label>
                      <p id="reviewEmpID" class="mb-2"></p>
                    </div>
                  </div>
                  
                  <div class="row mb-3">
                    <div class="col-md-6">
                      <label class="fw-bold">Department:</label>
                      <p id="reviewDepartment" class="mb-2"></p>
                    </div>
                    <div class="col-md-6">
                      <label class="fw-bold">Position:</label>
                      <p id="reviewPosition" class="mb-2"></p>
                    </div>
                  </div>
                  
                  <div class="row mb-3">
                    <div class="col-md-6">
                      <label class="fw-bold">Working Status:</label>
                      <p id="reviewStatus" class="mb-2"></p>
                    </div>
                    <div class="col-md-6">
                      <label class="fw-bold">Resignation Date:</label>
                      <p id="reviewResignationDate" class="mb-2"></p>
                    </div>
                  </div>
                  
                  <div class="mb-3">
                    <label class="fw-bold">Reason for Resignation:</label>
                    <p id="reviewReason" class="mb-2 border rounded p-2"></p>
                  </div>
                  
                  <div class="mb-3" id="rejectReasonSection" style="display: none;">
                    <label for="rejectReason" class="form-label fw-bold">Reason for Rejection <span class="text-danger">*</span></label>
                    <textarea name="reject_reason" id="rejectReason" class="form-control" rows="3" placeholder="Enter reason for rejecting the resignation..." required></textarea>
                    <div class="invalid-feedback">Please provide a reason for rejection.</div>
                  </div>
                  
                  <div id="statusInfoSection" style="display: none;">
                    <div class="mb-3">
                      <label class="fw-bold">Status Reason:</label>
                      <p id="statusReason" class="mb-2 border rounded p-2"></p>
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="submit" name="approveResignation" class="btn btn-success" id="approveBtn">
                    <i class="fas fa-check me-1"></i> Approve Resignation
                  </button>
                  <button type="button" class="btn btn-warning" id="rejectBtn">
                    <i class="fas fa-times me-1"></i> Reject Resignation
                  </button>
                  <button type="submit" name="rejectResignation" class="btn btn-danger" id="confirmRejectBtn" style="display: none;">
                    <i class="fas fa-paper-plane me-1"></i> Confirm Reject
                  </button>
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Close
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const reviewButtons = document.querySelectorAll('.review-btn');
    const reviewModal = new bootstrap.Modal(document.getElementById('reviewResignModal'));
    
    const approveBtn = document.getElementById('approveBtn');
    const rejectBtn = document.getElementById('rejectBtn');
    const confirmRejectBtn = document.getElementById('confirmRejectBtn');
    const rejectReasonSection = document.getElementById('rejectReasonSection');
    const statusInfoSection = document.getElementById('statusInfoSection');
    const rejectReasonInput = document.getElementById('rejectReason');
    const resignationForm = document.getElementById('resignationForm');

    reviewButtons.forEach(btn => {
      btn.addEventListener('click', function() {
        const resignationStatus = this.dataset.resignationStatus;
        
        // Populate modal with data
        document.getElementById('resignationId').value = this.dataset.resignationId;
        document.getElementById('reviewEmpName').textContent = this.dataset.employee;
        document.getElementById('reviewEmpID').textContent = this.dataset.employeeId;
        document.getElementById('reviewDepartment').textContent = this.dataset.department;
        document.getElementById('reviewPosition').textContent = this.dataset.position;
        document.getElementById('reviewStatus').textContent = this.dataset.status;
        document.getElementById('reviewResignationDate').textContent = this.dataset.resignationDate;
        document.getElementById('reviewReason').textContent = this.dataset.reason;
        document.getElementById('statusReason').textContent = this.dataset.statusReason || 'No reason provided';

        // Show/hide sections based on resignation status
        if (resignationStatus === 'Pending') {
          approveBtn.style.display = 'inline-block';
          rejectBtn.style.display = 'inline-block';
          confirmRejectBtn.style.display = 'none';
          rejectReasonSection.style.display = 'none';
          statusInfoSection.style.display = 'none';
          approveBtn.disabled = false;
          rejectReasonInput.removeAttribute('required');
        } else {
          // For approved/rejected resignations, show view mode
          approveBtn.style.display = 'none';
          rejectBtn.style.display = 'none';
          confirmRejectBtn.style.display = 'none';
          rejectReasonSection.style.display = 'none';
          statusInfoSection.style.display = 'block';
          document.querySelector('.modal-title').innerHTML = '<i class="fas fa-eye me-2"></i>Resignation Details';
        }

        reviewModal.show();
      });
    });

    // Handle reject button click
    rejectBtn.addEventListener('click', function() {
      rejectReasonSection.style.display = 'block';
      confirmRejectBtn.style.display = 'inline-block';
      rejectBtn.style.display = 'none';
      approveBtn.disabled = true;
      rejectReasonInput.setAttribute('required', 'required');
    });

    // Validate reject reason before submission
    confirmRejectBtn.addEventListener('click', function(e) {
      if (!rejectReasonInput.value.trim()) {
        e.preventDefault();
        rejectReasonInput.classList.add('is-invalid');
      } else {
        rejectReasonInput.classList.remove('is-invalid');
      }
    });

    // Clear validation on input
    rejectReasonInput.addEventListener('input', function() {
      if (this.value.trim()) {
        this.classList.remove('is-invalid');
      }
    });

    // Reset modal when hidden
    document.getElementById('reviewResignModal').addEventListener('hidden.bs.modal', function() {
      rejectReasonSection.style.display = 'none';
      confirmRejectBtn.style.display = 'none';
      rejectBtn.style.display = 'inline-block';
      approveBtn.disabled = false;
      rejectReasonInput.value = '';
      rejectReasonInput.classList.remove('is-invalid');
      rejectReasonInput.removeAttribute('required');
      document.querySelector('.modal-title').innerHTML = '<i class="fas fa-user-check me-2"></i>Review Resignation Request';
    });

    // Prevent form submission if reject reason is required but empty
    resignationForm.addEventListener('submit', function(e) {
      if (confirmRejectBtn.style.display !== 'none' && !rejectReasonInput.value.trim()) {
        e.preventDefault();
        rejectReasonInput.classList.add('is-invalid');
      }
    });
  });
  </script>
</body>
</html>