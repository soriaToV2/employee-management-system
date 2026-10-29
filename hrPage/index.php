<?php
session_start();
require "../functions.php";
require "../connection.php";
redirectToLogin('HR');

// Get total company employees
$total_employees_query = "SELECT COUNT(*) as total FROM employees WHERE status = 'Active'";
$total_employees_result = mysqli_query($con, $total_employees_query);
$total_employees = 0;
if ($total_employees_result && mysqli_num_rows($total_employees_result) > 0) {
    $total_employees_row = mysqli_fetch_assoc($total_employees_result);
    $total_employees = $total_employees_row['total'];
}

// Get probationary employees
$probationary_query = "SELECT COUNT(*) as probationary FROM employees WHERE employment_status = 'Probationary' AND status = 'Active'";
$probationary_result = mysqli_query($con, $probationary_query);
$probationary_employees = 0;
if ($probationary_result && mysqli_num_rows($probationary_result) > 0) {
    $probationary_row = mysqli_fetch_assoc($probationary_result);
    $probationary_employees = $probationary_row['probationary'];
}

// Get pending leaves
$pending_leaves_query = "SELECT COUNT(*) as pending_leaves FROM leave_requests WHERE status = 'Pending'";
$pending_leaves_result = mysqli_query($con, $pending_leaves_query);
$pending_leaves = 0;
if ($pending_leaves_result && mysqli_num_rows($pending_leaves_result) > 0) {
    $pending_leaves_row = mysqli_fetch_assoc($pending_leaves_result);
    $pending_leaves = $pending_leaves_row['pending_leaves'];
}

// Get pending resignations
$pending_resignations_query = "SELECT COUNT(*) as pending_resignations FROM resignations WHERE resignation_status = 'Pending'";
$pending_resignations_result = mysqli_query($con, $pending_resignations_query);
$pending_resignations = 0;
if ($pending_resignations_result && mysqli_num_rows($pending_resignations_result) > 0) {
    $pending_resignations_row = mysqli_fetch_assoc($pending_resignations_result);
    $pending_resignations = $pending_resignations_row['pending_resignations'];
}

// Get pending violations (reports)
$pending_violations_query = "SELECT COUNT(*) as pending_violations FROM reports WHERE status = 'Pending'";
$pending_violations_result = mysqli_query($con, $pending_violations_query);
$pending_violations = 0;
if ($pending_violations_result && mysqli_num_rows($pending_violations_result) > 0) {
    $pending_violations_row = mysqli_fetch_assoc($pending_violations_result);
    $pending_violations = $pending_violations_row['pending_violations'];
}

// Get promotional/transfer requests
$pending_promotions_query = "SELECT COUNT(*) as pending_promotions FROM promotion_request WHERE status = 'Pending'";
$pending_promotions_result = mysqli_query($con, $pending_promotions_query);
$pending_promotions = 0;
if ($pending_promotions_result && mysqli_num_rows($pending_promotions_result) > 0) {
    $pending_promotions_row = mysqli_fetch_assoc($pending_promotions_result);
    $pending_promotions = $pending_promotions_row['pending_promotions'];
}

// Get hiring requests
$pending_hiring_query = "SELECT COUNT(*) as pending_hiring FROM hiring_requests WHERE status = 'Pending'";
$pending_hiring_result = mysqli_query($con, $pending_hiring_query);
$pending_hiring = 0;
if ($pending_hiring_result && mysqli_num_rows($pending_hiring_result) > 0) {
    $pending_hiring_row = mysqli_fetch_assoc($pending_hiring_result);
    $pending_hiring = $pending_hiring_row['pending_hiring'];
}

// Get recent activities (leave requests, resignation requests, hiring requests, and reports)
$recent_activities = array();

// Get recent leave requests
$leave_activities_query = "SELECT 
    CONCAT(c.first_name, ' ', c.last_name) as employee_name,
    CONCAT('Leave Request: ', lt.leave_name) as activity,
    d.department_name,
    p.position_name,
    lr.status,
    lr.request_date as activity_date,
    'leave' as activity_type
FROM leave_requests lr
LEFT JOIN employees e ON lr.employee_id = e.employee_id
LEFT JOIN candidates c ON e.candidate_id = c.candidate_id
LEFT JOIN positions p ON e.position_id = p.position_id
LEFT JOIN departments d ON p.department_id = d.department_id
LEFT JOIN leave_types lt ON lr.leave_type_id = lt.leave_type_id
ORDER BY lr.request_date DESC
LIMIT 5";

$leave_activities_result = mysqli_query($con, $leave_activities_query);
if ($leave_activities_result && mysqli_num_rows($leave_activities_result) > 0) {
    while ($row = mysqli_fetch_assoc($leave_activities_result)) {
        $recent_activities[] = $row;
    }
}

// Get recent resignation requests
$resignation_activities_query = "SELECT 
    CONCAT(c.first_name, ' ', c.last_name) as employee_name,
    'Resignation Request' as activity,
    d.department_name,
    p.position_name,
    r.resignation_status as status,
    r.request_date as activity_date,
    'resignation' as activity_type
FROM resignations r
LEFT JOIN employees e ON r.employee_id = e.employee_id
LEFT JOIN candidates c ON e.candidate_id = c.candidate_id
LEFT JOIN positions p ON e.position_id = p.position_id
LEFT JOIN departments d ON p.department_id = d.department_id
ORDER BY r.request_date DESC
LIMIT 5";

