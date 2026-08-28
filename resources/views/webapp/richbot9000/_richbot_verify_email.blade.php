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
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('verify-richbot-email-button')) {
            e.preventDefault();

            // Find the input field relative to the clicked button
            const button = e.target;
            const card = button.closest('.card');
            const emailCodeInput = card.querySelector('#emailCodeInput');
            const emailCode = emailCodeInput.value;

            if (!emailCode) {
                showAlert('Please enter the email verification code.', 'warning');
                return;
            }

            fetch('/api/verify-email', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + appState.apiToken,
                },
                body: JSON.stringify({ token: emailCode }),
            })
            .then(response => {
                if (response.status === 401) {
                    throw new Error('Unauthorized. Please log in again.');
                }
                console.log('response');
                console.log(response.json());
                return response.json().then(data => {
                    if (!response.ok) {
                        throw new Error(data.error || 'Email verification failed.');
                    }
                    return data;
                });
            })
            .then(data => {


                console.log('data');
                console.log(data);

                if (data.success) {
                    showAlert('Email verified successfully!', 'success');
                    appState.user.email_verified_at = new Date().toISOString();
                    localStorage.setItem('app_state', JSON.stringify(appState));
                    updateUserUI();
                } else {
                    showAlert('Invalid verification code', 'danger');
                }









            })
            .catch(error => {
                console.error('Error verifying email:', error);
                showAlert(error.message || 'An error occurred. Please try again.', 'danger');
            });
        }
    });

    // Resend Verification Email Logic
    document.addEventListener('submit', function(e) {
        if (e.target.id === 'verificationEmailForm') {
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
                if (response.status === 401) {
                    throw new Error('Unauthorized. Please log in again.');
                }
                return response.json().then(data => {
                    if (!response.ok) {
                        throw new Error(data.error || 'Failed to resend verification email.');
                    }
                    return data;
                });
            })
            .then(data => {
                showAlert(data.message || 'Verification email sent successfully.', 'success');
                // If token is provided in response, auto-fill the input
                if (data.token) {
                    // Find the input field relative to the form
                    const form = e.target;
                    const card = form.closest('.card');
                    const emailCodeInput = card.querySelector('#emailCodeInput');
                    emailCodeInput.value = data.token;
                }
            })
            .catch(error => {
                console.error('Error resending verification email:', error);
                showAlert(error.message || 'An error occurred. Please try again.', 'danger');
            });
        }
    });


</script>
