<?php
session_start();
require "../functions.php";
require "../connection.php";
redirectToLogin('HR');

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_coe'])) {
        handleApproveCOE($con);
    } elseif (isset($_POST['deny_coe'])) {
        handleDenyCOE($con);
    }
}

// Handle GET request for viewing COE file
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['view_coe'])) {
    handleViewCOE($con);
}

// Fetch all COE requests with employee details
$coeQuery = "SELECT c.coe_id, c.employee_id, c.status, c.status_reason, c.request_date, c.updated_at,
                    c.file_data, c.processed_by,
                    emp.first_name, emp.last_name, 
                    p.position_name, d.department_name,
                    processor.first_name as processor_first, processor.last_name as processor_last
             FROM coe c
             JOIN employees e ON c.employee_id = e.employee_id
             JOIN candidates emp ON e.candidate_id = emp.candidate_id
             LEFT JOIN positions p ON e.position_id = p.position_id
             LEFT JOIN departments d ON p.department_id = d.department_id
             LEFT JOIN employees ep ON c.processed_by = ep.employee_id
             LEFT JOIN candidates processor ON ep.candidate_id = processor.candidate_id
             ORDER BY 
                 CASE WHEN c.status = 'Pending' THEN 1 ELSE 2 END,
                 c.request_date DESC";
