<?php
session_start();
require 'connection.php';

$err = null;
$employeeId = null;
$currentPass = null;
$newPass = null;
$confirmPass = null;

if(isset($_SESSION['employeeID']))
{
    $employeeId = $_SESSION['employeeID'];
}

if (isset($_POST['resetPass'])) {
    $employeeId = $_POST['employeeId'];
    $currentPass = $_POST['currentPass'];
    $newPass = $_POST['newPass'];
    $confirmPass = $_POST['confirmPass'];

    $query = "SELECT * FROM user_accounts WHERE login_identifier = '$employeeId'";
    $result = mysqli_query($con, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($currentPass, $row["password"])) {
            if ($newPass === $confirmPass) {
                $hashedNewPass = password_hash($newPass, PASSWORD_BCRYPT);

                mysqli_begin_transaction($con);
                $updateQuery = "UPDATE user_accounts SET password = '$hashedNewPass', is_first_login = 0 WHERE login_identifier = '$employeeId'";
                if (mysqli_query($con, $updateQuery)) {
                    mysqli_commit($con);
                    session_unset();
                    session_destroy();
                    $_SESSION['message'] = 'Password successfully updated. Please login with your new password.';
                    header('Location: index.php');
                    exit();
                } else {
                    mysqli_rollback($con);
                    $err = "Error updating password. Please try again.";
                }
            } else {
                $err = "New password and confirm password do not match.";
            }
        } else {
            $err = "Current password is incorrect.";
        }
    } else {
        $err = "Invalid Employee ID.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
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

        <?php 
        if(isset($_SESSION['message'])){
            echo ''.$_SESSION['message'].'';
        }
        ?><br><br>
        <form action="changePass.php" method="post">
            <label for="employeeId">Employee ID:</label><br>
            <input type="text" name="employeeId" id="employeeId" class="form-control" value="<?php echo $employeeId ;?>"><br><br>
            <label for="currentPass">Current Password:</label><br>
            <input type="password" name="currentPass" id="currentPass" class="form-control"><br><br>
            <label for="newPass">New Password:</label><br>
            <input type="password" name="newPass" id="newPass" class="form-control"><br><br>
            <label for="confirmPass">Confirm Password:</label><br>
            <input type="password" name="confirmPass" id="confirmPass" class="form-control">

            <?php echo $err; ?><br><br>

            <input type="submit" value="Reset Password" name="resetPass" class="btn btn-primary btn-lg"><br><br>
            <a href="index.php">Back to login</a>
        </form>
    </div>
    

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>

</body>
</html>