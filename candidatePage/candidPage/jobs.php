<?php
require_once '../../connection.php';

$candidate_id = $_SESSION['candidate_id'] ?? 0;

// Fetch jobs from database
$jobsQuery = "SELECT * FROM recruitment_posts ORDER BY post_date DESC";
$result = $con->query($jobsQuery);
$jobsExist = $result && $result->num_rows > 0;

$appliedJobs = [];
if ($candidate_id) {
    $res = $con->prepare("SELECT position_id FROM applications WHERE candidate_id=? AND status <> 'Cancelled'");
    $res->bind_param("i", $candidate_id);
    $res->execute();
    $resResult = $res->get_result();
    while ($row = $resResult->fetch_assoc()) {
        $appliedJobs[] = $row['position_id'];
    }
}
?>

<section class="tab-pane fade show active" id="jobs">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1">Available Jobs</h4>
            <p class="text-muted mb-0">Find your next opportunity at Acme Corp</p>
        </div>
        <!-- <div class="d-flex flex-column flex-md-row gap-3">
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Salary Range
                </button>
                <div class="dropdown-menu p-3" style="min-width: 280px">
                    <div class="mb-3">
                        <label class="form-label">Minimum Salary</label>
                        <input type="number" class="form-control" id="minSalary" placeholder="PHP" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Maximum Salary</label>
                        <input type="number" class="form-control" id="maxSalary" placeholder="PHP" min="0">
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" id="applySalaryFilter">Filter</button>
                        <button class="btn btn-outline-secondary" id="resetSalaryFilter">Reset Filter</button>
                    </div>
                </div>
            </div>
            <div class="input-group">
                <input type="search" class="form-control" id="jobSearch" placeholder="Search jobs..." aria-label="Search jobs">
                <button class="btn btn-outline-primary" type="button" id="searchBtn">Search</button>
            </div>
        </div> -->
    </div>

    <div class="row gy-4" id="jobsList">
        <?php if ($jobsExist): ?>
            <?php while ($job = $result->fetch_assoc()): 
                $alreadyApplied = in_array($job['position_id'], $appliedJobs);
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title fw-bold"><?= htmlspecialchars($job['job_title']) ?></h5>
                            <p class="card-text" style="word-break: break-word; line-height: 1.5; text-align: justify;">
                                <?= nl2br(htmlspecialchars($job['description'])) ?>
                            </p>
                            <p class="mb-0"><strong>Salary:</strong> PHP <?= ($job['min_salary']) ?> - <?= ($job['max_salary']) ?></p>
                            <?php $reqItems = array_filter(array_map('trim', explode('-', $job['requirements'])));
                            if (!empty($reqItems)):?>
                                <p class="mb-1"><strong>Requirements:</strong></p>
                                <ul class="mb-0">
                                    <?php foreach ($reqItems as $item): ?>
                                        <li><?= htmlspecialchars($item) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a 
                                href="<?= $alreadyApplied ? '#' : "apply.php?job_id=" . $job['position_id'] ?>" 
                                class="btn <?= $alreadyApplied ? 'btn-secondary disabled' : 'btn-primary' ?> w-100"
                            >
                                <?= $alreadyApplied ? 'Already Applied' : 'Apply Now' ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body empty-illustration text-center">
                        <h6 class="mb-1">No jobs found</h6>
                        <p class="small text-muted mb-0">Try adjusting your filters or check back later.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>