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
    // Phone Verification Logic
    document.addEventListener('click', function(e) {
        if (e.target.id === 'verify-richbot-phone-button' || e.target.classList.contains('verify-richbot-phone-button')) {
            e.preventDefault();

            // Find the input field relative to the clicked button
            const button = e.target;
            const card = button.closest('.card');
            let phoneCodeInput;
            
            if (card) {
                // If button is inside a card, find input in that card
                phoneCodeInput = card.querySelector('#phoneCodeInput');
            } else {
                // If button is not in a card (like in main menu), find any phone input
                phoneCodeInput = document.getElementById('phoneCodeInput');
            }
            
            const phoneCode = phoneCodeInput ? phoneCodeInput.value : '';

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
                console.log('Phone verification response:', data); // Debug log
                
                if (data.user) {
                    showAlert('Phone number verified successfully!', 'success');


                    console.log('data.user', data.user);
                    console.log('appState.user', appState.user);
                    
                    appState.user = data.user;
                    localStorage.setItem('app_state', JSON.stringify(appState));
                    updateUserUI();
                } else {
                    throw new Error('Invalid response from server');
                }
            })
            .catch(error => {
                console.error('Error verifying phone:', error);
                showAlert(error.message || 'An error occurred. Please try again.', 'danger');
            });
        }
    });

    // Resend SMS Verification Logic
    document.addEventListener('submit', function(e) {
        if (e.target.id === 'verificationSMSForm') {
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
        }
    });
</script>
