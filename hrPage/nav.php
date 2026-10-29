<?php
//HELLO WORLDS


$currentPage = basename($_SERVER['PHP_SELF']);
?>


<!-- Sidebar -->
      <div class="sidebar bg-dark min-vh-100">
        <div class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-3 text-white">
          <h3 class="fs-4">Company Name</h3>
          <p class="small mb-4">HR Portal</p>

          <ul class="nav nav-pills flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start w-100" id="menu">
            <li class="nav-item w-100">
              <a href="index.php" class="nav-link px-3 <?php echo ($currentPage == 'index.php') ? 'active bg-primary' : 'text-white'; ?>">
                  <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                  <?php if($currentPage == 'index.php') echo '<span class="status-dot"></span>'; ?>
              </a>
            </li>
            <li class="w-100">
              <a href="employeePage.php" class="nav-link px-3 <?php echo ($currentPage == 'employeePage.php') ? 'active bg-primary' : 'text-white'; ?>">
                  <i class="fas fa-users me-2"></i> Employees
                  <?php if($currentPage == 'employeePage.php') echo '<span class="status-dot"></span>'; ?>
              </a>
            </li>
            <li class="w-100">
              <a href="leaveReqPage.php" class="nav-link px-3 <?php echo ($currentPage == 'leaveReqPage.php') ? 'active bg-primary' : 'text-white'; ?>">
                  <i class="fas fa-calendar-alt me-2"></i> Leave Requests
                  <?php if($currentPage == 'leaveReqPage.php') echo '<span class="status-dot"></span>'; ?>
              </a>
            </li>
            <li class="w-100">
              <a href="resignPage.php" class="nav-link px-3 <?php echo ($currentPage == 'resignPage.php') ? 'active bg-primary' : 'text-white'; ?>">
                  <i class="fas fa-sign-out-alt me-2"></i> Resignations
                  <?php if($currentPage == 'resignPage.php') echo '<span class="status-dot"></span>'; ?>
              </a>
            </li>
            <li class="w-100">
              <a href="recruitPage.php" class="nav-link px-3 <?php echo ($currentPage == 'recruitPage.php') ? 'active bg-primary' : 'text-white'; ?>">
                  <i class="fas fa-user-plus me-2"></i> Recruitment
                  <?php if($currentPage == 'recruitPage.php') echo '<span class="status-dot"></span>'; ?>
              </a>
            </li>
            <li class="w-100">
              <a href="coe.php" class="nav-link px-3 <?php echo ($currentPage == 'coe.php') ? 'active bg-primary' : 'text-white'; ?>">
                  <i class="fas fa-user-plus me-2"></i> Employment Cert
                  <?php if($currentPage == 'coe.php') echo '<span class="status-dot"></span>'; ?>
              </a>
            </li>
            <li class="w-100">
              <a href="promotePage.php" class="nav-link px-3 <?php echo ($currentPage == 'promotePage.php') ? 'active bg-primary' : 'text-white'; ?>">
                  <i class="fas fa-chart-line me-2"></i> Promote/Demote
                  <?php if($currentPage == 'promotePage.php') echo '<span class="status-dot"></span>'; ?>
              </a>
            </li>
            <li class="w-100">
              <a href="violatePage.php" class="nav-link px-3 <?php echo ($currentPage == 'violatePage.php') ? 'active bg-primary' : 'text-white'; ?>">
                  <i class="fas fa-exclamation-triangle me-2"></i> Violations
                  <?php if($currentPage == 'violatePage.php') echo '<span class="status-dot"></span>'; ?>
              </a>
            </li>
            <li class="w-100">
              <a href="settings.php" class="nav-link px-3 <?php echo ($currentPage == 'settings.php') ? 'active bg-primary' : 'text-white'; ?>">
                  <i class="fas fa-cog me-2"></i> Settings
                  <?php if($currentPage == 'settings.php') echo '<span class="status-dot"></span>'; ?>
              </a>
            </li>
            <li class="w-100">
              <a href="../logout.php" class="nav-link text-white px-3">
                <i class="fas fa-exclamation-triangle me-2"></i> Logout
              </a>
            </li>
          </ul>
        </div>
      </div>