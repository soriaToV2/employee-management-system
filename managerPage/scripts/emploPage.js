document.addEventListener('DOMContentLoaded', function () {
    const detailButtons = document.querySelectorAll('.see-details-btn');
    let currentEmployeeData = null;

    // Employee Details Modal Functionality
    detailButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const employeeId = this.dataset.employeeId;
            currentEmployeeData = employeeDetails[employeeId];

            if (!currentEmployeeData) {
                alert('Employee data not found!');
                return;
            }

            populateModal(currentEmployeeData);
        });
    });

    function populateModal(data) {
        const basic = data.basic;

        // Basic Information
        document.getElementById('empName').value = basic.name || '';
        document.getElementById('empID').value = basic.employee_id || '';
        document.getElementById('empDepartment').value = basic.department || '';
        document.getElementById('empPosition').value = basic.position || '';
        document.getElementById('empBirth').value = basic.birth_date || '';
        document.getElementById('empStatus').value = basic.employment_status || '';
        document.getElementById('empEmail').value = basic.email || '';
        document.getElementById('empContact').value = basic.phone || '';
        document.getElementById('empAddress').value = basic.address || '';

        // Education
        const educationSection = document.getElementById('educationSection');
        educationSection.innerHTML = '';
        if (data.education && data.education.length > 0) {
            data.education.forEach(edu => {
                const educationDiv = document.createElement('div');
                educationDiv.className = 'border border-secondary rounded p-3 mb-3';
                educationDiv.innerHTML = `
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Education Level</label>
                            <input type="text" class="form-control" value="${edu.level}" disabled>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Degree</label>
                            <input type="text" class="form-control" value="${edu.degree}" disabled>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">School</label>
                            <input type="text" class="form-control" value="${edu.school}" disabled>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Year Graduated</label>
                            <input type="text" class="form-control" value="${edu.year}" disabled>
                        </div>
                    </div>
                `;
                educationSection.appendChild(educationDiv);
            });
        } else {
            educationSection.innerHTML = `
                <div class="border border-secondary rounded p-3 mb-3">
                    <div class="mb-3">
                        <label class="form-label">Education</label>
                        <input type="text" class="form-control" value="No education records found" disabled>
                    </div>
                </div>
            `;
        }

        // Work Experience
        const experienceSection = document.getElementById('experienceSection');
        experienceSection.innerHTML = '';
        if (data.experience && data.experience.length > 0) {
            data.experience.forEach(exp => {
                // Format dates to "Month Day, Year" format
                const formatDate = (dateString) => {
                    if (!dateString || dateString === 'Not specified' || dateString === 'Present') {
                        return dateString;
                    }

                    try {
                        const date = new Date(dateString);
                        if (isNaN(date.getTime())) {
                            return dateString; // Return original if invalid date
                        }

                        return date.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                    } catch (error) {
                        return dateString; // Return original if error
                    }
                };

                const startDate = formatDate(exp.start_date);
                const endDate = formatDate(exp.end_date);

                const experienceDiv = document.createElement('div');
                experienceDiv.className = 'border border-secondary rounded p-3 mb-3';
                experienceDiv.innerHTML = `
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Company</label>
                            <input type="text" class="form-control" value="${exp.company}" disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Position</label>
                            <input type="text" class="form-control" value="${exp.position}" disabled>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="text" class="form-control" value="${startDate}" disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="text" class="form-control" value="${endDate}" disabled>
                        </div>
                    </div>
                `;
                experienceSection.appendChild(experienceDiv);
            });
        } else {
            experienceSection.innerHTML = `
                <div class="border border-secondary rounded p-3 mb-3">
                    <div class="mb-3">
                        <label class="form-label">Work Experience</label>
                        <input type="text" class="form-control" value="No work experience found" disabled>
                    </div>
                </div>
            `;
        }

        // Skills
        const skillsSection = document.getElementById('skillsSection');
        skillsSection.innerHTML = '';
        if (data.skills && data.skills.length > 0) {
            const skillsDiv = document.createElement('div');
            skillsDiv.className = 'border border-secondary rounded p-3 mb-3';
            skillsDiv.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">Skills</label>
                    <input type="text" class="form-control" value="${data.skills.join(', ')}" disabled>
                </div>
            `;
            skillsSection.appendChild(skillsDiv);
        } else {
            skillsSection.innerHTML = `
                <div class="border border-secondary rounded p-3 mb-3">
                    <div class="mb-3">
                        <label class="form-label">Skills</label>
                        <input type="text" class="form-control" value="No skills found" disabled>
                    </div>
                </div>
            `;
        }

        // Certifications
        const certificationsSection = document.getElementById('certificationsSection');
        certificationsSection.innerHTML = '';
        if (data.certifications && data.certifications.length > 0) {
            data.certifications.forEach(cert => {
                const certDiv = document.createElement('div');
                certDiv.className = 'border border-secondary rounded p-3 mb-3';

                // Check if file link exists and create appropriate HTML
                let fileHtml = '';
                if (cert.file_link && cert.file_link !== 'Cant be found' && cert.file_link !== 'Not specified') {
                    // Create a clickable text link
                    fileHtml = `
                        <div class="mb-3">
                            <label class="form-label">Certificate File</label>
                            <div>
                                <a href="${cert.file_link}" target="_blank" class="text-primary text-decoration-none">
                                    <i class="fa-solid fa-file-pdf me-1"></i> View Certificate
                                </a>
                            </div>
                        </div>
                    `;
                } else {
                    // Show message if no file available
                    fileHtml = `
                        <div class="mb-3">
                            <label class="form-label">Certificate File</label>
                            <input type="text" class="form-control" value="No file available" disabled>
                        </div>
                    `;
                }

                certDiv.innerHTML = `
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Certification Name:</label>
                            <input type="text" class="form-control" value="${cert.certificate_name}" disabled>
                        </div>
                    </div>
                    ${fileHtml}
                `;
                certificationsSection.appendChild(certDiv);
            });
        } else {
            certificationsSection.innerHTML = `
                <div class="border border-secondary rounded p-3 mb-3">
                    <div class="mb-3">
                        <label class="form-label">Certifications</label>
                        <input type="text" class="form-control" value="No certifications found" disabled>
                    </div>
                </div>
            `;
        }

        populatePerformanceSection(data);
    }

    function populatePerformanceSection(data) {
        const performanceSection = document.getElementById('performanceSection');
        performanceSection.innerHTML = '';

        if (data.performance && Object.keys(data.performance).length > 0) {
            Object.values(data.performance).forEach(period => {
                const periodDiv = document.createElement('div');
                periodDiv.className = 'border border-secondary rounded p-3 mb-3';

                let categoriesHtml = '';
                period.categories.forEach(category => {
                    // Create a visual rating bar
                    const ratingPercent = (category.avg_rating / 5) * 100;
                    categoriesHtml += `
                        <div class="row align-items-center mb-2">
                            <div class="col-md-6">
                                <label class="form-label mb-1">${category.category_name}</label>
                            </div>
                            <div class="col-md-4">
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar ${getRatingColor(category.avg_rating)}" 
                                        role="progressbar" 
                                        style="width: ${ratingPercent}%"
                                        aria-valuenow="${category.avg_rating}" 
                                        aria-valuemin="1" 
                                        aria-valuemax="5">
                                        ${category.avg_rating}/5
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <small class="text-muted">${category.questions_rated} questions</small>
                            </div>
                        </div>
                    `;
                });

                // Calculate overall average for the period
                const overallAvg = period.categories.reduce((sum, cat) => sum + cat.avg_rating, 0) / period.categories.length;
                const overallPercent = (overallAvg / 5) * 100;

                periodDiv.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 fw-bold">${period.period_name}</h6>
                        <span class="badge bg-primary">${period.quarter} ${period.year}</span>
                    </div>
                    ${categoriesHtml}
                    <div class="row align-items-center mt-3 pt-3 border-top">
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-bold">Overall Average</label>
                        </div>
                        <div class="col-md-4">
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar ${getRatingColor(overallAvg)} fw-bold" 
                                    role="progressbar" 
                                    style="width: ${overallPercent}%"
                                    aria-valuenow="${overallAvg.toFixed(2)}" 
                                    aria-valuemin="1" 
                                    aria-valuemax="5">
                                    ${overallAvg.toFixed(2)}/5
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">${period.categories.length} categories</small>
                        </div>
                    </div>
                `;

                performanceSection.appendChild(periodDiv);
            });
        } else {
            performanceSection.innerHTML = `
                <div class="border border-secondary rounded p-3 mb-3">
                    <div class="text-center text-muted">
                        <i class="fa-solid fa-chart-line fa-2x mb-2"></i>
                        <p class="mb-0">No performance reviews available</p>
                    </div>
                </div>
            `;
        }
    }

    // Helper function to determine rating color
    function getRatingColor(rating) {
        if (rating >= 4) return 'bg-success';
        if (rating >= 3) return 'bg-info';
        if (rating >= 2) return 'bg-warning';
        return 'bg-danger';
    }

    // Position Change Functionality
    document.getElementById('changePositionBtn').addEventListener('click', function (e) {
        e.stopPropagation();

        if (!currentEmployeeData) {
            alert('No employee data loaded. Please open employee details first.');
            return;
        }

        // Populate position change modal
        document.getElementById('currentPositionDisplay').value = currentEmployeeData.basic.position;
        document.getElementById('currentDepartmentDisplay').value = currentEmployeeData.basic.department;

        // Set the hidden employee ID field
        document.getElementById('formEmployeeId').value = currentEmployeeData.basic.employee_id;

        // Reset form
        document.getElementById('newPositionSelect').value = '';
        document.getElementById('changeTypeSelect').value = 'Promotion';
        document.getElementById('changeReason').value = '';

        // Close the employee details modal first
        const employeeDetailsModal = bootstrap.Modal.getInstance(document.getElementById('employeeDetailsModal'));
        employeeDetailsModal.hide();

        // Show the position change modal after a short delay
        setTimeout(() => {
            const positionChangeModal = new bootstrap.Modal(document.getElementById('positionChangeModal'));
            positionChangeModal.show();
        }, 300);
    });

    // Form validation (client-side validation)
    document.getElementById('positionChangeForm').addEventListener('submit', function (e) {
        const newPositionId = document.getElementById('newPositionSelect').value;
        const reason = document.getElementById('changeReason').value;

        let isValid = true;
        let errorMessage = '';

        if (!newPositionId) {
            isValid = false;
            errorMessage = 'Please select a new position';
        } else if (!reason.trim()) {
            isValid = false;
            errorMessage = 'Please provide a reason for the position change';
        }

        if (!isValid) {
            e.preventDefault();
            alert(errorMessage);
            return false;
        }

        // If validation passes, form will submit normally
        return true;
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