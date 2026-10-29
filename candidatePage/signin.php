<?php
require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/google_config.php'; // Google OAuth keys (not in git)
require '../connection.php';
require 'fileUploadHandler.php';

use Google\Client;

session_start();

$client = new Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET); 
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope('email');
$client->addScope('profile');
$client->addScope('https://www.googleapis.com/auth/drive.file');

// Handle OAuth callback
if(isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if(isset($token['access_token'])) {
            $client->setAccessToken($token['access_token']);

            $google_oauth = new \Google\Service\Oauth2($client);
            $google_account_info = $google_oauth->userinfo->get();
            
            $_SESSION['user_email'] = $google_account_info->email;
            $_SESSION['user_givenName'] = $google_account_info->givenName;
            $_SESSION['user_lastName'] = $google_account_info->familyName;
            $_SESSION['access_token'] = $token['access_token'];
            
            // Store the entire token array which includes refresh token
            $_SESSION['google_token'] = $token;
            
            // Redirect to clear the code from URL
            header('Location: signin.php');
            exit;
        } else {
            throw new Exception('Failed to get access token');
        }
    } catch (Exception $e) {
        $error_message = 'OAuth Error: ' . $e->getMessage();
    }
}

// User is logged in, show registration form
$accessToken = $_SESSION['access_token'] ?? null;
$fileHandler = null;
if ($accessToken) {
    try {
        $fileHandler = new FileUploadHandler($con, $accessToken);
    } catch (Exception $e) {
        $error_message = 'File handler error: ' . $e->getMessage();
    }
}

$err = null;

