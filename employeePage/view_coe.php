<?php
session_start();
require "../functions.php";
require "../connection.php";
redirectToLogin('Employee');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['coe_id'])) {
    $coeId = mysqli_real_escape_string($con, $_POST['coe_id']);
    $currentEmployeeID = $_SESSION['employeeID'];
    
    // Verify that the COE belongs to the current employee
    $query = "SELECT c.coe_id, c.employee_id, c.file_data, 
                     emp.first_name, emp.last_name
              FROM coe c
              JOIN employees e ON c.employee_id = e.employee_id
              JOIN candidates emp ON e.candidate_id = emp.candidate_id
              WHERE c.coe_id = '$coeId' 
              AND c.employee_id = '$currentEmployeeID' 
              AND c.status = 'Approved' 
              AND c.file_data IS NOT NULL";
    
    $result = mysqli_query($con, $query);
    $coe = mysqli_fetch_assoc($result);
    
    if ($coe && !empty($coe['file_data'])) {
        // Create a temporary file to determine MIME type
        $tempFile = tmpfile();
        fwrite($tempFile, $coe['file_data']);
        $tempFilePath = stream_get_meta_data($tempFile)['uri'];
        
        // Get MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tempFilePath);
        finfo_close($finfo);
        
        // Close temp file
        fclose($tempFile);
        
        // Set headers for inline display
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: inline; filename="Certificate_of_Employment_' . $coe['employee_id'] . '"');
        header('Content-Length: ' . strlen($coe['file_data']));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Expires: 0');
        
        // Output the file data
        echo $coe['file_data'];
        exit();
    } else {
        // If file not found, redirect back with error
        $_SESSION['message'] = "Certificate file not found or you don't have permission to view it.";
        $_SESSION['message_type'] = "error";
        header("Location: profPage.php");
        exit();
    }
} else {
    // Invalid request
    header("Location: profPage.php");
    exit();
}
?>