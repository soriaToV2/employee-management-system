<?php
session_start();
require "../functions.php";
require "../connection.php";
require "../EmployeeData.php";
redirectToLogin('HR');

// Handle employment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'promote') {
    $employeeId = mysqli_real_escape_string($con, $_POST['employee_id']);
    $newStatus = mysqli_real_escape_string($con, $_POST['employment_status']);
    
    // Update the employee's employment status
    $updateSql = "UPDATE employees SET employment_status = ? WHERE employee_id = ?";
    $stmt = mysqli_prepare($con, $updateSql);
    mysqli_stmt_bind_param($stmt, "ss", $newStatus, $employeeId);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Employee successfully promoted to {$newStatus}";
    } else {
        $_SESSION['error_message'] = "Error updating employee status: " . mysqli_error($con);
    }
    
    mysqli_stmt_close($stmt);
    
    // Redirect to clear POST data
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get filter parameters
$departmentFilter = isset($_GET['department']) ? $_GET['department'] : '';
$employeeIdFilter = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';

// Build the base query - FIXED: Removed applications table join
$sql = "SELECT 
        e.employee_id,
        e.employment_status,
        e.status,
        e.candidate_id,
        c.first_name,
        c.last_name,
        p.position_name,
        d.department_name,
        d.department_id
      FROM employees e
      JOIN candidates c ON e.candidate_id = c.candidate_id
      JOIN positions p ON e.position_id = p.position_id
      JOIN departments d ON p.department_id = d.department_id
      WHERE e.status = 'Active' ";

// Add filters if provided
if (!empty($departmentFilter)) {
    $sql .= " AND d.department_id = '" . mysqli_real_escape_string($con, $departmentFilter) . "'";
}

if (!empty($employeeIdFilter)) {
    $sql .= " AND e.employee_id LIKE '%" . mysqli_real_escape_string($con, $employeeIdFilter) . "%'";
}

$sql .= " ORDER BY e.employee_id";

$result = mysqli_query($con, $sql);

