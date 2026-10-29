<?php
session_start();
require "../functions.php";
require "../connection.php";
redirectToLogin('Manager');

$currentEmployeeID = $_SESSION['employeeID'];

// FOR VIEWING THE MANAGERS REQUEST OF EMPLOYEE
$allPendingRequestsQuery = "
    SELECT hr.*, p.position_name, d.department_name, c.first_name, c.last_name 
    FROM hiring_requests hr 
    JOIN positions p ON hr.position_id = p.position_id 
    JOIN departments d ON p.department_id = d.department_id 
    JOIN employees e ON hr.manager_id = e.employee_id 
    JOIN candidates c ON e.candidate_id = c.candidate_id 
    ORDER BY hr.date_requested DESC
";

// Fetch manager's requests with position information
$requestQuery = "
    SELECT hr.*, p.position_name, d.department_name 
    FROM hiring_requests hr 
    JOIN positions p ON hr.position_id = p.position_id 
    JOIN departments d ON p.department_id = d.department_id 
    WHERE hr.manager_id = '$currentEmployeeID' 
    ORDER BY hr.date_requested DESC
";

$myRequests = mysqli_query($con, $requestQuery);

// FIXED: Removed applications table join
$managerDeptQuery = "SELECT p.department_id, d.department_name
    FROM employees e 
    JOIN positions p ON e.position_id = p.position_id 
    JOIN departments d ON p.department_id = d.department_id
    WHERE e.employee_id = '$currentEmployeeID'
";

$managerDeptResult = mysqli_query($con, $managerDeptQuery);
$managerDept = mysqli_fetch_assoc($managerDeptResult);
$managerDepartmentId = $managerDept['department_id'];
$managerDepartmentName = $managerDept['department_name'];

// Fetch positions available for manager's department
$positionsQuery = "
    SELECT p.position_id, p.position_name 
    FROM positions p 
    WHERE p.department_id = '$managerDepartmentId' 
    ORDER BY p.position_name
";

$positions = mysqli_query($con, $positionsQuery);

//FOR FORM SUBMISSION
$successMessage = null;
$errorMessage = null;
if (isset($_POST['submit_request'])) {
    $positionId = $_POST['position_id'];
    $employmentType = $_POST['employment_type'];
    $numPeople = $_POST['num_people'];
    $justification = $_POST['justification'];
    
    $insertQuery = "INSERT INTO hiring_requests (manager_id, position_id, employment_type, num_people, justification) 
        VALUES ('$currentEmployeeID', '$positionId', '$employmentType', '$numPeople', '$justification')
    ";

    mysqli_begin_transaction($con);
    
    if (mysqli_query($con, $insertQuery)) {
      mysqli_commit($con);
        $successMessage = "New hiring request submitted successfully!";
        $myRequests = mysqli_query($con, $requestQuery);
        $positions = mysqli_query($con, $positionsQuery);
    } else {
      mysqli_rollback($con);
        $errorMessage = "Error submitting request: " . mysqli_error($con);
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
        <?php if (isset($successMessage)): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $successMessage; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $errorMessage; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <!-- Pending Requests Table -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i> My Hiring Requests</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>Department</th>
                                <th>Employment Type</th>
                                <th>Number of People</th>
                                <th>Date Requested</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                          <?php
                          if (mysqli_num_rows($myRequests) > 0){
                            while ($request = mysqli_fetch_assoc($myRequests)){
                              ?>
                              <tr>
                                <td><?php echo $request['position_name'] ;?></td>
                                <td><?php echo $request['department_name'] ;?></td>
                                <td><span class="badge 
                                                <?php 
                                                    switch($request['employment_type']) {
                                                        case 'Full Time': echo 'bg-success'; break;
                                                        case 'Part Time': echo 'bg-info text-dark'; break;
                                                        case 'Contract': echo 'bg-warning text-dark'; break;
                                                        default: echo 'bg-secondary';
                                                    }
                                                ?>">
                                                <?php echo $request['employment_type']; ?>
                                            </span>
                                  </td>
                                <td><?php echo $request['num_people']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($request['date_requested'])); ?></td>
                                <td>
                                            <span class="badge 
                                                <?php 
                                                    switch($request['status']) {
                                                        case 'Pending': echo 'bg-warning text-dark'; break;
                                                        case 'Approved': echo 'bg-success'; break;
                                                        case 'Rejected': echo 'bg-danger'; break;
                                                        case 'In Process': echo 'bg-secondary'; break;
                                                    }
                                                ?>">
                                                <?php echo $request['status']; ?>
                                            </span>
                                  </td>
                              </tr>
                              <?php
                            }
                          }else{
                            ?>
                            <tr>
                              <td colspan="6" class="text-center text-muted">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                No hiring requests submitted yet.
                              </td>
                            </tr>
                            <?php
                          }
                          ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Submit New Request Form -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-plus me-2"></i> Submit New Hiring Request</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="newEmpPage.php">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Position</label>
                            <select name="position_id" class="form-select" required>
                                <option value="">Select Position</option>
                                <?php
                                if (mysqli_num_rows($positions) > 0){
                                  while ($position = mysqli_fetch_assoc($positions)){
                                    ?>
                                    <option value="<?php echo $position['position_id'] ?>"><?php echo $position['position_name'] ;?></option>
                                    <?php
                                  }
                                }else{
                                  ?>
                                  <option value="">No positions available in your department</option>
                                  <?php
                                }
                                ?>

                            </select>
                            <?php if (mysqli_num_rows($positions) == 0): ?>
                                <div class="form-text text-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    No positions are defined for your department. Please contact HR.
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Employment Type</label>
                            <select name="employment_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="Full Time">Full Time</option>
                                <option value="Part Time">Part Time</option>
                                <option value="Contract">Contract</option>
                                <option value="Probationary">Probationary</option>
                                <option value="Intern">Intern</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Department</label>
                            <input type="text" class="form-control" value="<?php echo $managerDepartmentName; ?>" readonly>
                            <div class="form-text">Your department (automatically assigned)</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Number of People</label>
                            <input type="number" name="num_people" class="form-control" min="1" max="10" required>
                            <div class="form-text">Maximum 10 people per request</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Justification</label>
                        <textarea name="justification" class="form-control" rows="4" placeholder="Explain why you need this position filled..." required></textarea>
                    </div>
                    <button type="submit" name="submit_request" class="btn btn-success" <?php echo (mysqli_num_rows($positions) == 0) ? 'disabled' : ''; ?>>
                        <i class="fas fa-paper-plane me-1"></i>Submit Request
                    </button>
                    <?php if (mysqli_num_rows($positions) == 0): ?>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            You cannot submit requests until positions are defined for your department.
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

      </div>
    </div>
  </div>

  <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>