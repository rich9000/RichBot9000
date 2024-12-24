<div class="col-12">

    <div class="card col-4 m-auto mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">


        <div style="display: inline-block;">
            Email: <span class="richbot_user_email"> </span>
        </div>

            <span class="hidden_email_not_verified" style="color: green;">
                   Verified
                </span>
            <span class="hidden_email_verified" style="color: red;">
                  Not Verified
                </span>


    </div>
    <div class="card-body">
        <div>
            <div class="form-switch">
                <label for="emailNotifications">Email Notifications</label>
                <input type="checkbox" class="form-check-input" style="margin-left:0;" id="emailNotifications" name="notifications[email]">
            </div>

            <div style="text-align: center;">
                <div class="hidden_email_verified">
                    <p class="mt-2 text-muted">
                        <input type="text" id="emailCodeInput" placeholder="Email Code" />
                        <button class="verify-richbot-email-button" data-type="email">Verify</button>
                    </p>

                    <form id="verificationEmailForm" class="d-inline">
                        <button type="submit" class="btn btn-link p-0 m-0 align-baseline">Resend Verification Email</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
    // Email Verification Logic
    document.querySelector('.verify-richbot-email-button').addEventListener('click', function(e) {
        e.preventDefault();

        const emailCode = document.getElementById('emailCodeInput').value;

        if (!emailCode) {
            alert('Please enter the email code.');
            return;
        }

        fetch('/api/verify-email', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken, // Add token if needed
            },
            body: JSON.stringify({ token: emailCode }),
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Email verification failed.');
            }
            return response.json();
        })
        .then(data => {
            if (data.user) {

                console.log('data user email check' + appState.user.email)


                if (data.user.email === appState.user.email) {
                    alert('Email verified successfully.');
                    appState.user = data.user;
                    localStorage.setItem('app_state', JSON.stringify(appState));
                    updateUserUI();
                }
            } else {
                alert('Invalid code. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error verifying email:', error);
            alert('An error occurred. Please try again.' + error);
        });
    });

    // Resend Verification Email Logic
    document.getElementById('verificationEmailForm').addEventListener('submit', function(e) {
        e.preventDefault();

        fetch('/api/resend-email-verification', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json',
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to resend verification email.');
            }
            return response.json();
        })
        .then(data => {
            alert('Verification email resent successfully.');
        })
        .catch(error => {
            console.error('Error resending verification email:', error);
            alert('An error occurred. Please try again.');
        });
    });


</script>
