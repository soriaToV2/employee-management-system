<?php
require "connection.php";
session_start();

if (!isset($_SESSION['reset_account_id']) || !isset($_SESSION['code_verified'])) {
    header("Location: forgotPass.php");
    exit();
}

$err = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $err = "Passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $err = "Password must be at least 6 characters long!";
    } else {
        $account_id = $_SESSION['reset_account_id'];
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $updateQuery = "UPDATE user_accounts 
                       SET password = '$hashed_password', 
                           reset_token = NULL, 
                           reset_token_expires = NULL
                       WHERE account_id = $account_id";
                          
        if (mysqli_query($con, $updateQuery)) {
            $success = "Password reset successfully!";
            session_unset();
            
            echo '<script>
                setTimeout(function() {
                    window.location.href = "index.php";
                }, 2000);
            </script>';
        } else {
            $err = "Error resetting password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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

        .form-control {
            background-color: #111827 !important;
            color: #e2e8f0 !important;
            border: 1px solid #1e293b !important;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 15px;
            transition: all 0.25s ease;
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
        <h2 class="login-title">Reset Password</h2>

        <?php 
        if(!empty($err)){
            echo '<div class="error-msg">'.$err.'</div>';
        }
        if(!empty($success)){
            echo '<div class="success-msg">'.$success.'</div>';
        }
        ?>

        <?php if (empty($success)): ?>
        <form method="post">
            <input type="password" name="new_password" class="form-control" placeholder="New Password" required minlength="6">
            <input type="password" name="confirm_password" class="form-control" placeholder="Confirm New Password" required minlength="6">
            
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Reset Password</button>
            </div>

            <div class="extra-links">
                <a href="verify_code.php">Back</a> | 
                <a href="index.php">Login</a>
            </div>
        </form>
        <?php else: ?>
            <div class="extra-links">
                <a href="index.php">Go to Login</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>