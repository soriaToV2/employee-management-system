function openReviewModalFromBtn(button) {
    const reportId = button.getAttribute('data-report-id');
    const empName = button.getAttribute('data-emp');
    const empId = button.getAttribute('data-id');
    const violation = button.getAttribute('data-violation');
    const status = button.getAttribute('data-status');

    document.getElementById('reviewReportId').value = reportId;
    document.getElementById('reviewEmployeeId').value = empId;
    document.getElementById('reviewEmployeeIdDisplay').value = empId;
    document.getElementById('reviewEmployeeName').value = empName;
    document.getElementById('reviewViolationType').value = violation;
    document.getElementById('reviewStatusSelect').value = status;
}

function openInfoModalFromBtn(button) {
    const reportId = button.getAttribute('data-report-id');
    const hasFile = button.getAttribute('data-has-file') === '1';

    document.getElementById('infoEmployeeName').textContent = button.getAttribute('data-emp');
    document.getElementById('infoEmployeeId').textContent = button.getAttribute('data-id');
    document.getElementById('infoReporter').textContent = button.getAttribute('data-reporter') || 'N/A';
    document.getElementById('employeeDepartment').textContent = button.getAttribute('data-department') || 'N/A';
    document.getElementById('infoViolation').textContent = button.getAttribute('data-violation');
    document.getElementById('infoDate').textContent = button.getAttribute('data-date');
    document.getElementById('infoDesc').textContent = button.getAttribute('data-desc');

    const status = button.getAttribute('data-status');
    const statusBadge = document.getElementById('infoStatus');
    statusBadge.textContent = status;

    // Update badge color based on status
    statusBadge.className = 'badge ';
    switch (status) {
        case 'Pending': statusBadge.className += 'bg-warning text-dark'; break;
        case 'Reviewed': statusBadge.className += 'bg-info'; break;
        case 'Resolved': statusBadge.className += 'bg-success'; break;
        default: statusBadge.className += 'bg-secondary';
    }

    // Handle evidence section
    const evidenceSection = document.getElementById('evidenceSection');
    const noEvidenceMessage = document.getElementById('noEvidenceMessage');
    const evidenceButtons = document.getElementById('evidenceButtons');
    const viewEvidenceBtn = document.getElementById('viewEvidenceBtn');
    const downloadEvidenceBtn = document.getElementById('downloadEvidenceBtn');

    if (hasFile) {
        noEvidenceMessage.classList.add('d-none');
        evidenceButtons.classList.remove('d-none');

        // Set the file URLs - use absolute URLs to avoid issues
        const baseUrl = window.location.href.split('?')[0]; // Get current URL without query params
        const viewUrl = `${baseUrl}?download_file=1&report_id=${reportId}`;
        const downloadUrl = `${baseUrl}?download_file=1&report_id=${reportId}&download=1`;

        viewEvidenceBtn.href = viewUrl;
        downloadEvidenceBtn.href = downloadUrl;

        // Add error handling for links
        viewEvidenceBtn.onclick = function (e) {
            // Test if the file loads properly
            fetch(viewUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('File not found or error loading file');
                    }
                    // If successful, let the default link behavior happen
                })
                .catch(error => {
                    e.preventDefault();
                    alert('Error loading file: ' + error.message);
                });
        };

    } else {
        noEvidenceMessage.classList.remove('d-none');
        evidenceButtons.classList.add('d-none');
    }
}