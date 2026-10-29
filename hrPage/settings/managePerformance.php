<?php
session_start();
require "../../functions.php";
require "../../connection.php";
redirectToLogin('HR');

$successMsg = null;
$errorMsg = null;

// Add new category
if (isset($_POST['add_category'])) {
    $category_name = mysqli_real_escape_string($con, $_POST['category_name']);
    mysqli_begin_transaction($con);
    $query = "INSERT INTO review_categories (category_name) VALUES ('$category_name')";
    if (mysqli_query($con, $query)) {
      mysqli_commit($con);
        $successMsg = "Category added successfully!";
    } else {
      mysqli_rollback($con);
        $errorMsg = "Error adding category: " . mysqli_error($con);
    }
}

// Add new question
if (isset($_POST['add_question'])) {
    $category_id = mysqli_real_escape_string($con, $_POST['category_id']);
    $question_text = mysqli_real_escape_string($con, $_POST['question_text']);
    
    mysqli_begin_transaction($con);
    $query = "INSERT INTO review_questions (category_id, question_text) VALUES ('$category_id', '$question_text')";
    
    if (mysqli_query($con, $query)) {
      mysqli_commit($con);
        $successMsg = "Question added successfully!";
    } else {
      mysqli_rollback($con);
        $errorMsg = "Error adding question: " . mysqli_error($con);
    }
}

// Delete category
if (isset($_POST['delete_category'])) {
    $category_id = mysqli_real_escape_string($con, $_POST['category_id']);
    
    mysqli_query($con, "DELETE FROM review_questions WHERE category_id = '$category_id'");
    mysqli_begin_transaction($con);
    
    $query = "DELETE FROM review_categories WHERE category_id = '$category_id'";
    
    if (mysqli_query($con, $query)) {
      mysqli_commit($con);
        $successMsg = "Category and its questions deleted successfully!";
    } else {
      mysqli_rollback($con);
        $errorMsg = "Error deleting category: " . mysqli_error($con);
    }
}

// Delete question
if (isset($_POST['delete_question'])) {
    $question_id = mysqli_real_escape_string($con, $_POST['question_id']);
    
    $query = "DELETE FROM review_questions WHERE question_id = '$question_id'";
    mysqli_begin_transaction($con);
    
    if (mysqli_query($con, $query)) {
      mysqli_commit($con);
        $successMsg = "Question deleted successfully!";
    } else {
      mysqli_rollback($con);
        $errorMsg = "Error deleting question: " . mysqli_error($con);
    }
}

