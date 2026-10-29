<?php
require 'vendor/autoload.php';
require_once __DIR__ . '/hrPage/sendgrid/config.php'; // defines SENDGRID_API_KEY (not in git)
use SendGrid\Mail\Mail;

function login($con, $login_identifier, $password) {
    $query = "SELECT * FROM user_accounts WHERE login_identifier = '$login_identifier' and user_type != 'Candidate'";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);

    if (mysqli_num_rows($result) > 0) {
        if(password_verify($password, $row["password"])) {
            $accountId = $row['account_id'];
            $status_query = "SELECT e.status 
                           FROM employees e 
                           WHERE e.account_id = '$accountId'";
            $status_result = mysqli_query($con, $status_query);
            
            if ($status_result && mysqli_num_rows($status_result) > 0) {
                $status_row = mysqli_fetch_assoc($status_result);
                $employee_status = $status_row['status'];
                
                if ($employee_status === 'Suspended') {
                    $_SESSION['account_status'] = 'suspended';
                    $_SESSION['status_message'] = 'You have been suspended. To Resolve this issue, please go the HR immediately.';
                    header('Location: account_status.php');
                    exit();
                } elseif ($employee_status === 'Terminated') {
                    $_SESSION['account_status'] = 'terminated';
                    $_SESSION['status_message'] = 'You have been terminated. To Resolve this issue, please go the HR immediately.';
                    header('Location: account_status.php');
                    exit();
                }
            }
            
            if($row["is_first_login"] == 1) {
                $_SESSION['message'] = 'Please change your password as this is your first login and your password is the default password set by the HR.';
                $_SESSION['employeeID'] = $login_identifier;
                header('Location: changePass.php');
                exit();
            } else {
                $accountId = $row['account_id'];
                $query2 = "SELECT e.employee_id, c.first_name, c.last_name, p.position_name, e.employment_status 
                        FROM employees e 
                        JOIN candidates c ON e.candidate_id = c.candidate_id
                        JOIN positions p ON e.position_id = p.position_id
                        WHERE e.account_id = '$accountId'";
                $result2 = mysqli_query($con, $query2);
                
                if ($result2 && mysqli_num_rows($result2) > 0) {
                    $row2 = mysqli_fetch_assoc($result2);

                    $_SESSION["firstName"] = $row2["first_name"];
                    $_SESSION["lastName"] = $row2["last_name"];
                    $_SESSION['employeeName'] = $row2['first_name'] . ' ' . $row2['last_name'];
                    $_SESSION['employeePosition'] = $row2['position_name'];
                    $_SESSION['user_type'] = $row["user_type"];
                    $_SESSION['employeeID'] = $row2["employee_id"];
                    $_SESSION['employment_status'] = $row2['employment_status'];

                    if ($row["user_type"] === "Employee") {
                        header('Location: employeePage/index.php');
                        exit();
                    } elseif ($row["user_type"] === "Manager") {
                        header('Location: managerPage/index.php');
                        exit();
                    } elseif ($row["user_type"] === "HR") {
                        header('Location: hrPage/index.php');
                        exit();
                    } else {
                        return "Unknown user type.";
                    }
                } else {
                    return "Employee details not found.";
                }
            }
        } else {
            return "Password do not match.";
        }
    } else {
        return "Invalid Employee ID";
    }
}

function redirectToLogin($userType) {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== $userType) {
        header("Location: ../index.php");
        exit();
    }
}

