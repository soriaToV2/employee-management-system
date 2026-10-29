<?php

session_start();
require "../functions.php";
require "../connection.php";
redirectToLogin('Manager');

$currentEmployeeID = $_SESSION['employeeID'];

// Get manager's department
$manager_dept_query = "SELECT p.department_id 
FROM employees e 
LEFT JOIN positions p ON e.position_id = p.position_id 
WHERE e.employee_id = '$currentEmployeeID'";
$manager_dept_result = mysqli_query($con, $manager_dept_query);
$manager_department_id = null;

if ($manager_dept_result && mysqli_num_rows($manager_dept_result) > 0) {
    $manager_dept_row = mysqli_fetch_assoc($manager_dept_result);
    $manager_department_id = $manager_dept_row['department_id'];
}

// Get total employees in manager's department
$total_employees = 0;
if ($manager_department_id) {
    $total_emp_query = "SELECT COUNT(*) as emp_count 
    FROM employees e 
    LEFT JOIN positions p ON e.position_id = p.position_id 
    WHERE p.department_id = '$manager_department_id' AND e.status = 'Active'";
    $total_emp_result = mysqli_query($con, $total_emp_query);
    if ($total_emp_result && mysqli_num_rows($total_emp_result) > 0) {
        $total_emp_row = mysqli_fetch_assoc($total_emp_result);
        $total_employees = $total_emp_row['emp_count'];
    }
}

// Get pending leave requests for manager's department
$pending_leaves = 0;
if ($manager_department_id) {
    $pending_leaves_query = "SELECT COUNT(*) as leave_count 
    FROM leave_requests lr 
    LEFT JOIN employees e ON lr.employee_id = e.employee_id 
    LEFT JOIN positions p ON e.position_id = p.position_id 
    WHERE p.department_id = '$manager_department_id' AND lr.status = 'Pending'";
    $pending_leaves_result = mysqli_query($con, $pending_leaves_query);
    if ($pending_leaves_result && mysqli_num_rows($pending_leaves_result) > 0) {
        $pending_leaves_row = mysqli_fetch_assoc($pending_leaves_result);
        $pending_leaves = $pending_leaves_row['leave_count'];
    }
}

// Get open resignation requests for manager's department
$open_resignations = 0;
if ($manager_department_id) {
    $resignations_query = "SELECT COUNT(*) as resign_count 
    FROM resignations r 
    LEFT JOIN employees e ON r.employee_id = e.employee_id 
    LEFT JOIN positions p ON e.position_id = p.position_id 
    WHERE p.department_id = '$manager_department_id' AND r.resignation_status = 'Pending'";
    $resignations_result = mysqli_query($con, $resignations_query);
    if ($resignations_result && mysqli_num_rows($resignations_result) > 0) {
        $resignations_row = mysqli_fetch_assoc($resignations_result);
        $open_resignations = $resignations_row['resign_count'];
    }
}

// Get manager's overall performance rating
$manager_performance = "N/A";
$performance_query = "SELECT AVG(pr.rating) as avg_rating 
FROM performance_reviews pr 
WHERE pr.reviewee_id = '$currentEmployeeID' 
AND pr.period_id IN (SELECT period_id FROM rating_periods WHERE is_active = 1)";
$performance_result = mysqli_query($con, $performance_query);
if ($performance_result && mysqli_num_rows($performance_result) > 0) {
    $performance_row = mysqli_fetch_assoc($performance_result);
    if ($performance_row['avg_rating']) {
        $manager_performance = round(($performance_row['avg_rating'] / 5) * 100) . '%';
    }
}

// Get recent employee activities (leave requests and resignation requests)
$recent_activities = array();

// Get recent leave requests from manager's department
if ($manager_department_id) {
    $leave_activities_query = "SELECT 
        CONCAT(c.first_name, ' ', c.last_name) as employee_name,
        CONCAT('Leave Request: ', lt.leave_name) as activity,
        p.position_name,
        lr.status,
        lr.request_date as activity_date,
        'leave' as activity_type
    FROM leave_requests lr
    LEFT JOIN employees e ON lr.employee_id = e.employee_id
    LEFT JOIN candidates c ON e.candidate_id = c.candidate_id
    LEFT JOIN positions p ON e.position_id = p.position_id
    LEFT JOIN leave_types lt ON lr.leave_type_id = lt.leave_type_id
    WHERE p.department_id = '$manager_department_id'
    ORDER BY lr.request_date DESC
    LIMIT 8";
    
    $leave_activities_result = mysqli_query($con, $leave_activities_query);
    if ($leave_activities_result && mysqli_num_rows($leave_activities_result) > 0) {
        while ($row = mysqli_fetch_assoc($leave_activities_result)) {
            $recent_activities[] = $row;
        }
    }
}