// Handle form submission
if(isset($_POST['register'])) {
    // Basic Information
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $firstName = mysqli_real_escape_string($con, $_POST['firstName']);
    $lastName = mysqli_real_escape_string($con, $_POST['lastName']);
    $birthdate = mysqli_real_escape_string($con, $_POST['birthdate']);
    $contactNum = mysqli_real_escape_string($con, $_POST['contactNum']);
    $address = mysqli_real_escape_string($con, $_POST['address']);
    $password = $_POST['password'];
    
    if($password === $_POST['confirmPass']) {
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

        // Start transaction
        mysqli_begin_transaction($con);
        try {
            // First, check if email already exists
            $check_sql = "SELECT account_id FROM user_accounts WHERE login_identifier = ?";
            $stmt_check = mysqli_prepare($con, $check_sql);
            mysqli_stmt_bind_param($stmt_check, "s", $email);
            mysqli_stmt_execute($stmt_check);
            $result = mysqli_stmt_get_result($stmt_check);
            
            if(mysqli_num_rows($result) > 0) {
                throw new Exception('Email already exists. Please use a different email.');
            }

            // First, insert into user_accounts table
            $account_sql = "INSERT INTO user_accounts (login_identifier, password, is_first_login, user_type) 
                        VALUES (?, ?, 0, 'Candidate')";
            $stmt_account = mysqli_prepare($con, $account_sql);
            mysqli_stmt_bind_param($stmt_account, "ss", $email, $password);
            
            if(!mysqli_stmt_execute($stmt_account)) {
                throw new Exception('Failed to create user account: ' . mysqli_error($con));
            }
            
            $account_id = mysqli_insert_id($con);
            
            // Then insert into candidates table
            $candidate_sql = "INSERT INTO candidates (candidate_id, first_name, last_name, date_of_birth, phone_number, address) 
                            VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_candidate = mysqli_prepare($con, $candidate_sql);
            mysqli_stmt_bind_param($stmt_candidate, "isssss", $account_id, $firstName, $lastName, $birthdate, $contactNum, $address);
            
            if(!mysqli_stmt_execute($stmt_candidate)) {
                throw new Exception('Failed to create candidate profile: ' . mysqli_error($con));
            }
            
            $candidate_id = $account_id; // Since candidate_id references account_id
            
            // Insert Educational Background
            $education_levels = [
                'High School' => ['school' => $_POST['jhs'], 'year' => $_POST['jhsYearGrad'], 'degree' => null],
                'Senior High' => ['school' => $_POST['shs'], 'year' => $_POST['shsYearGrad'], 'degree' => $_POST['shsStrand']],
                'College' => ['school' => $_POST['college'], 'year' => $_POST['collegeYearGrad'], 'degree' => $_POST['collegeProgram']],
                'Masters' => ['school' => $_POST['masteral'], 'year' => $_POST['masteralYearGrad'], 'degree' => $_POST['masteralProgram']],
                'Doctorate' => ['school' => $_POST['doctorate'], 'year' => $_POST['doctorateYearGrad'], 'degree' => $_POST['doctorateProgram']]
            ];
            
            $edu_sql = "INSERT INTO educational_background (candidate_id, education_level, degree, school_name, year_graduated) 
                        VALUES (?, ?, ?, ?, ?)";
            $stmt_edu = mysqli_prepare($con, $edu_sql);
            
            foreach($education_levels as $level => $data) {
                if(!empty($data['school'])) {
                    mysqli_stmt_bind_param($stmt_edu, "issss", $candidate_id, $level, $data['degree'], 
                                        $data['school'], $data['year']);
                    if(!mysqli_stmt_execute($stmt_edu)) {
                        throw new Exception("Failed to insert education data for $level: " . mysqli_error($con));
                    }
                }
            }
            
            // Insert Skills
            if(isset($_POST['skill_name']) && is_array($_POST['skill_name'])) {
                $skill_sql = "INSERT INTO skills (candidate_id, skill_name, proficiency_level) VALUES (?, ?, ?)";
                $stmt_skill = mysqli_prepare($con, $skill_sql);
                
                foreach($_POST['skill_name'] as $index => $skill_name) {
                    if(!empty($skill_name) && isset($_POST['proficiency_level'][$index])) {
                        $proficiency_level = $_POST['proficiency_level'][$index];
                        mysqli_stmt_bind_param($stmt_skill, "iss", $candidate_id, $skill_name, $proficiency_level);
                        if(!mysqli_stmt_execute($stmt_skill)) {
                            throw new Exception("Failed to insert skill: " . mysqli_error($con));
                        }
                    }
                }
            }
            
            // Insert Work Experience
            if(isset($_POST['company_name']) && is_array($_POST['company_name'])) {
                $exp_sql = "INSERT INTO work_experience (candidate_id, company_name, position_title, start_date, end_date, description) 
                            VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_exp = mysqli_prepare($con, $exp_sql);
                
                foreach($_POST['company_name'] as $index => $company_name) {
                    if(!empty($company_name)) {
                        $position_title = $_POST['position_title'][$index] ?? '';
                        $start_date = $_POST['start_date'][$index] ?? null;
                        $end_date = $_POST['end_date'][$index] ?? null;
                        $description = $_POST['exp_description'][$index] ?? '';
                        
                        // Handle empty dates
                        $start_date = !empty($start_date) ? $start_date : null;
                        $end_date = !empty($end_date) ? $end_date : null;
                        
                        mysqli_stmt_bind_param($stmt_exp, "isssss", $candidate_id, $company_name, $position_title, 
                                            $start_date, $end_date, $description);
                        if(!mysqli_stmt_execute($stmt_exp)) {
                            throw new Exception("Failed to insert work experience: " . mysqli_error($con));
                        }
                    }
                }
            }
            
            // Handle certificate file uploads
            if(isset($_FILES['cert_file']) && is_array($_FILES['cert_file']['name'])) {
                foreach($_FILES['cert_file']['name'] as $index => $cert_file_name) {
                    if(!empty($cert_file_name) && $_FILES['cert_file']['error'][$index] === UPLOAD_ERR_OK) {
                        $cert_file = [
                            'name' => $_FILES['cert_file']['name'][$index],
                            'type' => $_FILES['cert_file']['type'][$index],
                            'tmp_name' => $_FILES['cert_file']['tmp_name'][$index],
                            'error' => $_FILES['cert_file']['error'][$index],
                            'size' => $_FILES['cert_file']['size'][$index]
                        ];
                        
                        // Get certificate name from form
                        $cert_name = $_POST['cert_name'][$index] ?? 'Certificate';
                        
                        // Upload certificate file to Google Drive
                        if ($fileHandler) {
                            $cert_upload_result = $fileHandler->uploadCertificateFile(
                                $candidate_id, 
                                $cert_file, 
                                $cert_name
                            );
                            
                            if(!$cert_upload_result['success']) {
                                throw new Exception("Failed to upload certificate file: " . $cert_upload_result['error']);
                            }
                            
                            // Store certificate in certifications table
                            $cert_sql = "INSERT INTO certifications (candidate_id, certificate_name, file_link, google_drive_id) 
                                        VALUES (?, ?, ?, ?)";
                            $stmt_cert = mysqli_prepare($con, $cert_sql);

                            $file_link = $cert_upload_result['file_url'] ?? '';
                            $google_drive_id = $cert_upload_result['google_drive_id'] ?? '';

                            mysqli_stmt_bind_param($stmt_cert, "isss", $candidate_id, $cert_name, $file_link, $google_drive_id);
                            if(!mysqli_stmt_execute($stmt_cert)) {
                                throw new Exception("Failed to store certificate metadata: " . mysqli_error($con));
                            }
                        } else {
                            throw new Exception("File handler not available for certificate upload");
                        }
                    }
                }
            }
            
            // Handle main document uploads
            if(isset($_FILES) && $fileHandler) {
                $uploadResults = [];
                
                // Check each file individually and only upload if it exists
                $filesToUpload = [];
                
                if(isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
                    $filesToUpload['Resume'] = $_FILES['resume'];
                }
                if(isset($_FILES['GovernmentID']) && $_FILES['GovernmentID']['error'] === UPLOAD_ERR_OK) {
                    $filesToUpload['Government ID'] = $_FILES['GovernmentID'];
                }
                if(isset($_FILES['birthCert']) && $_FILES['birthCert']['error'] === UPLOAD_ERR_OK) {
                    $filesToUpload['Birth Certificate'] = $_FILES['birthCert'];
                }
                if(isset($_FILES['diploma']) && $_FILES['diploma']['error'] === UPLOAD_ERR_OK) {
                    $filesToUpload['Diploma'] = $_FILES['diploma'];
                }
                
                if(!empty($filesToUpload)) {
                    $uploadResults = $fileHandler->uploadMultipleDocuments($candidate_id, $filesToUpload);
                    
                    // Check if any upload failed
                    foreach($uploadResults as $type => $result) {
                        if(!$result['success']) {
                            throw new Exception("Failed to upload $type: " . $result['error']);
                        }
                    }
                }
            }
            
            // Commit transaction
            mysqli_commit($con);
            
            $_SESSION['message'] = 'Registration successful! Your account has been created.';
            header('Location: login.php');
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($con);
            $error_message = 'Error: ' . $e->getMessage();
        }
    } else {
        $err = "Passwords do not match.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Registration</title>
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: "Poppins", sans-serif;
            background: linear-gradient(135deg, #0d1117, #1e293b);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #e2e8f0;
            padding: 30px;
        }

        .register-container {
            width: 100%;
            max-width: 900px;
            background: linear-gradient(145deg, #1e293b, #111827);
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3);
            padding: 40px 50px;
        }

        h1 {
            text-align: center;
            color: #4f46e5;
            font-weight: 800;
            margin-bottom: 10px;
        }

        h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #e2e8f0;
            border-left: 4px solid #4f46e5;
            padding-left: 10px;
            margin-top: 30px;
            margin-bottom: 15px;
        }

        label {
            color: #94a3b8;
            font-weight: 500;
        }

        .form-control {
            background-color: #111827 !important;
            color: #e2e8f0 !important;
            border: 1px solid #1e293b !important;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 15px;
            transition: all 0.25s ease;
        }

        .form-control::placeholder {
            color: #94a3b8;
        }

        .form-control:focus {
            background-color: #1e293b !important;
            border-color: #4f46e5 !important;
            box-shadow: 0 0 10px rgba(79, 70, 229, 0.5);
            color: #fff !important;
        }

        .form-section {
            background: rgba(17, 24, 39, 0.6);
            border: 1px solid #1e293b;
            border-radius: 12px;
            padding: 20px 25px;
            margin-bottom: 25px;
        }

        .btn-primary {
            border-radius: 50px;
            padding: 12px;
            font-weight: 600;
            background: linear-gradient(90deg, #4f46e5, #9333ea);
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #9333ea, #4f46e5);
            box-shadow: 0 0 12px rgba(79, 70, 229, 0.5);
        }

        .btn-outline-primary {
            border-radius: 50px;
            padding: 8px 16px;
            border: 1px solid #4f46e5;
            color: #4f46e5;
            background: transparent;
            transition: 0.3s;
        }

        .btn-outline-primary:hover {
            background: #4f46e5;
            color: #fff;
            box-shadow: 0 0 10px rgba(79, 70, 229, 0.4);
        }

        .remove-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 16px;
            font-size: 0.875rem;
            border-radius: 12px;
        }

        .dynamic-field {
            border: 1px solid #1e293b;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 15px;
            background-color: #111827;
        }

        .message {
            text-align: center;
            margin-bottom: 20px;
        }

        .success {
            color: #22c55e;
        }

        .error {
            color: #f87171;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Counters
            let counters = { skill: 0, experience: 0, certificate: 0 };

            // Selectors
            const skillsContainer = document.getElementById('skillsContainer');
            const experienceContainer = document.getElementById('experienceContainer');
            const certificateContainer = document.getElementById('certificateContainer');

            // ======= Generic function to create dynamic fields =======
            function createField(container, type, htmlContent) {
                const wrapper = document.createElement('div');
                wrapper.className = 'dynamic-field';
                wrapper.innerHTML = htmlContent;
                container.appendChild(wrapper);

                // Add remove functionality
                wrapper.querySelector('button.remove-btn').addEventListener('click', () => {
                    wrapper.remove();
                });
            }

            // ======= Add Skill =======
            window.addSkill = () => {
                counters.skill++;
                const html = `
                    <div class="form-group">
                        <label for="skill_name_${counters.skill}">Skill Name:</label>
                        <input type="text" name="skill_name[]" id="skill_name_${counters.skill}" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="proficiency_level_${counters.skill}">Proficiency Level:</label>
                        <select name="proficiency_level[]" id="proficiency_level_${counters.skill}" required class="form-control">
                            <option value="">Select Level</option>
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                            <option value="Expert">Expert</option>
                        </select>
                    </div>
                    <button type="button" class="remove-btn btn btn-outline-danger btn-sm mt-2">Remove</button>
                `;
                createField(skillsContainer, 'skill', html);
            };

            // ======= Add Experience =======
            window.addExperience = () => {
                counters.experience++;
                const html = `
                    <div class="form-group">
                        <label for="company_name_${counters.experience}">Company Name:</label>
                        <input type="text" name="company_name[]" id="company_name_${counters.experience}" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="position_title_${counters.experience}">Position Title:</label>
                        <input type="text" name="position_title[]" id="position_title_${counters.experience}" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="start_date_${counters.experience}">Start Date:</label>
                        <input type="date" name="start_date[]" id="start_date_${counters.experience}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="end_date_${counters.experience}">End Date:</label>
                        <input type="date" name="end_date[]" id="end_date_${counters.experience}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="exp_description_${counters.experience}">Description:</label>
                        <textarea name="exp_description[]" id="exp_description_${counters.experience}" rows="3" class="form-control"></textarea>
                    </div>
                    <button type="button" class="remove-btn btn btn-outline-danger btn-sm mt-2">Remove</button>
                `;
                createField(experienceContainer, 'experience', html);
            };

            // ======= Add Certificate =======
            window.addCertificate = () => {
                counters.certificate++;
                const html = `
                    <div class="form-group">
                        <label for="cert_name_${counters.certificate}">Certificate Name:</label>
                        <input type="text" name="cert_name[]" id="cert_name_${counters.certificate}" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="cert_file_${counters.certificate}">Upload Certificate File:</label>
                        <input type="file" name="cert_file[]" id="cert_file_${counters.certificate}" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required class="form-control">
                    </div>
                    <button type="button" class="remove-btn btn btn-outline-danger btn-sm mt-2">Remove</button>
                `;
                createField(certificateContainer, 'certificate', html);

                // Add file validation to new input
                const input = document.getElementById(`cert_file_${counters.certificate}`);
                input.addEventListener('change', () => validateFile(input));
            };

            // ======= Password validation =======
            const password = document.getElementById('password');
            const confirmPass = document.getElementById('confirmPass');
            const message = document.getElementById('password-message');

            function validatePassword() {
                if(password.value !== confirmPass.value) {
                    confirmPass.setCustomValidity("Passwords don't match");
                    message.textContent = "Passwords don't match";
                    message.className = 'message error';
                } else {
                    confirmPass.setCustomValidity('');
                    message.textContent = '';
                    message.className = '';
                }
            }

            password.addEventListener('input', validatePassword);
            confirmPass.addEventListener('input', validatePassword);

            // ======= File validation =======
            function validateFile(input) {
                const file = input.files[0];
                if(!file) return;

                const allowedTypes = [
                    'image/jpeg','image/jpg','image/png',
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];

                if(file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    input.value = '';
                } else if(!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Please upload images, PDF, or Word documents only.');
                    input.value = '';
                }
            }

            document.querySelectorAll('input[type="file"]').forEach(input => {
                input.addEventListener('change', () => validateFile(input));
            });

            // ======= Initialize first set of fields =======
            addSkill();
            addExperience();
            addCertificate();
        });
    </script>
