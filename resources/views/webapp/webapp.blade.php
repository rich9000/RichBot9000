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
        https://sdk.twilio.com;
    connect-src 'self' 
        https://api.stripe.com
        https://hooks.stripe.com
        https://maps.googleapis.com
        https://cdn.jsdelivr.net
        wss://richbot9000.com:9501
        https://api.richbot9000.com
        https://notify.richbot9000.com
        http://richbot9000.local:9501
        http://richbot9000.local:8080
        https://richbot9000.local
        http://localhost:8080
        http://localhost:9501
        ws://richbot9000.local:9501
        ws://localhost:9501;
    media-src 'self' blob:;
    frame-src 'self'
        https://js.stripe.com
        https://hooks.stripe.com;
    style-src 'self' 'unsafe-inline'
        https://cdn.jsdelivr.net
        https://cdnjs.cloudflare.com
        https://fonts.googleapis.com;
    font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com;
    img-src 'self' data: https://maps.googleapis.com;
">
    <meta http-equiv="Cross-Origin-Resource-Policy" content="cross-origin">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">


    <link href="datatables/datatables.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>



    <script                src="https://code.jquery.com/jquery-3.7.1.min.js"

        crossorigin="anonymous"></script>
    <script src="datatables/datatables.js"></script>
    <!-- Include Bootstrap JS from CDN -->





    <script src="webapp_public/twilio.min.js"></script>





    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>





    <!-- Custom CSS -->
    <style>
        /* Base Styles */
        html, body {
            margin: 0;
            height: 100%;
        }

        .hidden {
            display: none !important;
        }

        /* Layout */
        .container.main-container {
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 140px); /* Account for header and footer */
            padding-top: 1rem;
        }

        .content-section {
            flex: 1;
        }

        /* Navigation Styles */
        .navbar {
            background: linear-gradient(to right, #1a1a1a, #2d2d2d);
            padding: 0.75rem 0;
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
            z-index: 1050;
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
</head>
<body>
<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm" id="mainHeader">
    <div class="container-fluid px-4">
        <!-- Left Header Icon -->
        <div id="headerIconLeft" class="hidden">
            <i class="fas fa-bars" style="color: white; cursor: pointer;"></i>
        </div>

        <!-- Brand - Move inside headerContent but outside navbar-collapse -->
        <a class="navbar-brand d-flex align-items-center" href="#">
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
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a class="nav-link px-3 active nav-section-toggler" href="#" data-section="richbotSection">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li> 

                    <!-- User Menu - Only visible when logged in -->
                    <li class="nav-item dropdown hidden_richbot_logged_out">
                        <a class="nav-link px-3 dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user-cog me-1"></i>User Menu
                        </a>
                        <div class="dropdown-menu border-0 shadow-sm">
                            <div class="dropdown-header">Tools</div>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.assistants._prompt" data-section="assistants-prompt-section">
                                <i class="fas fa-robot me-2"></i>Assistants
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.cronbot._index" data-section="cronbots-section">
                                <i class="fas fa-clock me-2"></i>CronBots
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.integrations._index" data-section="integrations-section">
                                <i class="fas fa-plug me-2"></i>Integrations
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.contacts._index" data-section="contacts-section">
                                <i class="fas fa-address-book me-2"></i>Contacts
                            </a>
                        </div>
                    </li>

                    <!-- Open Tabs - Visible to Users -->
                    <li data-visible-role="None" class="nav-item dropdown hidden hidden_richbot_logged_out">
                        <a class="nav-link px-3 dropdown-toggle" href="#" id="openTabs" data-bs-toggle="dropdown">
                            <i class="fas fa-folder-open me-1"></i>Open Tabs
                        </a>
                        <div class="dropdown-menu border-0 shadow-sm">
                            <div class="dropdown-header">Recent Tabs</div>
                            <div id="openTabsList">
                                <!-- Dynamically populated tabs -->
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-file me-2"></i>No open tabs
                                </a>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" id="clearTabsBtn">
                                <i class="fas fa-trash-alt me-2"></i>Clear All Tabs
                            </a>
                        </div>
                    </li>

                    <!-- Admin Menu -->
                    <li data-visible-role="admin" class="nav-item dropdown hidden_richbot_logged_out">
                        <a class="nav-link px-3 dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-shield-alt me-1"></i>Admin
                        </a>
                        <div class="dropdown-menu border-0 shadow-sm">
                            <div class="dropdown-header">Management</div>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.assistants._index" data-section="assistants_content_section">
                                <i class="fas fa-users me-2"></i>Assistants
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.tools._index" data-section="ollama-tools-section">
                                <i class="fas fa-tools me-2"></i>Tools
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.pipelines._index" data-section="assistant-pipelines-section">
                                <i class="fas fa-project-diagram me-2"></i>Pipelines
                            </a>
                            <div class="dropdown-divider"></div>
                            <div class="dropdown-header">AI & Chat</div>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.realtime._realtime" data-section="realtime-section">
                                <i class="fas fa-comments me-2"></i>Realtime Chat
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.ollama._dashboard" data-section="ollama-section">
                                <i class="fas fa-brain me-2"></i>Ollama
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.conversations._index" data-section="conversations-section">
                                <i class="fas fa-brain me-2"></i>Conversations 
                            </a>
                            <div class="dropdown-divider"></div>
                            <div class="dropdown-header">System</div>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.richbot9000._admin_section" data-section="admin-section">
                                <i class="fas fa-cog me-2"></i>Admin Section
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.sms._sms_index" data-section="sms-messages-section">
                                <i class="fas fa-sms me-2"></i>SMS Messages
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.remote_richbot._richbots" data-section="remote-richbots-section">
                                <i class="fas fa-server me-2"></i>RichBots Overview
                            </a>
                        </div>
                    </li>

                    <!-- Editor Menu -->
                    <li data-visible-role="Editor" class="nav-item dropdown hidden_richbot_logged_out">
                        <a class="nav-link px-3 dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-edit me-1"></i>Editor
                        </a>
                        <div class="dropdown-menu border-0 shadow-sm">
                            <div class="dropdown-header">Content</div>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.conversations._create" data-section="create-conversation-section">
                                <i class="fas fa-plus-circle me-2"></i>New Conversation
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.realtime._realtime" data-section="realtime-section">
                                <i class="fas fa-comments me-2"></i>Realtime Chat
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.phone._answer" data-section="phone-answer-section">
                                <i class="fas fa-phone me-2"></i>Phones
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.phone._clients" data-section="phone-clients-section">
                                <i class="fas fa-phone me-2"></i>Phone Client
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.websockets._manager" data-section="websockets-manager-section">
                                <i class="fas fa-network-wired me-2"></i>WebSocket Manager
                            </a>


                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.websockets._client" data-section="websockets-client-section">
                                <i class="fas fa-network-wired me-2"></i>WebSocket Client   
                            </a>


                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.openai._realtime" data-section="realtime-section">
                                <i class="fas fa-comments me-2"></i>OpenAI Realtime Chat
                            </a>

                            
                            <div class="dropdown-divider"></div>
                            <div class="dropdown-header">Tools</div>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.tools._index" data-section="ollama-tools-section">
                                <i class="fas fa-tools me-2"></i>Tools
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.pipelines._index" data-section="assistant-pipelines-section">
                                <i class="fas fa-project-diagram me-2"></i>Pipelines
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.coding._prompt" data-section="coding-prompt-section">
                                <i class="fas fa-code me-2"></i>Coding Prompt
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.file_change_requests._index" data-section="file-change-request-section">
                                <i class="fas fa-file-code me-2"></i>File Changes
                            </a>
                        </div>
                    </li>

                    <!-- Add this after the Editor Menu and before the right-side menu -->
                    <li data-visible-role="admin" class="nav-item dropdown hidden_richbot_logged_out">
                        <a class="nav-link px-3 dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-archive me-1"></i>Legacy
                        </a>
                        <div class="dropdown-menu border-0 shadow-sm">
                            <div class="dropdown-header">Dashboard</div>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.richbot9000._admin_section" data-section="richbotSection">
                                <i class="fas fa-tachometer-alt me-2"></i>RichBot9000 Dashboard
                            </a>
                            
                            <div class="dropdown-divider"></div>
                            <div class="dropdown-header">Communication</div>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.openai._prompt" data-section="chat_content_section">
                                <i class="fas fa-comment me-2"></i>Chat
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.sms._sms_index" data-section="sms-messages-section">
                                <i class="fas fa-sms me-2"></i>SMS Messages
                            </a>
                            
                            <div class="dropdown-divider"></div>
                            <div class="dropdown-header">AI & Assistants</div>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="assistant_functions.content._index" data-section="functions_content_section">
                                <i class="fas fa-code me-2"></i>Functions
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="assistants.content._index" data-section="assistants_content_section">
                                <i class="fas fa-robot me-2"></i>Assistants
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.ollama._dashboard" data-section="ollama-section">
                                <i class="fas fa-brain me-2"></i>Ollama Overview
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.whisper._prompt" data-section="whisper-prompt">
                                <i class="fas fa-microphone me-2"></i>Whisper Test
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.ollama_conversations._ollama_prompt" data-section="ollama-prompt">
                                <i class="fas fa-comments me-2"></i>Ollama Prompt
                            </a>
                            
                            <div class="dropdown-divider"></div>
                            <div class="dropdown-header">Management</div>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.appointments._index" data-section="appointment-index-section">
                                <i class="fas fa-calendar me-2"></i>Appointments
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.project._projects" data-section="projects-index-section">
                                <i class="fas fa-project-diagram me-2"></i>Projects
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.remote_richbot._richbots" data-section="remote-richbots-section">
                                <i class="fas fa-server me-2"></i>RichBots Overview
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.assistants._index" data-section="ollama-assistants-section">
                                <i class="fas fa-users me-2"></i>Ollama Assistants
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.tools._index" data-section="ollama-tools-section">
                                <i class="fas fa-tools me-2"></i>Ollama Tools
                            </a>
                            <a class="dropdown-item nav-content-loader" href="#" data-view="webapp.pipelines._index" data-section="assistant-pipelines-section">
                                <i class="fas fa-stream me-2"></i>Assistant Pipelines
                            </a>
                        </div>
                    </li>
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
                <div class="d-flex align-items-center">
                    <i class="fas fa-phone me-2 hidden_phone_verified text-danger"></i>
                    <i class="fas fa-phone me-2 hidden_phone_not_verified text-success"></i>
                    <span class="hidden_phone_verified text-danger">Phone Unverified</span>
                    <span class="hidden_phone_not_verified text-success">Phone Verified</span>
                </div>
            </div>

            <!-- Verification Actions -->
            <div class="px-3 py-2">
                <div class="hidden_email_verified mb-2">
                    <a href="#" id="resendVerificationLink" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-envelope me-2"></i>Resend Email Verification
                    </a>
                </div>
                <div class="hidden_phone_verified">
                    <a href="#" id="resendSMSVerificationLink" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-sms me-2"></i>Resend SMS Verification
                    </a>
                </div>
            </div>

            <!-- Debug & System -->
            <div class="dropdown-divider"></div>
            <div class="dropdown-header">System</div>
            <a class="dropdown-item" href="#" data-bs-toggle="collapse" data-bs-target="#debugCardBody">
                <i class="fas fa-bug me-2"></i>App State Debug
            </a>
            
            <!-- Connection Status --> 
            <div class="dropdown-header hidden">Connections Status</div>
            <div class="px-3 py-2">
                <ul id="servicesList" class="list-unstyled mb-0">
                    <!-- Dynamically populated -->
                </ul>
            </div>

            <!-- Logout -->
            <div class="dropdown-divider"></div>
            <a class="dropdown-item text-danger" href="#" id="logoutButton">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
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

</body>
</html>
