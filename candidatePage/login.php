<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/google_config.php'; // Google OAuth keys (not in git)
require '../connection.php';
require '../functions.php';

use Google\Client;

$client = new Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET); 
$client->setRedirectUri(GOOGLE_REDIRECT_URI);

$client->addScope('email');
$client->addScope('profile');
$client->addScope('https://www.googleapis.com/auth/drive.file');

$url = $client->createAuthUrl();

$err = null;
if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM user_accounts WHERE login_identifier = '$email'";
    $result = mysqli_query($con, $query);

    if(mysqli_num_rows($result) > 0){
        $user = mysqli_fetch_assoc($result);
        if(password_verify($password, $user['password'])){
            $query1 = "SELECT * FROM candidates WHERE candidate_id = '".$user['account_id']."'";
            $result1 = mysqli_query($con, $query1);
            $candidate = mysqli_fetch_assoc($result1);

            $_SESSION['candidate_id'] = $user['account_id'];
            $_SESSION['candidate_email'] = $user['login_identifier'];
            $_SESSION['candidate_firstName'] = $candidate['first_name'];
            $_SESSION['candidate_lastName'] = $candidate['last_name'];
            $_SESSION['user_type'] = $candidate['last_name'];

            header("Location: candidPage/candidPage.php");
            exit();
        } else {
            $err = "Invalid password.";
        }
    } else {
        $err = "User not found with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Login</title>
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
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

        .btn-google {
            border-radius: 50px;
            padding: 12px;
            font-weight: 600;
            background: #ffffff;
            color: #374151;
            border: 1px solid #d1d5db;
            transition: 0.3s;
        }

        .btn-google:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }

        .error-msg {
            color: #f87171;
            margin-bottom: 15px;
        }

        .divider {
            margin: 20px 0;
            text-align: center;
            position: relative;
            color: #94a3b8;
        }

        .divider::before,
        .divider::after {
            content: '';
            height: 1px;
            width: 40%;
            background: #1e293b;
            position: absolute;
            top: 50%;
        }

        .divider::before {
            left: 0;
        }

        .divider::after {
            right: 0;
        }

        .divider span {
            padding: 0 10px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h2 class="company-title">ACME CORPORATIONS</h2>
        <h2 class="login-title">LOGIN</h2>

        <?php 
        if(isset($_SESSION['message'])){
            echo '<div class="alert alert-info">'.$_SESSION['message'].'</div>';
            unset($_SESSION['message']);
        }

        if(!empty($err)){
            echo '<div class="error-msg">'.$err.'</div>';
        }
        ?>

        <form action="login.php" method="post">
            <input type="text" name="email" class="form-control" placeholder="Email" required>
            <input type="password" name="password" class="form-control" placeholder="Password" required>

            <div class="d-grid gap-2 mb-3">
                <button type="submit" name="login" class="btn btn-primary btn-lg">Login</button>
            </div>

            <div class="divider"><span>or</span></div>

            <div class="d-grid">
                <a href="<?= $url ?>" class="btn btn-google btn-lg d-flex align-items-center justify-content-center gap-2">
                    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" 
                        alt="Google Icon" style="width:20px; height:20px;">
                    Sign in with Google
                </a>

            </div>
        </form>
    </div>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>