<!-- resources/views/webapp/webapp.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Credential Manager</title>
    <meta http-equiv="Content-Security-Policy" content="
    default-src 'self';
    script-src 'self' 'unsafe-inline' 'unsafe-eval'
        https://cdnjs.cloudflare.com
        https://cdn.jsdelivr.net
        https://code.jquery.com
        https://js.stripe.com
        https://maps.googleapis.com
        https://sdk.twilio.com
        https://unpkg.com;
    connect-src 'self' 
        https://api.stripe.com
        https://hooks.stripe.com
        https://maps.googleapis.com
        https://cdn.jsdelivr.net
        https://unpkg.com
        wss://{{ config('app.domain') }}:{{ config('app.ws_port') }}
        wss://{{ config('app.domain') }}:{{ config('app.ws_port_alt') }}
        wss://richbot9000.local:9501
        https://api.{{ config('app.domain') }}
        https://notify.{{ config('app.domain') }}
        http://richbot9000.local:9501
        http://richbot9000.local:8080
        https://richbot9000.local
        http://localhost:8080
        http://localhost:9501
        ws://richbot9000.local:9501
        ws://richbot9000.local:9502
        wss://richbot9000.local:9501
        wss://richbot9000.local:9502
        ws://{{ config('app.domain') }}:{{ config('app.ws_port') }}
        ws://{{ config('app.domain') }}:{{ config('app.ws_port_alt') }}
        ws://localhost:9501
        ws://localhost:9502;
    media-src 'self' data: blob:;
    frame-src 'self'
        https://js.stripe.com
        https://hooks.stripe.com;
    style-src 'self' 'unsafe-inline'
        https://cdn.jsdelivr.net
        https://cdnjs.cloudflare.com
        https://fonts.googleapis.com
        https://unpkg.com;
    font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com;
    img-src 'self' data: https://maps.googleapis.com;
">
    <meta http-equiv="Cross-Origin-Resource-Policy" content="cross-origin">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">


    <link href="datatables/datatables.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>



    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        crossorigin="anonymous"></script>
    <script src="datatables/datatables.js"></script>
    <!-- Include Bootstrap JS from CDN -->

    <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
    
    <script src="webapp_public/twilio.min.js"></script>
    <script src="ai_easy_form/ai_easy_form.js"></script>
    <script src="webapp_public/richbot-widget.js"></script>
    <script src="webapp_public/richbot-display.js"></script>
    <script src="webapp_public/survey.js"></script> 
    <script src="webapp_public/richbot-client.js"></script> 
    <script src="webapp_public/phone-tree-manager.js"></script>
    <script src="webapp_public/audio-manager.js"></script>  
    <script src="webapp_public/audio-recorder-widget.js"></script>
    <script src="webapp_public/ConversationPathBuilder.js"></script>
    <script src="webapp_public/ConversationPathBuilderV2.js"></script>
    <script src="webapp_public/PhoneTreePath.js"></script>
    <script src="webapp_public/PipelineBuilder.js"></script>
<!-- Load base classes first -->
<script src="webapp_public/nodes/BaseNode.js"></script>
<script src="webapp_public/nodes/AssistantToolHandler.js"></script>

<!-- Load parent node classes -->
<script src="webapp_public/nodes/EntryNode.js"></script>
<script src="webapp_public/nodes/ActionNode.js"></script>
<script src="webapp_public/ActionNodeList.js"></script>
<script src="webapp_public/nodes/DecisionNode.js"></script>
<script src="webapp_public/nodes/DataNode.js"></script>

<!-- Load specific entry nodes -->
<script src="webapp_public/nodes/RootEntryNode.js"></script>
<script src="webapp_public/nodes/ChatEntryNode.js"></script>
<script src="webapp_public/nodes/TwilioInboundEntryNode.js"></script>
<script src="webapp_public/nodes/TwilioOutboundEntryNode.js"></script>

