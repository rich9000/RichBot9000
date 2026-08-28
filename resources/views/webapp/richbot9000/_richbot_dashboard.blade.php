<div class="container-fluid py-4">
    <div class="row">


    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">RichBot 9000 Dashboard</h5>
            </div>
        </div>
    </div>







        <!-- Main Dashboard Card -->
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-tachometer-alt"></i> RichBot 9000 Dashboard</h4>
                </div>
                <div class="card-body">


                




                    <div class="row hidden_richbot_dashboard">
                        <!-- First Column -->
                        <div class="col-md-6">
                            <!-- Active Assistants Row -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <a href="#" class="nav-content-loader text-decoration-none" data-view="webapp.assistants._index" data-section="assistants_content_section">
                                        <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded hover-shadow">
                                            <div>
                                                <h5 class="mb-0"><i class="fas fa-robot"></i> Active Assistants</h5>
                                            </div>
                                            <h2 class="mb-0" id="activeAssistantsCount">0</h2>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <!-- Active Cronbots Row -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <a href="#" class="nav-content-loader text-decoration-none" data-view="webapp.cronbot._index" data-section="cronbots-section">
                                        <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded hover-shadow">
                                            <div>
                                                <h5 class="mb-0"><i class="fas fa-clock"></i> Active Cronbots</h5>
                                            </div>
                                            <h2 class="mb-0" id="activeCronbotsCount">0</h2>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <!-- Available Tools Row -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <a href="#" class="nav-content-loader text-decoration-none" data-view="webapp.tools._index" data-section="ollama-tools-section">
                                        <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded hover-shadow">
                                            <div>
                                                <h5 class="mb-0"><i class="fas fa-tools"></i> Available Tools</h5>
                                            </div>
                                            <h2 class="mb-0" id="toolsCount">0</h2>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <!-- Total Chats Row -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <a href="#" class="nav-content-loader text-decoration-none" data-view="webapp.openai._prompt" data-section="chat_content_section">
                                        <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded hover-shadow">
                                            <div>
                                                <h5 class="mb-0"><i class="fas fa-comments"></i> Total Chats</h5>
                                            </div>
                                            <h2 class="mb-0" id="totalChatsCount">0</h2>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Second Column -->
                        <div class="col-md-6">
                            <!-- Contacts Row -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <a href="#" class="nav-content-loader text-decoration-none" data-view="webapp.contacts._index" data-section="contacts-section">
                                        <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded hover-shadow">
                                            <div>
                                                <h5 class="mb-0"><i class="fas fa-address-book"></i> Contacts</h5>
                                            </div>
                                            <h2 class="mb-0" id="contactsCount">0</h2>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <!-- Integrations Row -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <a href="#" class="nav-content-loader text-decoration-none" data-view="webapp.integrations._index" data-section="integrations-section">
                                        <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded hover-shadow">
                                            <div>
                                                <h5 class="mb-0"><i class="fas fa-plug"></i> Integrations</h5>
                                            </div>
                                            <h2 class="mb-0" id="integrationsCount">0</h2>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <!-- Surveys Row -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <a href="#" class="nav-content-loader text-decoration-none" data-view="webapp.survey._index" data-section="surveys-section">
                                        <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded hover-shadow">
                                            <div>
                                                <h5 class="mb-0"><i class="fas fa-file-code"></i> Surveys</h5>
                                            </div>
                                            <h2 class="mb-0" id="surveysCount">0</h2>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <!-- Pipelines Row -->
                            <div class="row">
                                <div class="col-12">
                                    <a href="#" class="nav-content-loader text-decoration-none" data-view="webapp.pipelines._index" data-section="assistant-pipelines-section">
                                        <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded hover-shadow">
                                            <div>
                                                <h5 class="mb-0"><i class="fas fa-project-diagram"></i> Pipelines</h5>
                                            </div>
                                            <h2 class="mb-0" id="pipelinesCount">0</h2>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Scheduled Tasks -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar"></i> Upcoming Scheduled Tasks</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Assistant</th>
                                    <th>Task</th>
                                    <th>Next Run</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="upcomingCronbots">
                                <!-- Will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Remote Richbots -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-server"></i> Remote Richbots</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Last Seen</th>
                                    <th>IP Address</th>
                                    <th>Version</th>
                                    <th>OS</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="remoteRichbots">
                                <!-- Will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center" style="cursor: pointer;" onclick="toggleActivitySection()">
                    <h5 class="mb-0">
                        <i class="fas fa-history"></i> Recent Activity 
                        <span id="activityCount" class="badge bg-secondary ms-2">0</span>
                    </h5>
                    <i class="fas fa-chevron-down" id="activityToggleIcon"></i>
                </div>
                <div class="card-body" id="activityBody" style="display: none;">
                    <div id="recentActivity">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="activityLog">
                                    <!-- Will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>



    // ... rest of your existing script code ...
