<?php
session_start();
require "../../functions.php";
require "../../connection.php";
redirectToLogin('HR');

$successMsg = null;
$errorMsg = null;

if (isset($_POST['delete_leave_type'])) {
    $report_type_id = $_POST['report_type_id'];

    mysqli_begin_transaction($con);
    $delete_query = "DELETE FROM report_types WHERE report_type_id = $report_type_id";
    if (mysqli_query($con, $delete_query)) {
        mysqli_commit($con);
        $successMsg = "Report type deleted successfully.";
    } else {
        mysqli_rollback($con);
        $errorMsg = "Error deleting report type: " . mysqli_error($con);
    }
}

if (isset($_POST['edit_report_type'])) {
    $report_type_id = $_POST['report_type_id'];
    $report_name = $_POST['report_name'];

    mysqli_begin_transaction($con);
    $delete_query = "update report_types set report_name = '$report_name' WHERE report_type_id = $report_type_id ";
    if (mysqli_query($con, $delete_query)) {
        mysqli_commit($con);
        $successMsg = "Report type deleted successfully.";
    } else {
        mysqli_rollback($con);
        $errorMsg = "Error deleting report type: " . mysqli_error($con);
    }
}

if (isset($_POST['add_report_type'])) {
    $report_type_id = $_POST['report_name'];
    mysqli_begin_transaction($con);
    $addReportQuery = "INSERT INTO report_types (report_name) VALUES ('$report_type_id')";
    if (mysqli_query($con, $addReportQuery)) {
        mysqli_commit($con);
        $successMsg = "Report type added successfully!";
    } else {
        mysqli_rollback($con);
        $errorMsg = "Error adding report type: " . mysqli_error($con);
    }
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
            <h4 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Report Type Management</h4>
          </div>
          <div class="card-body">
            <!-- Add Leave Type Form -->
            <div class="mb-4">
              <button class="btn btn-success mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#addLeaveForm">
                <i class="fas fa-plus me-1"></i> Add Report Type
              </button>
              
              <div class="collapse" id="addLeaveForm">
                <div class="card card-body">
                  <form method="POST" action="">
                    <div class="row">
                      <div class="col-md-5">
                        <label for="report_name" class="form-label">Leave Type Name</label>
                        <input type="text" class="form-control" id="report_name" name="report_name" required>
                      </div>
                      <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" name="add_report_type" class="btn btn-primary w-100">
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
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT * FROM report_types";
                    $result = mysqli_query($con, $query);
                    while($row = mysqli_fetch_assoc($result)) {
                        if(empty($row)){
                        ?>
                            <tr>
                                <td colspan="3" class="text-center">No leave types found.</td>
                            </tr>
                        <?php
                        }else{
                            ?>
                            
                            <tr>
                                <td style="display: none;"><?php echo $row['report_type_id']; ?></td>
                                <td><?php echo $row['report_name']; ?></td>
                                <td>
                                    <!-- Edit Button -->
                                    <button class="btn btn-outline-primary btn-sm edit-btn" 
                                            data-report-id="<?php echo $row['report_type_id']; ?>"
                                            data-report-name="<?php echo $row['report_name']; ?>">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </button>
                                    
                                    <!-- Delete Button -->
                                    <button class="btn btn-outline-danger btn-sm delete-btn"
                                            data-report-id="<?php echo $row['report_type_id']; ?>"
                                            data-report-name="<?php echo $row['report_name']; ?>">
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
            <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Report Type</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="report_type_id" id="reportId">
            <div class="mb-3">
              <label for="editLeaveName" class="form-label">Report Type Name</label>
              <input type="text" class="form-control" id="editLeaveName" name="report_name" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="edit_report_type" class="btn btn-primary">Save Changes</button>
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
            <input type="hidden" name="report_type_id" id="deleteReportId">
            <p class="mb-0">Are you sure you want to delete the leave type <strong id="deleteReportName"></strong>?</p>
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
        const leaveId = this.getAttribute('data-report-id');
        const leaveName = this.getAttribute('data-report-name');
        
        // Populate modal with data
        document.getElementById('reportId').value = leaveId;
        document.getElementById('editLeaveName').value = leaveName;
        
        editModal.show();
      });
    });

    // Delete functionality
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteLeaveModal'));
    
    deleteButtons.forEach(btn => {
      btn.addEventListener('click', function() {
        const leaveId = this.getAttribute('data-report-id');
        const leaveName = this.getAttribute('data-report-name');
        
        // Populate modal with data
        document.getElementById('deleteReportId').value = leaveId;
        document.getElementById('deleteReportName').textContent = leaveName;
        
        deleteModal.show();
      });
    });
  });
  </script>
</body>
</html>