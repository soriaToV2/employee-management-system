<?php
session_start();
require "../functions.php";
require "../connection.php";
redirectToLogin('Employee');

if($_SESSION['employment_status'] === 'Probationary'){
    header('Location: index.php');
    exit();
}

$currentEmployeeID = $_SESSION['employeeID'];

$err = null;
$success = null;

// Handle file viewing
if (isset($_GET['view_proof'])) {
    $leaveId = mysqli_real_escape_string($con, $_GET['view_proof']);
    
    $query = "SELECT proof FROM leave_requests WHERE leave_id = '$leaveId' AND employee_id = '$currentEmployeeID'";
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
    header("Location: leavePage.php?error=proof_not_found");
    exit();
}

if (isset($_POST['submit'])) {
    $leaveType = $_POST['leaveType'];
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    $leaveReason = $_POST['leaveReason'];
    
    // File upload handling
    $proofData = null;
    
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        $fileType = $_FILES['proof']['type'];
        
        // Validate file type
        if (in_array($fileType, $allowedTypes)) {
            // Validate file size (max 5MB)
            if ($_FILES['proof']['size'] <= 5 * 1024 * 1024) {
                $proofData = mysqli_real_escape_string($con, file_get_contents($_FILES['proof']['tmp_name']));
            } else {
                $err = "File size too large. Maximum size is 5MB.";
            }
        } else {
            $err = "Invalid file type. Only JPG, JPEG, PNG, and PDF files are allowed.";
        }
    }
    
    if (!$err) {
        if ($proofData) {
            $requestLeaveQuery = "INSERT INTO leave_requests (employee_id, leave_type_id, start_date, end_date, reason, proof, request_date) 
                                VALUES ('$currentEmployeeID', '$leaveType', '$startDate', '$endDate', '$leaveReason', '$proofData', NOW())";
        } else {
            $requestLeaveQuery = "INSERT INTO leave_requests (employee_id, leave_type_id, start_date, end_date, reason, request_date) 
                                VALUES ('$currentEmployeeID', '$leaveType', '$startDate', '$endDate', '$leaveReason', NOW())";
        }
        
        mysqli_begin_transaction($con);
        if ($requestLeaveResult = mysqli_query($con, $requestLeaveQuery)) {
            mysqli_commit($con);
            header("Location: leavePage.php?success=1");
            exit();
        } else {
            mysqli_rollback($con);
            $err = "Error submitting leave request. Please try again.";
        }
    }
}

if (isset($_POST['editUpdate'])) {
    $leaveType = $_POST['editLeaveType'];
    $startDate = $_POST['editStartDate'];
    $endDate = $_POST['editEndDate'];
    $leaveReason = $_POST['editReason'];
    $leaveId = $_POST['leave_id'];
    
    // File upload handling for edit
    $proofUpdate = "";
    $proofData = null;
    
    if (isset($_FILES['editProof']) && $_FILES['editProof']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        $fileType = $_FILES['editProof']['type'];
        
        // Validate file type
        if (in_array($fileType, $allowedTypes)) {
            // Validate file size (max 5MB)
            if ($_FILES['editProof']['size'] <= 5 * 1024 * 1024) {
                $proofData = mysqli_real_escape_string($con, file_get_contents($_FILES['editProof']['tmp_name']));
                $proofUpdate = ", proof = '$proofData'";
            } else {
                $err = "File size too large. Maximum size is 5MB.";
            }
        } else {
            $err = "Invalid file type. Only JPG, JPEG, PNG, and PDF files are allowed.";
        }
    }
    
    if (!$err) {
        $editQuery = "UPDATE leave_requests SET 
                     leave_type_id = '$leaveType', 
                     start_date = '$startDate', 
                     end_date = '$endDate', 
                     reason = '$leaveReason'
                     $proofUpdate
                     WHERE leave_id = '$leaveId' AND employee_id = '$currentEmployeeID' AND status IN ('Pending', 'In Process')";
        
        mysqli_begin_transaction($con);
        if (mysqli_query($con, $editQuery)) {
            mysqli_commit($con);
            header("Location: leavePage.php?success=edited");
            exit();
        } else {
            mysqli_rollback($con);
            $err = "Error updating leave request.";
        }
    }
}

if (isset($_POST['cancelLeave'])) {
    $leaveId = $_POST['leave_id'];
    
    $cancelQuery = "UPDATE leave_requests SET status = 'Cancelled' WHERE leave_id = '$leaveId' AND employee_id = '$currentEmployeeID' AND status IN ('Pending', 'In Process')";
    
    mysqli_begin_transaction($con);
    if (mysqli_query($con, $cancelQuery)) {
        mysqli_commit($con);
        header("Location: leavePage.php?success=cancelled");
        exit();
    } else {
        mysqli_rollback($con);
        $err = "Error cancelling leave request.";
    }
}