<!-- Load specific action nodes -->
<script src="webapp_public/nodes/SayActionNode.js"></script>
<script src="webapp_public/nodes/PlayActionNode.js"></script>
<script src="webapp_public/nodes/AssistantActionNode.js"></script>
<script src="webapp_public/nodes/PipelineActionNode.js"></script>
<script src="webapp_public/nodes/PhoneTreeActionNode.js"></script>
<script src="webapp_public/nodes/SurveyActionNode.js"></script>
<script src="webapp_public/nodes/HangupActionNode.js"></script>
<script src="webapp_public/nodes/VoiceMailActionNode.js"></script>
<script src="webapp_public/nodes/TransferActionNode.js"></script>
<script src="webapp_public/nodes/RouteActionNode.js"></script>
<script src="webapp_public/nodes/ConversationPathActionNode.js"></script>
<script src="webapp_public/nodes/ScriptActionNode.js"></script>
<script src="webapp_public/nodes/WebsocketActionNode.js"></script>
<script src="webapp_public/nodes/SMSActionNode.js"></script>
<script src="webapp_public/nodes/EmailActionNode.js"></script>
<script src="webapp_public/nodes/WaitActionNode.js"></script>
<script src="webapp_public/nodes/MonitorCallActionNode.js"></script>
<script src="webapp_public/nodes/AssistantToolActionNode.js"></script>

<!-- Load specific data nodes -->
<script src="webapp_public/nodes/OutageCheckDataNode.js"></script>
<script src="webapp_public/nodes/CustomerLookupDataNode.js"></script>
<script src="webapp_public/nodes/CustomDataNode.js"></script>
<script src="webapp_public/nodes/ContextAssistantDataNode.js"></script>
<script src="webapp_public/nodes/APIDataNode.js"></script>
<script src="webapp_public/nodes/FileDataNode.js"></script>
<script src="webapp_public/nodes/AssistantToolDataNode.js"></script>

<!-- Load specific decision nodes -->
<script src="webapp_public/nodes/UserDecisionNode.js"></script>
<script src="webapp_public/nodes/AssistantDecisionNode.js"></script>
<script src="webapp_public/nodes/ConditionalDecisionNode.js"></script>
<script src="webapp_public/nodes/AssistantToolDecisionNode.js"></script>
<!-- Load factory and builder -->
<script src="webapp_public/nodes/NodeFactory.js"></script>


