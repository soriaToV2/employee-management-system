<?php
session_start();
require "../../functions.php";
require "../../connection.php";
redirectToLogin('HR');

$successMsg = null;
$errorMsg = null;

// Handle Add Leave Type
if (isset($_POST['add_leave_type'])) {
    $leave_name = mysqli_real_escape_string($con, $_POST['leave_name']);
    $max_leave = intval($_POST['max_leave']);
    
    if (empty($leave_name) || $max_leave <= 0) {
        $errorMsg = "Please provide valid leave name and maximum leave days.";
    } else {
        $check_query = "SELECT * FROM leave_types WHERE leave_name = '$leave_name'";
        $check_result = mysqli_query($con, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $errorMsg = "Leave type '$leave_name' already exists.";
        } else {
            $insert_query = "INSERT INTO leave_types (leave_name, max_leave) VALUES ('$leave_name', '$max_leave')";
            mysqli_begin_transaction($con);
            if (mysqli_query($con, $insert_query)) {
                mysqli_commit($con);
                $successMsg = "Leave type added successfully!";
            } else {
                mysqli_rollback($con);
                $errorMsg = "Error adding leave type: " . mysqli_error($con);
            }
        }
    }
}

// Handle Edit Leave Type
if (isset($_POST['edit_leave_type'])) {
    $leave_type_id = intval($_POST['leave_type_id']);
    $leave_name = mysqli_real_escape_string($con, $_POST['leave_name']);
    $max_leave = intval($_POST['max_leave']);
    
    if (empty($leave_name) || $max_leave <= 0) {
        $errorMsg = "Please provide valid leave name and maximum leave days.";
    } else {
        $check_query = "SELECT * FROM leave_types WHERE leave_name = '$leave_name' AND leave_type_id != '$leave_type_id'";
        $check_result = mysqli_query($con, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $errorMsg = "Leave type '$leave_name' already exists.";
        } else {
            $update_query = "UPDATE leave_types SET leave_name = '$leave_name', max_leave = '$max_leave' WHERE leave_type_id = '$leave_type_id'";
            if (mysqli_query($con, $update_query)) {
                $successMsg = "Leave type updated successfully!";
            } else {
                $errorMsg = "Error updating leave type: " . mysqli_error($con);
            }
        }
    }
}

if (isset($_POST['delete_leave_type'])) {
    $leave_type_id = intval($_POST['leave_type_id']);
    
    $delete_query = "delete from leave_types where leave_type_id = '$leave_type_id'";
    mysqli_begin_transaction($con);
    if(mysqli_query($con, $delete_query)){
        mysqli_commit($con);
        $successMsg = "Leave type deleted successfully!";
    }else{
        $errorMsg = "Error deleting leave type: " . mysqli_error($con);
    }
}

