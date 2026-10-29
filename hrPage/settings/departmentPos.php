<?php
session_start();
require "../../functions.php";
require "../../connection.php";
redirectToLogin('HR');

$successMsg = null;
$errorMsg = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new department
    if (isset($_POST['add_department'])) {
        $department_name = mysqli_real_escape_string($con, $_POST['department_name']);
        
        $query = "INSERT INTO departments (department_name) VALUES ('$department_name')";
        
        if (mysqli_query($con, $query)) {
            $successMsg = "Department added successfully!";
        } else {
            $errorMsg = "Error adding department: " . mysqli_error($con);
        }
    }
    
    // Add new position
    if (isset($_POST['add_position'])) {
        $department_id = mysqli_real_escape_string($con, $_POST['department_id']);
        $position_name = mysqli_real_escape_string($con, $_POST['position_name']);
        
        $query = "INSERT INTO positions (department_id, position_name) VALUES ('$department_id', '$position_name')";
        
        if (mysqli_query($con, $query)) {
            $successMsg = "Position added successfully!";
        } else {
            $errorMsg = "Error adding position: " . mysqli_error($con);
        }
    }
    
    // Delete department
    if (isset($_POST['delete_department'])) {
        $department_id = mysqli_real_escape_string($con, $_POST['department_id']);
        
        // First delete related positions
        mysqli_query($con, "DELETE FROM positions WHERE department_id = '$department_id'");
        
        // Then delete department
        $query = "DELETE FROM departments WHERE department_id = '$department_id'";
        
        if (mysqli_query($con, $query)) {
            $successMsg = "Department and its positions deleted successfully!";
        } else {
            $errorMsg = "Error deleting department: " . mysqli_error($con);
        }
    }
    
    // Delete position
    if (isset($_POST['delete_position'])) {
        $position_id = mysqli_real_escape_string($con, $_POST['position_id']);
        
        $query = "DELETE FROM positions WHERE position_id = '$position_id'";
        
        if (mysqli_query($con, $query)) {
            $successMsg = "Position deleted successfully!";
        } else {
            $errorMsg = "Error deleting position: " . mysqli_error($con);
        }
    }
}

// Fetch all departments with their positions
$query = "SELECT d.department_id, d.department_name, p.position_id, p.position_name 
          FROM departments d 
          LEFT JOIN positions p ON d.department_id = p.department_id 
          ORDER BY d.department_name, p.position_name";

$result = mysqli_query($con, $query);

$departments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $department_id = $row['department_id'];
    if (!isset($departments[$department_id])) {
        $departments[$department_id] = [
            'department_name' => $row['department_name'],
            'positions' => []
        ];
    }
    if ($row['position_id']) {
        $departments[$department_id]['positions'][] = [
            'position_id' => $row['position_id'],
            'position_name' => $row['position_name']
        ];
    }
}