// Get recent resignation requests from manager's department
if ($manager_department_id) {
    $resignation_activities_query = "SELECT 
        CONCAT(c.first_name, ' ', c.last_name) as employee_name,
        'Resignation Request' as activity,
        p.position_name,
        r.resignation_status as status,
        r.request_date as activity_date,
        'resignation' as activity_type
    FROM resignations r
    LEFT JOIN employees e ON r.employee_id = e.employee_id
    LEFT JOIN candidates c ON e.candidate_id = c.candidate_id
    LEFT JOIN positions p ON e.position_id = p.position_id
    WHERE p.department_id = '$manager_department_id'
    ORDER BY r.request_date DESC
    LIMIT 8";
    
    $resignation_activities_result = mysqli_query($con, $resignation_activities_query);
    if ($resignation_activities_result && mysqli_num_rows($resignation_activities_result) > 0) {
        while ($row = mysqli_fetch_assoc($resignation_activities_result)) {
            $recent_activities[] = $row;
        }
    }
}

// Sort all activities by date (most recent first) and limit to 8
usort($recent_activities, function($a, $b) {
    return strtotime($b['activity_date']) - strtotime($a['activity_date']);
});
$recent_activities = array_slice($recent_activities, 0, 8);

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
            <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width:45px; height:45px;">M</div>
            <div>
              <h6 class="mb-0"><?php echo $_SESSION['employeeName']; ?></h6>
              <small><?php echo $_SESSION['employeePosition']; ?></small>
            </div>
          </div>
        </div>

        <!-- Dashboard Summary Cards -->
        <div class="row g-3 mb-4">
          <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm text-center p-3">
              <i class="fas fa-users fa-2x text-primary mb-2"></i>
              <h4><?php echo $total_employees; ?></h4>
              <p class="mb-0">Total Employees</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm text-center p-3">
              <i class="fas fa-calendar-alt fa-2x text-warning mb-2"></i>
              <h4><?php echo $pending_leaves; ?></h4>
              <p class="mb-0">Pending Leaves</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm text-center p-3">
              <i class="fas fa-sign-out-alt fa-2x text-danger mb-2"></i>
              <h4><?php echo $open_resignations; ?></h4>
              <p class="mb-0">Open Resignations</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm text-center p-3">
              <i class="fas fa-chart-line fa-2x text-success mb-2"></i>
              <h4><?php echo $manager_performance; ?></h4>
              <p class="mb-0">Your Overall Performance</p>
            </div>
          </div>
        </div>

        <!-- Recent Activities Table -->
        <div class="card shadow-sm mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Employee Activities</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered align-middle">
                <thead>
                  <tr>
                    <th>Employee Name</th>
                    <th>Activity</th>
                    <th>Position</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($recent_activities)): ?>
                    <?php foreach ($recent_activities as $activity): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($activity['employee_name']); ?></td>
                        <td><?php echo htmlspecialchars($activity['activity']); ?></td>
                        <td><?php echo htmlspecialchars($activity['position_name']); ?></td>
                        <td>
                          <?php 
                          $status_class = '';
                          $status_text = $activity['status'];
                          
                          switch($status_text) {
                              case 'Pending':
                                  $status_class = 'bg-warning text-dark';
                                  break;
                              case 'Approved':
                                  $status_class = 'bg-success';
                                  break;
                              case 'Denied':
                              case 'Rejected':
                                  $status_class = 'bg-danger';
                                  break;
                              case 'Cancelled':
                                  $status_class = 'bg-secondary';
                                  break;
                              default:
                                  $status_class = 'bg-info';
                          }
                          ?>
                          <span class="badge <?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($status_text); ?>
                          </span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="4" class="text-center">No recent employee activities found.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Quick Access -->
        <div class="card shadow-sm">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-link me-2"></i>Quick Access</h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-3">
                <a href="emploPage.php" class="btn btn-outline-primary w-100"><i class="fas fa-users me-2"></i>Manage Employees</a>
              </div>
              <div class="col-md-3">
                <a href="leaveReqPage.php" class="btn btn-outline-warning w-100"><i class="fas fa-calendar-check me-2"></i>Leave Requests</a>
              </div>
              <div class="col-md-3">
                <a href="newEmpPage.php" class="btn btn-outline-success w-100"><i class="fas fa-user-plus me-2"></i>New Employee</a>
              </div>
              <div class="col-md-3">
                <a href="resignPage.php" class="btn btn-outline-danger w-100"><i class="fas fa-user-minus me-2"></i>Manage Resignations</a>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>