$resignation_activities_result = mysqli_query($con, $resignation_activities_query);
if ($resignation_activities_result && mysqli_num_rows($resignation_activities_result) > 0) {
    while ($row = mysqli_fetch_assoc($resignation_activities_result)) {
        $recent_activities[] = $row;
    }
}

// Get recent hiring requests
$hiring_activities_query = "SELECT 
    CONCAT(emp_c.first_name, ' ', emp_c.last_name) as employee_name,
    CONCAT('Hiring Request: ', pos.position_name) as activity,
    d.department_name,
    p.position_name,
    hr.status,
    hr.date_requested as activity_date,
    'hiring' as activity_type
FROM hiring_requests hr
LEFT JOIN employees emp ON hr.manager_id = emp.employee_id
LEFT JOIN candidates emp_c ON emp.candidate_id = emp_c.candidate_id
LEFT JOIN positions p ON emp.position_id = p.position_id
LEFT JOIN positions pos ON hr.position_id = pos.position_id
LEFT JOIN departments d ON pos.department_id = d.department_id
ORDER BY hr.date_requested DESC
LIMIT 5";

$hiring_activities_result = mysqli_query($con, $hiring_activities_query);
if ($hiring_activities_result && mysqli_num_rows($hiring_activities_result) > 0) {
    while ($row = mysqli_fetch_assoc($hiring_activities_result)) {
        $recent_activities[] = $row;
    }
}

// Get recent reports (violations)
$report_activities_query = "SELECT 
    CONCAT(rep_c.first_name, ' ', rep_c.last_name) as employee_name,
    CONCAT('Report: ', rt.report_name) as activity,
    d.department_name,
    p.position_name,
    r.status,
    r.submitted_at as activity_date,
    'report' as activity_type
FROM reports r
LEFT JOIN employees rep_emp ON r.reporter_id = rep_emp.employee_id
LEFT JOIN candidates rep_c ON rep_emp.candidate_id = rep_c.candidate_id
LEFT JOIN positions p ON rep_emp.position_id = p.position_id
LEFT JOIN departments d ON p.department_id = d.department_id
LEFT JOIN report_types rt ON r.report_type_id = rt.report_type_id
ORDER BY r.submitted_at DESC
LIMIT 5";

$report_activities_result = mysqli_query($con, $report_activities_query);
if ($report_activities_result && mysqli_num_rows($report_activities_result) > 0) {
    while ($row = mysqli_fetch_assoc($report_activities_result)) {
        $recent_activities[] = $row;
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
  <title>HR Dashboard | Company Name</title>
  <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../css/Global.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body>
  <div class="container-fluid">
    <div class="row flex-nowrap">
      
      <?php include 'nav.php' ?>

      <!-- Main Content -->
      <div class="col py-4 px-5">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
          <h2 class="fw-bold">HR Dashboard</h2>
          <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width:45px; height:45px;">HR</div>
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
              <p class="mb-0">Total Company Employee</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm text-center p-3">
              <i class="fas fa-user-plus fa-2x text-success mb-2"></i>
              <h4><?php echo $probationary_employees; ?></h4>
              <p class="mb-0">Probationary Employee</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm text-center p-3">
              <i class="fas fa-calendar-check fa-2x text-warning mb-2"></i>
              <h4><?php echo $pending_leaves; ?></h4>
              <p class="mb-0">Pending Leaves</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm text-center p-3">
              <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2"></i>
              <h4><?php echo $pending_resignations; ?></h4>
              <p class="mb-0">Pending Resignations</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm text-center p-3">
              <i class="fas fa-gavel fa-2x text-danger mb-2"></i>
              <h4><?php echo $pending_violations; ?></h4>
              <p class="mb-0">Pending Violations</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm text-center p-3">
              <i class="fas fa-exchange-alt fa-2x text-info mb-2"></i>
              <h4><?php echo $pending_promotions; ?></h4>
              <p class="mb-0">Promotional/Transfer Request</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm text-center p-3">
              <i class="fas fa-briefcase fa-2x text-success mb-2"></i>
              <h4><?php echo $pending_hiring; ?></h4>
              <p class="mb-0">Hiring Request</p>
            </div>
          </div>
        </div>

        <!-- Recent Activity Table -->
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activities</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-hover align-middle ">
                <thead> 
                  <tr>
                    <th>Employee Name</th>
                    <th>Activity</th>
                    <th>Department</th>
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
                        <td><?php echo htmlspecialchars($activity['department_name']); ?></td>
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
                              case 'Reviewed':
                              case 'In Process':
                                  $status_class = 'bg-info';
                                  break;
                              case 'Resolved':
                                  $status_class = 'bg-primary';
                                  break;
                              default:
                                  $status_class = 'bg-light text-dark';
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
                      <td colspan="5" class="text-center">No recent activities found.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Quick Access -->
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-link me-2"></i>Quick Access</h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-3">
                <a href="employeePage.php" class="btn btn-outline-primary w-100"><i class="fas fa-users me-2"></i>Manage Employees</a>
              </div>
              <div class="col-md-3">
                <a href="recruitPage.php" class="btn btn-outline-success w-100"><i class="fas fa-user-plus me-2"></i>Recruitment</a>
              </div>
              <div class="col-md-3">
                <a href="promotePage.php" class="btn btn-outline-warning w-100"><i class="fas fa-chart-line me-2"></i>Promotions</a>
              </div>
              <div class="col-md-3">
                <a href="violatePage.php" class="btn btn-outline-danger w-100"><i class="fas fa-exclamation-triangle me-2"></i>Violations</a>
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