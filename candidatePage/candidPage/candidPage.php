<?php
session_start();
require "../../connection.php";
if (!isset($_SESSION['candidate_id'])) {
    header("Location: ../login.php");
    exit();
}

?>

<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Acme Corp Job Portal</title>
        <link rel="stylesheet" href="../../bootstrap/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div class="d-flex" id="wrapper">

            <!-- Sidebar -->
            <aside id="sidebar-wrapper" class="border-end bg-primary text-white">
                <div class="sidebar-header p-4 text-center d-flex flex-column align-items-center">
                    <div class="avatar mb-2">AC</div>
                    <div class="fw-bold">Acme Corp</div>
                    <small class="d-block text-white-50">Candidate Portal</small>
                </div>
                <div class="list-group list-group-flush px-2 pb-3">
                    <button id="jobsTabBtn" class="list-group-item list-group-item-action bg-transparent text-white active"
                            data-bs-toggle="tab" data-bs-target="#jobs" aria-controls="jobs">
                        <i class="fa-solid fa-briefcase me-2"></i>
                        <span class="d-inline-block ms-1">Jobs</span>
                    </button>

                    <button id="applicationsTabBtn" class="list-group-item list-group-item-action bg-transparent text-white"
                            data-bs-toggle="tab" data-bs-target="#applications" aria-controls="applications">
                        <i class="fa-solid fa-file-alt"></i>
                        <span class="d-inline-block ms-1">Applications</span>
                    </button>

                    <button id="profileTabBtn" class="list-group-item list-group-item-action bg-transparent text-white"
                            data-bs-toggle="tab" data-bs-target="#profile" aria-controls="profile">
                        <i class="fa-solid fa-user"></i>
                        <span class="d-inline-block ms-1">Profile</span>
                    </button>
                </div>
            </aside>

            <!-- Page Content -->
            <div id="page-content-wrapper" class="w-100">
                <!-- Navbar -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
                    <div class="container-fluid">
                        <div class="d-flex align-items-center">
                            <button class="btn btn-icon me-3" id="menu-toggle">
                                <i class="fa-solid fa-bars"></i>
                            </button>
                            <a class="navbar-brand fw-bold text-primary mb-0 d-flex align-items-center" href="#" onclick="return false;">
                                <span class="bg-primary text-white rounded-3 p-2 me-2">AC</span>
                                Job Portal
                            </a>
                        </div>
                        <div class="collapse navbar-collapse">
                            <ul class="navbar-nav ms-auto align-items-center">
                                <li class="nav-item">
                                    <button class="nav-link px-3 border-0 bg-transparent" id="themeModeToggle" title="Toggle Theme">
                                        <i class="fa-solid fa-sun theme-icon-light"></i>
                                        <i class="fa-solid fa-moon theme-icon-dark d-none"></i>
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="btn btn-outline-danger rounded-pill ms-3" onclick="window.location.href='logout.php'">
                                        <i class="fa-solid fa-right-from-bracket me-2"></i>
                                        Sign Out
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </nav>

                <!-- Hero / quick info -->
                <div class="container-fluid p-4">
                    <div class="hero p-4 mb-4">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <h3 class="display-6 fw-bold mb-3">Explore opportunities at Acme Corp</h3>
                                <p class="lead mb-4">Browse open roles, save your profile, and submit applications — tailored for you.</p>
                            </div>
                            <div class="col-lg-4 d-none d-lg-block text-end">
                                <img src="office-image.png" 
                                     alt="Office" class="img-fluid rounded-4 shadow-sm" style="max-width: 300px;">
                            </div>
                        </div>
                    </div>

                    <main class="container-fluid p-0">
                        <div class="tab-content">
                            <?php
                                include 'profile.php';
                                include 'jobs.php';
                                include 'applications.php';
                            ?>
                        </div>
                    </main>
                </div>
            </div>
        </div>
        <script src="../../bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="script.js"></script>
    </body>
</html>