<script src="webapp_public/CronbotBuilder.js"></script>


    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>

    <link rel="stylesheet" href="css/phone-tree.css">

    <!-- Custom CSS -->
    <style>
        /* Base Styles */
        html, body {
            margin: 0;
            height: 100%;
        }

        /* Role-based background colors */
        .role-admin {
            background-color: rgba(108, 99, 255, 0.05);
        }

        .role-user {
            background-color: rgba(40, 167, 69, 0.05);
        }

        .role-tools-user {
            background-color: rgba(255, 193, 7, 0.05);
        }

        .role-tools-admin {
            background-color: rgba(255, 193, 7, 0.1);
        }

        .role-phone-tree-user {
            background-color: rgba(23, 162, 184, 0.05);
        }

        .role-phone-tree-admin {
            background-color: rgba(23, 162, 184, 0.1);
        }

        .role-pipelines-user {
            background-color: rgba(220, 53, 69, 0.05);
        }

        .role-pipelines-admin {
            background-color: rgba(220, 53, 69, 0.1);
        }

        .role-surveys-user {
            background-color: rgba(111, 66, 193, 0.05);
        }

        .hidden {
            display: none !important;
        }

        /* Layout */
        .container.main-container {
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 140px); /* Account for header and footer */
            padding-top: 0;
        }

        .content-section {
            flex: 1;
            
        }

        /* Navigation Styles */
        .navbar {
            background: linear-gradient(to right, #1a1a1a, #2d2d2d);
            padding: 0.75rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .nav-link {
            position: relative;
            font-weight: 500;
            padding: 0.5rem 1rem;
            color: rgba(255, 255, 255, 0.85) !important;
            transition: color 0.2s ease;
        }

        .nav-link:hover {
            color: rgba(255, 255, 255, 1) !important;
        }

        .nav-link.active {
            color: #fff !important;
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--bs-primary);
        }

        /* Dropdown Styles */
        .dropdown-menu {
            border: none;
            border-radius: 0.5rem;
            padding: 0.5rem 0;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .dropdown-header {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            padding: 0.5rem 1rem;
            color: #6c757d;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .dropdown-item i {
            width: 1.25rem;
            opacity: 0.7;
            transition: opacity 0.2s ease;
        }

        .dropdown-item:hover i {
            opacity: 1;
        }

        /* User Menu Styles */
        .user-menu .fa-user-circle {
            font-size: 1.5rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .richbot_user_name {
            line-height: 1.2;
            font-weight: 500;
        }

        .richbot_user_email {
            font-size: 0.75rem;
            line-height: 1;
            opacity: 0.75;
        }

        /* Verification Icons */
        .fa-envelope, .fa-phone {
            font-size: 0.75rem;
            opacity: 0.9;
        }

        /* Notification Badge */
        .position-absolute.badge {
            transform: translate(25%, -25%) !important;
            font-size: 0.65rem;
            padding: 0.25rem 0.5rem;
        }

        /* Footer Styles */
        #mainFooter {
            background-color: #1a1a1a;
            padding: 1rem 0;
            margin-top: auto;
        }

        /* Alert Container */
        .alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 300px;
            z-index: 1050; /* Ensure it appears above other content */
        }

        /* Form Elements */
        .form-control:focus {
            box-shadow: none;
            border-color: var(--bs-primary);
        }

        /* Button Styles */
        .btn-primary {
            background-color: #6c63ff;
            border-color: #6c63ff;
        }

        .btn-primary:hover {
            background-color: #5a54d1;
            border-color: #5a54d1;
        }

        /* Sortable Elements */
        .stage-drag-handle {
            cursor: grab;
            opacity: 0.5;
            transition: opacity 0.2s ease;
        }

        .stage-drag-handle:hover {
            opacity: 1;
        }

        .sortable-ghost {
            opacity: 0.5;
            background: #f8f9fa;
        }

        /* List Styles */
        .assistant-list, .files-list {
            max-height: 150px;
            overflow-y: auto;
        }

        .file-item {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Additional styles for Open Tabs */
        #openTabsList {
            max-height: 300px;
            overflow-y: auto;
        }

        #openTabsList .dropdown-item {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 250px;
        }

        #openTabsList:empty::after {
            content: 'No open tabs';
            padding: 0.5rem 1rem;
            color: #6c757d;
            font-style: italic;
            display: block;
        }

        /* Hover effect for clear tabs button */
        #clearTabsBtn {
            color: #dc3545;
        }

        #clearTabsBtn:hover {
            background-color: rgba(220, 53, 69, 0.1);
        }

        .nav-tabs .nav-link {
    color: #495057;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
}

.nav-tabs .nav-link.active {
    color: #495057;
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
}

.card-header {
    background-color: #f8f9fa;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    margin: 0 2px;
}
    </style>


    <!-- Node Module System -->
    <script type="module">
        // Initialize global namespace for node system
        window.nodeSystem = {
            loadedModules: new Map(),
            async loadModule(moduleName) {
                if (this.loadedModules.has(moduleName)) {
                    return this.loadedModules.get(moduleName);
                }
                try {
                    const module = await import(`/webapp_public/nodes/${moduleName}.js`);
                    this.loadedModules.set(moduleName, module);
                    return module;
                } catch (error) {
                    console.error(`Error loading module ${moduleName}:`, error);
                    throw error;
                }
            }
        };

        // Load base modules
        try {
            await window.nodeSystem.loadModule('BaseNode');
            await window.nodeSystem.loadModule('NodeLoader');
            await window.nodeSystem.loadModule('NodeFactory');
            console.log('Base node modules loaded successfully');
        } catch (error) {
            console.error('Error loading base node modules:', error);
        }
    </script>

    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
