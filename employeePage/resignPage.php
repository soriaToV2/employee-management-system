<?php

session_start();
require "../functions.php";
require "../connection.php";
redirectToLogin('Employee');

$currentEmployeeID = $_SESSION['employeeID'];

$err = null;
$lastDay = null;
$resignReason = null;

if (isset($_POST['submit'])) {
  $lastDay = $_POST['lastDay'];
  $resignReason = $_POST['resignReason'];
  
  $query = "INSERT INTO resignations (employee_id, reason, resignation_date) 
            VALUES ('$currentEmployeeID', '$resignReason', '$lastDay')";

  $today = date('Y-m-d');
    if ($lastDay <= $today) {
        $err = "Last working day must be in the future.";
    } elseif (empty($resignReason)) {
        $err = "Please provide a reason for resignation.";
    } else {
        $checkQuery = "SELECT resignation_id FROM resignations 
                      WHERE employee_id = '$currentEmployeeID' 
                      AND resignation_status = 'Pending'";
        $checkResult = mysqli_query($con, $checkQuery);
        
        if (mysqli_num_rows($checkResult) > 0) {
            $err = "You already have a pending resignation request.";
        } else {
            $query = "INSERT INTO resignations (employee_id, reason, resignation_date) 
                     VALUES ('$currentEmployeeID', '$resignReason', '$lastDay')";
            
            mysqli_begin_transaction($con);
            if (mysqli_query($con, $query)) {
                mysqli_commit($con);
                $success = "Your resignation has been successfully submitted! HR will review your request shortly.";
            } else {
                mysqli_rollback($con);
                $err = "Error submitting resignation: " . mysqli_error($con);
            }
          }
      }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resignation | Employee Portal</title>
  <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../css/Global.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container-fluid">
    <div class="row flex-nowrap">

      <?php include 'nav.php'; ?>
      
      <!-- Main Content -->
      <div class="col py-4 px-5">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
          <h2 class="fw-bold">Resignation</h2>
          <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width:45px; height:45px;">EN</div>
            <div>
                    <h6 class="mb-0"><?php echo $_SESSION['employeeName']; ?></h6>
                    <small><?php echo $_SESSION['employeePosition']; ?></small>
                </div>
          </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Success!</strong> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($err)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Error!</strong> <?php echo $err; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Page Content -->
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-door-open me-2"></i> File a Resignation</h5>
          </div>

          <div class="card-body">
            <div class="alert alert-warning">
              <strong>
                <i class="fa-solid fa-triangle-exclamation" style="color: #f39c12; font-size: 24px;"></i>
              </strong>
              Important Information:<br>
              Before proceeding, please consider discussing your concerns with your manager or HR. 
              We value your contributions and would like to address any issues you may be facing.
            </div>

            <form action="resignPage.php" method="post" id="resignationForm">
              <!-- Last Working Day -->
              <div class="mb-3">
                <label for="last-day" class="form-label fw-semibold">Proposed Last Working Day</label>
                <input type="date" id="last-day" name="lastDay" value="<?php echo $lastDay; ?>" class="form-control" required>
              </div>

              <!-- Comments -->
              <div class="mb-3">
                <label for="comments" class="form-label fw-semibold">Reasons for Resignation</label>
                <textarea id="comments" name="resignReason" class="form-control" rows="4" placeholder="Provide your reason for resignation in detailed"><?php echo $resignReason; ?></textarea>
              </div>

              <!-- Submit Button -->
              <div class="d-flex justify-content-end">
                <button type="submit" name="submit" class="btn btn-primary px-4">
                  <i class="fas fa-paper-plane me-1"></i> Submit Resignation
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>

</body>
</html>