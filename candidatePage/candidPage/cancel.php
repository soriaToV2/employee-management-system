<?php
require_once '../../connection.php';
session_start();

if (!isset($_SESSION['candidate_id'])) {
    die("You must log in to cancel an application.");
}

$candidate_id = $_SESSION['candidate_id'];

if (!isset($_GET['id'])) {
    die("Invalid application.");
}

$application_id = intval($_GET['id']);

$check = $con->prepare("SELECT * FROM applications WHERE application_id=? AND candidate_id=?");
$check->bind_param("ii", $application_id, $candidate_id);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    die("You cannot cancel this application.");
}

// Update status
$update = $con->prepare("UPDATE applications SET status='Cancelled' WHERE application_id=? AND candidate_id=?");
$update->bind_param("ii", $application_id, $candidate_id);
$update->execute();

if ($update->affected_rows > 0) {
    echo "<script>
            alert('Application cancelled successfully!');
            window.location='candidPage.php?tab=applications';
          </script>";
} else {
    echo "<script>
            alert('No changes made or application not found.');
            window.location='candidPage.php?tab=applications';
          </script>";
}
?>