<div class="row mb-2 hidden_not_admin" >
    <div class="col-md-12 col-lg-12 hidden_not_admin">
        <div class="card hidden_not_admin">
            <div class="card-header bg-dark border-bottom-0 p-0">
                <ul class="nav nav-tabs card-header-tabs" id="adminTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-white px-4 py-3" id="admin-overview-tab" data-bs-toggle="tab" data-bs-target="#adminOverview" type="button" role="tab" aria-controls="overview" aria-selected="false">
                            <i class="fas fa-tachometer-alt me-2"></i>Overview
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-white active px-4 py-3" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="true">
                            <i class="fas fa-users me-2"></i>Users
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-white px-4 py-3" id="events-tab" data-bs-toggle="tab" data-bs-target="#events" type="button" role="tab" aria-controls="events" aria-selected="false">
                            <i class="fas fa-calendar-alt me-2"></i>Events
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body pt-0">
                <div class="tab-content border-top pt-4" id="adminTabsContent">
                    <div class="tab-pane fade" id="adminOverview" role="tabpanel" aria-labelledby="admin-overview-tab">
                        <div class="row">
                            <div class="col-md-12">
                                <h4>Admin Overview</h4>
                                <p>Welcome to the admin dashboard overview.</p>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade show active" id="users" role="tabpanel" aria-labelledby="users-tab">
                        @include('webapp.richbot9000._admin_users')
                    </div>

                    <div class="tab-pane fade" id="events" role="tabpanel" aria-labelledby="events-tab">
                        <div class="row">
                            <div class="col-md-12">
                                <h4>Events Log</h4>
                                <p>Event tracking and monitoring will be displayed here.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom tab styling */
    .nav-tabs {
        border-bottom: 0;
    }
    
    .nav-tabs .nav-link {
        border: none;
        font-weight: 500;
        position: relative;
        transition: all 0.2s ease;
        background: transparent;
    }

    .nav-tabs .nav-link:hover {
        border: none;
        background: rgba(255,255,255,0.1);
    }

    .nav-tabs .nav-link.active {
        color: #fff !important;
        background: rgba(255,255,255,0.2);
        border: none;
    }

    .nav-tabs .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: #fff;
    }

    /* Add subtle shadow to card */
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    /* Improve tab content spacing */
    .tab-content {
        background: #fff;
    }

    .tab-pane {
        padding: 1rem 0;
    }
</style>