</head>
<body>
    <div class="register-container">
    <h1>Candidate Registration</h1>

    <?php if(isset($_SESSION['user_email'])): ?>
        <div class="alert alert-info text-center">
            Logged in as: <strong><?php echo $_SESSION['user_email']; ?></strong>
        </div>
    <?php endif; ?>

    <?php if(isset($error_message)): ?>
        <div class="message error"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if(isset($_SESSION['message'])): ?>
        <div class="message success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>

    <form action="signin.php" method="post" enctype="multipart/form-data">
        <!-- BASIC INFORMATION -->
        <div class="form-section">
            <h2>Basic Information</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" 
                        value="<?php echo $_SESSION['user_email'] ?? ''; ?>" readonly required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">First Name</label>
                    <input type="text" name="firstName" class="form-control" 
                        value="<?php echo $_SESSION['user_givenName'] ?? ''; ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="lastName" class="form-control" 
                        value="<?php echo $_SESSION['user_lastName'] ?? ''; ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="birthdate" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contactNum" class="form-control" placeholder="09123456789" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control" required>
                </div>
            </div>
        </div>

        <!-- EDUCATIONAL BACKGROUND -->
        <div class="form-section">
            <h2>Educational Background</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">High School</label>
                    <input type="text" name="jhs" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Year Graduated</label>
                    <input type="number" name="jhsYearGrad" class="form-control" min="1900" max="2030">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Senior High</label>
                    <input type="text" name="shs" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Track and Strand</label>
                    <input type="text" name="shsStrand" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Year Graduated</label>
                    <input type="number" name="shsYearGrad" class="form-control" min="1900" max="2030">
                </div>

                <div class="col-md-4">
                    <label class="form-label">College</label>
                    <input type="text" name="college" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Program</label>
                    <input type="text" name="collegeProgram" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Year Graduated</label>
                    <input type="number" name="collegeYearGrad" class="form-control" min="1900" max="2030">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Masteral</label>
                    <input type="text" name="masteral" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Program</label>
                    <input type="text" name="masteralProgram" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Year Graduated</label>
                    <input type="number" name="masteralYearGrad" class="form-control" min="1900" max="2030">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Doctorate</label>
                    <input type="text" name="doctorate" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Program</label>
                    <input type="text" name="doctorateProgram" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Year Graduated</label>
                    <input type="number" name="doctorateYearGrad" class="form-control" min="1900" max="2030">
                </div>
            </div>
        </div>

        <!-- SKILLS -->
        <div class="form-section">
            <h2>Skills</h2>
            <div id="skillsContainer"></div>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addSkill()">+ Add Skill</button>
        </div>

        <!-- WORK EXPERIENCE -->
        <div class="form-section">
            <h2>Work Experience</h2>
            <div id="experienceContainer"></div>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addExperience()">+ Add Work Experience</button>
        </div>

        <!-- CERTIFICATIONS -->
        <div class="form-section">
            <h2>Certifications</h2>
            <div id="certificateContainer"></div>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addCertificate()">+ Add Certificate</button>
        </div>

        <!-- DOCUMENTS -->
        <div class="form-section">
            <h2>Documents</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Resume</label>
                    <input type="file" name="resume" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Government ID</label>
                    <input type="file" name="GovernmentID" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Birth Certificate</label>
                    <input type="file" name="birthCert" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Diploma</label>
                    <input type="file" name="diploma" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                </div>
            </div>
        </div>

        <!-- ACCOUNT SECURITY -->
        <div class="form-section">
            <h2>Account Security</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirmPass" id="confirmPass" class="form-control" required>
                </div>
                <div id="password-message" class="text-danger small w-100 text-center mt-1"></div>

            </div>
            <?php if($err != null): ?>
                <div class="message error mt-3"><?php echo $err; ?></div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-4">
            <button type="submit" name="register" class="btn btn-primary btn-lg px-5">Register</button>
        </div>
    </form>
</div>
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>