</head>
<body>
<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm" id="mainHeader">
    <div class="container-fluid px-4">
        <!-- Left Header Icon -->
        <div id="headerIconLeft" class="hidden">
            <i class="fas fa-bars" style="color: white; cursor: pointer;"></i>
        </div>

        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center" href=" " onclick="window.location.reload()">
            <i class="fas fa-robot text-primary me-2"></i>
            <span class="fw-bold">RichBot9000</span>
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Header Content -->
        <div id="headerContent" class="container-fluid">
            <!-- Main Navigation -->
            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- Left Side Menu -->
                <ul class="navbar-nav me-auto">
                    <!-- Menu items will be dynamically generated here -->
                </ul>

                <!-- Right Side Menu -->
                <ul class="navbar-nav align-items-center">
                <li class="nav-item dropdown hidden_richbot_logged_out">
                <a class="nav-link px-3 dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
    <div class="d-flex align-items-center gap-3">
        <!-- User Icon -->
        <i class="fas fa-user-circle fs-4"></i> 
                
                <!-- User Info - Hidden on Mobile -->
                <div class="d-none d-md-flex flex-column">
                    <div class="d-flex align-items-center gap-2">
                        <span class="richbot_user_name text-white fw-medium"></span>
                        <!-- Notification Badge -->
                        <span class="badge rounded-pill bg-danger" id="notificationCount">
                            <i class="fas fa-bell me-1"></i><span>0</span>
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-light opacity-75 richbot_user_email"></small>
                        <!-- Verification Icons -->
                        <div class="d-flex gap-1 align-items-center">
                            <i class="fas fa-envelope hidden_email_verified text-danger" 
                               data-bs-toggle="tooltip" 
                               title="Email not verified"></i>
                            <i class="fas fa-envelope hidden_email_not_verified text-success" 
                               data-bs-toggle="tooltip" 
                               title="Email verified"></i>
                            <i class="fas fa-phone hidden_phone_verified text-danger" 
                               data-bs-toggle="tooltip" 
                               title="Phone not verified"></i>
                            <i class="fas fa-phone hidden_phone_not_verified text-success" 
                               data-bs-toggle="tooltip" 
                               title="Phone verified"></i>
                        </div>
                    </div>
                </div>
    </div>
