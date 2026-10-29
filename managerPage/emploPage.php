<?php
session_start();
require "../functions.php";
require "../connection.php";
require "../EmployeeData.php";
redirectToLogin('Manager');

$currentEmployeeID = $_SESSION['employeeID'];

$managerDeptQuery = "SELECT p.department_id 
                     FROM employees e
                     JOIN positions p ON e.position_id = p.position_id
                     WHERE e.employee_id = '$currentEmployeeID'";
$managerDeptResult = mysqli_query($con, $managerDeptQuery);

if ($managerDeptResult && mysqli_num_rows($managerDeptResult) > 0) {
    $managerDept = mysqli_fetch_assoc($managerDeptResult);
    $managerDepartmentId = $managerDept['department_id'];
} else {
    $managerDepartmentId = 0; 
    echo "<script>alert('Error: Could not determine manager department.');</script>";
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

        <!-- Employees Table Card -->
        <div class="card shadow-sm mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-users me-2"></i>Employees List</h5>
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
                      $sql = "SELECT 
                          e.employee_id,
                          e.employment_status,
                          e.status,
                          e.candidate_id,
                          c.first_name,
                          c.last_name,
                          p.position_name,
                          d.department_name
                        FROM employees e
                        JOIN candidates c ON e.candidate_id = c.candidate_id
                        JOIN positions p ON e.position_id = p.position_id
                        JOIN departments d ON p.department_id = d.department_id
                        WHERE e.status = 'Active' 
                        AND d.department_id = '$managerDepartmentId'";//eto tatanggalin sa hr para lahat ng employee despite their department can be displayed sa hr page

                      $result = mysqli_query($con, $sql);

                      if (mysqli_num_rows($result) > 0) {
                          $employeeDetails = [];
                          
                          while($row = mysqli_fetch_assoc($result)) {
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
                          echo "<tr><td colspan='6' class='text-center'>No employees found</td></tr>";
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
                <button type="button" class="btn btn-warning" id="changePositionBtn">
                    <i class="fas fa-exchange-alt me-1"></i> Change Position
                </button>
                  <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                      <i class="fa-solid fa-ban me-1"></i> Close
                  </button>
              </div>
          </div>
      </div>
  </div>

<!-- Position Change Modal -->
<div class="modal fade" id="positionChangeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="handle_position_change.php" method="POST" id="positionChangeForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exchange-alt me-2"></i>Change Employee Position</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Current Position</label>
                            <input type="text" class="form-control" id="currentPositionDisplay" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Current Department</label>
                            <input type="text" class="form-control" id="currentDepartmentDisplay" disabled>
                        </div>
                    </div>
                    
                    <input type="hidden" name="employee_id" id="formEmployeeId">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select New Position</label>
                        <select class="form-select" name="proposed_position_id" id="newPositionSelect" required>
                            <option value="">-- Choose a position --</option>
                            <?php
                            // Query to get all available positions
                            $positionsQuery = "SELECT p.position_id, p.position_name, d.department_name, d.department_id 
                                             FROM positions p 
                                             JOIN departments d ON p.department_id = d.department_id 
                                             ORDER BY d.department_name, p.position_name";
                            $positionsResult = mysqli_query($con, $positionsQuery);
                            
                            $currentDepartmentPositions = [];
                            $otherDepartmentPositions = [];
                            
                            while ($position = mysqli_fetch_assoc($positionsResult)) {
                                if ($position['department_id'] == $managerDepartmentId) {
                                    $currentDepartmentPositions[] = $position;
                                } else {
                                    $otherDepartmentPositions[] = $position;
                                }
                            }
                            
                            // Display current department positions first
                            if (!empty($currentDepartmentPositions)) {
                                echo '<optgroup label="Current Department Positions">';
                                foreach ($currentDepartmentPositions as $position) {
                                    echo "<option value='{$position['position_id']}' data-dept='{$position['department_name']}'>
                                            {$position['position_name']} - {$position['department_name']}
                                          </option>";
                                }
                                echo '</optgroup>';
                            }
                            
                            // Display other department positions
                            if (!empty($otherDepartmentPositions)) {
                                echo '<optgroup label="Other Department Positions">';
                                foreach ($otherDepartmentPositions as $position) {
                                    echo "<option value='{$position['position_id']}' data-dept='{$position['department_name']}'>
                                            {$position['position_name']} - {$position['department_name']}
                                          </option>";
                                }
                                echo '</optgroup>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Change Type</label>
                        <select class="form-select" name="change_type" id="changeTypeSelect" required>
                            <option value="Promotion">Select Change Type</option>
                            <option value="Promote">Promote</option>
                            <option value="Demote">Demote</option>
                            <option value="Transfer">Transfer</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Change</label>
                        <textarea class="form-control" name="reason" id="changeReason" rows="3" placeholder="Explain the reason for this position change..." required></textarea>
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i> Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="scripts/emploPage.js"></script>
</body>
</html>