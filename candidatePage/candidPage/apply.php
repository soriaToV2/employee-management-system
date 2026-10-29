<?php
require_once '../../connection.php';
session_start();

if (!isset($_SESSION['candidate_id'])) {
    die("You must log in to apply.");
}

$candidate_id = $_SESSION['candidate_id'];

if (!isset($_GET['job_id']) || empty($_GET['job_id'])) {
    die("Invalid job.");
}

$position_id = intval($_GET['job_id']);

$success = false;

// Check if the candidate already applied
$check = $con->prepare("SELECT application_id, status FROM applications WHERE candidate_id=? AND position_id=?");
$check->bind_param("ii", $candidate_id, $position_id);
$check->execute();
$result = $check->get_result();

if ($row = $result->fetch_assoc()) {
    if ($row['status'] === 'Cancelled') {
        // Reactivate cancelled application
        $update = $con->prepare("UPDATE applications SET status='Applied', date_applied=NOW() WHERE application_id=?");
        $update->bind_param("i", $row['application_id']);
        if ($update->execute()) {
            $success = true; // reactivation successful
        }
    }
    // If already active, silently redirect
    if (!$success) {
        header("Location: candidPage.php?tab=applications");
        exit;
    }
} else {
    // Insert new application if never applied
    $insert = $con->prepare("INSERT INTO applications (candidate_id, position_id, status, date_applied) VALUES (?, ?, 'Applied', NOW())");
    $insert->bind_param("ii", $candidate_id, $position_id);
    if ($insert->execute()) {
        $success = true;
    } else {
        echo "Error: " . $con->error;
        exit;
    }
}

// Show success message
if ($success) {
    echo "<script>
            alert('Application submitted successfully!');
            window.location='candidPage.php?tab=applications';
          </script>";
}
?>