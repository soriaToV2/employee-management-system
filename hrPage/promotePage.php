<?php
session_start();
require "../functions.php";
require "../connection.php";
redirectToLogin('HR');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit'])) {
        $request_id =  $_POST['request_id'];
        $status =  $_POST['status'];
        
        // Check if status_reason is provided and not empty
        $status_reason = isset($_POST['status_reason']) && trim($_POST['status_reason']) !== '' 
            ?  $_POST['status_reason'] 
            : NULL;
        
        if ($status === 'Approved') {
            $query = "SELECT pr.*, e.position_id as current_position_id 
                     FROM promotion_request pr 
                     JOIN employees e ON pr.employee_id = e.employee_id 
                     WHERE pr.request_id = '$request_id'";
            $result = mysqli_query($con, $query);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $request = mysqli_fetch_assoc($result);
                
                mysqli_begin_transaction($con);
                
                $success = true;
                $error_message = "";
                
                $update_employee = "UPDATE employees 
                                   SET position_id = '{$request['proposed_position']}' 
                                   WHERE employee_id = '{$request['employee_id']}'";
                
                if (mysqli_query($con, $update_employee)) {
                    if ($status_reason === NULL) {
                        $update_request = "UPDATE promotion_request 
                                          SET status = '$status', 
                                              status_reason = NULL,
                                              updated_at = NOW() 
                                          WHERE request_id = '$request_id'";
                    } else {
                        $update_request = "UPDATE promotion_request 
                                          SET status = '$status', 
                                              status_reason = '$status_reason',
                                              updated_at = NOW() 
                                          WHERE request_id = '$request_id'";
                    }
                    
                    if (mysqli_query($con, $update_request)) {
                        mysqli_commit($con);
                        $_SESSION['success'] = "Promotion approved successfully! Employee position has been updated.";
                    } else {
                        $success = false;
                        $error_message = "Failed to update promotion request: " . mysqli_error($con);
                    }
                } else {
                    $success = false;
                    $error_message = "Failed to update employee position: " . mysqli_error($con);
                }
                
                if (!$success) {
                    mysqli_rollback($con);
                    $_SESSION['error'] = $error_message;
                }
                
            } else {
                $_SESSION['error'] = "Promotion request not found.";
            }
            
        } elseif ($status === 'Denied' || $status === 'In Process') {
            if ($status_reason === NULL) {
                $update_request = "UPDATE promotion_request 
                                 SET status = '$status', 
                                     status_reason = NULL,
                                     updated_at = NOW() 
                                 WHERE request_id = '$request_id'";
            } else {
                $update_request = "UPDATE promotion_request 
                                 SET status = '$status', 
                                     status_reason = '$status_reason',
                                     updated_at = NOW() 
                                 WHERE request_id = '$request_id'";
            }
            
            if (mysqli_query($con, $update_request)) {
                $action = $status === 'Denied' ? 'rejected' : 'set to "In Process"';
                $_SESSION['success'] = "Promotion request has been $action.";
            } else {
                $_SESSION['error'] = "Failed to update request status: " . mysqli_error($con);
            }
        }
        
        header("Location: promotePage.php");
        exit;
    }
}

// query para may malagay sa table
$query = "SELECT pr.*, 
                 e.employee_id,
                 CONCAT(c.first_name, ' ', c.last_name) as employee_name,
                 curr_p.position_name as current_position,
                 curr_d.department_name as current_department,
                 new_p.position_name as proposed_position_name,
                 new_d.department_name as proposed_department
          FROM promotion_request pr
          JOIN employees e ON pr.employee_id = e.employee_id
          JOIN candidates c ON e.candidate_id = c.candidate_id
          JOIN positions curr_p ON e.position_id = curr_p.position_id
          JOIN departments curr_d ON curr_p.department_id = curr_d.department_id
          JOIN positions new_p ON pr.proposed_position = new_p.position_id
          JOIN departments new_d ON new_p.department_id = new_d.department_id
          ORDER BY pr.created_at DESC";

