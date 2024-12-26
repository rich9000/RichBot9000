<div class="col-12">

    <div class="card col-4 m-auto mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div style="display: inline-block;">
                Phone Number: <span class="richbot_user_phone_number"> </span>
            </div>

            <span class="hidden_phone_not_verified" style="color: green;">
                Verified
            </span>
            <span class="hidden_phone_verified">
                Unverified
            </span>
        </div>
        <div class="card-body">

            <div>

                <div class="form-switch">

                    <label for="smsNotifications">SMS Notifications</label>
                    <input type="checkbox" class="form-check-input" style="margin-left:0;" id="smsNotifications" name="notifications[sms]">
                </div>

                <div style="text-align: center;">


                    <div class="hidden_phone_verified">
                        <p class="mt-2 text-muted">
                            <input type="text" id="phoneCodeInput" placeholder="Phone Code"/>
                            <button id="verify-richbot-phone-button" data-type="sms">Verify</button>
                        </p>

                        <form id="verificationSMSForm" class="d-inline">
                            <button type="submit" class="btn btn-link p-0 m-0 align-baseline">Resend SMS Message</button>
                        </form>

                    </div>

                </div>

            </div>
        </div>
    </div>
</div>






<script>





    document.querySelector('#verify-richbot-phone-button').addEventListener('click', function(e) {
        e.preventDefault();

        const phoneCode = document.getElementById('phoneCodeInput').value;

        if (!phoneCode) {
            showAlert('Please enter the phone verification code.', 'warning');
            return;
        }

        fetch('/api/verify-sms', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
            },
            body: JSON.stringify({ token: phoneCode }),
        })
        .then(response => {
            if (response.status === 401) {
                throw new Error('Unauthorized. Please log in again.');
            }
            return response.json().then(data => {
                if (!response.ok) {
                    throw new Error(data.error || 'Phone verification failed.');
                }
                return data;
            });
        })
        .then(data => {
            if (data.user) {
                if (data.user.email === appState.user.email) {
                    showAlert('Phone number verified successfully!', 'success');
                    appState.user = data.user;
                    localStorage.setItem('app_state', JSON.stringify(appState));
                    updateUserUI();
                }
            } else {
                throw new Error('Invalid response from server');
            }
        })
        .catch(error => {
            console.error('Error verifying phone:', error);
            showAlert(error.message || 'An error occurred. Please try again.', 'danger');
        });
    });

    document.getElementById('verificationSMSForm').addEventListener('submit', function(e) {
        e.preventDefault();

        fetch('/api/resend-sms-verification', {
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
                    throw new Error(data.error || 'Failed to resend verification SMS.');
                }
                return data;
            });
        })
        .then(data => {
            showAlert(data.message || 'Verification SMS sent successfully.', 'success');
        })
        .catch(error => {
            console.error('Error resending verification SMS:', error);
            showAlert(error.message || 'An error occurred. Please try again.', 'danger');
        });
    });

</script>