$coeResult = mysqli_query($con, $coeQuery);
$coeRequests = [];
while ($row = mysqli_fetch_assoc($coeResult)) {
    $coeRequests[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Management | Company Name</title>
  <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../css/Global.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body>
  <div class="container-fluid">
    <div class="row flex-nowrap">

      <!-- Sidebar -->
      <?php include 'nav.php' ?>

      <!-- Main Content -->
      <div class="col py-4 px-5">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
          <h2 class="fw-bold">Employee Management</h2>
          <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width:45px; height:45px;">HR</div>
            <div>
              <h6 class="mb-0"><?php echo $_SESSION['employeeName']; ?></h6>
              <small><?php echo $_SESSION['employeePosition']; ?></small>
            </div>
          </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- COE Requests Card -->
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
            <div>
              <i class="fas fa-file-certificate me-2"></i>
              <h5 class="mb-0 d-inline">Certificate of Employment Requests</h5>
            </div>
            <span class="badge bg-light text-dark">
              <?php 
              $pendingCount = array_filter($coeRequests, function($req) { return $req['status'] == 'Pending'; });
              echo count($pendingCount) . ' Pending';
              ?>
            </span>
          </div>
          <div class="card-body">
            <?php if (!empty($coeRequests)): ?>
            <div class="table-responsive">
              <table class="table table-bordered align-middle">
                <thead>
                  <tr>
                    <th>Employee Name</th>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Request Date</th>
                    <th>Processed By</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($coeRequests as $request): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($request['employee_id']); ?></td>
                    <td>
                      <span class="badge 
                        <?php 
                        switch($request['status']) {
                            case 'Approved': echo 'bg-success'; break;
                            case 'Denied': echo 'bg-danger'; break;
                            default: echo 'bg-warning text-dark';
                        }
                        ?>">
                        <?php echo htmlspecialchars($request['status']); ?>
                      </span>
                      <?php if ($request['status_reason']): ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars($request['status_reason']); ?></small>
                      <?php endif; ?>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                    <td><?php echo $request['processor_first'] ? htmlspecialchars($request['processor_first'] . ' ' . $request['processor_last']) : 'N/A'; ?></td>
                    <td>
                    <div class="d-flex gap-1">
                      <!-- Info Button - Always visible -->
                      <button title="More Info" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#infoModal<?php echo $request['coe_id']; ?>">
                        <i class="fas fa-info-circle"></i>
                      </button>
                      
                      <?php if ($request['status'] == 'Pending'): ?>
                        <button title="Approve" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $request['coe_id']; ?>">
                          <i class="fas fa-check"></i>
                        </button>
                        <button title="Deny" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#denyModal<?php echo $request['coe_id']; ?>">
                          <i class="fas fa-times"></i>
                        </button>
                      <?php elseif ($request['status'] == 'Approved' && !empty($request['file_data'])): ?>
                        <a title="View" href="?view_coe=1&coe_id=<?php echo $request['coe_id']; ?>" target="_blank" class="btn btn-primary btn-sm">
                          <i class="fas fa-eye"></i>
                        </a>
                      <?php elseif ($request['status'] == 'Approved' && empty($request['file_data'])): ?>
                        <span class="text-muted small">No file uploaded</span>
                      <?php endif; ?>
                    </div>
                  </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php else: ?>
              <div class="text-center py-4">
                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                <h5>No Certificate of Employment Requests</h5>
                <p class="text-muted">There are no COE requests to display.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Info Modals -->
  <?php foreach ($coeRequests as $request): ?>
  <div class="modal fade" id="infoModal<?php echo $request['coe_id']; ?>" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">COE Request Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <strong>Employee Name:</strong><br>
              <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
            </div>
            <div class="col-md-6">
              <strong>Employee ID:</strong><br>
              <?php echo htmlspecialchars($request['employee_id']); ?>
            </div>
          </div>
          
          <div class="row mb-3">
            <div class="col-md-6">
              <strong>Department:</strong><br>
              <?php echo htmlspecialchars($request['department_name'] ?? 'N/A'); ?>
            </div>
            <div class="col-md-6">
              <strong>Position:</strong><br>
              <?php echo htmlspecialchars($request['position_name'] ?? 'N/A'); ?>
            </div>
          </div>
          
          <div class="row mb-3">
            <div class="col-md-6">
              <strong>Request Date:</strong><br>
              <?php echo date('M d, Y', strtotime($request['request_date'])); ?>
            </div>
            <div class="col-md-6">
              <strong>Last Updated:</strong><br>
              <?php echo date('M d, Y', strtotime($request['updated_at'])); ?>
            </div>
          </div>
          
          <div class="row mb-3">
            <div class="col-12">
              <strong>Status:</strong><br>
              <span class="badge 
                <?php 
                switch($request['status']) {
                    case 'Approved': echo 'bg-success'; break;
                    case 'Denied': echo 'bg-danger'; break;
                    default: echo 'bg-warning text-dark';
                }
                ?>">
                <?php echo htmlspecialchars($request['status']); ?>
              </span>
            </div>
          </div>
          
          <?php if ($request['status_reason']): ?>
          <div class="row mb-3">
            <div class="col-12">
              <strong>Status Reason:</strong><br>
              <div class="border rounded p-3">
                <?php echo htmlspecialchars($request['status_reason']); ?>
              </div>
            </div>
          </div>
          <?php endif; ?>
          
          <?php if ($request['processed_by']): ?>
          <div class="row">
            <div class="col-12">
              <strong>Processed By:</strong><br>
              <?php echo $request['processor_first'] ? htmlspecialchars($request['processor_first'] . ' ' . $request['processor_last']) : 'N/A'; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Approve Modals -->
  <?php foreach ($coeRequests as $request): ?>
    <?php if ($request['status'] == 'Pending'): ?>
  <div class="modal fade" id="approveModal<?php echo $request['coe_id']; ?>" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post" action="" enctype="multipart/form-data">
          <input type="hidden" name="approve_coe" value="1">
          <input type="hidden" name="coe_id" value="<?php echo $request['coe_id']; ?>">
          <input type="hidden" name="employee_id" value="<?php echo $request['employee_id']; ?>">
          <div class="modal-header">
            <h5 class="modal-title">Approve Certificate of Employment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>Approve COE request for <strong><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></strong>?</p>
            <div class="mb-3">
              <label for="coe_file" class="form-label">Upload Certificate File</label>
              <input type="file" class="form-control" id="coe_file" name="coe_file" 
                     accept=".pdf,.jpg,.jpeg,.png" required>
              <div class="form-text">Supported formats: PDF, JPG, JPEG, PNG (Max: 5MB)</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-success">Approve & Upload</button>
          </div>
        </form>
      </div>
    </div>
  </div>
    <?php endif; ?>
  <?php endforeach; ?>

  <!-- Deny Modals -->
  <?php foreach ($coeRequests as $request): ?>
    <?php if ($request['status'] == 'Pending'): ?>
  <div class="modal fade" id="denyModal<?php echo $request['coe_id']; ?>" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post" action="">
          <input type="hidden" name="deny_coe" value="1">
          <input type="hidden" name="coe_id" value="<?php echo $request['coe_id']; ?>">
          <div class="modal-header">
            <h5 class="modal-title">Deny Certificate of Employment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>Deny COE request for <strong><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></strong>?</p>
            <div class="mb-3">
              <label for="status_reason" class="form-label">Reason for Denial</label>
              <textarea class="form-control" id="status_reason" name="status_reason" rows="3" 
                        placeholder="Enter reason for denial..." required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger">Deny Request</button>
          </div>
        </form>
      </div>
    </div>
  </div>
    <?php endif; ?>
  <?php endforeach; ?>

  <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>