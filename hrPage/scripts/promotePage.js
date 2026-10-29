document.addEventListener('DOMContentLoaded', function () {
    // View Request Buttons
    const viewRequestButtons = document.querySelectorAll('.view-request');
    viewRequestButtons.forEach(button => {
        button.addEventListener('click', function () {
            // Get all data attributes
            const requestId = this.getAttribute('data-request-id');
            const employeeName = this.getAttribute('data-employee-name');
            const employeeId = this.getAttribute('data-employee-id');
            const currentDepartment = this.getAttribute('data-current-department');
            const currentPosition = this.getAttribute('data-current-position');
            const proposedPosition = this.getAttribute('data-proposed-position');
            const proposedDepartment = this.getAttribute('data-proposed-department');
            const changeType = this.getAttribute('data-change-type');
            const reason = this.getAttribute('data-reason');
            const requestDate = this.getAttribute('data-created-at');
            const currentStatus = this.getAttribute('data-current-status');
            const statusReason = this.getAttribute('data-status-reason');

            // Set badge color based on change type
            let changeTypeBadgeClass = 'bg-secondary';
            switch (changeType) {
                case 'Promote': changeTypeBadgeClass = 'bg-success'; break;
                case 'Demote': changeTypeBadgeClass = 'bg-warning'; break;
                case 'Transfer': changeTypeBadgeClass = 'bg-info'; break;
            }

            // Set badge color based on current status
            let statusBadgeClass = 'bg-secondary';
            switch (currentStatus) {
                case 'Pending': statusBadgeClass = 'bg-warning'; break;
                case 'In Process': statusBadgeClass = 'bg-info'; break;
                case 'Approved': statusBadgeClass = 'bg-success'; break;
                case 'Denied': statusBadgeClass = 'bg-danger'; break;
            }

            // Populate modal fields
            document.getElementById('viewEmployeeName').textContent = employeeName;
            document.getElementById('viewEmployeeId').textContent = employeeId;
            document.getElementById('viewCurrentDepartment').textContent = currentDepartment;
            document.getElementById('viewCurrentPosition').textContent = currentPosition;
            document.getElementById('viewProposedDepartment').textContent = proposedDepartment;
            document.getElementById('viewProposedPosition').textContent = proposedPosition;
            document.getElementById('viewChangeTypeBadge').textContent = changeType;
            document.getElementById('viewChangeTypeBadge').className = `badge ${changeTypeBadgeClass}`;
            document.getElementById('viewCurrentStatusBadge').textContent = currentStatus;
            document.getElementById('viewCurrentStatusBadge').className = `badge ${statusBadgeClass}`;
            document.getElementById('viewReason').textContent = reason;
            document.getElementById('viewRequestDate').textContent = requestDate;
            document.getElementById('viewRequestId').value = requestId;

            // Populate status select and status reason
            document.getElementById('statusSelect').value = currentStatus;
            document.getElementById('statusReason').value = statusReason || '';

            // Show modal
            new bootstrap.Modal(document.getElementById('viewRequestModal')).show();
        });
    });

    // Form validation
    const statusForm = document.getElementById('statusForm');
    statusForm.addEventListener('submit', function (e) {
        const status = document.getElementById('statusSelect').value;

        if (!status) {
            e.preventDefault();
            alert('Please select a status for this request.');
            return;
        }

    });

    // Auto-close alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});