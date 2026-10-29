<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$employmentStatus = $_SESSION['employment_status'] ?? '';
$isProbationary = ($employmentStatus === 'Probationary');

$navItems = [
    [
        'href' => 'index.php',
        'icon' => 'fas fa-tachometer-alt',
        'text' => 'Dashboard',
        'restricted' => false
    ],
    [
        'href' => 'performPage.php',
        'icon' => 'fas fa-chart-line',
        'text' => 'Performance',
        'restricted' => true
    ],
    [
        'href' => 'leavePage.php',
        'icon' => 'fas fa-calendar-alt',
        'text' => 'Leave Request',
        'restricted' => true
    ],
    [
        'href' => 'reportPage.php',
        'icon' => 'fas fa-file-alt',
        'text' => 'File a Report',
        'restricted' => false
    ],
    [
        'href' => 'resignPage.php',
        'icon' => 'fas fa-sign-out-alt',
        'text' => 'Resignation',
        'restricted' => false
    ],
    [
        'href' => 'profPage.php',
        'icon' => 'fas fa-user',
        'text' => 'Profile',
        'restricted' => false
    ]
];
?>

<div class="sidebar bg-dark min-vh-100">
  <div class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-3 text-white">
    <h3 class="fs-4">Company Name</h3>
    <p class="small mb-4">Employee Portal</p>

    <ul class="nav nav-pills flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start w-100" id="menu">
      <?php foreach ($navItems as $item): ?>
        <li class="w-100">
          <?php 
          $isCurrentPage = ($currentPage == $item['href']);
          $isDisabled = ($isProbationary && $item['restricted']);
          ?>
          
          <?php if ($isDisabled): ?>
            <a href="#" class="nav-link px-3 text-muted disabled" style="pointer-events: none; opacity: 0.6;" title="Not available for probationary employees">
              <i class="<?php echo $item['icon']; ?> me-2"></i> <?php echo $item['text']; ?>
              <small class="ms-2 badge bg-warning">Probationary</small>
            </a>
          <?php else: ?>
            <a href="<?php echo $item['href']; ?>" class="nav-link px-3 <?php echo $isCurrentPage ? 'active bg-primary' : 'text-white'; ?>">
              <i class="<?php echo $item['icon']; ?> me-2"></i> <?php echo $item['text']; ?>
              <?php if($isCurrentPage) echo '<span class="status-dot"></span>'; ?>
            </a>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
      
      <li class="w-100">
        <a href="../logout.php" class="nav-link text-white px-3">
          <i class="fas fa-sign-out-alt me-2"></i> Logout
        </a>
      </li>
    </ul>
  </div>
</div>