// Add new rating period
if (isset($_POST['add_period'])) {
    $period_name = mysqli_real_escape_string($con, $_POST['period_name']);
    $quarter = mysqli_real_escape_string($con, $_POST['quarter']);
    $year = mysqli_real_escape_string($con, $_POST['year']);
    $start_date = mysqli_real_escape_string($con, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($con, $_POST['end_date']);
    
    mysqli_begin_transaction($con);
    $query = "INSERT INTO rating_periods (period_name, quarter, year, start_date, end_date, is_active) 
              VALUES ('$period_name', '$quarter', '$year', '$start_date', '$end_date', 0)";
    
    if (mysqli_query($con, $query)) {
        mysqli_commit($con);
        $successMsg = "Rating period added successfully!";
    } else {
        mysqli_rollback($con);
        $errorMsg = "Error adding rating period: " . mysqli_error($con);
    }
}

// Toggle rating period status (enable/disable)
if (isset($_POST['toggle_period'])) {
    $period_id = mysqli_real_escape_string($con, $_POST['period_id']);
    $new_status = mysqli_real_escape_string($con, $_POST['new_status']);
    
    // If activating a period, deactivate all others first
    if ($new_status == 1) {
        mysqli_query($con, "UPDATE rating_periods SET is_active = 0");
    }
    
    mysqli_begin_transaction($con);
    $query = "UPDATE rating_periods SET is_active = '$new_status' WHERE period_id = '$period_id'";
    
    if (mysqli_query($con, $query)) {
        mysqli_commit($con);
        $action = $new_status == 1 ? "enabled" : "disabled";
        $successMsg = "Rating period $action successfully!";
    } else {
        mysqli_rollback($con);
        $errorMsg = "Error updating rating period: " . mysqli_error($con);
    }
}

// Edit rating period
if (isset($_POST['edit_period'])) {
    $period_id = mysqli_real_escape_string($con, $_POST['period_id']);
    $period_name = mysqli_real_escape_string($con, $_POST['period_name']);
    $quarter = mysqli_real_escape_string($con, $_POST['quarter']);
    $year = mysqli_real_escape_string($con, $_POST['year']);
    $start_date = mysqli_real_escape_string($con, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($con, $_POST['end_date']);
    
    mysqli_begin_transaction($con);
    $query = "UPDATE rating_periods SET 
              period_name = '$period_name', 
              quarter = '$quarter', 
              year = '$year', 
              start_date = '$start_date', 
              end_date = '$end_date' 
              WHERE period_id = '$period_id'";
    
    if (mysqli_query($con, $query)) {
        mysqli_commit($con);
        $successMsg = "Rating period updated successfully!";
    } else {
        mysqli_rollback($con);
        $errorMsg = "Error updating rating period: " . mysqli_error($con);
    }
}

// Fetch all categories with their questions
$query = "SELECT c.category_id, c.category_name, q.question_id, q.question_text 
          FROM review_categories c 
          LEFT JOIN review_questions q ON c.category_id = q.category_id 
          ORDER BY c.category_id, q.question_id";

$result = mysqli_query($con, $query);

$categories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $category_id = $row['category_id'];
    if (!isset($categories[$category_id])) {
        $categories[$category_id] = [
            'category_name' => $row['category_name'],
            'questions' => []
        ];
    }
    if ($row['question_id']) {
        $categories[$category_id]['questions'][] = [
            'question_id' => $row['question_id'],
            'question_text' => $row['question_text']
        ];
    }
}

// Fetch categories for dropdown - store in array for reuse
$categories_dropdown_result = mysqli_query($con, "SELECT * FROM review_categories ORDER BY category_name");
$categories_dropdown = [];
while ($cat = mysqli_fetch_assoc($categories_dropdown_result)) {
    $categories_dropdown[] = $cat;
}

// Fetch all rating periods
$periods_query = "SELECT * FROM rating_periods ORDER BY year DESC, quarter DESC";
$periods_result = mysqli_query($con, $periods_query);
$rating_periods = [];
while ($period = mysqli_fetch_assoc($periods_result)) {
    $rating_periods[] = $period;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Name - HR Recruitment</title>
    <link href="../../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../css/Global.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .delete-form {
            display: inline;
        }
        .confirmation-modal .modal-header {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row flex-nowrap">

            <!-- Sidebar -->
            <?php include 'nav.php'; ?>

            <!-- Main Content -->
            <div class="col py-4 px-5">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                    <h2 class="fw-bold">Settings</h2>
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width:45px; height:45px;">HR</div>
                        <div>
                            <h6 class="mb-0"><?php echo htmlspecialchars($_SESSION['employeeName']); ?></h6>
                            <small><?php echo htmlspecialchars($_SESSION['employeePosition']); ?></small>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($successMsg) { ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($successMsg); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php } ?>

                <?php if ($errorMsg) { ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($errorMsg); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php } ?>

                <!-- Delete Confirmation Modals -->
                <!-- Delete Category Modal -->
                <div class="modal fade" id="deleteCategoryModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Delete Category
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete this category and all its questions? This action cannot be undone.</p>
                                <form id="deleteCategoryForm" method="POST" class="delete-form">
                                    <input type="hidden" name="category_id" id="deleteCategoryId">
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" form="deleteCategoryForm" name="delete_category" class="btn btn-danger">
                                    <i class="fas fa-trash me-1"></i>Delete Category
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete Question Modal -->
                <div class="modal fade" id="deleteQuestionModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Delete Question
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete this question? This action cannot be undone.</p>
                                <form id="deleteQuestionForm" method="POST" class="delete-form">
                                    <input type="hidden" name="question_id" id="deleteQuestionId">
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" form="deleteQuestionForm" name="delete_question" class="btn btn-danger">
                                    <i class="fas fa-trash me-1"></i>Delete Question
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Period Modal -->
                <div class="modal fade" id="editPeriodModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-edit me-2"></i>Edit Rating Period
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="editPeriodForm" method="POST">
                                    <input type="hidden" name="period_id" id="editPeriodId">
                                    <div class="mb-3">
                                        <label class="form-label">Period Name</label>
                                        <input type="text" name="period_name" id="editPeriodName" class="form-control" required>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Quarter</label>
                                            <input type="text" name="quarter" id="editQuarter" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Year</label>
                                            <input type="number" name="year" id="editYear" class="form-control">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Start Date</label>
                                            <input type="date" name="start_date" id="editStartDate" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">End Date</label>
                                            <input type="date" name="end_date" id="editEndDate" class="form-control" required>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" form="editPeriodForm" name="edit_period" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Update Period
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Rating Performance Management
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Add Category and Question Forms -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-plus me-1"></i> Add Rating Category
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <div class="input-group">
                                                <input type="text" 
                                                       name="category_name" 
                                                       class="form-control" 
                                                       placeholder="Enter category name" 
                                                       required>
                                                <button type="submit" 
                                                        name="add_category" 
                                                        class="btn btn-success">
                                                    Add Category
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-plus me-1"></i> Add Rating Question
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <div class="row g-2">
                                                <div class="col-md-5">
                                                    <select name="category_id" class="form-select" required>
                                                        <option value="">Select Category</option>
                                                        <?php foreach ($categories_dropdown as $cat) { ?>
                                                            <option value="<?php echo $cat['category_id']; ?>">
                                                                <?php echo htmlspecialchars($cat['category_name']); ?>
                                                            </option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="text" 
                                                           name="question_text" 
                                                           class="form-control" 
                                                           placeholder="Enter question text" 
                                                           required>
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="submit" 
                                                            name="add_question" 
                                                            class="btn btn-info w-100">
                                                        Add
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Categories and Questions Table with Rowspan -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="30%">Category</th>
                                        <th width="60%">Questions</th>
                                        <th width="10%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categories)) { ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-3">
                                                No categories found. Add some categories and questions to get started.
                                            </td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($categories as $category_id => $category) { ?>
                                            <?php 
                                            $question_count = count($category['questions']);
                                            $has_questions = $question_count > 0;
                                            ?>
                                            
                                            <?php if ($has_questions) { ?>
                                                <?php foreach ($category['questions'] as $index => $question) { ?>
                                                    <tr>
                                                        <?php if ($index === 0) { ?>
                                                            <td rowspan="<?php echo $question_count; ?>" 
                                                                class="fw-bold ">

                                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                                                <br>
                                                                <small>
                                                                    (<?php echo $question_count; ?> question<?php echo $question_count > 1 ? 's' : ''; ?>)
                                                                </small>
                                                                
                                                                <!-- Delete Category Button -->
                                                                <button type="button" 
                                                                        class="btn btn-outline-danger btn-sm mt-2"
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#deleteCategoryModal"
                                                                        data-category-id="<?php echo $category_id; ?>"
                                                                        data-category-name="<?php echo htmlspecialchars($category['category_name']); ?>">
                                                                    <i class="fas fa-trash me-1"></i>Delete Category
                                                                </button>
                                                            </td>
                                                        <?php } ?>
                                                        
                                                        <td><?php echo htmlspecialchars($question['question_text']); ?></td>
                                                        
                                                        <td>
                                                            <!-- Delete Question Button -->
                                                            <button type="button" 
                                                                    class="btn btn-outline-danger btn-sm"
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#deleteQuestionModal"
                                                                    data-question-id="<?php echo $question['question_id']; ?>"
                                                                    data-question-text="<?php echo htmlspecialchars($question['question_text']); ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <tr>
                                                    <td class="fw-bold">
                                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                                        <br>
                                                        <button type="button" 
                                                                class="btn btn-outline-danger btn-sm mt-2"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#deleteCategoryModal"
                                                                data-category-id="<?php echo $category_id; ?>"
                                                                data-category-name="<?php echo htmlspecialchars($category['category_name']); ?>">
                                                            <i class="fas fa-trash me-1"></i>Delete Category
                                                        </button>
                                                    </td>
                                                    <td colspan="2" class="fw-bold ">
                                                        No questions added yet
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        <?php } ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Rating Periods Management -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-calendar me-2"></i>Rating Periods Management
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Add Rating Period Form -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-plus me-1"></i> Add New Rating Period
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <div class="row g-3">
                                                <div class="col-md-3">
                                                    <label class="form-label">Period Name</label>
                                                    <input type="text" name="period_name" class="form-control" 
                                                        placeholder="e.g., Q1 2024 Performance Review" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Quarter</label>
                                                    <select name="quarter" class="form-select" required>
                                                        <option value="">Select Quarter</option>
                                                        <option value="Q1">Q1</option>
                                                        <option value="Q2">Q2</option>
                                                        <option value="Q3">Q3</option>
                                                        <option value="Q4">Q4</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Year</label>
                                                    <input type="text" name="year" class="form-control" 
                                                        placeholder="e.g., 2024" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Start Date</label>
                                                    <input type="date" name="start_date" class="form-control" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">End Date</label>
                                                    <input type="date" name="end_date" class="form-control" required>
                                                </div>
                                                <div class="col-md-1 d-flex align-items-end">
                                                    <button type="submit" name="add_period" class="btn btn-secondary w-100">
                                                        Add
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rating Periods Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="25%">Period Name</th>
                                        <th width="15%">Quarter/Year</th>
                                        <th width="15%">Start Date</th>
                                        <th width="15%">End Date</th>
                                        <th width="15%">Status</th>
                                        <th width="15%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($rating_periods)) { ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-3">
                                                No rating periods found. Add a rating period to get started.
                                            </td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($rating_periods as $period) { ?>
                                            <tr>
                                                <td class="fw-bold"><?php echo htmlspecialchars($period['period_name']); ?></td>
                                                <td><?php echo htmlspecialchars($period['quarter'] . ' ' . $period['year']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($period['start_date'])); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($period['end_date'])); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $period['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                        <?php echo $period['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <?php if (!$period['is_active']) { ?>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="period_id" value="<?php echo $period['period_id']; ?>">
                                                                <input type="hidden" name="new_status" value="1">
                                                                <button type="submit" name="toggle_period" class="btn btn-outline-success" 
                                                                        title="Enable this rating period">
                                                                    <i class="fas fa-play"></i> Enable
                                                                </button>
                                                            </form>
                                                        <?php } else { ?>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="period_id" value="<?php echo $period['period_id']; ?>">
                                                                <input type="hidden" name="new_status" value="0">
                                                                <button type="submit" name="toggle_period" class="btn btn-outline-warning" 
                                                                        title="Disable this rating period">
                                                                    <i class="fas fa-pause"></i> Disable
                                                                </button>
                                                            </form>
                                                        <?php } ?>
                                                        
                                                        <!-- Edit Button -->
                                                        <button type="button" 
                                                                class="btn btn-outline-primary"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editPeriodModal"
                                                                data-period-id="<?php echo $period['period_id']; ?>"
                                                                data-period-name="<?php echo htmlspecialchars($period['period_name']); ?>"
                                                                data-quarter="<?php echo htmlspecialchars($period['quarter']); ?>"
                                                                data-year="<?php echo htmlspecialchars($period['year']); ?>"
                                                                data-start-date="<?php echo htmlspecialchars($period['start_date']); ?>"
                                                                data-end-date="<?php echo htmlspecialchars($period['end_date']); ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle category deletion modal
        const deleteCategoryModal = document.getElementById('deleteCategoryModal');
        if (deleteCategoryModal) {
            deleteCategoryModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const categoryId = button.getAttribute('data-category-id');
                const categoryName = button.getAttribute('data-category-name');
                
                const modalTitle = deleteCategoryModal.querySelector('.modal-title');
                const modalBody = deleteCategoryModal.querySelector('.modal-body p');
                const categoryIdInput = document.getElementById('deleteCategoryId');
                
                modalTitle.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Delete Category: ' + categoryName;
                modalBody.textContent = 'Are you sure you want to delete the category "' + categoryName + '" and all its questions? This action cannot be undone.';
                categoryIdInput.value = categoryId;
            });
        }

        // Handle question deletion modal
        const deleteQuestionModal = document.getElementById('deleteQuestionModal');
        if (deleteQuestionModal) {
            deleteQuestionModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const questionId = button.getAttribute('data-question-id');
                const questionText = button.getAttribute('data-question-text');
                
                const modalTitle = deleteQuestionModal.querySelector('.modal-title');
                const modalBody = deleteQuestionModal.querySelector('.modal-body p');
                const questionIdInput = document.getElementById('deleteQuestionId');
                
                modalTitle.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Delete Question';
                modalBody.textContent = 'Are you sure you want to delete the question: "' + questionText + '"? This action cannot be undone.';
                questionIdInput.value = questionId;
            });
        }

        // Handle edit period modal
        const editPeriodModal = document.getElementById('editPeriodModal');
        if (editPeriodModal) {
            editPeriodModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const periodId = button.getAttribute('data-period-id');
                const periodName = button.getAttribute('data-period-name');
                const quarter = button.getAttribute('data-quarter');
                const year = button.getAttribute('data-year');
                const startDate = button.getAttribute('data-start-date');
                const endDate = button.getAttribute('data-end-date');
                
                const modalTitle = editPeriodModal.querySelector('.modal-title');
                const periodIdInput = document.getElementById('editPeriodId');
                const periodNameInput = document.getElementById('editPeriodName');
                const quarterSelect = document.getElementById('editQuarter');
                const yearSelect = document.getElementById('editYear');
                const startDateInput = document.getElementById('editStartDate');
                const endDateInput = document.getElementById('editEndDate');
                
                modalTitle.innerHTML = '<i class="fas fa-edit me-2"></i>Edit Rating Period: ' + periodName;
                periodIdInput.value = periodId;
                periodNameInput.value = periodName;
                quarterSelect.value = quarter;
                yearSelect.value = year;
                startDateInput.value = startDate;
                endDateInput.value = endDate;
            });
        }
    </script>
</body>
</html>