<?php

session_start();
require "../functions.php";
require "../connection.php";
redirectToLogin('Employee');

$currentEmployeeID = $_SESSION['employeeID'];
$isProbationary = ($_SESSION['employment_status'] === 'Probationary');

// Check if there's an active rating period
$ratingPeriodQuery = "SELECT * FROM rating_periods WHERE is_active = 1 AND CURDATE() BETWEEN start_date AND end_date LIMIT 1";
$ratingPeriodResult = mysqli_query($con, $ratingPeriodQuery);
$activeRatingPeriod = mysqli_fetch_assoc($ratingPeriodResult);
$isRatingPeriodActive = ($activeRatingPeriod !== null);

// Handle form submission for rating
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reviewee_id'])) {
    
    if (!$isRatingPeriodActive) {
        $_SESSION['message'] = "Rating period is not currently active. Please wait for HR to open the next rating period.";
        $_SESSION['message_type'] = "warning";
        header("Location: performPage.php");
        exit();
    }
    
    $reviewer_id = $currentEmployeeID;
    $reviewee_id = mysqli_real_escape_string($con, $_POST['reviewee_id']);
    $ratings = $_POST['rating'];
    $period_id = $activeRatingPeriod['period_id'];
    
    // Check if user has already rated this employee in the current period
    $checkQuery = "SELECT review_id FROM performance_reviews 
                   WHERE reviewer_id = '$reviewer_id' 
                   AND reviewee_id = '$reviewee_id' 
                   AND period_id = '$period_id'
                   LIMIT 1";
    $checkResult = mysqli_query($con, $checkQuery);
    
    if (mysqli_num_rows($checkResult) > 0) {
        $_SESSION['message'] = "You have already rated this employee for the current rating period.";
        $_SESSION['message_type'] = "warning";
    } else {
        // Insert ratings for each question
        $success = true;
        foreach ($ratings as $question_id => $rating) {
            $question_id = mysqli_real_escape_string($con, $question_id);
            $rating = mysqli_real_escape_string($con, $rating);
            
            $insertQuery = "INSERT INTO performance_reviews 
                           (reviewer_id, reviewee_id, question_id, rating, period_id, review_date) 
                           VALUES ('$reviewer_id', '$reviewee_id', '$question_id', '$rating', '$period_id', NOW())";
            
            if (!mysqli_query($con, $insertQuery)) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $_SESSION['message'] = "Rating submitted successfully for " . $activeRatingPeriod['period_name'] . "!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error submitting rating. Please try again.";
            $_SESSION['message_type'] = "error";
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: performPage.php");
    exit();
}

// Get current department - FIXED: Removed applications table join
$getCurrentDeptQuery = "SELECT p.department_id 
                       FROM employees e 
                       JOIN positions p ON e.position_id = p.position_id 
                       WHERE e.employee_id = '$currentEmployeeID'";
$currentDeptResult = mysqli_query($con, $getCurrentDeptQuery);
$currentDept = mysqli_fetch_assoc($currentDeptResult);
$currentDepartmentID = $currentDept['department_id'];

// Get categories and questions
$categoriesQuery = "SELECT rc.category_id, rc.category_name, rq.question_id, rq.question_text 
                   FROM review_categories rc 
                   LEFT JOIN review_questions rq ON rc.category_id = rq.category_id 
                   ORDER BY rc.category_id, rq.question_id";
$categoriesResult = mysqli_query($con, $categoriesQuery);

$categories = [];
while ($row = mysqli_fetch_assoc($categoriesResult)) {
    $categoryId = $row['category_id'];
    if (!isset($categories[$categoryId])) {
        $categories[$categoryId] = [
            'category_name' => $row['category_name'],
            'questions' => []
        ];
    }
    if ($row['question_id']) {
        $categories[$categoryId]['questions'][] = [
            'question_id' => $row['question_id'],
            'question_text' => $row['question_text']
        ];
    }
}

// Get performance ratings for current employee (only from completed periods)
$performanceQuery = "
    SELECT 
        rc.category_id,
        rc.category_name,
        AVG(pr.rating) as avg_rating,
        COUNT(pr.rating) as rating_count
    FROM performance_reviews pr
    JOIN review_questions rq ON pr.question_id = rq.question_id
    JOIN review_categories rc ON rq.category_id = rc.category_id
    JOIN rating_periods rp ON pr.period_id = rp.period_id
    WHERE pr.reviewee_id = '$currentEmployeeID'
    AND rp.is_active = 0  -- Only include completed periods
    GROUP BY rc.category_id, rc.category_name
    ORDER BY rc.category_id
";

$performanceResult = mysqli_query($con, $performanceQuery);
$performanceData = [];
$overallRating = 0;
$totalCategories = 0;

while ($row = mysqli_fetch_assoc($performanceResult)) {
    $performanceData[$row['category_id']] = [
        'category_name' => $row['category_name'],
        'avg_rating' => number_format($row['avg_rating'], 1),
        'rating_count' => $row['rating_count']
    ];
    $overallRating += $row['avg_rating'];
    $totalCategories++;
}

// Calculate overall rating
if ($totalCategories > 0) {
    $overallRating = number_format($overallRating / $totalCategories, 1);
} else {
    $overallRating = "N/A";
}

// Define color classes for different categories
$categoryColors = [
    1 => 'text-success', // Punctuality
    2 => 'text-info',    // Quality of Work
    3 => 'text-warning', // Communication
    4 => 'text-danger'   // Teamwork
];

// Default values if no ratings exist
$defaultRatings = [
    1 => 'N/A',
    2 => 'N/A', 
    3 => 'N/A',
    4 => 'N/A'
];

// Merge actual data with defaults
foreach ($defaultRatings as $categoryId => $defaultValue) {
    if (!isset($performanceData[$categoryId])) {
        $categoryName = '';
        switch($categoryId) {
            case 1: $categoryName = 'Punctuality'; break;
            case 2: $categoryName = 'Quality'; break;
            case 3: $categoryName = 'Communication'; break;
            case 4: $categoryName = 'Teamwork'; break;
        }
        $performanceData[$categoryId] = [
            'category_name' => $categoryName,
            'avg_rating' => $defaultValue,
            'rating_count' => 0
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Performance | Employee Portal</title>
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
        
        <!-- Display Messages -->
        <?php if (isset($_SESSION['message'])): ?>
          <div class="alert alert-<?php echo $_SESSION['message_type'] === 'success' ? 'success' : ($_SESSION['message_type'] === 'warning' ? 'warning' : 'danger'); ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <!-- Rating Period Status -->
        <?php if ($isRatingPeriodActive): ?>
          <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Rating Period Active:</strong> <?php echo $activeRatingPeriod['period_name']; ?> 
            (<?php echo date('M j', strtotime($activeRatingPeriod['start_date'])); ?> - <?php echo date('M j, Y', strtotime($activeRatingPeriod['end_date'])); ?>)
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php else: ?>
          <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>No Active Rating Period:</strong> Rating is currently closed. Please wait for HR to open the next rating period.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
          <h2 class="fw-bold">Performance</h2>
          <div class="d-flex align-items-center">
            <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width:45px; height:45px;">EN</div>
            <div>
              <h6 class="mb-0"><?php echo $_SESSION['employeeName']; ?></h6>
              <small><?php echo $_SESSION['employeePosition']; ?></small>
            </div>
          </div>
        </div>

        <!-- Action Button -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="fw-semibold">Performance Overview</h4> 
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#rateModal" 
            <?php echo (!$isRatingPeriodActive || $isProbationary) ? 'disabled' : ''; ?>>
            <i class="fas fa-star me-1"></i> 
            <?php 
              if ($isProbationary) {
                  echo 'Probationary - Cannot Rate';
              } else {
                  echo $isRatingPeriodActive ? 'Start Rating Others' : 'Rating Closed';
              }
            ?>
          </button>
        </div>

        <!-- Stats Cards -->
        <div class="d-flex justify-content-between mb-4 text-center gap-3">
          <!-- Overall Rating Card -->
          <div class="card shadow-sm flex-grow-1 p-5">
            <h4 class="fw-bold text-primary"><?php echo $overallRating; ?>/5</h4>
            <p class="mb-0">Overall</p>
          </div>
          
          <!-- Dynamic Category Cards -->
          <?php foreach ($performanceData as $categoryId => $category): ?>
            <div class="card shadow-sm flex-grow-1 p-5">
              <h4 class="fw-bold <?php echo $categoryColors[$categoryId] ?? 'text-secondary'; ?>">
                <?php echo $category['avg_rating']; ?>/5
              </h4>
              <p class="mb-0"><?php echo $category['category_name']; ?></p>
              <?php if ($category['rating_count'] > 0): ?>
                <small class="text-muted"><?php echo $category['rating_count']; ?> ratings</small>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Performance History Table -->
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i> Performance History</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-striped align-middle">
                <thead>
                  <tr>
                    <th>Rating Period</th>
                    <th>Rating</th>
                    <th>Attendance</th>
                    <th>Quality of Work</th>
                    <th>Communication</th>
                    <th>Teamwork</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  // Get performance history by rating period
                  $historyQuery = "
                      SELECT 
                          rp.period_name,
                          rp.quarter,
                          rp.year,
                          AVG(pr.rating) as overall_rating,
                          AVG(CASE WHEN rq.category_id = 1 THEN pr.rating END) as punctuality,
                          AVG(CASE WHEN rq.category_id = 2 THEN pr.rating END) as quality,
                          AVG(CASE WHEN rq.category_id = 3 THEN pr.rating END) as communication,
                          AVG(CASE WHEN rq.category_id = 4 THEN pr.rating END) as teamwork
                      FROM performance_reviews pr
                      JOIN review_questions rq ON pr.question_id = rq.question_id
                      JOIN rating_periods rp ON pr.period_id = rp.period_id
                      WHERE pr.reviewee_id = '$currentEmployeeID'
                      AND rp.is_active = 0
                      GROUP BY rp.period_id, rp.period_name, rp.quarter, rp.year
                      ORDER BY rp.year DESC, rp.quarter DESC
                  ";
                  
                  $historyResult = mysqli_query($con, $historyQuery);
                  
                  if (mysqli_num_rows($historyResult) > 0) {
                      while ($history = mysqli_fetch_assoc($historyResult)) {
                          echo "<tr>";
                          echo "<td>" . htmlspecialchars($history['period_name']) . "</td>";
                          echo "<td>" . number_format($history['overall_rating'], 1) . "/5</td>";
                          echo "<td>" . ($history['punctuality'] ? number_format($history['punctuality'], 1) . "/5" : "N/A") . "</td>";
                          echo "<td>" . ($history['quality'] ? number_format($history['quality'], 1) . "/5" : "N/A") . "</td>";
                          echo "<td>" . ($history['communication'] ? number_format($history['communication'], 1) . "/5" : "N/A") . "</td>";
                          echo "<td>" . ($history['teamwork'] ? number_format($history['teamwork'], 1) . "/5" : "N/A") . "</td>";
                          echo "</tr>";
                      }
                  } else {
                      echo "<tr><td colspan='6' class='text-center'>No performance history available</td></tr>";
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Rating Modal -->
  <div class="modal fade" id="rateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <!-- START OF THE FORM -->
        <form action="performPage.php" method="post" id="rateForm">
          <div class="modal-header">
            <h5 class="modal-title">Rate an Employee - <?php echo $activeRatingPeriod['period_name'] ?? 'Current Period'; ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <?php if (!$isRatingPeriodActive): ?>
              <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Rating period is not currently active. Please wait for HR to open the next rating period.
              </div>
            <?php else: ?>
              <div class="mb-4">
                <label for="employeeName" class="form-label fw-semibold">Select Employee</label>
                <select class="form-select" id="employeeName" name="reviewee_id" required>
                  <option value="">-- Choose Employee --</option>
                  <?php 
                  // FIXED: Removed applications table join
                  $getEmployeesQuery = "SELECT e.employee_id, c.first_name, c.last_name 
                                      FROM employees e 
                                      JOIN candidates c ON e.candidate_id = c.candidate_id 
                                      JOIN positions p ON e.position_id = p.position_id 
                                      WHERE p.department_id = '$currentDepartmentID' 
                                      AND e.employee_id != '$currentEmployeeID' 
                                      AND e.status = 'Active'";
                  $getEmployeesQueryResult = mysqli_query($con, $getEmployeesQuery);

                  while($getEmployees = mysqli_fetch_assoc($getEmployeesQueryResult)) {
                    ?><option value="<?php echo $getEmployees['employee_id']; ?>"><?php echo $getEmployees['first_name']." ".$getEmployees['last_name'] ;?></option><?php
                  }
                  ?>
                </select>
              </div>

              <!-- Rating Questions by Category -->
              <div class="rating-categories">
                <?php foreach ($categories as $categoryId => $category): ?>
                  <div class="category-section mb-4 p-3 border rounded">
                    <h6 class="fw-bold text-primary mb-3"><?php echo htmlspecialchars($category['category_name']); ?></h6>
                    
                    <?php foreach ($category['questions'] as $question): ?>
                      <div class="question-item mb-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                          <label class="form-label mb-1 small"><?php echo htmlspecialchars($question['question_text']); ?></label>
                        </div>
                        <div class="rating-stars">
                          <div class="btn-group btn-group-sm" role="group">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                              <input type="radio" class="btn-check" name="rating[<?php echo $question['question_id']; ?>]" id="rating_<?php echo $question['question_id']; ?>_<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                              <label class="btn btn-outline-primary" for="rating_<?php echo $question['question_id']; ?>_<?php echo $i; ?>">
                                <?php echo $i; ?>
                              </label>
                            <?php endfor; ?>
                          </div>
                          <small class="text-muted ms-2">(1 = Poor, 5 = Excellent)</small>
                        </div>
                      </div>
                      <hr class="my-2">
                    <?php endforeach; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="modal-footer">
            <?php if ($isRatingPeriodActive): ?>
              <input type="hidden" name="reviewer_id" value="<?php echo $currentEmployeeID; ?>">
              <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Submit Rating</button>
            <?php endif; ?>
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-ban"></i> Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    function submitRating(event) {
      event.preventDefault();
      
      // Validate that all questions are answered
      const unansweredQuestions = [];
      const questionGroups = {};
      
      // Group questions by their IDs
      document.querySelectorAll('.rating-categories input[type="radio"]').forEach(radio => {
        const questionId = radio.name.match(/\[(\d+)\]/)[1];
        if (!questionGroups[questionId]) {
          questionGroups[questionId] = false;
        }
        if (radio.checked) {
          questionGroups[questionId] = true;
        }
      });
      
      // Check for unanswered questions
      for (const questionId in questionGroups) {
        if (!questionGroups[questionId]) {
          const questionText = document.querySelector(`input[name="rating[${questionId}]"]`).closest('.question-item').querySelector('.form-label').textContent;
          unansweredQuestions.push(questionText);
        }
      }

      if (unansweredQuestions.length > 0) {
        alert('Please answer all rating questions before submitting.');
        return false;
      }

      // If all validations pass, submit the form
      document.getElementById('rateForm').submit();
    }

    // Add form submission handler
    document.getElementById('rateForm').addEventListener('submit', submitRating);

    // Reset form when modal is closed
    document.getElementById('rateModal').addEventListener('hidden.bs.modal', function () {
      document.getElementById('rateForm').reset();
    });
  </script>
</body>
</html>