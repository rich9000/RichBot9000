<!-- Login Section -->
<div class="col-md-3 hidden_dash_logged_in" id="dashLoginSection">
    <div class="card mb-4" id="dash-login-info">
        <div class="card-header">
            <h4>Rainbow Dash Login</h4>
        </div>
        <div class="card-body">
            <form id="dashLoginForm">
                <div class="mb-3">
                    <input type="email" id="dash-email" class="form-control" placeholder="Email" required>
                </div>
                <div class="mb-3">
                    <input type="password" id="dash-password" class="form-control" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login to Rainbow Dashboard</button>
            </form>
        </div>
    </div>
</div>

<!-- Login Section -->
<div class="col-md-3 hidden_dash_not_logged_in hidden" id="dashLoginSection">
    <div class="card mb-4" id="dash-login-info">
        <div class="card-header">
            <h4>Rainbow Dash Section</h4>
        </div>
        <div class="card-body">
            <p><strong>Dash Name:</strong> <span class="dash-user-name-span"></span><br/>
            <strong>Dash Email:</strong> <span class="dash-user-email-span"></span></p>
            <p>Welcome to the Rainbow Dashboard Toolpage.</p>
            <p>Get all the tickets and comments for the last week.</p>

            <button id="fetchTicketsBtn" class="btn btn-secondary w-100 mt-3">Fetch Tickets and Analyze Trends</button>
        </div>
    </div>
</div>

<script>

    $(document).ready(function() {

        updateDashDisplay();




        // Event listener for the Fetch Tickets button
        $('#fetchTicketsBtn').on('click', function() {
            fetchTicketsAndAnalyze();
        });




    });


    // New function to fetch tickets and analyze trends
    function fetchTicketsAndAnalyze() {
        const token = appState.dashApiToken;

        ajaxRequest(`https://dash.rainbowtel.net/api/tickets`, 'GET', {}, token)
            .then(tickets => {
                const ticketList = tickets.map(ticket => `- ${ticket.title}`).join('\n');
                const promptMessage = `Here are the tickets from the last week:\n\n${ticketList}\n\nWould you like to analyze trends and summarize?`;

                if(confirm(promptMessage)) {
                    analyzeTicketTrends(tickets);
                }
            })
            .catch(xhr => {
                alert('Failed to fetch tickets. Please try again.');
            });
    }

    function analyzeTicketTrends(tickets) {
        // Placeholder for trend analysis logic
        const trends = "Trend analysis goes here.";
        alert(`Trends Summary:\n\n${trends}`);
    }



        // Handle login form submission
    $('#dashLoginForm').on('submit', function(e) {
        //alert('dash login');
        e.preventDefault();
        const email = $('#dash-email').val();
        const password = $('#dash-password').val();
        handleDashLogin(email, password)
            .then(response => {

                localStorage.setItem('dash_api_token', response.token);
                appState.dashApiToken = response.token;
                localStorage.setItem('app_state', JSON.stringify(appState));
                checkDashUser(response.token);
                updateDashDisplay();

            });
    });


    function handleDashLogin(email, password) {
        return ajaxRequest(`https://dash.rainbowtel.net/api/login`, 'POST', { email, password }).then(response => {
            return response;
        }).catch(xhr => {
            alert(xhr.responseJSON.message);
            return Promise.reject(xhr);
        });
    }


    function updateDashDisplay(){


alert('updating display');

        if(appState.dashUser){

            const dashUser = appState.dashUser;
            $('.dash-user-name-span').text(dashUser.name);
            $('.dash-user-email-span').text(dashUser.email);


            $('.hidden_dash_logged_in').addClass('hidden');
            $('.hidden_dash_not_logged_in').removeClass('hidden');


            alert('there is a dash user');


        } else{


            $('.hidden_dash_noy_logged_in').addClass('hidden');
            $('.hidden_dash_logged_in').removeClass('hidden');


            alert('there is no dash user');

        }






    }

    function checkDashUser(token) {


        console.log(token);

        ajaxRequest(`https://dash.rainbowtel.net/api/user`, 'GET', {}, token).then(user => {

            console.log('dash_check_user_success', user);


            appState.dashUser = user;

            updateState('dashUser', user);
            // appState.apiToken = token;
            localStorage.setItem('app_state', JSON.stringify(appState));

            return user;

        }).catch(() => {
            removeStateItem('dashUser');

        });
    }


</script>

