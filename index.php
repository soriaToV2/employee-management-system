<?php
session_start();
require 'connection.php';
require 'functions.php';

$loginErr = null;
$employeeId = null;
$password = null;

if (isset($_POST['login'])) {
    $employeeId = $_POST['employeeId'];
    $password = $_POST['password'];
    $loginErr = login($con, $employeeId, $password);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login</title>
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
    </style>
</head>
<body>

    <div class="login-container">
        <h2 class="company-title">ACME CORPORATIONS</h2>
        <h2 class="login-title">EMPLOYEE LOGIN</h2>

        <?php 
        if(isset($_SESSION['message'])){
            echo '<div class="alert alert-info">'.$_SESSION['message'].'</div>';
            unset($_SESSION['message']);
        }
        ?>

        <?php 
        if(!empty($loginErr)){
            echo '<div class="error-msg">'.$loginErr.'</div>';
        }
        ?>

        <form action="index.php" method="post">
            <input type="text" name="employeeId" class="form-control" placeholder="Employee ID" value="<?php echo $employeeId; ?>" required>
            <input type="password" name="password" class="form-control" placeholder="Password" required>

            <div class="d-grid">
                <button type="submit" name="login" class="btn btn-primary btn-lg">Login</button>
            </div>

            <div class="extra-links">
                <a href="forgotPass.php">Forgot Password?</a>
            </div>
        </form>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
