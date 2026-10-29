<?php
session_start();
require "../functions.php";
require "../connection.php";
redirectToLogin('Employee');

$currentEmployeeID = $_SESSION['employeeID'];
$employeeFirstName = $_SESSION['firstName'];
$employeeLastName = $_SESSION['lastName'];

// Fetch employee basic information including email from candidate's user account
$employeeQuery = "SELECT e.employee_id, e.date_hired, e.employment_status, e.status,
                        c.candidate_id, c.first_name, c.last_name, c.date_of_birth, c.phone_number, c.address,
                        p.position_name, d.department_name,
                        u.login_identifier as email
                FROM employees e
                JOIN candidates c ON e.candidate_id = c.candidate_id
                JOIN positions p ON e.position_id = p.position_id
                JOIN departments d ON p.department_id = d.department_id
                JOIN user_accounts u ON c.candidate_id = u.account_id
                WHERE e.employee_id = '$currentEmployeeID'";
$employeeResult = mysqli_query($con, $employeeQuery);
$employee = mysqli_fetch_assoc($employeeResult);

$candidate_id = $employee['candidate_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['update_profile'])) {
        $firstName = mysqli_real_escape_string($con, $_POST['first_name']);
        $lastName = mysqli_real_escape_string($con, $_POST['last_name']);
        $phoneNumber = mysqli_real_escape_string($con, $_POST['phone_number']);
        $address = mysqli_real_escape_string($con, $_POST['address']);
        
        $updateCandidateQuery = "UPDATE candidates SET 
                                first_name = '$firstName',
                                last_name = '$lastName',
                                phone_number = '$phoneNumber',
                                address = '$address'
                                WHERE candidate_id = '$candidate_id'";
        
        if (mysqli_query($con, $updateCandidateQuery)) {
            $_SESSION['message'] = "Profile updated successfully!";
            $_SESSION['message_type'] = "success";
            
            // Update session variables
            $_SESSION['employeeName'] = $firstName . ' ' . $lastName;
            $_SESSION['firstName'] = $firstName;
            $_SESSION['lastName'] = $lastName;
        } else {
            $_SESSION['message'] = "Error updating profile: " . mysqli_error($con);
            $_SESSION['message_type'] = "error";
        }
    }
    
    // Update education information
    if (isset($_POST['update_education'])) {
        $educationUpdated = false;
        foreach ($_POST['education'] as $educationId => $educationData) {
            $schoolName = mysqli_real_escape_string($con, $educationData['school_name']);
            $degree = mysqli_real_escape_string($con, $educationData['degree']);
            $yearGraduated = mysqli_real_escape_string($con, $educationData['year_graduated']);
            
            $updateEducationQuery = "UPDATE educational_background SET 
                                   school_name = '$schoolName',
                                   degree = '$degree',
                                   year_graduated = '$yearGraduated'
                                   WHERE education_id = '$educationId' AND candidate_id = '$candidate_id'";
            if (mysqli_query($con, $updateEducationQuery)) {
                $educationUpdated = true;
            }
        }
        
        if ($educationUpdated) {
            $_SESSION['message'] = "Education information updated successfully!";
            $_SESSION['message_type'] = "success";
        }
    }
    
    // Update work experience
    if (isset($_POST['update_work_experience'])) {
        $workUpdated = false;
        foreach ($_POST['work_experience'] as $experienceId => $workData) {
            $companyName = mysqli_real_escape_string($con, $workData['company_name']);
            $positionTitle = mysqli_real_escape_string($con, $workData['position_title']);
            $startDate = mysqli_real_escape_string($con, $workData['start_date']);
            $endDate = mysqli_real_escape_string($con, $workData['end_date']);
            $description = mysqli_real_escape_string($con, $workData['description']);
            
            // Convert month input to proper date format
            $startDate = $startDate ? $startDate . '-01' : null;
            $endDate = $endDate ? $endDate . '-01' : null;
            
            $updateWorkQuery = "UPDATE work_experience SET 
                              company_name = '$companyName',
                              position_title = '$positionTitle',
                              start_date = " . ($startDate ? "'$startDate'" : "NULL") . ",
                              end_date = " . ($endDate ? "'$endDate'" : "NULL") . ",
                              description = '$description'
                              WHERE experience_id = '$experienceId' AND candidate_id = '$candidate_id'";
            if (mysqli_query($con, $updateWorkQuery)) {
                $workUpdated = true;
            }
        }
        
        if ($workUpdated) {
            $_SESSION['message'] = "Work experience updated successfully!";
            $_SESSION['message_type'] = "success";
        }
    }
    
    // Handle adding new skill
    if (isset($_POST['add_skill'])) {
        $skillName = mysqli_real_escape_string($con, $_POST['skill_name']);
        $proficiencyLevel = mysqli_real_escape_string($con, $_POST['proficiency_level']);
        
        $addSkillQuery = "INSERT INTO skills (candidate_id, skill_name, proficiency_level) 
                         VALUES ('$candidate_id', '$skillName', '$proficiencyLevel')";
        
        if (mysqli_query($con, $addSkillQuery)) {
            $_SESSION['message'] = "Skill added successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error adding skill: " . mysqli_error($con);
            $_SESSION['message_type'] = "error";
        }
    }

    // Handle Certificate of Employment request
    if (isset($_POST['request_coe'])) {
        $employeeID = mysqli_real_escape_string($con, $_POST['employee_id']);
        
        $checkPendingQuery = "SELECT * FROM coe WHERE employee_id = '$employeeID' and status != 'Denied'";
        $pendingResult = mysqli_query($con, $checkPendingQuery);
        $checkPendingQuery = "SELECT * FROM coe WHERE employee_id = '$employeeID' and status = 'Approved'";
        $pendingResult = mysqli_query($con, $checkPendingQuery);
        
        if(mysqli_num_rows($pendingResult) > 0){
            $_SESSION['message'] = "You already have a  Certificate of Employment.";
            $_SESSION['message_type'] = "warning";
        }
        elseif (mysqli_num_rows($pendingResult) > 0) {
            $_SESSION['message'] = "You already have a pending Certificate of Employment request.";
            $_SESSION['message_type'] = "warning";
        } else {
            // Insert new COE request
            $insertCoeQuery = "INSERT INTO coe (employee_id) 
                            VALUES ('$employeeID')";
            
            if (mysqli_query($con, $insertCoeQuery)) {
                $_SESSION['message'] = "Certificate of Employment request submitted successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error submitting request: " . mysqli_error($con);
                $_SESSION['message_type'] = "error";
            }
        }
    }

    // Handle editing skill
    if (isset($_POST['edit_skill'])) {
        $skillId = mysqli_real_escape_string($con, $_POST['skill_id']);
        $skillName = mysqli_real_escape_string($con, $_POST['skill_name']);
        $proficiencyLevel = mysqli_real_escape_string($con, $_POST['proficiency_level']);
        
        $updateSkillQuery = "UPDATE skills SET 
                            skill_name = '$skillName',
                            proficiency_level = '$proficiencyLevel'
                            WHERE skill_id = '$skillId' AND candidate_id = '$candidate_id'";
        
        if (mysqli_query($con, $updateSkillQuery)) {
            $_SESSION['message'] = "Skill updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating skill: " . mysqli_error($con);
            $_SESSION['message_type'] = "error";
        }
    }

    // Handle deleting skill
    if (isset($_POST['delete_skill'])) {
        $skillId = mysqli_real_escape_string($con, $_POST['skill_id']);
        
        $deleteSkillQuery = "DELETE FROM skills WHERE skill_id = '$skillId' AND candidate_id = '$candidate_id'";
        
        if (mysqli_query($con, $deleteSkillQuery)) {
            $_SESSION['message'] = "Skill deleted successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error deleting skill: " . mysqli_error($con);
            $_SESSION['message_type'] = "error";
        }
    }

    // Handle editing certification
    if (isset($_POST['edit_certification'])) {
        $certificationId = mysqli_real_escape_string($con, $_POST['certification_id']);
        $certificateName = mysqli_real_escape_string($con, $_POST['certificate_name']);
        $fileLink = mysqli_real_escape_string($con, $_POST['file_link']);
        $googleDriveId = mysqli_real_escape_string($con, $_POST['google_drive_id']);
        
        $updateCertQuery = "UPDATE certifications SET 
                        certificate_name = '$certificateName'
                        WHERE certification_id = '$certificationId' AND candidate_id = '$candidate_id'";
        
        if (mysqli_query($con, $updateCertQuery)) {
            $_SESSION['message'] = "Certification updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating certification: " . mysqli_error($con);
            $_SESSION['message_type'] = "error";
        }
    }

    // Handle deleting certification
    if (isset($_POST['delete_certification'])) {
        $certificationId = mysqli_real_escape_string($con, $_POST['certification_id']);
        
        $deleteCertQuery = "DELETE FROM certifications WHERE certification_id = '$certificationId' AND candidate_id = '$candidate_id'";
        
        if (mysqli_query($con, $deleteCertQuery)) {
            $_SESSION['message'] = "Certification deleted successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error deleting certification: " . mysqli_error($con);
            $_SESSION['message_type'] = "error";
        }
    }

    header("Location: profPage.php");
    exit();
}

