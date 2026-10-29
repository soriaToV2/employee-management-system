
const postRecruitModal = new bootstrap.Modal(document.getElementById('postRecruitModal'));
const editRecruitModal = new bootstrap.Modal(document.getElementById('editRecruitModal'));
const deleteRecruitModal = new bootstrap.Modal(document.getElementById('deleteRecruitModal'));
const hireRequestModal = new bootstrap.Modal(document.getElementById('hireRequestModal'));
const editCandidateModal = new bootstrap.Modal(document.getElementById('editCandidateModal'));
const viewCandidateInfoModal = new bootstrap.Modal(document.getElementById('viewCandidateInfoModal'));

function showEditCandidateModal(applicationId, candidateId, positionId, currentStatus, currentInterviewDate, currentRank, currentComments) {
    document.getElementById('application_id').value = applicationId;
    document.getElementById('candidate_id').value = candidateId;
    document.getElementById('position_id').value = positionId;

    // Pre-fill existing data if available
    document.getElementById('status').value = currentStatus || '';

    // Format interview date for datetime-local input
    if (currentInterviewDate && currentInterviewDate !== '0000-00-00 00:00:00') {
        const interviewDate = new Date(currentInterviewDate);
        const formattedDate = interviewDate.toISOString().slice(0, 16);
        document.getElementById('interview_date').value = formattedDate;
    } else {
        document.getElementById('interview_date').value = '';
    }

    document.getElementById('rank').value = currentRank || '';
    document.getElementById('comments').value = currentComments || '';

    // Show/hide interview date field based on current status
    toggleInterviewDateField();

    editCandidateModal.show();
}

function showViewCandidateInfo(personalInfoJson, educBgJson, workExpJson, skillsJson, certsJson, docsJson) {
    // Parse JSON data
    const personalInfo = JSON.parse(personalInfoJson);
    const educBg = JSON.parse(educBgJson);
    const workExp = JSON.parse(workExpJson);
    const skills = JSON.parse(skillsJson);
    const certs = JSON.parse(certsJson);
    const docs = JSON.parse(docsJson);

    // Format date of birth
    const dob = personalInfo.date_of_birth ? new Date(personalInfo.date_of_birth).toLocaleDateString() : 'Not specified';

    // Build Personal Information HTML
    let personalInfoHTML = `
              <div class="row mb-2">
                  <div class="col-md-6"><strong>Full Name:</strong> ${personalInfo.first_name} ${personalInfo.last_name}</div>
                  <div class="col-md-6"><strong>Date of Birth:</strong> ${dob}</div>
              </div>
              <div class="row mb-2">
                  <div class="col-md-6"><strong>Phone:</strong> ${personalInfo.phone_number || 'Not specified'}</div>
                  <div class="col-md-6"><strong>Address:</strong> ${personalInfo.address || 'Not specified'}</div>
              </div>
          `;

    // Build Educational Background HTML
    let educBgHTML = '<div class="table-responsive"><table class="table table-dark table-sm"><thead><tr><th>Level</th><th>Degree</th><th>School</th><th>Year Graduated</th></tr></thead><tbody>';
    if (educBg.length > 0) {
        educBg.forEach(edu => {
            educBgHTML += `
                      <tr>
                          <td>${edu.education_level}</td>
                          <td>${edu.degree || 'N/A'}</td>
                          <td>${edu.school_name}</td>
                          <td>${edu.year_graduated || 'N/A'}</td>
                      </tr>
                  `;
        });
    } else {
        educBgHTML += '<tr><td colspan="4" class="text-center">No educational background found</td></tr>';
    }
    educBgHTML += '</tbody></table></div>';

    // Build Work Experience HTML
    let workExpHTML = '<div class="table-responsive"><table class="table table-dark table-sm"><thead><tr><th>Company</th><th>Position</th><th>Start Date</th><th>End Date</th><th>Description</th></tr></thead><tbody>';
    if (workExp.length > 0) {
        workExp.forEach(work => {
            const startDate = work.start_date ? new Date(work.start_date).toLocaleDateString() : 'N/A';
            const endDate = work.end_date ? new Date(work.end_date).toLocaleDateString() : 'Present';
            workExpHTML += `
                      <tr>
                          <td>${work.company_name}</td>
                          <td>${work.position_title}</td>
                          <td>${startDate}</td>
                          <td>${endDate}</td>
                          <td>${work.description || 'No description'}</td>
                      </tr>
                  `;
        });
    } else {
        workExpHTML += '<tr><td colspan="5" class="text-center">No work experience found</td></tr>';
    }
    workExpHTML += '</tbody></table></div>';

    // Build Skills HTML
    let skillsHTML = '<div class="table-responsive"><table class="table table-dark table-sm"><thead><tr><th>Skill Name</th><th>Proficiency Level</th></tr></thead><tbody>';
    if (skills.length > 0) {
        skills.forEach(skill => {
            skillsHTML += `
                      <tr>
                          <td>${skill.skill_name}</td>
                          <td><span class="badge bg-primary">${skill.proficiency_level}</span></td>
                      </tr>
                  `;
        });
    } else {
        skillsHTML += '<tr><td colspan="2" class="text-center">No skills found</td></tr>';
    }
    skillsHTML += '</tbody></table></div>';

    // Build Certifications HTML
    let certsHTML = '<div class="table-responsive"><table class="table table-dark table-sm"><thead><tr><th>Certificate Name</th><th>File Link</th></tr></thead><tbody>';
    if (certs.length > 0) {
        certs.forEach(cert => {
            certsHTML += `
                      <tr>
                          <td>${cert.certificate_name}</td>
                          <td><a href="${cert.file_link}" target="_blank" class="text-info">View Certificate</a></td>
                      </tr>
                  `;
        });
    } else {
        certsHTML += '<tr><td colspan="2" class="text-center">No certifications found</td></tr>';
    }
    certsHTML += '</tbody></table></div>';

    // Build Documents HTML
    let docsHTML = '<div class="table-responsive"><table class="table table-dark table-sm"><thead><tr><th>Document Type</th><th>File Link</th></tr></thead><tbody>';
    if (docs.length > 0) {
        docs.forEach(doc => {
            docsHTML += `
                      <tr>
                          <td>${doc.document_type}</td>
                          <td><a href="${doc.file_link}" target="_blank" class="text-info">View Document</a></td>
                      </tr>
                  `;
        });
    } else {
        docsHTML += '<tr><td colspan="2" class="text-center">No documents found</td></tr>';
    }
    docsHTML += '</tbody></table></div>';

    // Update modal content
    document.getElementById('personalInfoSection').innerHTML = personalInfoHTML;
    document.getElementById('educationalBackgroundSection').innerHTML = educBgHTML;
    document.getElementById('workExperienceSection').innerHTML = workExpHTML;
    document.getElementById('skillsSection').innerHTML = skillsHTML;
    document.getElementById('certificationsSection').innerHTML = certsHTML;

    // Add documents section if it doesn't exist
    if (!document.getElementById('documentsSection')) {
        const certificationsSection = document.getElementById('certificationsSection');
        const newSection = document.createElement('div');
        newSection.className = 'modal-body';
        newSection.innerHTML = '<h6>DOCUMENTS</h6><div id="documentsSection"></div>';
        certificationsSection.parentNode.insertBefore(newSection, certificationsSection.nextSibling);
    }
    document.getElementById('documentsSection').innerHTML = docsHTML;

    viewCandidateInfoModal.show();
}