// Get all departments for the filter dropdown
$departmentsQuery = "SELECT department_id, department_name FROM departments ORDER BY department_name";
$departmentsResult = mysqli_query($con, $departmentsQuery);
$departments = [];
while ($dept = mysqli_fetch_assoc($departmentsResult)) {
    $departments[] = $dept;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Management | Company Name</title>
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

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                <div>
                <i class="fas fa-filter me-2"></i>
                <h5 class="mb-0 d-inline">Filter Employees</h5>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="department" class="form-label fw-semibold text-white">Department</label>
                    <select class="form-select border-secondary" id="department" name="department">
                    <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>" 
                                <?php echo ($departmentFilter == $dept['department_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="employee_id" class="form-label fw-semibold text-white">Employee ID</label>
                    <input type="text" class="form-control border-secondary" id="employee_id" name="employee_id" 
                    value="<?php echo htmlspecialchars($employeeIdFilter); ?>" 
                    placeholder="Enter employee ID">
                </div>
                <div class="col-md-2 d-grid gap-2">
                    <button type="submit" class="btn btn-primary fw-semibold">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                    <a href="?" class="btn btn-outline-primary fw-semibold">
                        <i class="fas fa-rotate me-1"></i> Clear
                    </a>
                </div>
                </form>
            </div>
        </div>

        <!-- Employees Table Card -->
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
            <div>
              <i class="fas fa-users me-2"></i>
              <h5 class="mb-0 d-inline">Employee Lists</h5>
            </div>
            <div class="text-white">
              <small>
                <?php 
                $totalEmployees = mysqli_num_rows($result);
                echo "Total: " . $totalEmployees . " employee" . ($totalEmployees != 1 ? 's' : '');
                
                if (!empty($departmentFilter) || !empty($employeeIdFilter)) {
                  echo " (Filtered)";
                }
                ?>
              </small>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered align-middle">
                <thead>
                  <tr>
                    <th>Employee Name</th>
                    <th>ID</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  if (mysqli_num_rows($result) > 0) {
                      $employeeDetails = [];
                      
                      while($row = mysqli_fetch_assoc($result)) {
                          // Determine badge color based on employment status
                          $badge_class = '';
                          switch($row['employment_status']) {
                              case 'Full Time': $badge_class = 'bg-success'; break;
                              case 'Part Time': $badge_class = 'bg-warning'; break;
                              case 'Probationary': $badge_class = 'bg-info'; break;
                              case 'Intern': $badge_class = 'bg-secondary'; break;
                              default: $badge_class = 'bg-secondary';
                          }

                          $employeeData = new EmployeeData($con, $row['candidate_id']);
                          
                          // Get basic info
                          $basicInfo = $employeeData->getBasicInfo();
                          $basicData = mysqli_fetch_assoc($basicInfo);
                          
                          // Get email
                          $emailResult = $employeeData->getEmail();
                          $email = 'Not Available';
                          if ($emailResult) {
                              $emailData = mysqli_fetch_assoc($emailResult);
                              $email = isset($emailData['login_identifier']) ? $emailData['login_identifier'] : 'Not Available';
                          }
                          
                          // Get education
                          $educationResult = $employeeData->getEducation();
                          $educationData = [];
                          if ($educationResult) {
                              while($edu = mysqli_fetch_assoc($educationResult)) {
                                  $educationData[] = [
                                      'level' => $edu['education_level'] ?? 'Not specified',
                                      'degree' => $edu['degree'] ?? 'Not applicable',
                                      'school' => $edu['school_name'] ?? 'Not specified',
                                      'year' => $edu['year_graduated'] ?? 'Not specified'
                                  ];
                              }
                          }
                          
                          // Get certifications
                          $certResult = $employeeData->getCertifications();
                          $certificationsData = [];
                          if ($certResult) {
                              while($cert = mysqli_fetch_assoc($certResult)) {
                                $certificationsData[] = [
                                  'certificate_name' => $cert['certificate_name'] ?? 'Unnamed Certificate',
                                  'file_link' => $cert['file_link'] ?? null,
                                ];
                              }
                          }
                          
                          // Get skills
                          $skillsResult = $employeeData->getSkills();
                          $skillsData = [];
                          if ($skillsResult) {
                              while($skill = mysqli_fetch_assoc($skillsResult)) {
                                  $skillsData[] = $skill['skill_name'] . ' (' . ($skill['proficiency_level'] ?? 'Not rated') . ')';
                              }
                          }
                          
                          // Get experience
                          $experienceResult = $employeeData->getExperience();
                          $experienceData = [];
                          if ($experienceResult) {
                              while($exp = mysqli_fetch_assoc($experienceResult)) {
                                  $experienceData[] = [
                                      'company' => $exp['company_name'] ?? 'Not specified',
                                      'position' => $exp['position_title'] ?? 'Not specified',
                                      'start_date' => $exp['start_date'] ?? 'Not specified',
                                      'end_date' => $exp['end_date'] ?? 'Present'
                                  ];
                              }
                          }

                          // Safely get values with isset checks
                          $birthDate = isset($basicData['date_of_birth']) ? $basicData['date_of_birth'] : 'Not specified';
                          $phone = isset($basicData['phone_number']) ? $basicData['phone_number'] : 'Not available';
                          $address = isset($basicData['address']) ? $basicData['address'] : 'Not available';

                          // Store all data in array
                          $employeeDetails[$row['employee_id']] = [
                              'basic' => [
                                  'name' => $row['first_name'] . ' ' . $row['last_name'],
                                  'employee_id' => $row['employee_id'],
                                  'department' => $row['department_name'],
                                  'position' => $row['position_name'],
                                  'birth_date' => $birthDate,
                                  'employment_status' => $row['employment_status'],
                                  'email' => $email,
                                  'phone' => $phone,
                                  'address' => $address
                              ],
                              'education' => $educationData,
                              'experience' => $experienceData,
                              'skills' => $skillsData,
                              'certifications' => $certificationsData,
                              'performance' => []
                          ];

                          // Fetch performance data for this employee
                          $performanceQuery = "
                              SELECT 
                                  rp.period_id,
                                  rp.period_name,
                                  rp.quarter,
                                  rp.year,
                                  rc.category_name,
                                  AVG(pr.rating) as avg_rating,
                                  COUNT(pr.question_id) as questions_rated
                              FROM performance_reviews pr
                              JOIN review_questions rq ON pr.question_id = rq.question_id
                              JOIN review_categories rc ON rq.category_id = rc.category_id
                              JOIN rating_periods rp ON pr.period_id = rp.period_id
                              WHERE pr.reviewee_id = '{$row['employee_id']}'
                              AND pr.rating IS NOT NULL
                              GROUP BY rp.period_id, rc.category_id
                              ORDER BY rp.year DESC, rp.quarter DESC, rc.category_name
                          ";

                          $performanceResult = mysqli_query($con, $performanceQuery);

                          if ($performanceResult && mysqli_num_rows($performanceResult) > 0) {
                              $performanceData = [];
                              while($perf = mysqli_fetch_assoc($performanceResult)) {
                                  $periodId = $perf['period_id'];
                                  if (!isset($performanceData[$periodId])) {
                                      $performanceData[$periodId] = [
                                          'period_name' => $perf['period_name'],
                                          'quarter' => $perf['quarter'],
                                          'year' => $perf['year'],
                                          'categories' => []
                                      ];
                                  }
                                  
                                  $performanceData[$periodId]['categories'][] = [
                                      'category_name' => $perf['category_name'],
                                      'avg_rating' => round($perf['avg_rating'], 2),
                                      'questions_rated' => $perf['questions_rated']
                                  ];
                              }
                              $employeeDetails[$row['employee_id']]['performance'] = $performanceData;
                          }

                          // Display table row
                          echo "<tr>
                              <td>{$row['first_name']} {$row['last_name']}</td>
                              <td>{$row['employee_id']}</td>
                              <td>{$row['department_name']}</td>
                              <td>{$row['position_name']}</td>
                              <td><span class='badge {$badge_class}'>{$row['employment_status']}</span></td>
                              <td>
                                  <button 
                                      class='btn btn-outline-primary btn-sm see-details-btn' 
                                      data-bs-toggle='modal' 
                                      data-bs-target='#employeeDetailsModal'
                                      data-employee-id='{$row['employee_id']}'
                                  >See Details</button>
                              </td>
                          </tr>";
                      }
                      
                      // Store all employee details in a JavaScript variable
                      echo "<script>const employeeDetails = " . json_encode($employeeDetails) . ";</script>";
                      
                  } else {
                      echo "<tr><td colspan='6' class='text-center'>No employees found" . 
                          ((!empty($departmentFilter) || !empty($employeeIdFilter)) ? " matching the filter criteria" : "") . 
                          "</td></tr>";
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Probationary Employees Table Card -->
        <div class="card shadow-sm mb-4">
        <div class="card-header bg-warning text-dark d-flex align-items-center justify-content-between">
            <div>
            <i class="fas fa-clock me-2"></i>
            <h5 class="mb-0 d-inline">Probationary Employees</h5>
            </div>
            <div class="text-dark">
            <small>
                Employees on probation period (6 months)
            </small>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                <tr>
                    <th>Employee Name</th>
                    <th>ID</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Date Hired</th>
                    <th>Days Remaining</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php
                // Query for probationary employees
                $probationSql = "SELECT 
                        e.employee_id,
                        e.date_hired,
                        e.candidate_id,
                        c.first_name,
                        c.last_name,
                        p.position_name,
                        d.department_name
                        FROM employees e
                        JOIN candidates c ON e.candidate_id = c.candidate_id
                        JOIN positions p ON e.position_id = p.position_id
                        JOIN departments d ON p.department_id = d.department_id
                        WHERE e.employment_status = 'Probationary' 
                        AND e.status = 'Active'
                        ORDER BY e.date_hired";

                $probationResult = mysqli_query($con, $probationSql);
                
                if (mysqli_num_rows($probationResult) > 0) {
                    while($probationRow = mysqli_fetch_assoc($probationResult)) {
                        $dateHired = new DateTime($probationRow['date_hired']);
                        $today = new DateTime();
                        $probationEnd = clone $dateHired;
                        $probationEnd->modify('+6 months');
                        
                        $interval = $today->diff($probationEnd);
                        $daysRemaining = $interval->days;
                        $isPastDue = $today > $probationEnd;
                        
                        // Determine badge color based on days remaining
                        $badge_class = '';
                        if ($isPastDue) {
                            $badge_class = 'bg-danger';
                            $daysText = 'Overdue';
                        } elseif ($daysRemaining <= 7) {
                            $badge_class = 'bg-warning';
                            $daysText = $daysRemaining . ' days';
                        } else {
                            $badge_class = 'bg-info';
                            $daysText = $daysRemaining . ' days';
                        }
                        
                        echo "<tr>
                            <td>{$probationRow['first_name']} {$probationRow['last_name']}</td>
                            <td>{$probationRow['employee_id']}</td>
                            <td>{$probationRow['department_name']}</td>
                            <td>{$probationRow['position_name']}</td>
                            <td>" . $dateHired->format('M d, Y') . "</td>
                            <td><span class='badge {$badge_class}'>{$daysText}</span></td>
                            <td>";
                        
                        // Only show edit button if probation period is completed
                        if ($isPastDue) {
                            echo "<button 
                                class='btn btn-success btn-sm promote-btn' 
                                data-bs-toggle='modal' 
                                data-bs-target='#promoteEmployeeModal'
                                data-employee-id='{$probationRow['employee_id']}'
                                data-employee-name='{$probationRow['first_name']} {$probationRow['last_name']}'
                            >
                                <i class='fas fa-user-check me-1'></i> Promote to Regular
                            </button>";
                        } else {
                            echo "<span class='text-muted'>Pending</span>";
                        }
                        
                        echo "</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='text-center'>No probationary employees found</td></tr>";
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

  <!-- Employee Details Modal -->
  <div class="modal fade" id="employeeDetailsModal" tabindex="-1" aria-hidden="true"> 
      <div class="modal-dialog modal-dialog-centered modal-xl">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title"><i class="fas fa-user me-2"></i>Employee Details</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                  <!-- Personal Information -->
                  <h6 class="fw-bold text-primary mb-3" style="font-size: 20px;"><i class="fa-solid fa-id-card me-2"></i>Personal Information</h6>
                  <div class="border border-secondary rounded p-3 mb-3">
                      <div class="row">
                          <div class="col-md-6 mb-3">
                              <label class="form-label">Full Name</label>
                              <input type="text" class="form-control" id="empName" disabled>
                          </div>
                          <div class="col-md-3 mb-3">
                              <label class="form-label">Birth Date</label>
                              <input type="date" class="form-control" id="empBirth" disabled>
                          </div>
                          <div class="col-md-3 mb-3">
                              <label class="form-label">Employee ID</label>
                              <input type="text" class="form-control" id="empID" disabled>
                          </div>
                      </div>
                      <div class="row">
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Department</label>
                              <input type="text" class="form-control" id="empDepartment" disabled>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Position</label>
                              <input type="text" class="form-control" id="empPosition" disabled>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">Employment Status</label>
                              <input type="text" class="form-control" id="empStatus" disabled>
                          </div>
                      </div>
                  </div>

                  <!-- Contact Information -->
                  <h6 class="fw-bold text-primary mt-4 mb-3" style="font-size: 20px;"><i class="fa-solid fa-address-book me-2"></i>Contact Information</h6>
                  <div class="border border-secondary rounded p-3 mb-3">
                      <div class="row">
                          <div class="col-md-6 mb-3">
                              <label class="form-label">Email</label>
                              <input type="email" class="form-control" id="empEmail" disabled>
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">Contact Number</label>
                              <input type="text" class="form-control" id="empContact" disabled>
                          </div>
                      </div>
                      <div class="mb-3">
                          <label class="form-label">Address</label>
                          <input type="text" class="form-control" id="empAddress" disabled>
                      </div>
                  </div>

                  <!-- Educational Background -->
                  <h6 class="fw-bold text-primary mt-4 mb-3" style="font-size: 20px;"><i class="fa-solid fa-graduation-cap me-2"></i>Educational Background</h6>
                  <div id="educationSection">
                      <!-- Education content will be dynamically inserted here -->
                  </div>

                  <!-- Work Experience -->
                  <h6 class="fw-bold text-primary mt-4 mb-3" style="font-size: 20px;"><i class="fa-solid fa-briefcase me-2"></i>Work Experience</h6>
                  <div id="experienceSection">
                      <!-- Work experience content will be dynamically inserted here -->
                  </div>

                  <!-- Skills -->
                  <h6 class="fw-bold text-primary mt-4 mb-3" style="font-size: 20px;"><i class="fa-solid fa-code me-2"></i>Skills</h6>
                  <div id="skillsSection">
                      <!-- Skills content will be dynamically inserted here -->
                  </div>

                  <!-- Certifications -->
                  <h6 class="fw-bold text-primary mt-4 mb-3" style="font-size: 20px;"><i class="fa-solid fa-certificate me-2"></i>Certifications</h6>
                  <div id="certificationsSection">
                      <!-- Certifications content will be dynamically inserted here -->
                  </div>

                  <!-- Performance Reviews -->
                  <h6 class="fw-bold text-primary mt-4 mb-3" style="font-size: 20px;">
                      <i class="fa-solid fa-chart-line me-2"></i>Performance Reviews
                  </h6>
                  <div id="performanceSection">
                      <!-- Performance review content will be dynamically inserted here -->
                  </div>

              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                      <i class="fa-solid fa-ban me-1"></i> Close
                  </button>
              </div>
          </div>
      </div>
  </div>

  <!-- Promote Employee Modal -->
<div class="modal fade" id="promoteEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-check me-2"></i>Promote Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        This will change the employee's employment status from 'Probationary' to 'Full Time'.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Employee Name</label>
                        <input type="text" class="form-control" id="promoteEmpName" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Employee ID</label>
                        <input type="text" class="form-control" id="promoteEmpID" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">New Employment Status</label>
                        <select class="form-select" name="employment_status" required>
                            <option value="Full Time" selected>Full Time - Regular Employee</option>
                            <option value="Part Time">Part Time</option>
                        </select>
                    </div>
                    
                    <input type="hidden" name="employee_id" id="promoteEmployeeID">
                    <input type="hidden" name="action" value="promote">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-user-check me-1"></i> Confirm Promotion
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

  <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
document.addEventListener('DOMContentLoaded', function () {
    const promoteButtons = document.querySelectorAll('.promote-btn');
    const promoteEmpName = document.getElementById('promoteEmpName');
    const promoteEmpID = document.getElementById('promoteEmpID');
    const promoteEmployeeID = document.getElementById('promoteEmployeeID');

    promoteButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const employeeId = this.dataset.employeeId;
            const employeeName = this.dataset.employeeName;

            promoteEmpName.value = employeeName;
            promoteEmpID.value = employeeId;
            promoteEmployeeID.value = employeeId;
        });
    });

    // Existing employee details functionality
    const detailButtons = document.querySelectorAll('.see-details-btn');

    detailButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const employeeId = this.dataset.employeeId;
            const employeeData = employeeDetails[employeeId];

            if (!employeeData) {
                alert('Employee data not found!');
                return;
            }

            populateModal(employeeData);
        });
    });

    function populateModal(data) {
        const basic = data.basic;

        // Basic Information
        document.getElementById('empName').value = basic.name || '';
        document.getElementById('empID').value = basic.employee_id || '';
        document.getElementById('empDepartment').value = basic.department || '';
        document.getElementById('empPosition').value = basic.position || '';
        document.getElementById('empBirth').value = basic.birth_date || '';
        document.getElementById('empStatus').value = basic.employment_status || '';
        document.getElementById('empEmail').value = basic.email || '';
        document.getElementById('empContact').value = basic.phone || '';
        document.getElementById('empAddress').value = basic.address || '';

        // Education
        const educationSection = document.getElementById('educationSection');
        educationSection.innerHTML = '';
        if (data.education && data.education.length > 0) {
            data.education.forEach(edu => {
                const educationDiv = document.createElement('div');
                educationDiv.className = 'border border-secondary rounded p-3 mb-3';
                educationDiv.innerHTML = `
                      <div class="row">
                          <div class="col-md-3 mb-3">
                              <label class="form-label">Education Level</label>
                              <input type="text" class="form-control" value="${edu.level}" disabled>
                          </div>
                          <div class="col-md-3 mb-3">
                              <label class="form-label">Degree</label>
                              <input type="text" class="form-control" value="${edu.degree}" disabled>
                          </div>
                          <div class="col-md-4 mb-3">
                              <label class="form-label">School</label>
                              <input type="text" class="form-control" value="${edu.school}" disabled>
                          </div>
                          <div class="col-md-2 mb-3">
                              <label class="form-label">Year Graduated</label>
                              <input type="text" class="form-control" value="${edu.year}" disabled>
                          </div>
                      </div>
                  `;
                educationSection.appendChild(educationDiv);
            });
        } else {
            educationSection.innerHTML = `
                  <div class="border border-secondary rounded p-3 mb-3">
                      <div class="mb-3">
                          <label class="form-label">Education</label>
                          <input type="text" class="form-control" value="No education records found" disabled>
                      </div>
                  </div>
              `;
        }

        // Work Experience
        const experienceSection = document.getElementById('experienceSection');
        experienceSection.innerHTML = '';
        if (data.experience && data.experience.length > 0) {
            data.experience.forEach(exp => {
                // Format dates to "Month Day, Year" format
                const formatDate = (dateString) => {
                    if (!dateString || dateString === 'Not specified' || dateString === 'Present') {
                        return dateString;
                    }

                    try {
                        const date = new Date(dateString);
                        if (isNaN(date.getTime())) {
                            return dateString; // Return original if invalid date
                        }

                        return date.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                    } catch (error) {
                        return dateString; // Return original if error
                    }
                };

                const startDate = formatDate(exp.start_date);
                const endDate = formatDate(exp.end_date);

                const experienceDiv = document.createElement('div');
                experienceDiv.className = 'border border-secondary rounded p-3 mb-3';
                experienceDiv.innerHTML = `
                      <div class="row">
                          <div class="col-md-6 mb-3">
                              <label class="form-label">Company</label>
                              <input type="text" class="form-control" value="${exp.company}" disabled>
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">Position</label>
                              <input type="text" class="form-control" value="${exp.position}" disabled>
                          </div>
                      </div>
                      <div class="row">
                          <div class="col-md-6 mb-3">
                              <label class="form-label">Start Date</label>
                              <input type="text" class="form-control" value="${startDate}" disabled>
                          </div>
                          <div class="col-md-6 mb-3">
                              <label class="form-label">End Date</label>
                              <input type="text" class="form-control" value="${endDate}" disabled>
                          </div>
                      </div>
                  `;
                experienceSection.appendChild(experienceDiv);
            });
        } else {
            experienceSection.innerHTML = `
                  <div class="border border-secondary rounded p-3 mb-3">
                      <div class="mb-3">
                          <label class="form-label">Work Experience</label>
                          <input type="text" class="form-control" value="No work experience found" disabled>
                      </div>
                  </div>
              `;
        }

        // Skills
        const skillsSection = document.getElementById('skillsSection');
        skillsSection.innerHTML = '';
        if (data.skills && data.skills.length > 0) {
            const skillsDiv = document.createElement('div');
            skillsDiv.className = 'border border-secondary rounded p-3 mb-3';
            skillsDiv.innerHTML = `
                  <div class="mb-3">
                      <label class="form-label">Skills</label>
                      <input type="text" class="form-control" value="${data.skills.join(', ')}" disabled>
                  </div>
              `;
            skillsSection.appendChild(skillsDiv);
        } else {
            skillsSection.innerHTML = `
                  <div class="border border-secondary rounded p-3 mb-3">
                      <div class="mb-3">
                          <label class="form-label">Skills</label>
                          <input type="text" class="form-control" value="No skills found" disabled>
                      </div>
                  </div>
              `;
        }

        // Certifications
        const certificationsSection = document.getElementById('certificationsSection');
        certificationsSection.innerHTML = '';
        if (data.certifications && data.certifications.length > 0) {
            data.certifications.forEach(cert => {
                const certDiv = document.createElement('div');
                certDiv.className = 'border border-secondary rounded p-3 mb-3';

                // Check if file link exists and create appropriate HTML
                let fileHtml = '';
                if (cert.file_link && cert.file_link !== 'Cant be found' && cert.file_link !== 'Not specified') {
                    // Create a clickable text link
                    fileHtml = `
                          <div class="mb-3">
                              <label class="form-label">Certificate File</label>
                              <div>
                                  <a href="${cert.file_link}" target="_blank" class="text-primary text-decoration-none">
                                      <i class="fa-solid fa-file-pdf me-1"></i> View Certificate
                                  </a>
                              </div>
                          </div>
                      `;
                } else {
                    // Show message if no file available
                    fileHtml = `
                          <div class="mb-3">
                              <label class="form-label">Certificate File</label>
                              <input type="text" class="form-control" value="No file available" disabled>
                          </div>
                      `;
                }

                certDiv.innerHTML = `
                      <div class="row">
                          <div class="col-md-6 mb-3">
                              <label class="form-label">Certification Name:</label>
                              <input type="text" class="form-control" value="${cert.certificate_name}" disabled>
                          </div>
                      </div>
                      ${fileHtml}
                  `;
                certificationsSection.appendChild(certDiv);
            });
        } else {
            certificationsSection.innerHTML = `
                  <div class="border border-secondary rounded p-3 mb-3">
                      <div class="mb-3">
                          <label class="form-label">Certifications</label>
                          <input type="text" class="form-control" value="No certifications found" disabled>
                      </div>
                  </div>
              `;
        }

        populatePerformanceSection(data);
    }

    function populatePerformanceSection(data) {
        const performanceSection = document.getElementById('performanceSection');
        performanceSection.innerHTML = '';

        if (data.performance && Object.keys(data.performance).length > 0) {
            Object.values(data.performance).forEach(period => {
                const periodDiv = document.createElement('div');
                periodDiv.className = 'border border-secondary rounded p-3 mb-3';

                let categoriesHtml = '';
                period.categories.forEach(category => {
                    // Create a visual rating bar
                    const ratingPercent = (category.avg_rating / 5) * 100;
                    categoriesHtml += `
                          <div class="row align-items-center mb-2">
                              <div class="col-md-6">
                                  <label class="form-label mb-1">${category.category_name}</label>
                              </div>
                              <div class="col-md-4">
                                  <div class="progress" style="height: 20px;">
                                      <div class="progress-bar ${getRatingColor(category.avg_rating)}" 
                                          role="progressbar" 
                                          style="width: ${ratingPercent}%"
                                          aria-valuenow="${category.avg_rating}" 
                                          aria-valuemin="1" 
                                          aria-valuemax="5">
                                          ${category.avg_rating}/5
                                      </div>
                                  </div>
                              </div>
                              <div class="col-md-2">
                                  <small class="text-muted">${category.questions_rated} questions</small>
                              </div>
                          </div>
                      `;
                });

                // Calculate overall average for the period
                const overallAvg = period.categories.reduce((sum, cat) => sum + cat.avg_rating, 0) / period.categories.length;
                const overallPercent = (overallAvg / 5) * 100;

                periodDiv.innerHTML = `
                      <div class="d-flex justify-content-between align-items-center mb-3">
                          <h6 class="mb-0 fw-bold">${period.period_name}</h6>
                          <span class="badge bg-primary">${period.quarter} ${period.year}</span>
                      </div>
                      ${categoriesHtml}
                      <div class="row align-items-center mt-3 pt-3 border-top">
                          <div class="col-md-6">
                              <label class="form-label mb-1 fw-bold">Overall Average</label>
                          </div>
                          <div class="col-md-4">
                              <div class="progress" style="height: 25px;">
                                  <div class="progress-bar ${getRatingColor(overallAvg)} fw-bold" 
                                      role="progressbar" 
                                      style="width: ${overallPercent}%"
                                      aria-valuenow="${overallAvg.toFixed(2)}" 
                                      aria-valuemin="1" 
                                      aria-valuemax="5">
                                      ${overallAvg.toFixed(2)}/5
                                  </div>
                              </div>
                          </div>
                          <div class="col-md-2">
                              <small class="text-muted">${period.categories.length} categories</small>
                          </div>
                      </div>
                  `;

                performanceSection.appendChild(periodDiv);
            });
        } else {
            performanceSection.innerHTML = `
                  <div class="border border-secondary rounded p-3 mb-3">
                      <div class="text-center text-muted">
                          <i class="fa-solid fa-chart-line fa-2x mb-2"></i>
                          <p class="mb-0">No performance reviews available</p>
                      </div>
                  </div>
              `;
        }
    }

    // Helper function to determine rating color
    function getRatingColor(rating) {
        if (rating >= 4) return 'bg-success';
        if (rating >= 3) return 'bg-info';
        if (rating >= 2) return 'bg-warning';
        return 'bg-danger';
    }
});

  </script>
</body>
</html>