$query = "SELECT * FROM leave_types ORDER BY leave_name";
$result = mysqli_query($con, $query);
$leave_types = [];
while ($row = mysqli_fetch_assoc($result)) {
    $leave_types[] = $row;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Company Name - HR Recruitment</title>
  <link href="../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../css/Global.css" rel="stylesheet">
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
          <h2 class="fw-bold">Settings</h2>
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

        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Leave Types Management</h4>
          </div>
          <div class="card-body">
            <!-- Add Leave Type Form -->
            <div class="mb-4">
              <button class="btn btn-success mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#addLeaveForm">
                <i class="fas fa-plus me-1"></i> Add Leave Type
              </button>
              
              <div class="collapse" id="addLeaveForm">
                <div class="card card-body">
                  <form method="POST" action="">
                    <div class="row">
                      <div class="col-md-5">
                        <label for="leave_name" class="form-label">Leave Type Name</label>
                        <input type="text" class="form-control" id="leave_name" name="leave_name" required>
                      </div>
                      <div class="col-md-5">
                        <label for="max_leave" class="form-label">Maximum Leave Days per Year</label>
                        <input type="number" class="form-control" id="max_leave" name="max_leave" min="1" required>
                      </div>
                      <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" name="add_leave_type" class="btn btn-primary w-100">
                          <i class="fas fa-save me-1"></i> Save
                        </button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <!-- Leave Types Table -->
            <div class="table-responsive">
              <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Leave Type</th>
                    <th>Max Leave per Year</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                    <?php
                    if (empty($leave_types)){
                        ?>
                        <tr>
                            <td colspan="3" class="text-center">No leave types found.</td>
                        </tr>
                        <?php
                    }else{
                        foreach ($leave_types as $leave){
                            ?>
                            <tr>
                                <td style="display: none;"><?php echo $leave['leave_type_id']; ?></td>
                                <td><?php echo htmlspecialchars($leave['leave_name']); ?></td>
                                <td><?php echo $leave['max_leave']; ?> days</td>
                                <td>
                                    <!-- Edit Button -->
                                    <button class="btn btn-outline-primary btn-sm edit-btn" 
                                            data-leave-id="<?php echo $leave['leave_type_id']; ?>"
                                            data-leave-name="<?php echo htmlspecialchars($leave['leave_name']); ?>"
                                            data-max-leave="<?php echo $leave['max_leave']; ?>">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </button>
                                    
                                    <!-- Delete Button -->
                                    <button class="btn btn-outline-danger btn-sm delete-btn"
                                            data-leave-id="<?php echo $leave['leave_type_id']; ?>"
                                            data-leave-name="<?php echo htmlspecialchars($leave['leave_name']); ?>">
                                        <i class="fas fa-trash me-1"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php
                        }
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

  <!-- Edit Leave Type Modal -->
  <div class="modal fade" id="editLeaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="">
          <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Leave Type</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="leave_type_id" id="editLeaveId">
            <div class="mb-3">
              <label for="editLeaveName" class="form-label">Leave Type Name</label>
              <input type="text" class="form-control" id="editLeaveName" name="leave_name" required>
            </div>
            <div class="mb-3">
              <label for="editMaxLeave" class="form-label">Maximum Leave Days per Year</label>
              <input type="number" class="form-control" id="editMaxLeave" name="max_leave" min="1" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="edit_leave_type" class="btn btn-primary">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete Leave Type Modal -->
  <div class="modal fade" id="deleteLeaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="leave_type_id" id="deleteLeaveId">
            <p class="mb-0">Are you sure you want to delete the leave type <strong id="deleteLeaveName"></strong>?</p>
            <p class="text-muted small mt-2 mb-0">This action cannot be undone.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="delete_leave_type" class="btn btn-danger">
              <i class="fas fa-trash me-1"></i> Delete
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="../../bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Edit functionality
    const editButtons = document.querySelectorAll('.edit-btn');
    const editModal = new bootstrap.Modal(document.getElementById('editLeaveModal'));
    
    editButtons.forEach(btn => {
      btn.addEventListener('click', function() {
        const leaveId = this.getAttribute('data-leave-id');
        const leaveName = this.getAttribute('data-leave-name');
        const maxLeave = this.getAttribute('data-max-leave');
        
        // Populate modal with data
        document.getElementById('editLeaveId').value = leaveId;
        document.getElementById('editLeaveName').value = leaveName;
        document.getElementById('editMaxLeave').value = maxLeave;
        
        editModal.show();
      });
    });

    // Delete functionality
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteLeaveModal'));
    
    deleteButtons.forEach(btn => {
      btn.addEventListener('click', function() {
        const leaveId = this.getAttribute('data-leave-id');
        const leaveName = this.getAttribute('data-leave-name');
        
        // Populate modal with data
        document.getElementById('deleteLeaveId').value = leaveId;
        document.getElementById('deleteLeaveName').textContent = leaveName;
        
        deleteModal.show();
      });
    });
  });
  </script>
</body>
</html>