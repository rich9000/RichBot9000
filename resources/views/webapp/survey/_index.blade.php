<!-- resources/views/webapp/survey/index.blade.php -->
<div class="container-fluid pb-5" id="survey-management">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Survey Management</h2>
        <button class="btn btn-primary" id="create-survey-btn">
            <i class="fas fa-plus"></i> Create Survey
        </button>
    </div>

    <!-- Bulk Actions Container -->
    <div id="bulk-actions-container" class="card mb-3 d-none">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <span class="me-3"><span id="selected-count">0</span> surveys selected</span>
                <div class="btn-group">
                    <button class="btn btn-danger" id="bulk-delete-btn" disabled onclick="surveyManager.confirmDeleteSurvey()">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Surveys List -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Your Surveys</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="surveys-table">
                    <thead>
                        <tr>
                            <th>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="select-all-surveys">
                                </div>
                            </th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Questions</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="surveys-list">
                        <!-- Surveys will be populated here via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Survey Detail Section -->
    <div id="survey-detail-section" class="d-none">
        <div class="card mb-4">
            <div class="card-header bg-light d-flex justify-content-between">
                <h5 class="mb-0" id="survey-detail-title">Survey Details</h5>
                <div>
                    <button class="btn btn-sm btn-outline-primary" id="edit-survey-btn">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-outline-danger" id="delete-survey-btn">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" id="back-to-surveys-btn">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                </div>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" id="surveyTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-light bg-dark active" id="questions-tab" data-bs-toggle="tab" data-bs-target="#questions-content" type="button" role="tab" aria-controls="questions-content" aria-selected="true">Questions</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-light bg-dark" id="campaigns-tab" data-bs-toggle="tab" data-bs-target="#campaigns-content" type="button" role="tab" aria-controls="campaigns-content" aria-selected="false">Campaigns</button>
                    </li>
                </ul>
                
                <div class="tab-content pt-3" id="surveyTabsContent">
                    <!-- Questions Tab -->
                    <div class="tab-pane fade show active" id="questions-content" role="tabpanel" aria-labelledby="questions-tab">
                        <div class="card bg-dark text-light">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5>Survey Questions</h5>
                                    <button class="btn btn-sm btn-success" id="add-question-btn">
                                        <i class="fas fa-plus"></i> Add Question
                                    </button>
                                </div>
                                
                                <div id="questions-container" class="list-group mb-3">
                                    <!-- Questions will be populated here via JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Campaigns Tab -->
                    <div class="tab-pane fade" id="campaigns-content" role="tabpanel" aria-labelledby="campaigns-tab">
                        <div class="card bg-dark text-light">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5>Survey Campaigns</h5>
                                    <button class="btn btn-sm btn-success" id="create-campaign-btn">
                                        <i class="fas fa-plus"></i> Create Campaign
                                    </button>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover table-dark" id="campaigns-table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Status</th>
                                                <th>Dates</th>
                                                <th>Contacts</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="campaigns-list">
                                            <!-- Campaigns will be populated here via JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Campaign Detail Section -->
    <div id="campaign-detail-section" class="d-none">
        <div class="card mb-4">
            <div class="card-header bg-light d-flex justify-content-between">
                <h5 class="mb-0" id="campaign-detail-title">Campaign Details</h5>
                <div>
                    <button class="btn btn-sm btn-outline-primary" id="edit-campaign-btn">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-outline-danger" id="delete-campaign-btn">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" id="back-to-survey-btn">
                        <i class="fas fa-arrow-left"></i> Back to Survey
                    </button>
                </div>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" id="campaignTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-light bg-dark active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details-content" type="button" role="tab" aria-controls="details-content" aria-selected="true">Details</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-light bg-dark" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts-content" type="button" role="tab" aria-controls="contacts-content" aria-selected="false">Contacts</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-light bg-dark" id="responses-tab" data-bs-toggle="tab" data-bs-target="#responses-content" type="button" role="tab" aria-controls="responses-content" aria-selected="false">Responses</button>
                    </li>
                </ul>
                
                <div class="tab-content pt-3" id="campaignTabsContent">
                    <!-- Details Tab -->
                    <div class="tab-pane fade show active" id="details-content" role="tabpanel" aria-labelledby="details-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Name:</strong> <span id="campaign-name"></span></p>
                                <p><strong>Description:</strong> <span id="campaign-description"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Status:</strong> <span id="campaign-status"></span></p>
                                <p><strong>Dates:</strong> <span id="campaign-dates"></span></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contacts Tab -->
                    <div class="tab-pane fade" id="contacts-content" role="tabpanel" aria-labelledby="contacts-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Campaign Contacts</h5>
                            <button class="btn btn-sm btn-success" id="add-contact-btn">
                                <i class="fas fa-plus"></i> Add Contact
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover" id="contacts-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="contacts-list">
                                    <!-- Contacts will be populated here via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Responses Tab -->
                    <div class="tab-pane fade" id="responses-content" role="tabpanel" aria-labelledby="responses-tab">
                        <div class="table-responsive">
                            <table class="table table-hover" id="responses-table">
                                <thead>
                                    <tr>
                                        <th>Contact</th>
                                        <th>Submitted</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="responses-list">
                                    <!-- Responses will be populated here via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Survey Modal -->