function handleApproveCOE($con) {
    $coeId = mysqli_real_escape_string($con, $_POST['coe_id']);
    $employeeId = mysqli_real_escape_string($con, $_POST['employee_id']);
    $processedBy = $_SESSION['employeeID'];
    
    // Check if file was uploaded
    if (isset($_FILES['coe_file']) && $_FILES['coe_file']['error'] === UPLOAD_ERR_OK) {
        // Validate file type and size
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['coe_file']['type'], $allowedTypes)) {
            $_SESSION['error_message'] = "Invalid file type. Only PDF, JPG, JPEG, and PNG files are allowed.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        
        if ($_FILES['coe_file']['size'] > $maxFileSize) {
            $_SESSION['error_message'] = "File too large. Maximum size is 5MB.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        
        // Read file data
        $fileData = mysqli_real_escape_string($con, file_get_contents($_FILES['coe_file']['tmp_name']));
        // $fileName = mysqli_real_escape_string($con, $_FILES['coe_file']['name']);
        // $fileType = mysqli_real_escape_string($con, $_FILES['coe_file']['type']);
        
        // Update COE record with file data and approval
        $updateQuery = "UPDATE coe SET 
                       status = 'Approved', 
                       file_data = '$fileData',
                       status_reason = 'Certificate approved and uploaded',
                       processed_by = '$processedBy',
                       updated_at = CURRENT_TIMESTAMP 
                       WHERE coe_id = '$coeId'";
        
        if (mysqli_query($con, $updateQuery)) {
            $_SESSION['success_message'] = "Certificate of Employment approved and file uploaded successfully!";
        } else {
            $_SESSION['error_message'] = "Database update failed: " . mysqli_error($con);
        }
    } else {
        $_SESSION['error_message'] = "Please select a valid file to upload.";
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

function handleDenyCOE($con) {
    $coeId = mysqli_real_escape_string($con, $_POST['coe_id']);
    $statusReason = mysqli_real_escape_string($con, $_POST['status_reason']);
    $processedBy = $_SESSION['employeeID'];
    
    $updateQuery = "UPDATE coe SET 
                   status = 'Denied', 
                   status_reason = '$statusReason',
                   processed_by = '$processedBy',
                   updated_at = CURRENT_TIMESTAMP 
                   WHERE coe_id = '$coeId'";
    
    if (mysqli_query($con, $updateQuery)) {
        $_SESSION['success_message'] = "Certificate of Employment request denied successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to deny request: " . mysqli_error($con);
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

function handleViewCOE($con) {
    $coeId = mysqli_real_escape_string($con, $_GET['coe_id']);
    
    $query = "SELECT c.file_data, c.employee_id, emp.first_name, emp.last_name 
              FROM coe c
              JOIN employees e ON c.employee_id = e.employee_id
              JOIN candidates emp ON e.candidate_id = emp.candidate_id
              WHERE c.coe_id = '$coeId' AND c.status = 'Approved'";
    
    $result = mysqli_query($con, $query);
    $coe = mysqli_fetch_assoc($result);
    
    if ($coe && !empty($coe['file_data'])) {
        // Determine file type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($coe['file_data']);
        
        $fileName = "COE_" . $coe['employee_id'] . "_" . $coe['first_name'] . "_" . $coe['last_name'];
        
        // Set appropriate file extension based on mime type
        $extensions = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png'
        ];
        
        $extension = $extensions[$mimeType] ?? 'bin';
        $fileName .= "." . $extension;
        
        // Set headers for inline viewing (instead of download)
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: inline; filename="' . $fileName . '"');
        header('Content-Length: ' . strlen($coe['file_data']));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        // Output the file data
        echo $coe['file_data'];
        exit();
    } else {
        $_SESSION['error_message'] = "Certificate file not found or request not approved.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

function sendCandidateEmail($toEmail, $toName, $subject, $message) {
    $email = new Mail();
    
    $sendgrid_api_key = SENDGRID_API_KEY;
    $from_email = 'soria_marcangelo@plpasig.edu.ph';
    $from_name = 'ACME CORP';
    
    try {
        $email->setFrom($from_email, $from_name);
        $email->setSubject($subject);
        $email->addTo($toEmail, $toName);
        $email->addContent("text/html", $message);
        
        $sendgrid = new SendGrid($sendgrid_api_key);
        $response = $sendgrid->send($email);
        
        return $response->statusCode() >= 200 && $response->statusCode() < 300;
        
    } catch (Exception $e) {
        error_log("SendGrid Error: " . $e->getMessage());
        return false;
    }
}

function sendApplicationUpdateEmail($candidateEmail, $candidateName, $position, $status, $interviewDate = null) {
    $subject = "Application Update - " . $position;
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 15px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Application Status Update</h2>
            </div>
            <div class='content'>
                <p>Dear $candidateName,</p>
                <p>We would like to inform you about an update regarding your application for the <strong>$position</strong> position.</p>
                
                <div style='background: white; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0;'>
                    <strong>Current Status:</strong> $status<br>
    ";
    
    if ($interviewDate && in_array($status, ['Initial Interview', 'Final Interview'])) {
        $formattedDate = date('F j, Y \a\t g:i A', strtotime($interviewDate));
        $message .= "<strong>Interview Schedule:</strong> $formattedDate<br>";
    }
    
    $message .= "
                </div>
                
                <p>We will contact you if we need any additional information.</p>
                <p>Thank you for your interest in joining our team!</p>

                <div class='footer'>
                    <p>Best regards,<br>HR Department<br>ACME CORPORATION</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendCandidateEmail($candidateEmail, $candidateName, $subject, $message);
}

function sendHiredEmail($candidateEmail, $candidateName, $position, $status, $employee_id) {
    $status = strtoupper($status);

    $subject = "Application Update - " . $position;
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 15px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Application Status Update</h2>
            </div>
            <div class='content'>
                <p>Dear $candidateName,</p>
                <p>We would like to inform you that you have been <strong>OFFICIALLY $status </strong> from your application for the <strong>$position</strong> position. Kindly take note of your Employee ID below as you will use it to access our system inside the company</p>
                
                <div style='background: white; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0;'>
                    <strong>Current Status:</strong> $status<br>
                    <strong>Employee ID:</strong> $employee_id<br>
                </div>
                
                <p>We will contact you if we need any additional information.</p>
                <p>Thank you for your interest in joining our team!</p>
                
                <div class='footer'>
                    <p>Best regards,<br>HR Department<br>ACME CORPORATION</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendCandidateEmail($candidateEmail, $candidateName, $subject, $message);
}