function closeEditCandidateModal() {
    editCandidateModal.hide();
}

function toggleInterviewDateField() {
    const status = document.getElementById('status').value;
    const interviewDateField = document.getElementById('interviewDateField');
    const interviewDateInput = document.getElementById('interview_date');

    if (status === 'Initial Interview' || status === 'Final Interview') {
        interviewDateField.style.display = 'block';
        interviewDateInput.setAttribute('required', 'required');

        // Set minimum date to today
        const today = new Date();
        const minDate = today.toISOString().slice(0, 16);
        interviewDateInput.min = minDate;
    } else {
        interviewDateField.style.display = 'none';
        interviewDateInput.removeAttribute('required');
        interviewDateInput.value = '';
    }
}

function showHireRequestModal(requestID) {
    document.getElementById('request_id').value = requestID;
    hireRequestModal.show();
}

function closeHireRequestModal() {
    hireRequestModal.hide();
}

function showPostRecruitModal() {
    postRecruitModal.show();
}

function closePostRecruitModal() {
    postRecruitModal.hide();
}

function showEditRecruitModal(postId, jobTitle, positionId, minSalary, maxSalary, description, requirements) {
    document.getElementById('editPostId').value = postId;
    document.getElementById('editJobTitle').value = jobTitle;
    document.getElementById('editPositionSelect').value = positionId;
    document.getElementById('editMinSalary').value = minSalary;
    document.getElementById('editMaxSalary').value = maxSalary;
    document.getElementById('editJobDescription').value = description;
    document.getElementById('editRequirements').value = requirements;
    editRecruitModal.show();
}

function closeEditRecruitModal() {
    editRecruitModal.hide();
}

function showDeleteRecruitModal(postId) {
    document.getElementById('deletePostId').value = postId;
    deleteRecruitModal.show();
}

function closeDeleteRecruitModal() {
    deleteRecruitModal.hide();
}

// Email notification functions
function sendCandidateNotification(candidateEmail, candidateName, position, status, interviewDate = null) {
    const emailData = {
        to_email: candidateEmail,
        to_name: candidateName,
        position: position,
        status: status,
        interview_date: interviewDate,
        company_name: 'Your Company Name'
    };

    return sendEmailJS(emailData);
}

function sendEmailJS(emailData) {
    // This would typically be done via PHP, but you can also use client-side
    // if you prefer. However, for security, it's better to keep it server-side.
    console.log('Email would be sent with data:', emailData);
    return true;
}

// Helper function to format date for display
function formatDateForDisplay(dateString) {
    if (!dateString) return 'Not scheduled';

    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}