// Fetch education data
$educationQuery = "SELECT * FROM educational_background 
                WHERE candidate_id = '$candidate_id'
                ORDER BY FIELD(education_level, 'Doctorate', 'Masters', 'College', 'Senior High', 'High School')";
$educationResult = mysqli_query($con, $educationQuery);
$educations = [];
while ($row = mysqli_fetch_assoc($educationResult)) {
    $educations[$row['education_id']] = $row;
}

// Fetch work experience
$workQuery = "SELECT * FROM work_experience 
            WHERE candidate_id = '$candidate_id'
            ORDER BY start_date DESC";
$workResult = mysqli_query($con, $workQuery);
$workExperiences = mysqli_fetch_all($workResult, MYSQLI_ASSOC);

// Fetch skills
$skillsQuery = "SELECT * FROM skills 
                WHERE candidate_id = '$candidate_id'
                ORDER BY proficiency_level DESC";
$skillsResult = mysqli_query($con, $skillsQuery);
$skills = mysqli_fetch_all($skillsResult, MYSQLI_ASSOC);

// Fetch certifications
$certsQuery = "SELECT * FROM certifications 
            WHERE candidate_id = '$candidate_id'
            ORDER BY certification_id DESC";
$certsResult = mysqli_query($con, $certsQuery);
$certifications = mysqli_fetch_all($certsResult, MYSQLI_ASSOC);

