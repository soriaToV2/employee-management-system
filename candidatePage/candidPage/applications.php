<?php
require_once '../../connection.php'; // adjust path if needed

// ensure user is logged in
if (!isset($_SESSION['candidate_id'])) {
    // redirect to login or show message
    header('Location: ../login.php');
    exit();
}

$candidate_id = intval($_SESSION['candidate_id']);

// Prepared statement to avoid SQL injection
$sql = "
    SELECT a.*, r.job_title
    FROM applications a
    LEFT JOIN recruitment_posts r ON a.position_id = r.position_id
    WHERE a.candidate_id = ?
    ORDER BY a.date_applied DESC
";

function getBadgeClass($status) {
    switch (strtolower($status)) {
        case 'applied':
            return 'bg-success';
        case 'hired':
            return 'bg-success';
        case 'screening':
            return 'bg-info text-dark';
        case 'initial interview':
            return 'bg-primary';
        case 'final interview':
            return 'bg-warning text-dark';
        case 'rejected':
            return 'bg-danger';
        case 'cancelled':
            return 'bg-danger';
        default:
            return 'bg-light text-dark';
    }
}

$stmt = $con->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $con->error);
}
$stmt->bind_param('i', $candidate_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<section class="tab-pane fade" id="applications">
    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="mb-3">My Applications</h4>

            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Status</th>
                                <th>Date Applied</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['job_title'] ?? '—') ?></td>
                                    <td>
                                        <span class="badge <?= getBadgeClass($row['status']) ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('F d, Y', strtotime($row['date_applied'])) ?></td>
                                    <td>
                                        <?php
                                            $statusLower = strtolower($row['status']);
                                            if ($statusLower === 'applied') :
                                        ?>
                                            <a href="cancel.php?id=<?= (int)$row['application_id'] ?>"
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('Are you sure you want to cancel this application?');">
                                                Cancel
                                            </a>
                                        <?php elseif (in_array($statusLower, ['initial interview', 'final interview', 'hired', 'rejected'])) : ?>
                                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#appModal<?= (int)$row['application_id'] ?>">
                                                View
                                            </button>

                                            <div class="modal fade" id="appModal<?= (int)$row['application_id'] ?>" tabindex="-1" aria-labelledby="appModalLabel<?= (int)$row['application_id'] ?>" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="appModalLabel<?= (int)$row['application_id'] ?>">Application Details</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p><strong>Job Title:</strong> <?= htmlspecialchars($row['job_title'] ?? '—') ?></p>
                                                            <p><strong>Status:</strong> <span class="badge <?= getBadgeClass($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></p>
                                                            <p><strong>Date Applied:</strong> <?= date('F j, Y', strtotime($row['date_applied'])) ?></p>

                                                            <?php if (in_array($statusLower, ['initial interview', 'final interview', 'hired'])) :
                                                                $interviewDate = $row['interview_date'] ? date('F j, Y', strtotime($row['interview_date'])) : '—';
                                                                $interviewTime = $row['interview_date'] ? date('h:i A', strtotime($row['interview_date'])) : '—';
                                                            ?>
                                                                <p><strong>Date of Interview:</strong> <?= $interviewDate ?></p>
                                                                <p><strong>Time of Interview:</strong> <?= $interviewTime ?></p>
                                                                <p><strong>Comments:</strong> <?= nl2br(htmlspecialchars($row['hr_comment'] ?? '—')) ?></p>
                                                            <?php elseif ($statusLower === 'rejected') : ?>
                                                                <p><strong>Reason:</strong> <?= nl2br(htmlspecialchars($row['hr_comment'] ?? '—')) ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <h6 class="mb-1">No applications yet</h6>
                    <p class="text-muted mb-0">Apply to jobs to see them listed here.</p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</section>

<?php
$stmt->close();
?>