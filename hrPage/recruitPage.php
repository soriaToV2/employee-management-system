<?php
session_start();
require "../functions.php";
require "../connection.php";
redirectToLogin('HR');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

  // Para sa editing ng hiring requests
  if (isset($_POST['hireRequestSubmit'])) {
      $request_id = mysqli_real_escape_string($con, $_POST['request_id']);
      $status = mysqli_real_escape_string($con, $_POST['hireRequestStatus']);
      
      $update_query = "UPDATE hiring_requests SET status = '$status' WHERE request_id = '$request_id'";

      mysqli_begin_transaction($con);
      
      if (mysqli_query($con, $update_query)) {
        mysqli_commit($con);
          $_SESSION['message'] = "Hiring request updated successfully!";
          $_SESSION['message_type'] = "success";
      } else {
        mysqli_rollback($con);
          $_SESSION['message'] = "Error updating hiring request: " . mysqli_error($con);
          $_SESSION['message_type'] = "error";
      }
      
      header("Location: " . $_SERVER['PHP_SELF']);
      exit();
  }
    
    // Post new recruitment
    if (isset($_POST['postRecruit'])) {
        $job_title = mysqli_real_escape_string($con, $_POST['jobTitle']);
        $position_id = mysqli_real_escape_string($con, $_POST['position_id']);
        $min_salary = mysqli_real_escape_string($con, $_POST['minSalary']);
        $max_salary = mysqli_real_escape_string($con, $_POST['maxSalary']);
        $description = mysqli_real_escape_string($con, $_POST['jobDescription']);
        $requirements = mysqli_real_escape_string($con, $_POST['requirements']);
        
        $insert_query = "INSERT INTO recruitment_posts (job_title, position_id, min_salary, max_salary, description, requirements, post_date) 
                        VALUES ('$job_title', '$position_id', '$min_salary', '$max_salary', '$description', '$requirements', NOW())";

        mysqli_begin_transaction($con);
        if (mysqli_query($con, $insert_query)) {
          mysqli_commit($con);
            $_SESSION['message'] = "Recruitment post created successfully!";
            $_SESSION['message_type'] = "success";
        } else {
          mysqli_rollback($con);
            $_SESSION['message'] = "Error creating recruitment post: " . mysqli_error($con);
            $_SESSION['message_type'] = "error";
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // Edit recruitment post
    if (isset($_POST['editRecruit'])) {
        $post_id = mysqli_real_escape_string($con, $_POST['editPostId']);
        $job_title = mysqli_real_escape_string($con, $_POST['jobTitle']);
        $position_id = mysqli_real_escape_string($con, $_POST['position_id']);
        $min_salary = mysqli_real_escape_string($con, $_POST['minSalary']);
        $max_salary = mysqli_real_escape_string($con, $_POST['maxSalary']);
        $description = mysqli_real_escape_string($con, $_POST['jobDescription']);
        $requirements = mysqli_real_escape_string($con, $_POST['requirements']);
        
        $update_query = "UPDATE recruitment_posts 
                        SET job_title = '$job_title', 
                            position_id = '$position_id', 
                            min_salary = '$min_salary',
                            max_salary = '$max_salary',
                            description = '$description', 
                            requirements = '$requirements'
                        WHERE post_id = '$post_id'";
        mysqli_begin_transaction($con);
        if (mysqli_query($con, $update_query)) {
          mysqli_commit($con);
            $_SESSION['message'] = "Recruitment post updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
          mysqli_rollback($con);
            $_SESSION['message'] = "Error updating recruitment post: " . mysqli_error($con);
            $_SESSION['message_type'] = "error";
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // Delete recruitment post
    if (isset($_POST['deletePost'])) {
        $post_id = mysqli_real_escape_string($con, $_POST['deletePostId']);
        
        $delete_query = "DELETE FROM recruitment_posts WHERE post_id = '$post_id'";
        
        mysqli_begin_transaction($con);
        if (mysqli_query($con, $delete_query)) {
          mysqli_commit($con);
            $_SESSION['message'] = "Recruitment post deleted successfully!";
            $_SESSION['message_type'] = "success";
        } else {
          mysqli_rollback($con);
            $_SESSION['message'] = "Error deleting recruitment post: " . mysqli_error($con);
            $_SESSION['message_type'] = "error";
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    if (isset($_POST['submitEditCandidate'])) {
    $application_id = mysqli_real_escape_string($con, $_POST['application_id']);
    $candidate_id = mysqli_real_escape_string($con, $_POST['candidate_id']);
    $position_id = mysqli_real_escape_string($con, $_POST['position_id']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    
    // Handle interview date
    $interview_date_value = "NULL";
    if (($status == 'Initial Interview' || $status == 'Final Interview') && !empty($_POST['interview_date'])) {
        $interview_date = mysqli_real_escape_string($con, $_POST['interview_date']);
        $interview_date_value = "'$interview_date'";
    }
    
    // Handle rank and comments
    $rank_value = empty($_POST['rank']) ? "NULL" : "'" . mysqli_real_escape_string($con, $_POST['rank']) . "'";
    $comments_value = empty($_POST['comments']) ? "NULL" : "'" . mysqli_real_escape_string($con, $_POST['comments']) . "'";
    
    mysqli_begin_transaction($con);
    
    try {
        // Get candidate email and position name for notification
        $candidate_info_query = "SELECT u.login_identifier as email, c.first_name, c.last_name, p.position_name 
                               FROM applications a 
                               JOIN candidates c ON a.candidate_id = c.candidate_id 
                               JOIN positions p ON a.position_id = p.position_id 
                               join user_accounts u on u.account_id = c.candidate_id
                               WHERE a.application_id = '$application_id'";
        $candidate_info_result = mysqli_query($con, $candidate_info_query);
        $candidate_info = mysqli_fetch_assoc($candidate_info_result);
        
        // Update the application status
        $update_candidate_query = "UPDATE applications SET status = '$status', interview_date = $interview_date_value, rank = $rank_value, 
                                  hr_comment = $comments_value 
                                  WHERE application_id = '$application_id'";
        
        if (!mysqli_query($con, $update_candidate_query)) {
            throw new Exception("Error updating candidate application: " . mysqli_error($con));
        }
        
        
        $candidateEmail = $candidate_info['email'];
        $candidateName = $candidate_info['first_name'] . ' ' . $candidate_info['last_name'];
        $positionName = $candidate_info['position_name'];
        
        if ($status == 'Hired') {
            $check_employee_query = "SELECT * FROM employees WHERE candidate_id = '$candidate_id'";
            $check_result = mysqli_query($con, $check_employee_query);
            
            if (mysqli_num_rows($check_result) == 0) {
                // Generate employee ID
                $current_year = date('y');
                $employee_id = $current_year . '-' . $candidate_id;
                $default_password = password_hash($employee_id, PASSWORD_BCRYPT);
                
                // Create user account
                $create_user_query = "INSERT INTO user_accounts (login_identifier, password, user_type, login_attempts) 
                                    VALUES ('$employee_id', '$default_password', 'Employee', 0)";
                
                if (!mysqli_query($con, $create_user_query)) {
                    throw new Exception("Error creating user account: " . mysqli_error($con));
                }
                
                // Get the newly created account_id
                $account_id = mysqli_insert_id($con);
                
                // Create employee record WITH position_id
                $current_date = date('Y-m-d');
                $create_employee_query = "INSERT INTO employees (employee_id, account_id, candidate_id, position_id, date_hired, employment_status, status) 
                                        VALUES ('$employee_id', '$account_id', '$candidate_id', '$position_id', '$current_date', 'Probationary', 'Active')";
                
                if (!mysqli_query($con, $create_employee_query)) {
                    throw new Exception("Error creating employee record: " . mysqli_error($con));
                }
                $emailSent = sendHiredEmail(
                  $candidateEmail, 
                  $candidateName, 
                  $positionName, 
                  $status, 
                  $employee_id
                );
                $successMessage = "Candidate application updated successfully and employee account created! Employee ID: " . $employee_id;
            } else {
                $successMessage = "Candidate application updated successfully! (Employee account already exists)";
            }
        } else {
          $emailSent = sendApplicationUpdateEmail(
            $candidateEmail, 
            $candidateName, 
            $positionName, 
            $status, 
            $interview_date_value !== "NULL" ? $interview_date : null
          );
          $successMessage = "Candidate application updated successfully!";
        }
        
        // Add email status to message
        if ($emailSent) {
            $successMessage .= " Email notification sent to candidate.";
        } else {
            $successMessage .= " Failed to send email notification.";
        }
        
        $_SESSION['message'] = $successMessage;
        mysqli_commit($con);
        $_SESSION['message_type'] = "success";
        
    } catch (Exception $e) {
        mysqli_rollback($con);
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

}

// Get positions for dropdown
$positions_query = "SELECT position_id, position_name FROM positions ORDER BY position_name";
$positions_result = mysqli_query($con, $positions_query);

// Get recruitment posts data
$recruitQuery = "SELECT 
    hr.request_id,
    d.department_name AS 'Department',
    p.position_name AS 'Position',
    hr.employment_type AS 'Employment Type',
    hr.num_people AS 'Number of People',
    hr.justification AS 'Reason',
    hr.status AS 'Request Status'
FROM hiring_requests hr
INNER JOIN positions p ON hr.position_id = p.position_id
INNER JOIN departments d ON p.department_id = d.department_id
ORDER BY hr.date_requested DESC;";
$recruit = mysqli_query($con, $recruitQuery);

$postRecruitmentQuery = "SELECT 
                    rp.post_id,
                    rp.job_title AS 'Job Title',
                    p.position_id,
                    p.position_name AS 'Position',
                    d.department_name AS 'Department',
                    rp.min_salary,
                    rp.max_salary,
                    CONCAT(rp.min_salary, ' - ', rp.max_salary) AS 'Salary',
                    rp.description AS 'Job Description',
                    rp.requirements AS 'Requirements',
                    rp.post_date AS 'Date Posted'
                FROM recruitment_posts rp
                INNER JOIN positions p ON rp.position_id = p.position_id
                INNER JOIN departments d ON p.department_id = d.department_id
                ORDER BY rp.post_date DESC";
$posts = mysqli_query($con, $postRecruitmentQuery);

$candidatesQuery = "SELECT 
                    a.application_id,
                    c.candidate_id,
                    CONCAT(c.first_name, ' ', c.last_name) AS 'Candidate Name',
                    p.position_id,
                    p.position_name AS 'Position',
                    a.status AS 'Status',
                    a.interview_date AS 'Date of Interview',
                    a.rank AS 'Rank'
                FROM applications a
                INNER JOIN candidates c ON a.candidate_id = c.candidate_id
                INNER JOIN positions p ON a.position_id = p.position_id
                ORDER BY a.date_applied DESC";
$candidates = mysqli_query($con, $candidatesQuery);

?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Company Name - HR Recruitment</title>
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
          <h2 class="fw-bold">Recruitment</h2>
          <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width:45px; height:45px;">HR</div>
            <div>
              <h6 class="mb-0"><?php echo $_SESSION['employeeName']; ?></h6>
              <small><?php echo $_SESSION['employeePosition']; ?></small>
            </div>
          </div>
        </div>

        <!-- Display Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type'] == 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>

        <!-- HIRING REQUEST -->
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white d-flex align-items-center">
            <i class="fas fa-user-plus me-2"></i>
            <h5 class="mb-0">Hiring Requests</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Employment Type</th>
                    <th>Number of People</th>
                    <th>Reason</th>
                    <th>Request Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  while ($recruitRow = mysqli_fetch_assoc($recruit)) {
                    ?>
                      <tr>
                        <td><?php echo $recruitRow['Department'];?></td>
                        <td><?php echo $recruitRow['Position'];?></td>
                        <td><?php echo $recruitRow['Employment Type'];?></td>
                        <td><?php echo $recruitRow['Number of People'];?></td>
                        <td><?php echo $recruitRow['Reason'];?></td>
                        <td><?php echo $recruitRow['Request Status'];?></td>
                        <td>
                          <button onclick="showHireRequestModal(<?php echo $recruitRow['request_id'] ;?>)" class="btn btn-outline-primary btn-sm">
                            Edit
                          </button>
                        </td>
                      </tr>
                    <?php
                  }
                  ?>

                </tbody>
              </table>
            </div>
          </div>
        </div><br>

        <!-- Recruitment Posts -->
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white d-flex align-items-center">
            <i class="fas fa-user-plus me-2"></i>
            <h5 class="mb-0">Posted Recruitment</h5>
          </div>

          <div class="card-body">
            <!-- Button outside the table -->
            <div class="d-flex justify-content-end mb-3">
              <button onclick="showPostRecruitModal()" class="btn btn-outline-primary fw-semibold">
                <i class="fas fa-plus-circle me-1"></i> POST A RECRUITMENT
              </button>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Job Title</th>
                    <th>Department</th>
                    <th>Salary</th>
                    <th>Date Posted</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  while ($postRow = mysqli_fetch_assoc($posts)) {
                    $postDate = new DateTime($postRow['Date Posted']);
                    $formattedDate = $postDate->format('M d, Y');
                  ?>
                    <tr>
                      <td><?php echo $postRow['Job Title']; ?></td>
                      <td style="display: none;"><?php echo $postRow['Position']; ?></td>
                      <td><?php echo $postRow['Department']; ?></td>
                      <td><?php echo $postRow['Salary']; ?></td>
                      <td style="display: none;"><?php echo $postRow['Job Description']; ?></td>
                      <td style="display: none;"><?php echo $postRow['Requirements']; ?></td>
                      <td><?php echo $formattedDate; ?></td>
                      <td>
                        <button onclick="showEditRecruitModal(
                          <?php echo $postRow['post_id']; ?>,
                          '<?php echo htmlspecialchars($postRow['Job Title'], ENT_QUOTES); ?>',
                          '<?php echo $postRow['position_id']; ?>',
                          '<?php echo $postRow['min_salary']; ?>',
                          '<?php echo $postRow['max_salary']; ?>',
                          `<?php echo htmlspecialchars($postRow['Job Description'], ENT_QUOTES); ?>`,
                          `<?php echo htmlspecialchars($postRow['Requirements'], ENT_QUOTES); ?>`
                        )" class="btn btn-outline-primary btn-sm">Edit/View</button>

                        <button onclick="showDeleteRecruitModal(<?php echo $postRow['post_id']; ?>)" class="btn btn-outline-danger btn-sm">Delete</button>
                      </td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <br>

        <!-- Candidate Table Card -->
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white d-flex align-items-center">
            <i class="fas fa-user-plus me-2"></i>
            <h5 class="mb-0">Candidates for Recruitment</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Candidate Name</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th>Date of Interview</th>
                    <th>Rank</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  while($candidateRow = mysqli_fetch_assoc(result: $candidates)) {
                    $candidateId = $candidateRow['candidate_id'];
                    // Personal info
                    $personalInfoQuery = "SELECT * FROM candidates WHERE candidate_id = $candidateId";
                    $personalInfoResult = mysqli_query($con, $personalInfoQuery);
                    $personalInfo = mysqli_fetch_assoc($personalInfoResult);
                    
                    // Educational background info
                    $educBgQuery = "SELECT * FROM educational_background WHERE candidate_id = $candidateId";
                    $educBgResult = mysqli_query($con, $educBgQuery);
                    $educBgInfo = [];
                    while($row = mysqli_fetch_assoc($educBgResult)) {
                        $educBgInfo[] = $row;
                    }
                    
                    // Work experience info
                    $workExperienceQuery = "SELECT * FROM work_experience WHERE candidate_id = $candidateId";
                    $workExperienceResult = mysqli_query($con, $workExperienceQuery);
                    $workExperienceInfo = [];
                    while($row = mysqli_fetch_assoc($workExperienceResult)) {
                        $workExperienceInfo[] = $row;
                    }
                    
                    // Skills                     
                    $skillsQuery = "SELECT * FROM skills WHERE candidate_id = $candidateId";
                    $skillsResult = mysqli_query($con, $skillsQuery);
                    $skillsInfo = [];
                    while($row = mysqli_fetch_assoc($skillsResult)) {
                        $skillsInfo[] = $row;
                    }
                    
                    // Certification info
                    $certificationQuery = "SELECT * FROM certifications WHERE candidate_id = $candidateId";
                    $certificationResult = mysqli_query($con, $certificationQuery);
                    $certificationInfo = [];
                    while($row = mysqli_fetch_assoc($certificationResult)) {
                        $certificationInfo[] = $row;
                    }
                    
                    // Documents info
                    $documentsQuery = "SELECT * FROM documents WHERE candidate_id = $candidateId";
                    $documentsResult = mysqli_query($con, $documentsQuery);
                    $documentsInfo = [];
                    while($row = mysqli_fetch_assoc($documentsResult)) {
                        $documentsInfo[] = $row;
                    }
                    
                    // Convert arrays to JSON for JavaScript
                    $educBgJson = htmlspecialchars(json_encode($educBgInfo), ENT_QUOTES, 'UTF-8');
                    $workExpJson = htmlspecialchars(json_encode($workExperienceInfo), ENT_QUOTES, 'UTF-8');
                    $skillsJson = htmlspecialchars(json_encode($skillsInfo), ENT_QUOTES, 'UTF-8');
                    $certsJson = htmlspecialchars(json_encode($certificationInfo), ENT_QUOTES, 'UTF-8');
                    $docsJson = htmlspecialchars(json_encode($documentsInfo), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr>
                      <td><?php echo $candidateRow['Candidate Name']; ?></td>
                      <td><?php echo $candidateRow['Position']; ?></td>
                      <td><?php echo $candidateRow['Status']; ?></td>
                      <td><?php echo $candidateRow['Date of Interview']; ?></td>
                      <td><?php echo $candidateRow['Rank']; ?></td>
                      <td>
                        <button class="btn btn-outline-primary btn-sm" onclick="showEditCandidateModal(
                          <?php echo $candidateRow['application_id']; ?>,
                          <?php echo $candidateRow['candidate_id']; ?>,
                          <?php echo $candidateRow['position_id']; ?>,
                          '<?php echo $candidateRow['Status']; ?>',
                          '<?php echo $candidateRow['Date of Interview']; ?>',
                          '<?php echo $candidateRow['Rank']; ?>',
                          `<?php echo htmlspecialchars($candidateRow['Comments'] ?? '', ENT_QUOTES); ?>`
                        )">Edit</button>

                        <button class="btn btn-outline-primary btn-sm" onclick="showViewCandidateInfo(
                          `<?php echo htmlspecialchars(json_encode($personalInfo), ENT_QUOTES, 'UTF-8'); ?>`,
                          `<?php echo $educBgJson; ?>`,
                          `<?php echo $workExpJson; ?>`,
                          `<?php echo $skillsJson; ?>`,
                          `<?php echo $certsJson; ?>`,
                          `<?php echo $docsJson; ?>`
                        )">View</button>
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
      </div>
    </div>
  </div>

  <!-- MODAL PARA MAEDIT YUNG CANDIDATES -->
  <div class="modal fade" id="editCandidateModal" tabindex="-1" aria-labelledby="editCandidateLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark text-white">
        <form action="recruitPage.php" method="post">
          <div class="modal-header">
            <h5 class="modal-title" id="editCandidateLabel">Edit Candidate Application</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Hidden IDs -->
            <input type="hidden" name="application_id" id="application_id">
            <input type="hidden" name="candidate_id" id="candidate_id">
            <input type="hidden" name="position_id" id="position_id">

            <!-- Status Dropdown -->
            <div class="mb-3">
              <label for="status" class="form-label fw-semibold">Application Status</label>
              <select name="status" id="status" class="form-select" onchange="toggleInterviewDateField()">
                <option value="">Select a status</option>
                <option value="Applied">Applied</option>
                <option value="Screening">Screening</option>
                <option value="Initial Interview">Initial Interview</option>
                <option value="Final Interview">Final Interview</option>
                <option value="Hired">Hired</option>
                <option value="Rejected">Rejected</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>

            <!-- Interview Date (conditionally shown) -->
            <div class="mb-3" id="interviewDateField" style="display:none;">
              <label for="interview_date" class="form-label fw-semibold">Interview Date & Time</label>
              <input type="datetime-local" name="interview_date" id="interview_date" class="form-control">
              <small class="text-muted">Required when status is "Initial Interview" or "Final Interview"</small>
            </div>

            <!-- Rank -->
            <div class="mb-3">
              <label for="rank" class="form-label fw-semibold">Rank (1-5)</label>
              <input type="number" min="1" max="5" name="rank" id="rank" class="form-control">
            </div>

            <!-- Comments -->
            <div class="mb-3">
              <label for="comments" class="form-label fw-semibold">Comments</label>
              <textarea name="comments" id="comments" class="form-control" rows="3"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" name="submitEditCandidate" class="btn btn-primary">Update</button>
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- MODAL PARA SA VIEWING OF CANDIDATES DOCUMENTS, WORK EXPERIENCES, SKILLS, CERTIFICATIONS, AND EDUCATIONAL BACKGROUND -->
  <div class="modal fade" id="viewCandidateInfoModal" tabindex="-1" aria-labelledby="viewCandidateInfoLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl">
          <div class="modal-content bg-dark text-white">
              <div class="modal-header">
                  <h5 class="modal-title" id="viewCandidateInfoLabel">Candidate Information</h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                  
                  <!-- Personal Information -->
                  <div class="mb-4">
                      <h6 class="border-bottom pb-2 mb-3">PERSONAL INFORMATION</h6>
                      <div id="personalInfoSection" class="ps-3">
                          <!-- Personal Information will be loaded here via JavaScript -->
                      </div>
                  </div>

                  <!-- Educational Background -->
                  <div class="mb-4">
                      <h6 class="border-bottom pb-2 mb-3">EDUCATIONAL BACKGROUND</h6>
                      <div id="educationalBackgroundSection" class="ps-3">
                          <!-- Educational Background will be loaded here via JavaScript -->
                      </div>
                  </div>

                  <!-- Work Experience -->
                  <div class="mb-4">
                      <h6 class="border-bottom pb-2 mb-3">WORK EXPERIENCE</h6>
                      <div id="workExperienceSection" class="ps-3">
                          <!-- Work Experience will be loaded here via JavaScript -->
                      </div>
                  </div>

                  <!-- Skills -->
                  <div class="mb-4">
                      <h6 class="border-bottom pb-2 mb-3">SKILLS</h6>
                      <div id="skillsSection" class="ps-3">
                          <!-- Skills will be loaded here via JavaScript -->
                      </div>
                  </div>

                  <!-- Certifications -->
                  <div class="mb-4">
                      <h6 class="border-bottom pb-2 mb-3">CERTIFICATIONS</h6>
                      <div id="certificationsSection" class="ps-3">
                          <!-- Certifications will be loaded here via JavaScript -->
                      </div>
                  </div>

                  <!-- Documents -->
                  <div class="mb-4">
                      <h6 class="border-bottom pb-2 mb-3">DOCUMENTS</h6>
                      <div id="documentsSection" class="ps-3">
                          <!-- Documents will be loaded here via JavaScript -->
                      </div>
                  </div>

              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
              </div>
          </div>
      </div>
  </div>

  <!-- MODAL PARA MAEDIT STATUS NG HIRING REQUESTS -->
  <div class="modal fade" id="hireRequestModal" tabindex="-1" aria-labelledby="hireRequestLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark text-white">
        <form action="recruitPage.php" method="post">
          <div class="modal-header">
            <h5 class="modal-title" id="hireRequestLabel">Edit Hiring Request</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Hidden input for request ID -->
            <input type="hidden" name="request_id" id="request_id">

            <!-- Status Dropdown -->
            <div class="mb-3">
              <label for="hireRequestStatus" class="form-label fw-semibold">Status</label>
              <select name="hireRequestStatus" id="hireRequestStatus" class="form-select">
                <option value="">Select</option>
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
                <option value="In Process">In Process</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" name="hireRequestSubmit" class="btn btn-primary">Edit</button>
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
          </div>
        </form>
      </div>
    </div>
  </div>


  <!-- MODAL PARA MAKAPAGPOST NG RECRUITMENT SI HR -->
  <div class="modal fade" id="postRecruitModal" tabindex="-1" aria-labelledby="postRecruitLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content bg-dark text-white">
        <form action="recruitPage.php" method="post">
          <div class="modal-header">
            <h5 class="modal-title" id="postRecruitLabel">Post a New Recruitment</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <!-- Job Title -->
            <div class="mb-3">
              <label for="jobTitle" class="form-label fw-semibold">Job Title</label>
              <input type="text" name="jobTitle" id="jobTitle" class="form-control" required>
            </div>

            <!-- Position -->
            <div class="mb-3">
              <label for="positionSelect" class="form-label fw-semibold">Position</label>
              <select name="position_id" id="positionSelect" class="form-select" required>
                <option value="" disabled selected>Select Position</option>
                <?php
                while ($position = mysqli_fetch_assoc($positions_result)) {
                    echo "<option value='{$position['position_id']}'>{$position['position_name']}</option>";
                }
                mysqli_data_seek($positions_result, 0); 
                ?>
              </select>
            </div>

            <div class="row mb-3">
              <div class="col-md-6">
                <label for="minSalary" class="form-label fw-semibold">Minimum Salary (₱)</label>
                <input type="text" name="minSalary" id="minSalary" class="form-control" placeholder="e.g., 30,000" required>
              </div>
              <div class="col-md-6">
                <label for="maxSalary" class="form-label fw-semibold">Maximum Salary (₱)</label>
                <input type="text" name="maxSalary" id="maxSalary" class="form-control" placeholder="e.g., 50,000" required>
              </div>
            </div>

            <!-- Job Description -->
            <div class="mb-3">
              <label for="jobDescription" class="form-label fw-semibold">Job Description</label>
              <textarea name="jobDescription" id="jobDescription" class="form-control" rows="3" required></textarea>
            </div>

            <!-- Requirements -->
            <div class="mb-3">
              <label for="requirements" class="form-label fw-semibold">Requirements</label>
              <textarea name="requirements" id="requirements" class="form-control" rows="3" required></textarea>
            </div>
          </div>

          <div class="modal-footer">
            <button type="submit" name="postRecruit" class="btn btn-primary">Post Recruitment</button>
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>


  <!-- MODAL PARA MAEDIT NI HR YUNG POSTS -->
  <div class="modal fade" id="editRecruitModal" tabindex="-1" aria-labelledby="editRecruitLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content bg-dark text-white">
        <form action="recruitPage.php" method="post">
          <div class="modal-header">
            <h5 class="modal-title" id="editRecruitLabel">Edit Recruitment Post</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <input type="hidden" name="editPostId" id="editPostId">

            <!-- Job Title -->
            <div class="mb-3">
              <label for="editJobTitle" class="form-label fw-semibold">Job Title</label>
              <input type="text" name="jobTitle" id="editJobTitle" class="form-control" required>
            </div>

            <!-- Position -->
            <div class="mb-3">
              <label for="editPositionSelect" class="form-label fw-semibold">Position</label>
              <select name="position_id" id="editPositionSelect" class="form-select" required>
                <option value="" disabled selected>Select Position</option>
                <?php
                mysqli_data_seek($positions_result, 0); // Reset pointer
                while ($position = mysqli_fetch_assoc($positions_result)) {
                    echo "<option value='{$position['position_id']}'>{$position['position_name']}</option>";
                }
                ?>
              </select>
            </div>

            <!-- Salary Range - ADDED: Salary fields -->
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="editMinSalary" class="form-label fw-semibold">Minimum Salary (₱)</label>
                <input type="text" name="minSalary" id="editMinSalary" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label for="editMaxSalary" class="form-label fw-semibold">Maximum Salary (₱)</label>
                <input type="text" name="maxSalary" id="editMaxSalary" class="form-control" required>
              </div>
            </div>

            <!-- Job Description -->
            <div class="mb-3">
              <label for="editJobDescription" class="form-label fw-semibold">Job Description</label>
              <textarea name="jobDescription" id="editJobDescription" class="form-control" rows="3" required></textarea>
            </div>

            <!-- Requirements -->
            <div class="mb-3">
              <label for="editRequirements" class="form-label fw-semibold">Requirements</label>
              <textarea name="requirements" id="editRequirements" class="form-control" rows="3" required></textarea>
            </div>
          </div>

          <div class="modal-footer">
            <button type="submit" name="editRecruit" class="btn btn-primary">Update Post</button>
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- MODAL PARA MADELETE YUNG POSTS -->
  <div class="modal fade" id="deleteRecruitModal" tabindex="-1" aria-labelledby="deleteRecruitLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark text-white">
        <form action="recruitPage.php" method="post">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteRecruitLabel">Delete Recruitment Post</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <p>Are you sure you want to delete this recruitment post?</p>
            <input type="hidden" id="deletePostId" name="deletePostId" value="">
          </div>

          <div class="modal-footer">
            <button type="submit" name="deletePost" class="btn btn-danger">Delete</button>
            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>

  <script src="scripts/recruitPage.js"></script>
</body>
</html>
