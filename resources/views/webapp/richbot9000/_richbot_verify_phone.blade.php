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
        console.log('Phone Code Input:', document.getElementById('phoneCodeInput'));
        if (!phoneCode) {
            alert('Please enter the phone code.');
            return;
        }
        console.log('abut to verify sms');
        fetch('/api/verify-sms', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken, // Add token if needed
            },
            body: JSON.stringify({ token: phoneCode }),
        })
            .then(response => {

                console.log('response',response);

                if (!response.ok) {
                    throw new Error('Phone verification failed.');
                }
                return response.json();
            })
            .then(data => {

                console.log('data.user',data.user);
                console.log('data.user',data.message);


                if(data.user){

              //      alert('returned a user.');
                    if(data.user.email === appState.user.email){

                  //      alert('Phone verified successfully.');
                        // Optionally hide the card or update UI

                        document.querySelector('.hidden_phone_verified').style.display = 'none';

                        console.log('udated appstate user',data.user,appState.user)


                        appState.user = data.user;
                        localStorage.setItem('app_state', JSON.stringify(appState));

                        console.log('udated appstate',appState.user)

                        updateUserUI();

                    }

//Todo: do this all better through here

                } else {

                    alert('Invalid code. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error verifying phone:', error);
                alert('An error occurred. Please try again.' + error);
            });
    });













    document.getElementById('verificationSMSForm').addEventListener('submit', function(e) {
        e.preventDefault();

        fetch('/api/resend-sms-verification', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken, // Add token if needed
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to resend verification SMS.');
                }
                return response.json();
            })
            .then(data => {
                if (data.message) {
                    alert('Verification SMS resent successfully.');
                } else {
                    alert('Failed to resend verification SMS.');
                }
            })
            .catch(error => {
                console.error('Error resending verification SMS:', error);
                alert('An error occurred. Please try again.');
            });
    });

</script>
