<div class="container-fluid py-4">
    
<!-- Welcome Card -->
    <div class="card mb-4 ">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-tachometer-alt"></i> RichBot 9000 Dashboard</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Quick Stats -->
                <div class="col-md-3 mb-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5><i class="fas fa-robot"></i> Active Assistants</h5>
                            <h2 class="mb-0" id="activeAssistantsCount">0</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5><i class="fas fa-clock"></i> Active Cronbots</h5>
                            <h2 class="mb-0" id="activeCronbotsCount">0</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5><i class="fas fa-tools"></i> Available Tools</h5>
                            <h2 class="mb-0" id="toolsCount">0</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5><i class="fas fa-comments"></i> Total Chats</h5>
                            <h2 class="mb-0" id="totalChatsCount">0</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row hidden_richbot_logged_out">
        <!-- Quick Actions -->
        <div class="col-md-4 ">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="#" class="btn btn-primary">
                            <i class="fas fa-plus"></i> New Assistant
                        </a>
                        <a href="#" class="btn btn-success">
                            <i class="fas fa-clock"></i> Schedule Cronbot
                        </a>
                        <a href="#" class="btn btn-info">
                            <i class="fas fa-comments"></i> Start Chat
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Recent Activity</h5>
                </div>
                <div class="card-body">
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

    <!-- Upcoming Cronbots -->
    <div class="card mb-4  hidden_richbot_logged_out">
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
<script>



    // ... rest of your existing script code ...
</script>















<script>

async function loadDashboardStats() {


console.log('appstate',appState);


    try {
        const response = await fetch('/api/dashboard/stats', {
            headers: {
                'Authorization': `Bearer ${appState.apiToken}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) throw new Error('Failed to load stats');
        
        const stats = await response.json();
        
        // Update stats with animation
        animateCounter('activeAssistantsCount', stats.activeAssistants || 0);
        animateCounter('activeCronbotsCount', stats.activeCronbots || 0);
        animateCounter('toolsCount', stats.availableTools || 0);
        animateCounter('totalChatsCount', stats.totalChats || 0);
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
        ['activeAssistantsCount', 'activeCronbotsCount', 'toolsCount', 'totalChatsCount'].forEach(id => {
            document.getElementById(id).innerHTML = '<i class="fas fa-exclamation-circle text-danger"></i>';
        });
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
        
        // Show success message
        alert('Cronbot triggered successfully!');
    } catch (error) {
        console.error('Error triggering cronbot:', error);
        alert('Failed to trigger cronbot. Please try again.');
    }
}
document.addEventListener('DOMContentLoaded', function() {
    const initDashboard = () => {
        if (typeof appState === 'undefined' || !appState.apiToken) {
            console.log('Waiting for appState to be initialized...');
            setTimeout(initDashboard, 1000); // Retry after 500ms
            return;
        }
        
        console.log('AppState ready, initializing dashboard...');
        // Load initial data
        loadDashboardStats();
        loadRecentActivity();
        loadUpcomingCronbots();
        
        // Set up auto-refresh every 30 seconds
        setInterval(() => {
            loadDashboardStats();
            loadRecentActivity();
            loadUpcomingCronbots();
        }, 30000);
    };

    // Start initialization process
    initDashboard();
});


</script>
