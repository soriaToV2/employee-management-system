<?php
require "connection.php";
session_start();
if (!isset($_SESSION['reset_account_id'])) {
    header("Location: forgotPass.php");
    exit();
}

$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_code = mysqli_real_escape_string($con, $_POST['verification_code']);
    
    $account_id = $_SESSION['reset_account_id'];
    
    $query = "SELECT reset_token, reset_token_expires 
              FROM user_accounts 
              WHERE account_id = $account_id 
              AND reset_token = '$entered_code' 
              AND reset_token_expires > NOW()";
    
    $result = mysqli_query($con, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $_SESSION['code_verified'] = true;
        header("Location: reset_password.php");
        exit();
    } else {
        $err = "Invalid or expired verification code!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code</title>
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
            text-align: center;
            font-size: 18px;
            letter-spacing: 5px;
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

        .info-text {
            color: #94a3b8;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="company-title">ACME CORPORATIONS</h2>
        <h2 class="login-title">Enter Verification Code</h2>

        <p class="info-text">Please check your email and enter the 6-digit verification code.</p>

        <?php 
        if(!empty($err)){
            echo '<div class="error-msg">'.$err.'</div>';
        }
        ?>

        <form method="post">
            <input type="text" name="verification_code" class="form-control" placeholder="000000" maxlength="6" required pattern="[0-9]{6}" title="Enter 6-digit code">
            
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Verify Code</button>
            </div>

            <div class="extra-links">
                <a href="forgotPass.php">Back</a> | 
                <a href="index.php">Login</a>
            </div>
        </form>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-format the verification code input
        document.querySelector('input[name="verification_code"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>