// Format date for display
$hireDate = date('F d, Y', strtotime($employee['date_hired']));
$birthDate = date('F d, Y', strtotime($employee['date_of_birth']));
$fullName = $employee['first_name'] . ' ' . $employee['last_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile | Employee Portal</title>
<link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../css/Global.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row flex-nowrap">

        <?php include 'nav.php'; ?>

        <!-- Main Content -->
        <div class="col py-4 px-5">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
            <h2 class="fw-bold">Profile</h2>
            <div class="d-flex align-items-center">
                <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width:45px; height:45px;">EN</div>
                <div>
                    <h6 class="mb-0"><?php echo $_SESSION['employeeName']; ?></h6>
                    <small><?php echo $employee['position_name']; ?></small>
                </div>
            </div>
            </div>

            <!-- Display Messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            <?php endif; ?>

            <!-- Profile Overview Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-semibold">Profile Overview</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editInfoModal">
                <i class="fas fa-edit me-1"></i> Edit Information
            </button>
            </div>

            <!-- Profile Information Cards -->
            <div class="row g-4">

            <!-- Basic Information -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-id-badge me-2"></i> Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3"><strong>Full Name:</strong> <?php echo $fullName; ?></div>
                    <div class="mb-3"><strong>Employee ID:</strong> <?php echo $employee['employee_id']; ?></div>
                    <div class="mb-3"><strong>Position:</strong> <?php echo $employee['position_name']; ?></div>
                    <div class="mb-3"><strong>Department:</strong> <?php echo $employee['department_name']; ?></div>
                    <div class="mb-3"><strong>Employment Status:</strong> <?php echo $employee['employment_status']; ?></div>
                    <div class="mb-3"><strong>Hire Date:</strong> <?php echo $hireDate; ?></div>
                    <div class="mb-3"><strong>Date of Birth:</strong> <?php echo $birthDate; ?></div>
                </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-address-book me-2"></i> Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3"><strong>Contact Number:</strong> <?php echo $employee['phone_number'] ?: 'Not provided'; ?></div>
                    <div class="mb-3"><strong>Email:</strong> <?php echo $employee['email']; ?></div>
                    <div class="mb-3"><strong>Address:</strong> <?php echo $employee['address'] ?: 'Not provided'; ?></div>
                </div>
                </div>
            </div>

            <!-- Educational Background -->
            <div class="col-12">
                <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i> Educational Background</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($educations)): ?>
                        <?php foreach ($educations as $education): ?>
                            <div class="mb-3">
                                <strong><?php echo $education['education_level']; ?>:</strong>
                                <?php echo $education['school_name']; ?>
                                <?php if ($education['degree']): ?>(<?php echo $education['degree']; ?>)<?php endif; ?>
                                <?php if ($education['year_graduated']): ?> - Graduated <?php echo $education['year_graduated']; ?><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No educational background information available.</p>
                    <?php endif; ?>
                </div>
                </div>
            </div>

            <!-- Work Experience -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i> Work Experience</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($workExperiences)): ?>
                        <?php foreach ($workExperiences as $work): ?>
                            <div class="mb-3">
                                <strong><?php echo $work['position_title']; ?></strong><br>
                                <em><?php echo $work['company_name']; ?></em><br>
                                <?php echo date('M Y', strtotime($work['start_date'])); ?> - 
                                <?php echo $work['end_date'] ? date('M Y', strtotime($work['end_date'])) : 'Present'; ?>
                                <?php if ($work['description']): ?>
                                    <br><small><?php echo $work['description']; ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No work experience information available.</p>
                    <?php endif; ?>
                </div>
                </div>
            </div>

            <!-- Certificate of Employment -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-file-certificate me-2"></i> Certificate of Employment</h5>
                    </div>
                    <div class="card-body">
                        <?php
                            $coeQuery = "SELECT * FROM coe 
                                        WHERE employee_id = '$currentEmployeeID' 
                                        ORDER BY coe_id DESC 
                                        LIMIT 1";
                            $coeResult = mysqli_query($con, $coeQuery);
                            $coe = mysqli_fetch_assoc($coeResult);
                        ?>

                        <!-- Status and Button Row -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <?php if ($coe): ?>
                                    <div>
                                        <strong>Status:</strong> 
                                        <span class="badge 
                                            <?php 
                                            switch($coe['status']) {
                                                case 'Approved': echo 'bg-success'; break;
                                                case 'Denied': echo 'bg-danger'; break;
                                                default: echo 'bg-warning text-dark';
                                            }
                                            ?>">
                                            <?php echo $coe['status']; ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <p class="mb-0">No Certificate of Employment yet.</p>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <?php if (!$coe || $coe['status'] == 'Denied'): ?>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#requestCert">
                                        <i class="fas fa-plus me-1"></i> Request Certificate
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($coe && $coe['status'] == 'Pending'): ?>
                            <div class="mb-3">
                                <small class="text-warning">
                                    <i class="fas fa-clock me-1"></i> Your request is being processed
                                </small>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($coe): ?>
                            <?php if ($coe['status_reason']): ?>
                                <div class="mb-3">
                                    <strong>Remarks:</strong> 
                                    <span><?php echo $coe['status_reason']; ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($coe['status'] == 'Approved' && !empty($coe['file_data'])): ?>
                                <div class="mb-3">
                                    <form method="post" action="view_coe.php" class="d-inline" target="_blank">
                                        <input type="hidden" name="coe_id" value="<?php echo $coe['coe_id']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="fas fa-eye me-1"></i> View Certificate
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-2">
                                <small>
                                    <strong>Requested:</strong> <?php echo date('M d, Y', strtotime($coe['request_date'])); ?>
                                </small>
                            </div>
                            <div>
                                <small>
                                    <strong>Last updated:</strong> <?php echo date('M d, Y', strtotime($coe['updated_at'])); ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Skills & Certifications -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-code me-2"></i> Skills & Certifications</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Skills:</h6>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addSkillModal">
                            <i class="fas fa-plus me-1"></i> Add Skill
                        </button>
                    </div>
                    <?php if (!empty($skills)): ?>
                        <div class="mb-3">
                            <?php foreach ($skills as $skill): ?>
                                <div class="skill-item d-inline-block me-2 mb-2">
                                    <span class="badge bg-primary position-relative">
                                        <?php echo $skill['skill_name']; ?> (<?php echo $skill['proficiency_level']; ?>)
                                        <span class="position-absolute top-0 start-100 translate-middle p-1">
                                            <button class="btn btn-sm p-0 text-white" 
                                                    onclick="openEditSkillModal(<?php echo $skill['skill_id']; ?>, '<?php echo addslashes($skill['skill_name']); ?>', '<?php echo $skill['proficiency_level']; ?>')"
                                                    title="Edit Skill">
                                                <i class="fas fa-edit fa-xs"></i>
                                            </button>
                                            <button class="btn btn-sm p-0 text-white" 
                                                    onclick="openDeleteSkillModal(<?php echo $skill['skill_id']; ?>)"
                                                    title="Delete Skill">
                                                <i class="fas fa-times fa-xs"></i>
                                            </button>
                                        </span>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No skills information available.</p>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Certifications:</h6>
                    </div>
                    <?php if (!empty($certifications)): ?>
                        <div>
                            <?php foreach ($certifications as $cert): ?>
                                <div class="certification-item mb-3 p-2 border rounded">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <strong><?php echo $cert['certificate_name']; ?></strong><br>
                                            <a href="<?php echo $cert['file_link']; ?>" target="_blank" class="text-decoration-none">
                                                <small>View Certificate</small>
                                            </a>
                                        </div>
                                        <div class="btn-group btn-group-sm ms-2">
                                            <button class="btn btn-outline-primary" 
                                                    onclick="openEditCertModal(<?php echo $cert['certification_id']; ?>, '<?php echo addslashes($cert['certificate_name']); ?>')"
                                                    title="Edit Certification">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" 
                                                    onclick="openDeleteCertModal(<?php echo $cert['certification_id']; ?>)"
                                                    title="Delete Certification">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No certifications available.</p>
                    <?php endif; ?>
                </div>
                </div>
            </div>
 
            </div>
            
            <!-- Edit Profile Modal -->
            <div class="modal fade" id="editInfoModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-xl">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit Employee Profile</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">

                        <!-- Personal Information Form -->
                        <form method="post" action="profPage.php" class="mb-4">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <h6 class="fw-bold text-primary mb-3" style="font-size: 20px;"><i class="fa-solid fa-id-card me-2"></i>Personal Information</h6>
                            <div class="border border-secondary rounded p-3 mb-3">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" name="first_name" value="<?php echo $employee['first_name']; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" name="last_name" value="<?php echo $employee['last_name']; ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Birth Date</label>
                                        <input type="date" class="form-control" value="<?php echo $employee['date_of_birth']; ?>" readonly>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Employee ID</label>
                                        <input type="text" class="form-control" value="<?php echo $employee['employee_id']; ?>" disabled>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Employment Status</label>
                                        <input type="text" class="form-control" value="<?php echo $employee['employment_status']; ?>" readonly>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Department</label>
                                        <input type="text" class="form-control" value="<?php echo $employee['department_name']; ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Position</label>
                                        <input type="text" class="form-control" value="<?php echo $employee['position_name']; ?>" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <h6 class="fw-bold text-primary mt-4 mb-3" style="font-size: 20px;"><i class="fa-solid fa-address-book me-2"></i>Contact Information</h6>
                            <div class="border border-secondary rounded p-3 mb-3">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" value="<?php echo $employee['email']; ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" name="phone_number" value="<?php echo $employee['phone_number']; ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <input type="text" class="form-control" name="address" value="<?php echo $employee['address']; ?>">
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save Personal Information
                                </button>
                            </div>
                        </form>

                        <!-- Educational Background Form -->
                        <?php if (!empty($educations)): ?>
                        <form method="post" action="profPage.php" class="mb-4">
                            <input type="hidden" name="update_education" value="1">
                            
                            <h6 class="fw-bold text-primary mt-4 mb-3" style="font-size: 20px;"><i class="fa-solid fa-graduation-cap me-2"></i>Educational Background</h6>
                            <?php foreach ($educations as $educationId => $education): ?>
                            <div class="border border-secondary rounded p-3 mb-3">
                                <h6 class="fw-semibold text-dark mb-4 mt-3" style="font-size: 17px;"><?php echo $education['education_level']; ?></h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">School Name</label>
                                        <input type="text" class="form-control" name="education[<?php echo $educationId; ?>][school_name]" value="<?php echo $education['school_name']; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Degree/Course</label>
                                        <input type="text" class="form-control" name="education[<?php echo $educationId; ?>][degree]" value="<?php echo $education['degree']; ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Year Graduated</label>
                                        <input type="text" class="form-control" name="education[<?php echo $educationId; ?>][year_graduated]" value="<?php echo $education['year_graduated']; ?>">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save Education Information
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>

                        <!-- Work Experience Form -->
                        <?php if (!empty($workExperiences)): ?>
                        <form method="post" action="profPage.php">
                            <input type="hidden" name="update_work_experience" value="1">
                            
                            <h6 class="fw-bold text-primary mt-4 mb-3" style="font-size: 20px;"><i class="fa-solid fa-briefcase me-2"></i>Work Experience</h6>
                            <?php foreach ($workExperiences as $work): ?>
                            <div class="border border-secondary rounded p-3 mb-3">
                                <div>
                                    <div class="mb-3">
                                        <label class="form-label">Company Name</label>
                                        <input type="text" class="form-control" name="work_experience[<?php echo $work['experience_id']; ?>][company_name]" value="<?php echo $work['company_name']; ?>">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Position Held</label>
                                            <input type="text" class="form-control" name="work_experience[<?php echo $work['experience_id']; ?>][position_title]" value="<?php echo $work['position_title']; ?>">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">From</label>
                                            <input type="month" class="form-control" name="work_experience[<?php echo $work['experience_id']; ?>][start_date]" value="<?php echo date('Y-m', strtotime($work['start_date'])); ?>">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">To</label>
                                            <input type="month" class="form-control" name="work_experience[<?php echo $work['experience_id']; ?>][end_date]" value="<?php echo $work['end_date'] ? date('Y-m', strtotime($work['end_date'])) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="work_experience[<?php echo $work['experience_id']; ?>][description]" rows="3"><?php echo $work['description']; ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save Work Experience
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                            <i class="fa-solid fa-ban me-1"></i> Close
                        </button>
                    </div>
                    </div>
                </div>
            </div>

            <!-- Add Skills Modal -->
            <div class="modal fade" id="addSkillModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="post" action="profPage.php">
                            <input type="hidden" name="add_skill" value="1">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Skill</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Skill Name</label>
                                    <input type="text" class="form-control" name="skill_name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Proficiency Level</label>
                                    <select class="form-select" name="proficiency_level" required>
                                        <option value="">Select Level</option>
                                        <option value="Beginner">Beginner</option>
                                        <option value="Intermediate">Intermediate</option>
                                        <option value="Advanced">Advanced</option>
                                        <option value="Expert">Expert</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">Add Skill</button>
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Skill Modal -->
            <div class="modal fade" id="editSkillModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="post" action="profPage.php">
                            <input type="hidden" name="edit_skill" value="1">
                            <input type="hidden" name="skill_id" id="edit_skill_id">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Skill</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Skill Name</label>
                                    <input type="text" class="form-control" name="skill_name" id="edit_skill_name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Proficiency Level</label>
                                    <select class="form-select" name="proficiency_level" id="edit_proficiency_level" required>
                                        <option value="">Select Level</option>
                                        <option value="Beginner">Beginner</option>
                                        <option value="Intermediate">Intermediate</option>
                                        <option value="Advanced">Advanced</option>
                                        <option value="Expert">Expert</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">Update Skill</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Request Certificate Modal -->
            <div class="modal fade" id="requestCert" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="post" action="profPage.php">
                            <input type="hidden" name="request_coe" value="1">
                            <input type="hidden" name="employee_id" value="<?php echo $currentEmployeeID; ?>">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-file-certificate me-2"></i>Request Certificate of Employment</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <p>Are you sure you want to request a Certificate of Employment?</p>
                                    <p class="text-muted small">Your request will be processed by HR and you'll be notified once it's ready.</p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">Yes, Request COE</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Certification Modal -->
            <div class="modal fade" id="editCertModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="post" action="profPage.php">
                            <input type="hidden" name="edit_certification" value="1">
                            <input type="hidden" name="certification_id" id="edit_certification_id">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Certification</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Certificate Name</label>
                                    <input type="text" class="form-control" name="certificate_name" id="edit_certificate_name" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">Update Certification</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Skill Confirmation Modal -->
            <div class="modal fade" id="deleteSkillModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="post" action="profPage.php">
                            <input type="hidden" name="delete_skill" value="1">
                            <input type="hidden" name="skill_id" id="delete_skill_id">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Delete Skill</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete this skill?</p>
                                <p class="text-muted small">This action cannot be undone.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-danger">Delete Skill</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Certification Confirmation Modal -->
            <div class="modal fade" id="deleteCertModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="post" action="profPage.php">
                            <input type="hidden" name="delete_certification" value="1">
                            <input type="hidden" name="certification_id" id="delete_certification_id">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Delete Certification</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete this certification?</p>
                                <p class="text-muted small">This action cannot be undone.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-danger">Delete Certification</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>

    <script>
        function openEditSkillModal(skillId, skillName, proficiencyLevel) {
            document.getElementById('edit_skill_id').value = skillId;
            document.getElementById('edit_skill_name').value = skillName;
            document.getElementById('edit_proficiency_level').value = proficiencyLevel;
            
            var editSkillModal = new bootstrap.Modal(document.getElementById('editSkillModal'));
            editSkillModal.show();
        }

        function openDeleteSkillModal(skillId) {
            document.getElementById('delete_skill_id').value = skillId;
            
            var deleteSkillModal = new bootstrap.Modal(document.getElementById('deleteSkillModal'));
            deleteSkillModal.show();
        }

        function openEditCertModal(certificationId, certificateName) {
            document.getElementById('edit_certification_id').value = certificationId;
            document.getElementById('edit_certificate_name').value = certificateName;
            
            var editCertModal = new bootstrap.Modal(document.getElementById('editCertModal'));
            editCertModal.show();
        }

        function openDeleteCertModal(certificationId) {
            document.getElementById('delete_certification_id').value = certificationId;
            
            var deleteCertModal = new bootstrap.Modal(document.getElementById('deleteCertModal'));
            deleteCertModal.show();
        }
    </script>
</body>
</html>