// Handle success messages
if (isset($_GET['success'])) {
    switch($_GET['success']) {
        case '1':
            $success = "Leave request submitted successfully!";
            break;
        case 'cancelled':
            $success = "Leave request cancelled successfully!";
            break;
        case 'edited':
            $success = "Leave request updated successfully!";
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Leave Request | Employee Portal</title>
  <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../css/Global.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body>
  <div class="container-fluid">
    <div class="row flex-nowrap">
      
      <?php include 'nav.php'; ?>

      <!-- Main Content -->
      <div class="col py-4 px-5">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
          <h2 class="fw-bold">Leave Request</h2>
          <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width:45px; height:45px;">EN</div>
            <div>
              <h6 class="mb-0"><?php echo $_SESSION['employeeName']; ?></h6>
              <small><?php echo $_SESSION['employeePosition']; ?></small>
            </div>
          </div>
        </div>

        <!-- Success Message Alert -->
        <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Success!</strong> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Error Message Alert -->
        <?php if (!empty($err)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Error!</strong> <?php echo $err; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Leave Overview -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="fw-semibold">Leave Overview</h4>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">
            <i class="fas fa-plus me-2"></i>Apply for Leave
          </button>
        </div>

        <!-- Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Recent Leave Requests</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Dates</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Proof</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recentRequestQuery = "SELECT lr.*, lt.leave_name 
                                                FROM leave_requests lr 
                                                JOIN leave_types lt ON lr.leave_type_id = lt.leave_type_id 
                                                WHERE lr.employee_id = '$currentEmployeeID' 
                                                ORDER BY lr.request_date DESC";
                            $recentRequestResult = mysqli_query($con, $recentRequestQuery);
                            
                            if (mysqli_num_rows($recentRequestResult) > 0) {
                                while ($row = mysqli_fetch_assoc($recentRequestResult)) {
                                    // Calculate duration
                                    $startDate = new DateTime($row['start_date']);
                                    $endDate = new DateTime($row['end_date']);
                                    $duration = $startDate->diff($endDate)->days + 1;
                                    
                                    // Format dates for display
                                    $formattedStart = $startDate->format('M. d, Y');
                                    $formattedEnd = $endDate->format('M. d, Y');
                                    $submittedDate = date('M. d, Y', strtotime($row['request_date']));
                                    
                                    // Status badge color
                                    $statusClass = '';
                                    switch($row['status']) {
                                        case 'Approved': $statusClass = 'bg-success'; break;
                                        case 'Rejected': $statusClass = 'bg-danger'; break;
                                        case 'In Process': $statusClass = 'bg-info'; break;
                                        default: $statusClass = 'bg-warning text-dark'; break;
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $row['leave_name']; ?></td>
                                        <td><?php echo $formattedStart . ' – ' . $formattedEnd; ?></td>
                                        <td><?php echo $duration . ' day' . ($duration > 1 ? 's' : ''); ?></td>
                                        <td><span class="badge <?php echo $statusClass; ?>"><?php echo $row['status']; ?></span></td>
                                        <td>
                                            <?php if ($row['proof']): ?>
                                                <a href="leavePage.php?view_proof=<?php echo $row['leave_id']; ?>" 
                                                   class="btn btn-sm btn-outline-info" 
                                                   target="_blank">
                                                    <i class="fas fa-eye me-1"></i>View Proof
                                                </a>
                                            <?php else: ?>
                                                <span>No proof</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $submittedDate; ?></td>
                                        <td>
                                            <?php if ($row['status'] === 'Pending' || $row['status'] === 'In Process'): ?>
                                                <button class="btn btn-sm btn-outline-primary edit-leave-btn" 
                                                        data-leave-id="<?php echo $row['leave_id']; ?>"
                                                        data-leave-type="<?php echo $row['leave_type_id']; ?>"
                                                        data-start-date="<?php echo $row['start_date']; ?>"
                                                        data-end-date="<?php echo $row['end_date']; ?>"
                                                        data-reason="<?php echo $row['reason']; ?>">
                                                    Edit
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger cancel-leave-btn" 
                                                        data-leave-id="<?php echo $row['leave_id']; ?>">
                                                    Cancel
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-primary view-leave-btn" 
                                                        data-type="<?php echo $row['leave_name']; ?>"
                                                        data-dates="<?php echo $formattedStart . ' – ' . $formattedEnd; ?>"
                                                        data-status="<?php echo $row['status']; ?>"
                                                        data-reason="<?php echo $row['reason']; ?>"
                                                        data-submitted="<?php echo $submittedDate; ?>"
                                                        data-processedby="<?php echo $row['approved_by']; ?>"
                                                        data-statusReason="<?php echo $row['status_reason']; ?>"
                                                        data-proof="<?php echo $row['proof'] ? 'Yes' : 'No'; ?>">
                                                    View
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="7" class="text-center">No leave requests found.</td></tr>';
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

  <!-- Apply Leave Modal -->
  <div class="modal fade" id="applyLeaveModal" tabindex="-1" aria-labelledby="applyLeaveLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="post" action="leavePage.php" id="leaveForm" enctype="multipart/form-data">
          <div class="modal-header">
            <h5 class="modal-title" id="applyLeaveLabel">Apply for Leave</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">            
            <div class="mb-3">
              <label for="leaveType" class="form-label">Select Leave Type</label>
              <select id="leaveType" class="form-select" name="leaveType" required>
                <option value="">-- Choose Leave Type --</option>
                <?php 
                $leaveTypeQuery = "Select * from leave_types";
                $leaveTypeQueryResult = mysqli_query($con, $leaveTypeQuery);

                while ($row = mysqli_fetch_assoc($leaveTypeQueryResult)) {
                  ?>
                    <option value="<?php echo $row['leave_type_id'] ;?>"><?php echo $row['leave_name'] ;?></option>
                  <?php
                }
                ?>
              </select>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="startDate" class="form-label">Start Date</label>
                <input type="date" id="startDate" class="form-control" name="startDate" required>
              </div>
              <div class="col-md-6 mb-3">
                <label for="endDate" class="form-label">End Date</label>
                <input type="date" id="endDate" class="form-control" name="endDate" required>
              </div>
            </div>

            <div class="mb-3">
              <label for="reason" class="form-label">Reason for Leave</label>
              <textarea id="reason" name="leaveReason" class="form-control" rows="3" placeholder="Write your reason here..." required></textarea>
            </div>

            <div class="mb-3">
              <label for="proof" class="form-label">Upload Proof (Optional)</label>
              <input type="file" id="proof" name="proof" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
              <div class="form-text">Accepted formats: JPG, JPEG, PNG, PDF (Max: 5MB)</div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="submit" name="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Submit Leave</button>
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-ban"></i> Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Leave Modal -->
  <div class="modal fade" id="editLeaveModal" tabindex="-1" aria-labelledby="editLeaveLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="leavePage.php" id="editLeaveForm" enctype="multipart/form-data">
                <input type="hidden" name="leave_id" id="editLeaveId">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLeaveLabel"><i class="fas fa-edit me-2"></i>Edit Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editLeaveType" class="form-label">Leave Type</label>
                        <select id="editLeaveType" name="editLeaveType" class="form-select" required>
                            <option value="">-- Choose Leave Type --</option>
                            <?php 
                            $leaveTypeQuery = "SELECT * FROM leave_types";
                            $leaveTypeResult = mysqli_query($con, $leaveTypeQuery);
                            while ($type = mysqli_fetch_assoc($leaveTypeResult)) {
                                echo '<option value="' . $type['leave_type_id'] . '">' . $type['leave_name'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editStartDate" class="form-label">Start Date</label>
                            <input type="date" name="editStartDate" id="editStartDate" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editEndDate" class="form-label">End Date</label>
                            <input type="date" name="editEndDate" id="editEndDate" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="editReason" class="form-label">Reason for Leave</label>
                        <textarea id="editReason" name="editReason" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="editProof" class="form-label">Update Proof (Optional)</label>
                        <input type="file" id="editProof" name="editProof" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        <div class="form-text" id="currentProofText">Leave empty to keep current proof</div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="editUpdate" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i> Update Leave
                    </button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                        <i class="fa-solid fa-ban me-1"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
  </div>

  <!-- Cancel Leave Modal -->
  <div class="modal fade" id="cancelLeaveModal" tabindex="-1" aria-labelledby="cancelLeaveLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="leavePage.php">
                <input type="hidden" name="leave_id" id="cancelLeaveId">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelLeaveLabel"><i class="fas fa-times me-2"></i>Cancel Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this leave request? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="cancelLeave" class="btn btn-danger">
                        <i class="fas fa-times me-1"></i> Yes, Cancel
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-arrow-left me-1"></i> No, Keep It
                    </button>
                </div>
            </form>
        </div>
    </div>
  </div>

  <!-- View Leave Details Modal -->
  <div class="modal fade" id="viewLeaveModal" tabindex="-1" aria-labelledby="viewLeaveLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewLeaveLabel"><i class="fas fa-eye me-2"></i>Leave Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <p><strong>Leave Type:</strong> <span id="viewLeaveType"></span></p>
              <p><strong>Dates:</strong> <span id="viewLeaveDates"></span></p>
              <p><strong>Status:</strong> <span id="viewLeaveStatus"></span></p>
            </div>
            <div class="col-md-6">
              <p><strong>Submitted On:</strong> <span id="viewLeaveSubmitted"></span></p>
              <p><strong>Processed By:</strong> <span id="viewLeaveProcessedBy"></span></p>
              <p><strong>Proof Uploaded:</strong> <span id="viewLeaveProof"></span></p>
            </div>
          </div>
          <div class="row">
            <div class="col-12">
              <p><strong>Reason:</strong></p>
              <div class="border rounded p-3">
                <span id="viewLeaveReason"></span>
              </div>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-12">
              <p><strong>Status Notes:</strong> <span id="viewLeaveNotes"></span></p>
            </div>
          </div>
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
      // Edit Leave Request
      const editButtons = document.querySelectorAll('.edit-leave-btn');
      editButtons.forEach(button => {
          button.addEventListener('click', function() {
              const leaveId = this.getAttribute('data-leave-id');
              const leaveType = this.getAttribute('data-leave-type');
              const startDate = this.getAttribute('data-start-date');
              const endDate = this.getAttribute('data-end-date');
              const reason = this.getAttribute('data-reason');

              document.getElementById('editLeaveId').value = leaveId;
              document.getElementById('editLeaveType').value = leaveType;
              document.getElementById('editStartDate').value = startDate;
              document.getElementById('editEndDate').value = endDate;
              document.getElementById('editReason').value = reason;

              const modal = new bootstrap.Modal(document.getElementById('editLeaveModal'));
              modal.show();
          });
      });

      // Cancel Leave Request
      const cancelButtons = document.querySelectorAll('.cancel-leave-btn');
      cancelButtons.forEach(button => {
          button.addEventListener('click', function() {
              const leaveId = this.getAttribute('data-leave-id');
              document.getElementById('cancelLeaveId').value = leaveId;
              
              const modal = new bootstrap.Modal(document.getElementById('cancelLeaveModal'));
              modal.show();
          });
      });

      // View Leave Details
      const viewLeaveButtons = document.querySelectorAll('.view-leave-btn');
      viewLeaveButtons.forEach(button => {
          button.addEventListener('click', function() {
              const type = this.getAttribute('data-type');
              const dates = this.getAttribute('data-dates');
              const status = this.getAttribute('data-status');
              const reason = this.getAttribute('data-reason');
              const submitted = this.getAttribute('data-submitted');
              const processedBy = this.getAttribute('data-processedby');
              const statusReason = this.getAttribute('data-statusReason');
              const proof = this.getAttribute('data-proof');

              document.getElementById('viewLeaveType').textContent = type;
              document.getElementById('viewLeaveDates').textContent = dates;
              document.getElementById('viewLeaveStatus').textContent = status;
              document.getElementById('viewLeaveReason').textContent = reason;
              document.getElementById('viewLeaveSubmitted').textContent = submitted;
              document.getElementById('viewLeaveProcessedBy').textContent = processedBy || 'N/A';
              document.getElementById('viewLeaveNotes').textContent = statusReason || 'N/A';
              document.getElementById('viewLeaveProof').textContent = proof;

              const modal = new bootstrap.Modal(document.getElementById('viewLeaveModal'));
              modal.show();
          });
      });

      // File validation
      function validateFile(input) {
          const file = input.files[0];
          if (file) {
              const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
              const maxSize = 5 * 1024 * 1024; // 5MB
              
              if (!allowedTypes.includes(file.type)) {
                  alert('Invalid file type. Only JPG, JPEG, PNG, and PDF files are allowed.');
                  input.value = '';
                  return false;
              }
              
              if (file.size > maxSize) {
                  alert('File size too large. Maximum size is 5MB.');
                  input.value = '';
                  return false;
              }
          }
          return true;
      }

      document.getElementById('proof').addEventListener('change', function() {
          validateFile(this);
      });

      document.getElementById('editProof').addEventListener('change', function() {
          validateFile(this);
      });

      // Form validation for dates
      document.getElementById('leaveForm').addEventListener('submit', function(e) {
          const startDate = new Date(document.getElementById('startDate').value);
          const endDate = new Date(document.getElementById('endDate').value);
          
          if (startDate > endDate) {
              e.preventDefault();
              alert('Error: End date cannot be before start date.');
              return false;
          }
      });

      document.getElementById('editLeaveForm').addEventListener('submit', function(e) {
          const startDate = new Date(document.getElementById('editStartDate').value);
          const endDate = new Date(document.getElementById('editEndDate').value);
          
          if (startDate > endDate) {
              e.preventDefault();
              alert('Error: End date cannot be before start date.');
              return false;
          }
      });

      // Remove success parameter from URL without refreshing
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('success')) {
          const newUrl = window.location.pathname;
          window.history.replaceState({}, document.title, newUrl);
      }
  });
  </script>
</body>
</html>