</script>















<script>

async function loadDashboardStats() {
    try {
        const response = await fetch('/api/dashboard/stats', {
            headers: {
                'Authorization': `Bearer ${appState.apiToken}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) throw new Error('Failed to load stats');
        
        const stats = await response.json();
        
        // Update all stats with animation
        animateCounter('activeAssistantsCount', stats.activeAssistants || 0);
        animateCounter('activeCronbotsCount', stats.activeCronbots || 0);
        animateCounter('toolsCount', stats.availableTools || 0);
        animateCounter('totalChatsCount', stats.totalChats || 0);
        animateCounter('contactsCount', stats.contacts || 0);
        animateCounter('integrationsCount', stats.integrations || 0);
        animateCounter('surveysCount', stats.surveys || 0);
        animateCounter('pipelinesCount', stats.pipelines || 0);
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
        ['activeAssistantsCount', 'activeCronbotsCount', 'toolsCount', 'totalChatsCount', 
         'contactsCount', 'integrationsCount', 'surveysCount', 'pipelinesCount'].forEach(id => {
            document.getElementById(id).innerHTML = '<i class="fas fa-exclamation-circle text-danger"></i>';
        });
    }
}

let lastActivityCount = 0;

function toggleActivitySection() {
    const body = document.getElementById('activityBody');
    const icon = document.getElementById('activityToggleIcon');
    if (body.style.display === 'none') {
        body.style.display = 'block';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        body.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}

async function loadRecentActivity() {
    try {
        const response = await fetch('/api/dashboard/activity', {
            headers: {
                'Authorization': `Bearer ${appState.apiToken}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) throw new Error('Failed to load activity');
        
        const activities = await response.json();
        
        // Update activity count
        const activityCount = activities.length;
        const countBadge = document.getElementById('activityCount');
        countBadge.textContent = activityCount;
        
        // Check if there are new activities
        if (activityCount > lastActivityCount) {
            countBadge.classList.remove('bg-secondary');
            countBadge.classList.add('bg-danger');
        } else {
            countBadge.classList.remove('bg-danger');
            countBadge.classList.add('bg-secondary');
        }
        lastActivityCount = activityCount;
        
        const activityLog = document.getElementById('activityLog');
        if (activities.length === 0) {
            activityLog.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-muted">
                        <i class="fas fa-info-circle"></i> No recent activity
                    </td>
                </tr>`;
            return;
        }
        
        activityLog.innerHTML = activities.map(activity => `
            <tr>
                <td>${formatDateTime(activity.created_at)}</td>
                <td><span class="badge bg-${getActivityTypeColor(activity.type)}">${activity.type}</span></td>
                <td>${activity.description}</td>
                <td><span class="badge bg-${getStatusColor(activity.status)}">${activity.status}</span></td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error loading recent activity:', error);
        document.getElementById('activityLog').innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-danger">
                    <i class="fas fa-exclamation-circle"></i> Failed to load activity
                </td>
            </tr>`;
    }
}

async function loadUpcomingCronbots() {
    try {
        const response = await fetch('/api/dashboard/upcoming-cronbots', {
            headers: {
                'Authorization': `Bearer ${appState.apiToken}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) throw new Error('Failed to load cronbots');
        
        const cronbots = await response.json();
        
        const cronbotsList = document.getElementById('upcomingCronbots');
        if (cronbots.length === 0) {
            cronbotsList.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted">
                        <i class="fas fa-calendar-times"></i> No upcoming tasks
                    </td>
                </tr>`;
            return;
        }
        
        cronbotsList.innerHTML = cronbots.map(cronbot => `
            <tr>
                <td>${cronbot.assistant_name}</td>
                <td>${cronbot.prompt}</td>
                <td>${formatDateTime(cronbot.next_run_at)}</td>
                <td><span class="badge bg-${getStatusColor(cronbot.status)}">${cronbot.status}</span></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="triggerCronbot(${cronbot.id})">
                        <i class="fas fa-play"></i> Run Now
                    </button>
                </td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error loading upcoming cronbots:', error);
        document.getElementById('upcomingCronbots').innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-danger">
                    <i class="fas fa-exclamation-circle"></i> Failed to load upcoming tasks
                </td>
            </tr>`;
    }
}

async function loadRemoteRichbots() {
    try {
        const response = await fetch('/api/dashboard/remote-richbots', {
            headers: {
                'Authorization': `Bearer ${appState.apiToken}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) throw new Error('Failed to load remote richbots');
        
        const richbots = await response.json();
        
        const richbotsList = document.getElementById('remoteRichbots');
        if (richbots.length === 0) {
            richbotsList.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        <i class="fas fa-server"></i> No active remote richbots
                    </td>
                </tr>`;
            return;
        }
        
        richbotsList.innerHTML = richbots.map(richbot => `
            <tr>
                <td>${richbot.name}</td>
                <td><span class="badge bg-${getStatusColor(richbot.status)}">${richbot.status}</span></td>
                <td>${richbot.last_seen}</td>
                <td>${richbot.ip_address}</td>
                <td>${richbot.version}</td>
                <td>${richbot.os}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="manageRichbot(${richbot.id})">
                        <i class="fas fa-cog"></i> Manage
                    </button>
                </td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error loading remote richbots:', error);
        document.getElementById('remoteRichbots').innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-danger">
                    <i class="fas fa-exclamation-circle"></i> Failed to load remote richbots
                </td>
            </tr>`;
    }
}

// Animate counter function for stats
function animateCounter(elementId, finalValue) {
    const element = document.getElementById(elementId);
    const duration = 1000; // Animation duration in milliseconds
    const start = parseInt(element.textContent) || 0;
    const increment = (finalValue - start) / (duration / 16);
    let current = start;
    
    const animate = () => {
        current += increment;
        if ((increment > 0 && current >= finalValue) || 
            (increment < 0 && current <= finalValue)) {
            element.textContent = finalValue;
            return;
        }
        element.textContent = Math.round(current);
        requestAnimationFrame(animate);
    };
    
    animate();
}

// Helper functions
function getActivityTypeColor(type) {
    const colors = {
        'assistant': 'primary',
        'cronbot': 'success',
        'chat': 'info',
        'error': 'danger'
    };
    return colors[type] || 'secondary';
}

function getStatusColor(status) {
    const colors = {
        'active': 'success',
        'pending': 'warning',
        'completed': 'info',
        'error': 'danger'
    };
    return colors[status] || 'secondary';
}

function formatDateTime(datetime) {
    if (!datetime) return 'N/A';
    return new Date(datetime).toLocaleString();
}

async function triggerCronbot(cronbotId) {
    try {
        const button = event.target.closest('button');
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Running...';
        
        const response = await fetch(`/api/cronbots/${cronbotId}/trigger`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${appState.apiToken}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) throw new Error('Failed to trigger cronbot');
        
        // Refresh the dashboard data
        loadDashboardStats();
        loadRecentActivity();
        loadUpcomingCronbots();
        loadRemoteRichbots();
        
        // Show success message
        alert('Cronbot triggered successfully!');
    } catch (error) {
        console.error('Error triggering cronbot:', error);
        alert('Failed to trigger cronbot. Please try again.');
    }
}




    // Wait for all other scripts to load
    window.addEventListener('load', function() {
        // Additional delay to ensure all other DOM events have fired
        setTimeout(function() {
            // Your weather test initialization code here
           
            console.log('Weather test initialized after all other scripts');


            const initDashboard = () => {
        // If not logged in, don't initialize dashboard
        if (!appState?.tokens?.richbot || !appState?.user) {
            console.log('User not logged in, skipping dashboard initialization');
            return;
        }

        // If we have the token, initialize immediately
        if (appState.apiToken) {
            console.log('AppState ready, initializing dashboard...');
            // Load initial data
            loadDashboardStats();
            loadRecentActivity();
            loadUpcomingCronbots();
            loadRemoteRichbots();
            
            // Set up auto-refresh every 30 seconds
            setInterval(() => {
                loadDashboardStats();
                loadRecentActivity();
                loadUpcomingCronbots();
                loadRemoteRichbots();
            }, 30000);
            return;
        }

        // If we don't have the token yet, wait once and try again
        console.log('Waiting for appState to be initialized...');
        setTimeout(initDashboard, 100);
    };

    // Start initialization process
    initDashboard();



        }, 100);
    });
</script>