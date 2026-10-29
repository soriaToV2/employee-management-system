<?php

session_start();
require "../functions.php";
require "../connection.php";
redirectToLogin('Employee');

$employee_id = $_SESSION['employeeID'];

$recent_activities = array();

$leave_query = "SELECT 
    lr.request_date as activity_date,
    CONCAT('Leave Request: ', lt.leave_name, ' - ', lr.reason) as activity_description,
    lr.status as activity_status
FROM leave_requests lr
LEFT JOIN leave_types lt ON lr.leave_type_id = lt.leave_type_id
WHERE lr.employee_id = '$employee_id'
ORDER BY lr.request_date DESC
LIMIT 5";

$leave_result = mysqli_query($con, $leave_query);
if ($leave_result && mysqli_num_rows($leave_result) > 0) {
    while ($row = mysqli_fetch_assoc($leave_result)) {
        $recent_activities[] = array(
            'date' => $row['activity_date'],
            'activity' => $row['activity_description'],
            'status' => $row['activity_status'],
            'type' => 'leave'
        );
    }
}

// 2. Get resignation requests
$resignation_query = "SELECT 
    resignation_date as activity_date,
    CONCAT('Resignation Request: ', reason) as activity_description,
    resignation_status as activity_status
FROM resignations 
WHERE employee_id = '$employee_id'
ORDER BY resignation_date DESC
LIMIT 5";

$resignation_result = mysqli_query($con, $resignation_query);
if ($resignation_result && mysqli_num_rows($resignation_result) > 0) {
    while ($row = mysqli_fetch_assoc($resignation_result)) {
        $recent_activities[] = array(
            'date' => $row['activity_date'],
            'activity' => $row['activity_description'],
            'status' => $row['activity_status'],
            'type' => 'resignation'
        );
    }
}

// 3. Get reports filed
$reports_query = "SELECT 
    submitted_at as activity_date,
    CONCAT('Report Filed: ', rt.report_name) as activity_description,
    r.status as activity_status
FROM reports r
LEFT JOIN report_types rt ON r.report_type_id = rt.report_type_id
WHERE r.reporter_id = '$employee_id'
ORDER BY r.submitted_at DESC
LIMIT 5";

$reports_result = mysqli_query($con, $reports_query);
if ($reports_result && mysqli_num_rows($reports_result) > 0) {
    while ($row = mysqli_fetch_assoc($reports_result)) {
        $recent_activities[] = array(
            'date' => $row['activity_date'],
            'activity' => $row['activity_description'],
            'status' => $row['activity_status'],
            'type' => 'report'
        );
    }
}