<div class="modal fade" id="survey-modal" tabindex="-1" aria-labelledby="survey-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="survey-modal-label">Create Survey</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="survey-form">
                    <input type="hidden" id="survey-id">
                    <div class="mb-3">
                        <label for="survey-title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="survey-title" required>
                    </div>
                    <div class="mb-3">
                        <label for="survey-description" class="form-label">Description</label>
                        <textarea class="form-control" id="survey-description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="survey-status" class="form-label">Status</label>
                        <select class="form-select" id="survey-status">
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-survey-btn">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Question Modal -->
<div class="modal fade" id="question-modal" tabindex="-1" aria-labelledby="question-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="question-modal-label">Add Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="question-form">
                    <input type="hidden" id="question-id">
                    <div class="mb-3">
                        <label for="question-text" class="form-label">Question Text</label>
                        <textarea class="form-control" id="question-text" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="question-type" class="form-label">Question Type</label>
                        <select class="form-select" id="question-type">
                            <option value="text">Text (Short Answer)</option>
                            <option value="paragraph">Paragraph (Long Answer)</option>
                            <option value="single_choice">Single Choice (Radio Buttons)</option>
                            <option value="multiple_choice">Multiple Choice (Checkboxes)</option>
                            <option value="rating">Rating Scale</option>
                            <option value="date">Date</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="question-required" checked>
                            <label class="form-check-label" for="question-required">
                                Required
                            </label>
                        </div>
                    </div>
                    <div class="mb-3 d-none" id="options-container">
                        <label class="form-label">Options</label>
                        <div id="options-list">
                            <div class="input-group mb-2">
                                <input type="text" class="form-control option-input" placeholder="Option text">
                                <button class="btn btn-outline-danger remove-option" type="button">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-1" id="add-option-btn">
                            <i class="fas fa-plus"></i> Add Option
                        </button>
                    </div>
                    <div class="mb-3 d-none" id="rating-container">
                        <div class="row">
                            <div class="col-6">
                                <label for="rating-min" class="form-label">Min Value</label>
                                <input type="number" class="form-control" id="rating-min" value="1" min="0">
                            </div>
                            <div class="col-6">
                                <label for="rating-max" class="form-label">Max Value</label>
                                <input type="number" class="form-control" id="rating-max" value="5" min="1">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-question-btn">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Campaign Modal -->
<div class="modal fade" id="campaign-modal" tabindex="-1" aria-labelledby="campaign-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="campaign-modal-label">Create Campaign</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="campaign-form">
                    <input type="hidden" id="campaign-id">
                    <div class="mb-3">
                        <label for="campaign-name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="campaign-name" required>
                    </div>
                    <div class="mb-3">
                        <label for="campaign-description" class="form-label">Description</label>
                        <textarea class="form-control" id="campaign-description" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="campaign-start-date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="campaign-start-date">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="campaign-end-date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="campaign-end-date">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="campaign-status" class="form-label">Status</label>
                        <select class="form-select" id="campaign-status">
                            <option value="pending">Pending</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-campaign-btn">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Contacts Modal -->
<div class="modal fade" id="add-contacts-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Contacts to Campaign</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="contactsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="existing-contacts-tab" data-bs-toggle="tab" data-bs-target="#existing-contacts" type="button" role="tab">Existing Contacts</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="new-contact-tab" data-bs-toggle="tab" data-bs-target="#new-contact" type="button" role="tab">Add New Contact</button>
                    </li>
                </ul>
                <div class="tab-content mt-3" id="contactsTabsContent">
                    <div class="tab-pane fade show active" id="existing-contacts" role="tabpanel">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="select-all-contacts">
                                <label class="form-check-label" for="select-all-contacts">
                                    Select All
                                </label>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                    </tr>
                                </thead>
                                <tbody id="available-contacts-list">
                                    <!-- Contacts will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="new-contact" role="tabpanel">
                        <form id="new-contact-form">
                            <div class="mb-3">
                                <label for="contact-name" class="form-label">Name (Optional)</label>
                                <input type="text" class="form-control" id="contact-name" name="name">
                            </div>
                            <div class="mb-3">
                                <label for="contact-email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="contact-email" name="email">
                            </div>
                            <div class="mb-3">
                                <label for="contact-phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="contact-phone" name="phone">
                            </div>
                            <div class="form-text">At least one of email or phone is required.</div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="add-contacts-btn">Add Contacts</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="confirm-delete-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirm-delete-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="confirm-delete-message">
                Are you sure you want to delete this item? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Directly initialize SurveyManager
    window.surveyManager = new SurveyManager();
</script>


