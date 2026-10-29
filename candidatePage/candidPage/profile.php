<section class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm" style="background: linear-gradient(135deg, #0d6efd 0%, #084298 100%); border: none;">
                <div class="card-body text-center position-relative">
                    <div class="avatar-lg mx-auto mb-3" style="border: 4px solid white;">AC</div>
                    <h5 id="profileName" class="card-title fw-bold mb-1">Your name</h5>
                    <p id="profileEmail" class="text-muted small mb-3">your.email@example.com</p>
                    <p class="small text-muted px-3">A short bio will appear here after you save your profile.</p>
                    <hr class="my-4">
                    <div class="d-flex justify-content-around text-center">
                        <div>
                            <h6 class="fw-bold mb-1" id="profileApplicationsCount">0</h6>
                            <small class="text-muted">Applications</small>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1" id="profileSavedJobsCount">0</h6>
                            <small class="text-muted">Saved Jobs</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <h4 class="fw-bold mb-3">My Profile</h4>
            <div class="card shadow-sm p-3">
                <form id="profileForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" id="fullName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" id="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" id="phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">LinkedIn / Portfolio</label>
                            <input type="url" id="portfolio" class="form-control" placeholder="https://">
                        </div>
                        <div class="col-12">
                            <label class="form-label">About / Bio</label>
                            <textarea id="bio" class="form-control" rows="4"></textarea>
                        </div>
                    </div>
                    <div class="mt-3 text-end">
                        <button class="btn btn-primary px-4">Save Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>