// Sort all activities by date (most recent first)
usort($recent_activities, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Limit to 5 most recent activities
$recent_activities = array_slice($recent_activities, 0, 5);

// Get pending requests count
$pending_count = 0;

// Count pending leave requests
$pending_leave_query = "SELECT COUNT(*) as count FROM leave_requests 
WHERE employee_id = '$employee_id' AND status = 'Pending'";
$pending_leave_result = mysqli_query($con, $pending_leave_query);
if ($pending_leave_result) {
    $row = mysqli_fetch_assoc($pending_leave_result);
    $pending_count += $row['count'];
}

// Count pending resignation requests
$pending_resignation_query = "SELECT COUNT(*) as count FROM resignations 
WHERE employee_id = '$employee_id' AND resignation_status = 'Pending'";
$pending_resignation_result = mysqli_query($con, $pending_resignation_query);
if ($pending_resignation_result) {
    $row = mysqli_fetch_assoc($pending_resignation_result);
    $pending_count += $row['count'];
}

// Count pending reports (if applicable)
$pending_reports_query = "SELECT COUNT(*) as count FROM reports 
WHERE reporter_id = '$employee_id' AND status = 'Pending'";
$pending_reports_result = mysqli_query($con, $pending_reports_query);
if ($pending_reports_result) {
    $row = mysqli_fetch_assoc($pending_reports_result);
    $pending_count += $row['count'];
}

// Get employee's department info
$dept_query = "SELECT d.department_name 
FROM employees e 
LEFT JOIN positions p ON e.position_id = p.position_id 
LEFT JOIN departments d ON p.department_id = d.department_id 
WHERE e.employee_id = '$employee_id'";
$dept_result = mysqli_query($con, $dept_query);
$department_name = "N/A";
if ($dept_result && mysqli_num_rows($dept_result) > 0) {
    $dept_row = mysqli_fetch_assoc($dept_result);
    $department_name = $dept_row['department_name'];
}

// Get employee count in the same department
$dept_count_query = "SELECT COUNT(*) as dept_count 
FROM employees e 
LEFT JOIN positions p ON e.position_id = p.position_id 
LEFT JOIN departments d ON p.department_id = d.department_id 
WHERE d.department_name = '$department_name' AND e.status = 'Active'";
$dept_count_result = mysqli_query($con, $dept_count_query);
$dept_count = 0;
if ($dept_count_result && mysqli_num_rows($dept_count_result) > 0) {
    $dept_count_row = mysqli_fetch_assoc($dept_count_result);
    $dept_count = $dept_count_row['dept_count'];
}

// Get overall performance rating (average of latest performance reviews)
$performance_query = "SELECT AVG(pr.rating) as avg_rating 
FROM performance_reviews pr 
WHERE pr.reviewee_id = '$employee_id' 
AND pr.period_id IN (SELECT period_id FROM rating_periods WHERE is_active = 1)";
$performance_result = mysqli_query($con, $performance_query);
$avg_rating = "N/A";
if ($performance_result && mysqli_num_rows($performance_result) > 0) {
    $performance_row = mysqli_fetch_assoc($performance_result);
    $avg_rating = $performance_row['avg_rating'] ? number_format($performance_row['avg_rating'], 1) : "N/A";
}

// Get employment status
$employment_query = "SELECT employment_status 
FROM employees 
WHERE employee_id = '$employee_id'";
$employment_result = mysqli_query($con, $employment_query);
$employment_status = "N/A";
if ($employment_result && mysqli_num_rows($employment_result) > 0) {
    $employment_row = mysqli_fetch_assoc($employment_result);
    $employment_status = $employment_row['employment_status'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Dashboard | Company Name</title>
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
          <h2 class="fw-bold">Employee Dashboard</h2>
          <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width:45px; height:45px;">E</div>
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
              <h4><?php echo $dept_count; ?></h4>
              <p class="mb-0">Your Department</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm text-center p-3">
              <i class="fas fa-star fa-2x text-success mb-2"></i>
              <h4><?php echo $avg_rating; ?></h4>
              <p class="mb-0">Your Overall Performance</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm text-center p-3">
              <i class="fas fa-clock fa-2x text-warning mb-2"></i>
              <h4><?php echo $pending_count; ?></h4>
              <p class="mb-0">Pending Requests</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm text-center p-3">
              <i class="fas fa-briefcase fa-2x text-danger mb-2"></i>
              <h4><?php echo $employment_status; ?></h4>
              <p class="mb-0">Employment Status</p>
            </div>
          </div>
        </div>

        <!-- Recent Activities -->
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activities</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-hover align-middle">
                <thead> 
                  <tr>
                    <th>Date</th>
                    <th>Activity</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($recent_activities)): ?>
                    <?php foreach ($recent_activities as $activity): ?>
                      <tr>
                        <td><?php echo date('M. j, Y', strtotime($activity['date'])); ?></td>
                        <td><?php echo htmlspecialchars($activity['activity']); ?></td>
                        <td>
                          <?php 
                          $status_class = '';
                          switch($activity['status']) {
                              case 'Pending':
                              case 'Pending':
                                  $status_class = 'bg-warning text-dark';
                                  break;
                              case 'Approved':
                              case 'Completed':
                              case 'Hired':
                                  $status_class = 'bg-success';
                                  break;
                              case 'Denied':
                              case 'Rejected':
                              case 'Cancelled':
                                  $status_class = 'bg-danger';
                                  break;
                              case 'Reviewed':
                              case 'In Process':
                                  $status_class = 'bg-info';
                                  break;
                              default:
                                  $status_class = 'bg-secondary';
                          }
                          ?>
                          <span class="badge <?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($activity['status']); ?>
                          </span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="3" class="text-center">No recent activities found.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-link me-2"></i>Quick Access</h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-4">
                <a href="leavePage.php" class="btn btn-outline-primary w-100"><i class="fas fa-calendar-alt me-2"></i>Request Leave</a>
              </div>
              <div class="col-md-4">
                <a href="performPage.php" class="btn btn-outline-success w-100"><i class="fas fa-chart-line me-2"></i>View Performance</a>
              </div>
              <div class="col-md-4">
                <a href="reportPage.php" class="btn btn-outline-danger w-100"><i class="fas fa-file-alt me-2"></i>File a Report</a>
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