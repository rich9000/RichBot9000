<!-- resources/views/webapp/survey/campaigns.blade.php -->
<div class="container-fluid pb-5" id="campaign-management">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 id="campaign-title">Campaign Details</h2>
            <p class="text-muted" id="campaign-subtitle">Manage contacts and view responses</p>
        </div>
        <div>
            <button class="btn btn-outline-secondary" id="back-to-surveys-btn">
                <i class="fas fa-arrow-left"></i> Back to Surveys
            </button>
        </div>
    </div>

    <!-- Campaign Info Card -->
    <div class="card mb-4">
        <div class="card-header bg-light d-flex justify-content-between">
            <h5 class="mb-0">Campaign Information</h5>
            <div>
                <button class="btn btn-sm btn-outline-primary" id="edit-campaign-btn">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-4">Name:</dt>
                        <dd class="col-sm-8" id="display-campaign-name"></dd>
                        
                        <dt class="col-sm-4">Description:</dt>
                        <dd class="col-sm-8" id="display-campaign-description"></dd>
                        
                        <dt class="col-sm-4">Status:</dt>
                        <dd class="col-sm-8">
                            <span class="badge" id="display-campaign-status"></span>
                        </dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-4">Start Date:</dt>
                        <dd class="col-sm-8" id="display-campaign-start-date"></dd>
                        
                        <dt class="col-sm-4">End Date:</dt>
                        <dd class="col-sm-8" id="display-campaign-end-date"></dd>
                        
                        <dt class="col-sm-4">Survey:</dt>
                        <dd class="col-sm-8" id="display-campaign-survey"></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs" id="campaignTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts-content" type="button" role="tab" aria-controls="contacts-content" aria-selected="true">Contacts</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="responses-tab" data-bs-toggle="tab" data-bs-target="#responses-content" type="button" role="tab" aria-controls="responses-content" aria-selected="false">Responses</button>
        </li>
    </ul>
    
    <div class="tab-content pt-3" id="campaignTabsContent">
        <!-- Contacts Tab -->
        <div class="tab-pane fade show active" id="contacts-content" role="tabpanel" aria-labelledby="contacts-tab">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Campaign Contacts</h5>
                <button class="btn btn-success" id="add-campaign-contacts-btn">
                    <i class="fas fa-user-plus"></i> Add Contacts
                </button>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="campaign-contacts-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Sent At</th>
                                    <th>Completed At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="campaign-contacts-list">
                                <!-- Campaign contacts will be populated here via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Responses Tab -->
        <div class="tab-pane fade" id="responses-content" role="tabpanel" aria-labelledby="responses-tab">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Survey Responses</h5>
                <div>
                    <button class="btn btn-outline-success" id="export-responses-btn">
                        <i class="fas fa-file-export"></i> Export Responses
                    </button>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="campaign-responses-table">
                            <thead>
                                <tr>
                                    <th>Contact</th>
                                    <th>Started At</th>
                                    <th>Completed At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="campaign-responses-list">
                                <!-- Campaign responses will be populated here via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Response Details Modal -->
<div class="modal fade" id="response-details-modal" tabindex="-1" aria-labelledby="response-details-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="response-details-modal-label">Response Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong>Contact:</strong> <span id="response-contact-name"></span>
                </div>
                <div class="mb-3">
                    <strong>Time:</strong> <span id="response-timestamp"></span>
                </div>
                
                <hr>
                
                <div id="response-answers-container">
                    <!-- Response answers will be populated here via JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

