<?php
require "connection.php";
require "functions.php";
session_start();

$err = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = mysqli_real_escape_string($con, $_POST['employeeId']);
    
    $query = "SELECT 
                u1.login_identifier as employee_email,
                e.account_id
            FROM employees e
            JOIN user_accounts u1 ON e.candidate_id = u1.account_id
            JOIN user_accounts u2 ON e.account_id = u2.account_id
            WHERE e.employee_id = '$employeeId'";
    
    $result = mysqli_query($con, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $email = $user['employee_email'];
        
        $reset_token = sprintf("%06d", mt_rand(1, 999999));
        date_default_timezone_set('Asia/Manila');
        $reset_token_expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        $updateQuery = "UPDATE user_accounts 
                       SET reset_token = '$reset_token', 
                           reset_token_expires = '$reset_token_expires' 
                       WHERE account_id = " . $user['account_id'];
        
        if (mysqli_query($con, $updateQuery)) {
            $subject = "Password Reset Verification Code";
            $message = "
                <html>
                <head>
                    <title>Password Reset</title>
                </head>
                <body>
                    <h2>ACME CORPORATIONS - Password Reset</h2>
                    <p>Hello,</p>
                    <p>You have requested to reset your password. Use the verification code below:</p>
                    <h3 style='background-color: #f4f4f4; padding: 10px; text-align: center; font-size: 24px; letter-spacing: 5px;'>$reset_token</h3>
                    <p>This code will expire in 5 minutes.</p>
                    <p>If you didn't request this reset, please ignore this email.</p>
                    <br>
                    <p>Best regards,<br>ACME CORPORATIONS</p>
                </body>
                </html>
            ";
            
            if (sendCandidateEmail($email, 'Employee', $subject, $message)) {
                $_SESSION['reset_account_id'] = $user['account_id'];
                $_SESSION['reset_employee_id'] = $employeeId;
                $_SESSION['message'] = "Verification code sent to your email!";
                header("Location: verify_code.php");
                exit();
            } else {
                $err = "Failed to send verification email. Please try again.";
            }
        } else {
            $err = "Error generating reset token. Please try again.";
        }
    } else {
        $err = "Employee ID not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: "Poppins", sans-serif;
            background: linear-gradient(135deg, #0d1117, #1e293b);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #e2e8f0;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            background: linear-gradient(145deg, #1e293b, #111827);
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3);
            text-align: center;
        }

        .login-container h2.company-title {
            color: #4f46e5;
            font-weight: 800;
            margin-bottom: 15px;
        }

        .login-container h2.login-title {
            color: #e2e8f0;
            font-weight: 700;
            margin-bottom: 25px;
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

        .extra-links {
            margin-top: 12px;
        }

        .extra-links a {
            color: #8b5cf6;
            text-decoration: none;
            margin: 0 5px;
            transition: 0.3s;
        }

        .extra-links a:hover {
            text-decoration: underline;
        }

        .error-msg {
            color: #f87171;
            margin-bottom: 10px;
        }

        .success-msg {
            color: #4ade80;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="company-title">ACME CORPORATIONS</h2>
        <h2 class="login-title">Forgot Password</h2>

        <?php 
        if(isset($_SESSION['message'])){
            echo '<div class="alert alert-info">'.$_SESSION['message'].'</div>';
            unset($_SESSION['message']);
        }
        ?>

        <?php 
        if(!empty($err)){
            echo '<div class="error-msg">'.$err.'</div>';
        }
        if(!empty($success)){
            echo '<div class="success-msg">'.$success.'</div>';
        }
        ?>

        <form method="post">
            <input type="text" name="employeeId" class="form-control" placeholder="Employee ID" required>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Send Verification Code</button>
            </div>

            <div class="extra-links">
                <a href="index.php">Back to login</a>
            </div>
        </form>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>