// Fetch departments for dropdown
$departments_dropdown = mysqli_query($con, "SELECT * FROM departments ORDER BY department_name");
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
  <style>
    /* .delete-form {
        display: inline;
    }
    .confirmation-modal .modal-header {
        background-color: #dc3545;
        color: white;
    } */
    /* .department-header {
        background: linear-gradient(135deg, #e3f2fd, #bbdefb) !important;
        border-left: 4px solid #2196f3 !important;
    } */
  </style>
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
        <?php if ($successMsg) { ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($successMsg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php } ?>

        <?php if ($errorMsg) { ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($errorMsg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php } ?>

        <!-- Delete Confirmation Modals -->
        <!-- Delete Department Modal -->
        <div class="modal fade" id="deleteDepartmentModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>Delete Department
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p id="deleteDepartmentMessage">Are you sure you want to delete this department and all its positions? This action cannot be undone.</p>
                        <form id="deleteDepartmentForm" method="POST" class="delete-form">
                            <input type="hidden" name="department_id" id="deleteDepartmentId">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="deleteDepartmentForm" name="delete_department" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Delete Department
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Position Modal -->
        <div class="modal fade" id="deletePositionModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>Delete Position
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p id="deletePositionMessage">Are you sure you want to delete this position? This action cannot be undone.</p>
                        <form id="deletePositionForm" method="POST" class="delete-form">
                            <input type="hidden" name="position_id" id="deletePositionId">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="deletePositionForm" name="delete_position" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Delete Position
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-building me-2"></i>Department & Position Management
                </h4>
            </div>
            <div class="card-body">
                <!-- Add Department and Position Forms -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-plus me-1"></i> Add New Department
                                </h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="input-group">
                                        <input type="text" 
                                               name="department_name" 
                                               class="form-control" 
                                               placeholder="Enter department name" 
                                               required>
                                        <button type="submit" 
                                                name="add_department" 
                                                class="btn btn-success">
                                            Add Department
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-plus me-1"></i> Add New Position
                                </h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row g-2">
                                        <div class="col-md-5">
                                            <select name="department_id" class="form-select" required>
                                                <option value="">Select Department</option>
                                                <?php while ($dept = mysqli_fetch_assoc($departments_dropdown)) { ?>
                                                    <option value="<?php echo $dept['department_id']; ?>">
                                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <input type="text" 
                                                   name="position_name" 
                                                   class="form-control" 
                                                   placeholder="Enter position name" 
                                                   required>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" 
                                                    name="add_position" 
                                                    class="btn btn-info w-100">
                                                Add
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Departments and Positions Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th width="30%">Department</th>
                                <th width="60%">Positions</th>
                                <th width="10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($departments)) { ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">
                                        No departments found. Add some departments and positions to get started.
                                    </td>
                                </tr>
                            <?php } else { ?>
                                <?php foreach ($departments as $department_id => $department) { ?>
                                    <?php 
                                    $position_count = count($department['positions']);
                                    $has_positions = $position_count > 0;
                                    ?>
                                    
                                    <?php if ($has_positions) { ?>
                                        <?php foreach ($department['positions'] as $index => $position) { ?>
                                            <tr>
                                                <?php if ($index === 0) { ?>
                                                    <td rowspan="<?php echo $position_count; ?>" 
                                                        class="fw-bold department-header align-middle">
                                                        <?php echo htmlspecialchars($department['department_name']); ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            (<?php echo $position_count; ?> <?php echo $position_count > 1 ? 's' : ''; ?>)
                                                        </small>
                                                        
                                                        <!-- Delete Department Button -->
                                                        <button type="button" 
                                                                class="btn btn-outline-danger btn-sm mt-2"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#deleteDepartmentModal"
                                                                data-department-id="<?php echo $department_id; ?>"
                                                                data-department-name="<?php echo htmlspecialchars($department['department_name']); ?>">
                                                            <i class="fas fa-trash me-1"></i>Delete Department
                                                        </button>
                                                    </td>
                                                <?php } ?>
                                                
                                                <td><?php echo htmlspecialchars($position['position_name']); ?></td>
                                                
                                                <td>
                                                    <!-- Delete Position Button -->
                                                    <button type="button" 
                                                            class="btn btn-outline-danger btn-sm"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deletePositionModal"
                                                            data-position-id="<?php echo $position['position_id']; ?>"
                                                            data-position-name="<?php echo htmlspecialchars($position['position_name']); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td class="fw-bold department-header">
                                                <?php echo htmlspecialchars($department['department_name']); ?>
                                                <br>
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-sm mt-2"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteDepartmentModal"
                                                        data-department-id="<?php echo $department_id; ?>"
                                                        data-department-name="<?php echo htmlspecialchars($department['department_name']); ?>">
                                                    <i class="fas fa-trash me-1"></i>Delete Department
                                                </button>
                                            </td>
                                            <td colspan="2" class="fw-bold department-header">
                                                No positions added yet
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../../bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    // Handle department deletion modal
    const deleteDepartmentModal = document.getElementById('deleteDepartmentModal');
    if (deleteDepartmentModal) {
        deleteDepartmentModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const departmentId = button.getAttribute('data-department-id');
            const departmentName = button.getAttribute('data-department-name');
            
            const modalBody = document.getElementById('deleteDepartmentMessage');
            const departmentIdInput = document.getElementById('deleteDepartmentId');
            
            modalBody.textContent = 'Are you sure you want to delete the department "' + departmentName + '" and all its positions? This action cannot be undone.';
            departmentIdInput.value = departmentId;
        });
    }

    // Handle position deletion modal
    const deletePositionModal = document.getElementById('deletePositionModal');
    if (deletePositionModal) {
        deletePositionModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const positionId = button.getAttribute('data-position-id');
            const positionName = button.getAttribute('data-position-name');
            
            const modalBody = document.getElementById('deletePositionMessage');
            const positionIdInput = document.getElementById('deletePositionId');
            
            modalBody.textContent = 'Are you sure you want to delete the position: "' + positionName + '"? This action cannot be undone.';
            positionIdInput.value = positionId;
        });
    }
  </script>
</body>
</html>