</a>
        <div class="dropdown-menu dropdown-menu-end border-0 shadow-sm">
            <!-- Account Section -->
            <div class="dropdown-header">Account</div>
            <a class="dropdown-item nav-section-toggler" href="#" data-section="profileSection">
                <i class="fas fa-user me-2"></i>Profile
            </a>
            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#notificationModal">
                <i class="fas fa-bell me-2"></i>Notifications
                <span class="badge bg-danger ms-2" id="notificationCount">0</span>
            </a>

            <!-- Logout -->
            <a class="dropdown-item text-danger" href="#" id="logoutButton">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>

            <!-- Verification Status -->
            <div class="dropdown-divider"></div>
            <div class="dropdown-header">Verification Status</div>
            <div class="px-3 py-2">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-envelope me-2 hidden_email_verified text-danger"></i>
                    <i class="fas fa-envelope me-2 hidden_email_not_verified text-success"></i>
                    <span class="hidden_email_verified text-danger">Email Unverified</span>
                    <span class="hidden_email_not_verified text-success">Email Verified</span>
                </div>
                <!-- Email Verification Form -->
                <div class="hidden_email_verified mb-3">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="emailCodeInput" placeholder="Email Code">
                        <button class="btn btn-outline-primary verify-richbot-email-button" data-type="email">Verify</button>
                    </div>
                    <button type="button" class="btn btn-link btn-sm p-0 mt-1" id="resendEmailVerification">
                        Resend Verification Email
                    </button>
                </div>

                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-phone me-2 hidden_phone_verified text-danger"></i>
                    <i class="fas fa-phone me-2 hidden_phone_not_verified text-success"></i>
                    <span class="hidden_phone_verified text-danger">Phone Unverified</span>
                    <span class="hidden_phone_not_verified text-success">Phone Verified</span>
                </div>
                <!-- Phone Verification Form -->
                <div class="hidden_phone_verified mb-3">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="phoneCodeInput" placeholder="Phone Code">
                        <button class="btn btn-outline-primary verify-richbot-phone-button" data-type="sms">Verify</button>
                    </div>
                    <button type="button" class="btn btn-link btn-sm p-0 mt-1" id="resendSmsVerification">
                        Resend SMS Code
                    </button>
                </div>

                <!-- Notification Preferences -->
                <div class="dropdown-divider"></div>
                <div class="dropdown-header">Notification Preferences</div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="emailNotifications" name="notifications[email]">
                    <label class="form-check-label" for="emailNotifications">Email Notifications</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="smsNotifications" name="notifications[sms]">
                    <label class="form-check-label" for="smsNotifications">SMS Notifications</label>
                </div>
            </div>
            <!-- Debug & System -->
            <div class="dropdown-divider"></div>
            <div class="dropdown-header">Roles</div>
            <div class="px-3 py-2">
                <ul id="menuRolesList" class="list-unstyled mb-0">
                    <!-- Dynamically populated -->
                </ul>
            </div>

            <!-- Debug & System -->
            <div class="dropdown-divider"></div>
            <div class="dropdown-header">System</div>
            <a class="dropdown-item" href="#" data-bs-toggle="collapse" data-bs-target="#debugCardBody">
                <i class="fas fa-bug me-2"></i>App State Debug
            </a>
            
           
        </div>
    </li>

                <!-- Login/Register -->
    <li class="nav-item hidden_richbot_logged_in">
        <div class="d-flex gap-2">
            <a class="nav-link px-3 nav-section-toggler" href="#" data-section="richbotLoginSection">
                <i class="fas fa-sign-in-alt me-1"></i>Login
            </a>
            <a class="nav-link px-3 nav-section-toggler" href="#" data-section="richbotRegisterSection">
                <i class="fas fa-user-plus me-1"></i>Register
            </a>
        </div>
    </li>
                </ul>
            </div>
        </div>

        <!-- Right Header Icon -->
        <div id="headerIconRight" class="hidden">
            <i class="fas fa-user-circle" style="color: white; cursor: pointer;"></i>
        </div>
    </div>
</nav>
<!-- Navigation Bar -->

<div class="modal fade" id="notificationModal" tabindex="-1"
     aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationModalLabel">Notifications</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="notificationList">
                    <p>No notifications at this time.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Main Container -->
<div class="container main-container pt-3" id="main-container">
    <div id="alertContainer" class="alert-container"></div>
    <!-- Richbot 9000 Login Section -->
    <div class="content-section" id="richbotSection">
        @include('webapp.richbot9000._richbot_dashboard')
    </div>
    <!-- Richbot 9000 Login Section -->
    <div class="content-section" id="richbotLoginSection">
        @include('webapp.richbot9000._richbot_login')
    </div>
    <!-- Richbot 9000 Login Section -->
    <div class="content-section" id="richbotRegisterSection">
        @include('webapp.richbot9000._richbot_register')
    </div>

    <!-- Rainbow Dashboard Login Section -->
    <div class="content-section hidden" id="rainbowSection">
        <div class="hidden_rainbow_dash_logged_in">
            @include('webapp._rainbow_dash_login')
        </div>
        <div class="hidden_rainbow_dash_logged_out">
            Rainbow Dashboard
        </div>
    </div>



    <!-- Rainbow Dashboard Login Section -->
    <div class="content-section hidden" id="librenmsSection">
        @include('webapp.librenms._librenms_dashboard')

    </div>


    <!-- BambooHR Token Upload Section -->
    <div class="content-section hidden" id="bambooSection">

        <div class="hidden_richbot_logged_in">
            RichBot9000 Needs to be logged in to access the BambooHR Proxy
        </div>
        <div class="hidden_richbot_logged_out">
            RichBot9000 Logged in
        </div>


        <div class="hidden_bamboohr_logged_in">
            @include('webapp._bamboohr_token')

        </div>
        <div class="hidden_bamboohr_logged_out">
            BambooHR Dashboard
        </div>

    </div>

    <!-- User Profile Section -->
    <div class="content-section hidden" id="profileSection">
        @include('webapp._user_profile')
    </div>

