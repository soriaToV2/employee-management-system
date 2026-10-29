<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar bg-dark min-vh-100">
  <div class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-3 text-white">
    <h3 class="fs-4">Company Name</h3>
    <p class="small mb-4">Manager Portal</p>

    <ul class="nav nav-pills flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start w-100" id="menu">
      <li class="nav-item w-100">
        <a href="index.php" class="nav-link px-3 <?php echo ($currentPage == 'index.php') ? 'active bg-primary' : 'text-white'; ?>">
          <i class="fas fa-tachometer-alt me-2"></i> Dashboard
          <?php if($currentPage == 'index.php') echo '<span class="status-dot"></span>'; ?>
        </a>
      </li>
      <li class="w-100">
        <a href="performPage.php" class="nav-link text-white px-3 <?php echo ($currentPage == 'performPage.php') ? 'active bg-primary' : 'text-white'; ?>">
          <i class="fas fa-chart-line me-2"></i> Performance
          <?php if($currentPage == 'performPage.php') echo '<span class="status-dot"></span>'; ?>
        </a>
      </li>
      <li class="w-100">
        <a href="emploPage.php" class="nav-link text-white px-3 <?php echo ($currentPage == 'emploPage.php') ? 'active bg-primary' : 'text-white'; ?>">
          <i class="fas fa-users me-2"></i> Employees
          <?php if($currentPage == 'emploPage.php') echo '<span class="status-dot"></span>'; ?>
        </a>
      </li>
      <li class="w-100">
        <a href="leaveReqPage.php" class="nav-link text-white px-3 <?php echo ($currentPage == 'leaveReqPage.php') ? 'active bg-primary' : 'text-white'; ?>">
          <i class="fas fa-calendar-alt me-2"></i> Leave Requests
          <?php if($currentPage == 'leaveReqPage.php') echo '<span class="status-dot"></span>'; ?>
        </a>
      </li>
      <li class="w-100">
        <a href="resignPage.php" class="nav-link text-white px-3 <?php echo ($currentPage == 'resignPage.php') ? 'active bg-primary' : 'text-white'; ?>">
          <i class="fas fa-sign-out-alt me-2"></i> Resignations
          <?php if($currentPage == 'resignPage.php') echo '<span class="status-dot"></span>'; ?>
        </a>
      </li>
      <li class="w-100">
        <a href="newEmpPage.php" class="nav-link text-white px-3 <?php echo ($currentPage == 'newEmpPage.php') ? 'active bg-primary' : 'text-white'; ?>">
          <i class="fas fa-user-plus me-2"></i> New Employee
          <?php if($currentPage == 'newEmpPage.php') echo '<span class="status-dot"></span>'; ?>
        </a>
      </li>
      <li class="w-100">
        <a href="../logout.php" class="nav-link text-white px-3">
        <i class="fas fa-user-plus me-2"></i> Logout
        </a>
      </li>
    </ul>
  </div>
</div>