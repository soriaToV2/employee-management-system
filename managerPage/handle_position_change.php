<?php
session_start();
require "../functions.php";
require "../connection.php";
redirectToLogin('Manager');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id =  $_POST['employee_id'];
    $proposed_position_id =  $_POST['proposed_position_id'];
    $change_type =  $_POST['change_type'];
    $reason =  $_POST['reason'];

    if (empty($employee_id) || empty($proposed_position_id) || empty($change_type) || empty($reason)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: emploPage.php");
        exit;
    }

    try {
        $query = "INSERT INTO promotion_request (
                    employee_id, 
                    proposed_position, 
                    change_type, 
                    reason, 
                    status
                  ) VALUES ('$employee_id', $proposed_position_id, '$change_type', '$reason', 'Pending')";
        mysqli_begin_transaction($con);
        if (mysqli_query($con, $query)) {
            $_SESSION['success'] = "Position change request submitted successfully!";
            mysqli_commit($con);
        } else {
            mysqli_rollback($con);
            throw new Exception('Failed to insert into database: ' . mysqli_error($con));
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    header("Location: emploPage.php");
    exit;
}

?>