</div>

<script>
    // Global config from Laravel
    window.appConfig = {
        domain: '{{ config('app.domain') }}',
        url: '{{ config('app.url') }}',
        wsPort: '{{ config('app.ws_port') }}',
        wsPortAlt: '{{ config('app.ws_port_alt') }}',
        wsUrl: 'wss://{{ config('app.domain') }}:{{ config('app.ws_port') }}',
        wsUrlAlt: 'wss://{{ config('app.domain') }}:{{ config('app.ws_port_alt') }}',
        apiUrl: '{{ config('app.url') }}/api'
    };
</script>

<script>

    function updateLocalStorageDebug() {
        let localStorageData = JSON.stringify(localStorage, null, 2);
        let appStateData = JSON.stringify(appState, null, 2);
// Assuming localStorageData and appStateData are variables holding the data you want to display
        document.getElementById('localStorageDebug').textContent = localStorageData;
        document.getElementById('appStateDebug').textContent = appStateData;
    }

</script>


<!-- Bootstrap Bundle with Popper -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
>
</script>

<!-- Axios for HTTP requests -->
<script
    src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js">
</script>


<script src='https://cdn.jsdelivr.net/npm/fullcalendar/index.global.min.js'></script>
<!-- Include FullCalendar JS from CDN -->



<script src="/webapp_public/webapp.js?nocache={{ time() }}"></script>


<!-- webapp/partials/navbar.blade.php -->
<nav class="navbar  navbar-dark bg-dark shadow " id="mainFooter">
    <div class="container-fluid flex" id="footerContent">

        <div style="color:white;" class="">
            RichBot9000 Manager
        </div>

 
        <div class="" id="appstate-debug-section">

                <button class="btn btn-link button" data-bs-toggle="collapse" data-bs-target="#debugCardBody"
                        aria-expanded="false" aria-controls="debugCardBody">
                    App State Debug
                </button>


        </div>



    </div>

</nav>
<div id="debugCardBody" class="collapse">
    <div class="card-body">
        <div>AppState Debug</div>
        <pre id="appStateDebug"></pre>
        <div>Local Storage Debug</div>
        <pre id="localStorageDebug"></pre>
        <button class="btn btn-primary" id="updateLocalDebugBtn" onclick="updateLocalStorageDebug();return false;">
            Update Debug
        </button>
    </div>
</div>

<script>
// Function to update open tabs list
function updateOpenTabsList() {
    const tabsList = document.getElementById('openTabsList');
    // Your logic to populate tabs
    // Example:
    // tabs.forEach(tab => {
    //     const tabItem = document.createElement('a');
    //     tabItem.className = 'dropdown-item';
    //     tabItem.href = '#';
    //     tabItem.innerHTML = `<i class="fas fa-file me-2"></i>${tab.title}`;
    //     tabsList.appendChild(tabItem);
    // });
}

// Clear tabs functionality
document.getElementById('clearTabsBtn')?.addEventListener('click', function(e) {
    e.preventDefault();
    // Your clear tabs logic here
    updateOpenTabsList();
});
</script>

<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
  if (typeof toastr !== 'undefined') {
    toastr.options = {
      "closeButton": true,
      "debug": false,
      "newestOnTop": true,
      "progressBar": true,
      "positionClass": "toast-top-right",
      "preventDuplicates": true,
      "onclick": null,
      "showDuration": "300",
      "hideDuration": "1000",
      "timeOut": "3000",
      "extendedTimeOut": "1000",
      "showEasing": "swing",
      "hideEasing": "linear",
      "showMethod": "fadeIn",
      "hideMethod": "fadeOut"
    };
  }
</script>

</body>
</html>