$result = mysqli_query($con, $query);
$promotion_requests = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $promotion_requests[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Company Name - HR Promotions</title>
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

        <!-- Display Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white d-flex align-items-center">
            <i class="fas fa-chart-line me-2"></i>
            <h5 class="mb-0">All Position Change Requests</h5>
          </div>
          <div class="card-body">
            <?php if (empty($promotion_requests)): ?>
              <div class="text-center py-4">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No position change requests</h5>
                <p class="text-muted">There are no position change requests in the system.</p>
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>Employee Name</th>
                      <th>ID</th>
                      <th>Current Department</th>
                      <th>Current Position</th>
                      <th>Proposed Position</th>
                      <th>Status</th>
                      <th>Request Date</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($promotion_requests as $request): ?>
                      <tr>
                        <td><?php echo $request['employee_name']; ?></td>
                        <td><?php echo $request['employee_id']; ?></td>
                        <td><?php echo $request['current_department']; ?></td>
                        <td><?php echo $request['current_position']; ?></td>
                        <td><?php echo $request['proposed_position_name']; ?></td>
                        <td>
                          <span class="badge 
                            <?php 
                              switch($request['status']) {
                                case 'Pending': echo 'bg-warning'; break;
                                case 'In Process': echo 'bg-info'; break;
                                case 'Approved': echo 'bg-success'; break;
                                case 'Denied': echo 'bg-danger'; break;
                                default: echo 'bg-secondary';
                              }
                            ?>">
                            <?php echo $request['status']; ?>
                          </span>
                        </td>
                        <td><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></td>
                        <td>
                          <button class="btn btn-primary btn-sm view-request" 
                                  data-request-id="<?php echo $request['request_id']; ?>"
                                  data-employee-name="<?php echo $request['employee_name']; ?>"
                                  data-employee-id="<?php echo $request['employee_id']; ?>"
                                  data-current-department="<?php echo $request['current_department']; ?>"
                                  data-current-position="<?php echo $request['current_position']; ?>"
                                  data-proposed-position="<?php echo $request['proposed_position_name']; ?>"
                                  data-proposed-department="<?php echo $request['proposed_department']; ?>"
                                  data-change-type="<?php echo $request['change_type']; ?>"
                                  data-reason="<?php echo $request['reason']; ?>"
                                  data-created-at="<?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?>"
                                  data-current-status="<?php echo $request['status']; ?>"
                                  data-status-reason="<?php echo $request['status_reason']; ?>">
                            <i class="fas fa-eye"></i> View
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

  <!-- View Request Modal -->
  <div class="modal fade" id="viewRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="POST" action="promotePage.php" id="statusForm">
          <div class="modal-header">
            <h5 class="modal-title">Position Change Request Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <!-- Employee Information -->
            <div class="row mb-4">
              <div class="col-md-6">
                <h6 class="fw-bold text-primary mb-3">Employee Information</h6>
                <div class="mb-2">
                  <label class="form-label fw-semibold">Employee Name:</label>
                  <p class="form-control-static" id="viewEmployeeName"></p>
                </div>
                <div class="mb-2">
                  <label class="form-label fw-semibold">Employee ID:</label>
                  <p class="form-control-static" id="viewEmployeeId"></p>
                </div>
              </div>
              <div class="col-md-6">
                <h6 class="fw-bold text-primary mb-3">Request Information</h6>
                <div class="mb-2">
                  <label class="form-label fw-semibold">Request Date:</label>
                  <p class="form-control-static" id="viewRequestDate"></p>
                </div>
                <div class="mb-2">
                  <label class="form-label fw-semibold">Change Type:</label>
                  <p class="form-control-static">
                    <span class="badge" id="viewChangeTypeBadge"></span>
                  </p>
                </div>
                <div class="mb-2">
                  <label class="form-label fw-semibold">Current Status:</label>
                  <p class="form-control-static">
                    <span class="badge" id="viewCurrentStatusBadge"></span>
                  </p>
                </div>
              </div>
            </div>

            <!-- Position Information -->
            <div class="row mb-4">
              <div class="col-md-6">
                <h6 class="fw-bold text-primary mb-3">Current Position</h6>
                <div class="mb-2">
                  <label class="form-label fw-semibold">Department:</label>
                  <p class="form-control-static" id="viewCurrentDepartment"></p>
                </div>
                <div class="mb-2">
                  <label class="form-label fw-semibold">Position:</label>
                  <p class="form-control-static" id="viewCurrentPosition"></p>
                </div>
              </div>
              <div class="col-md-6">
                <h6 class="fw-bold text-primary mb-3">Proposed Position</h6>
                <div class="mb-2">
                  <label class="form-label fw-semibold">Department:</label>
                  <p class="form-control-static" id="viewProposedDepartment"></p>
                </div>
                <div class="mb-2">
                  <label class="form-label fw-semibold">Position:</label>
                  <p class="form-control-static" id="viewProposedPosition"></p>
                </div>
              </div>
            </div>

            <!-- Reason for Change -->
            <div class="mb-4">
              <h6 class="fw-bold text-primary mb-3">Reason for Change</h6>
              <div class="border rounded p-3">
                <p id="viewReason" class="mb-0"></p>
              </div>
            </div>

            <!-- Status Update Section -->
            <div class="mb-3">
              <h6 class="fw-bold text-primary mb-3">Update Request Status</h6>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Select Status:</label>
                  <select class="form-select" name="status" id="statusSelect" required>
                    <option value="">-- Choose Status --</option>
                    <option value="In Process">In Process</option>
                    <option value="Approved">Approve</option>
                    <option value="Denied">Deny</option>
                  </select>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Status Notes:</label>
                  <textarea class="form-control" name="status_reason" id="statusReason" rows="3" placeholder="Add notes about this status update..."></textarea>
                </div>
              </div>
            </div>

            <input type="hidden" name="request_id" id="viewRequestId">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" name="submit" class="btn btn-primary">
              <i class="fas fa-save me-1"></i> Update Status
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="scripts/promotePage.js"></script>
</body>
</html>