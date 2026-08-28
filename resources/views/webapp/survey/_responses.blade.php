<!-- resources/views/webapp/survey/responses.blade.php -->
<div class="container-fluid pb-5" id="survey-responses">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 id="survey-title">Survey Responses</h2>
            <p class="text-muted" id="survey-subtitle">View and analyze survey results</p>
        </div>
        <div>
            <button class="btn btn-outline-secondary" id="back-btn">
                <i class="fas fa-arrow-left"></i> Back
            </button>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Response Filters</h5>
        </div>
        <div class="card-body">
            <form id="filter-form" class="row g-3">
                <div class="col-md-4">
                    <label for="filter-campaign" class="form-label">Campaign</label>
                    <select class="form-select" id="filter-campaign">
                        <option value="">All Campaigns</option>
                        <!-- Campaigns will be populated here via JavaScript -->
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filter-date-from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="filter-date-from">
                </div>
                <div class="col-md-4">
                    <label for="filter-date-to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="filter-date-to">
                </div>
                <div class="col-12 text-end">
                    <button type="button" class="btn btn-outline-secondary" id="reset-filters-btn">Reset</button>
                    <button type="button" class="btn btn-primary" id="apply-filters-btn">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Card -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Response Summary</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="border rounded p-3 text-center">
                        <h3 id="total-responses">0</h3>
                        <p class="mb-0 text-muted">Total Responses</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="border rounded p-3 text-center">
                        <h3 id="complete-responses">0</h3>
                        <p class="mb-0 text-muted">Complete Responses</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="border rounded p-3 text-center">
                        <h3 id="average-completion-time">0 min</h3>
                        <p class="mb-0 text-muted">Avg. Completion Time</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="border rounded p-3 text-center">
                        <h3 id="response-rate">0%</h3>
                        <p class="mb-0 text-muted">Response Rate</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Questions Analysis -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Questions Analysis</h5>
        </div>
        <div class="card-body">
            <div id="questions-analysis-container">
                <!-- Question analysis will be populated here via JavaScript -->
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Export Data</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <button class="btn btn-outline-primary w-100" id="export-csv-btn">
                        <i class="fas fa-file-csv"></i> Export to CSV
                    </button>
                </div>
                <div class="col-md-4 mb-3">
                    <button class="btn btn-outline-success w-100" id="export-excel-btn">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </button>
                </div>
                <div class="col-md-4 mb-3">
                    <button class="btn btn-outline-danger w-100" id="export-pdf-btn">
                        <i class="fas fa-file-pdf"></i> Export to PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Response List -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">Individual Responses</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="responses-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Contact</th>
                            <th>Campaign</th>
                            <th>Started At</th>
                            <th>Completed At</th>
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
                    <strong>Campaign:</strong> <span id="response-campaign-name"></span>
                </div>
                <div class="mb-3">
                    <strong>Started:</strong> <span id="response-started-at"></span>
                </div>
                <div class="mb-3">
                    <strong>Completed:</strong> <span id="response-completed-at"></span>
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

