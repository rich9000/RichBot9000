<div class="card mb-4" id="user-info">
    <div class="card-header">
        <h4>User Profile</h4>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="profile-info-tab" data-bs-toggle="tab" href="#profile_info" role="tab" aria-controls="profile-info" aria-selected="true">Info</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link " id="notifications-tab" data-bs-toggle="tab" href="#notifications" role="tab" aria-controls="notifications" aria-selected="false">Notifications</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="password-tab" data-bs-toggle="tab" href="#password_tab" role="tab" aria-controls="password" aria-selected="false">Password</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="tokens-tab" data-bs-toggle="tab" href="#tokens" role="tab" aria-controls="tokens" aria-selected="false">Tokens</a>
            </li>
        </ul>
        <div class="tab-content" id="profileTabContent">

            <div class="tab-pane fade show active" id="profile_info" role="tabpanel" aria-labelledby="profile-info-tab">
                <div class="row">
                    Info
                </div>
            </div>


            <div class="tab-pane fade" id="notifications" role="tabpanel" aria-labelledby="notifications-tab">
                <div class="row">







                        <div class="card mb-4 col-md-6 col-lg-4">
                            <div class="card-body">
                                <section class="mb-4">
                                    <header>
                                        <h2 class="h5 font-weight-bold text-dark">
                                            Permissions
                                        </h2>
                                        <p class="mt-2 text-muted">
                                        <form id="notificationPreferences">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="emailNotifications" name="notifications[email]" checked>
                                                <label class="form-check-label" for="emailNotifications">Email Notifications</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="smsNotifications" name="notifications[sms]">
                                                <label class="form-check-label" for="smsNotifications">SMS Notifications</label>
                                                <small class="form-text text-muted">
                                                    By opting in, you agree to receive SMS notifications. Message and data rates may apply. You can opt out at any time by replying STOP to any message.
                                                </small>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="pushNotifications" name="notifications[push]">
                                                <label class="form-check-label" for="pushNotifications">Push Notifications</label>
                                            </div>
                                            <button type="submit" class="btn btn-primary mt-3">Save Preferences</button>

                                        </form>
                                        <script>
                                            document.addEventListener('DOMContentLoaded', function () {
                                                const notificationForm = document.getElementById('notificationPreferences');

                                                // Function to save notification preferences to localStorage
                                                const saveNotificationPreferences = () => {
                                                    const formData = new FormData(notificationForm);
                                                    const serializedForm = new URLSearchParams(formData).toString();
                                                    localStorage.setItem('notificationPreferences', serializedForm);
                                                };

                                                // Function to load saved notification preferences from localStorage
                                                const loadNotificationPreferences = () => {
                                                    const serializedForm = localStorage.getItem('notificationPreferences');
                                                    if (serializedForm) {
                                                        const params = new URLSearchParams(serializedForm);
                                                        params.forEach((value, key) => {
                                                            const input = document.querySelector(`[name="${key}"]`);
                                                            if (input) {
                                                                if (input.type === 'checkbox') {
                                                                    input.checked = value === 'on';
                                                                } else {
                                                                    input.value = value;
                                                                }
                                                            }
                                                        });
                                                    }
                                                };

                                                // Function to clear the form and remove saved data from localStorage


                                                // Save preferences on input and change events
                                                notificationForm.addEventListener('input', saveNotificationPreferences);
                                                notificationForm.addEventListener('change', saveNotificationPreferences);

                                                // Clear form data when the "Clear Preferences" button is clicked

                                                // Load saved preferences on page load
                                                loadNotificationPreferences();
                                            });


                                        </script>

                                        </p>
                                    </header>
                                </section>
                            </div>
                        </div>

                        <div class="card mb-4 hidden_phone_verified col-md-6 col-lg-4">
                            <div class="card-body">
                                <section class="mb-4">
                                    <header>
                                        <h2 class="h5 font-weight-bold text-dark">
                                            <i class="fas fa-phone" style="color: red;"></i>      Phone Not Verified
                                        </h2>

                                        <p class="mt-2 text-muted">
                                            <input type="text" placeholder="Phone Code"/>
                                            <button class="button">Verify</button>
                                        </p>
                                    </header>

                                    <form id="verificationEmailForm" class="d-inline">
                                        <button type="submit" class="btn btn-link p-0 m-0 align-baseline">Resend SMS Message</button>.
                                    </form>
                                </section>
                            </div>
                        </div>
                        <div class="card m-14 hidden_email_verified col-md-6 col-lg-4">
                            <div class="card-body">
                                <section class="mb-4">
                                    <header>
                                        <h2 class="h5 font-weight-bold text-dark">
                                            <i class="fas fa-envelope" style="color: red;"></i>   Email Not Verified
                                        </h2>
                                        <p class="mt-2 text-muted">
                                            <input type="text" placeholder="Phone Code"/>
                                            <button class="button">Verify</button>
                                        </p>
                                    </header>

                                    <form id="verificationEmailForm" class="d-inline">
                                        <button type="submit" class="btn btn-link p-0 m-0 align-baseline">Resend Verification Code</button>.
                                    </form>
                                </section>
                            </div>
                        </div>







                </div>
            </div>
            <div class="tab-pane" id="password_tab" role="tabpanel" aria-labelledby="password-tab">
                <div class="row">
                    <div class="col-md-6 col-lg-4 mx-auto">
                        <div class="card mb-4">
                            <div class="card-body">
                                <section class="mb-4">
                                    <header>
                                        <h2 class="h5 font-weight-bold text-dark">
                                            {{ __('Update Password') }}
                                        </h2>

                                        <p class="mt-2 text-muted">
                                            {{ __('Ensure your account is using a long, random password to stay secure.') }}
                                        </p>
                                    </header>

                                    <form id="updatePasswordForm" class="mt-4" autocomplete="off">
                                        @csrf <!-- CSRF Token for security -->

                                        <div class="mb-3">
                                            <label for="update_password_current_password" class="form-label">{{ __('Current Password') }}</label>
                                            <input  id="update_password_current_password" name="current_password" type="password" class="form-control" autocomplete="off">
                                            <div class="invalid-feedback" id="current-password-error"></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="update_password_password" class="form-label">{{ __('New Password') }}</label>
                                            <input id="update_password_password" name="password" type="password" class="form-control" autocomplete="new-password">
                                            <div class="invalid-feedback" id="password-error"></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="update_password_password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
                                            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password">
                                            <div class="invalid-feedback" id="password-confirmation-error"></div>
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                                            <p class="text-success ms-3" id="password-updated-message" style="display: none;">{{ __('Saved.') }}</p>
                                        </div>
                                    </form>
                                </section>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="tokens" role="tabpanel" aria-labelledby="tokens-tab">
                <div class="row">
                    <div class="col-md-6 col-lg-4 mx-auto">
                        <div class="card mt-2" id="tokens-div">
                            <div class="card-header">
                                Tokens
                            </div>
                            <div class="card-body" id="tokens-body">
                                <!-- Tokens will be loaded here -->
                            </div>
                        </div>

                        <div class="mt-2">
                            <button id="loadTokensBtn" class="btn btn-primary btn-sm">Load Tokens</button>
                            <button id="revokeAllTokensBtn" class="btn btn-danger btn-sm">Revoke All Tokens</button>
                        </div>

                    </div>
                    <div class="col-md-6 col-lg-4 mx-auto">
                        <div class="card mb-4">
                            <div class="card-body">
                                <section class="mb-4">
                                    <header>
                                        <h2 class="h5 font-weight-bold text-dark">
                                            API Tokens
                                        </h2>
                                    </header>
                                    <p class="mt-2 text-muted">
                                        Manage your API tokens here.
                                    </p>
                                    <form id="apiTokensForm" class="mt-4">
                                        <div class="mb-3">
                                            <label for="token_name" class="form-label">{{ __('Token Name') }}</label>
                                            <input id="token_name" name="token_name" type="text" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="token_expiry" class="form-label">{{ __('Expiry Date') }}</label>
                                            <input id="token_expiry" name="token_expiry" type="date" class="form-control">
                                        </div>
                                        <button type="submit" class="btn btn-primary">{{ __('Generate Token') }}</button>
                                    </form>
                                </section>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Load tokens on click
        $('#loadTokensBtn').click(function() {
            ajaxRequest(`${apiUrl}/user/tokens`, 'GET')
                .then(data => {
                    loadTokens(data.tokens);
                })
                .catch(err => {
                    console.error('Error loading tokens:', err);
                });
        });

        // Revoke all tokens on click
        $('#revokeAllTokensBtn').click(function() {
            ajaxRequest(`${apiUrl}/user/tokens`, 'DELETE')
                .then(response => {
                    alert('All tokens revoked successfully.');
                    $('#tokens-body').empty();
                })
                .catch(err => {
                    console.error('Error revoking all tokens:', err);
                });
        });

        // Function to revoke a single token
        $(document).on('click', '.revoke-token-btn', function() {
            const tokenId = $(this).data('token-id');
            ajaxRequest(`${apiUrl}/user/tokens/${tokenId}`, 'DELETE')
                .then(response => {
                    alert('Token revoked successfully.');
                    $(`#token-${tokenId}`).remove();
                })
                .catch(err => {
                    console.error('Error revoking token:', err);
                });
        });
    });

    // Function to load tokens into the div
    function loadTokens(tokens) {
        const tokensBody = $('#tokens-body');
        tokensBody.empty(); // Clear existing tokens

        if (tokens.length === 0) {
            tokensBody.append('<p>No tokens available.</p>');
            return;
        }

        tokens.forEach(token => {
            const tokenHtml = `
            <div class="token-item" id="token-${token.id}">
                <p><strong>Token:</strong> ${token.plain_text_token}</p>
                <p><strong>Name:</strong> ${token.name}</p>
                <p><strong>Last Used At:</strong> ${token.last_used_at}</p>
                <p><strong>Created At:</strong> ${token.created_at}</p>
                <p><strong>Abilities:</strong> ${token.abilities.join(', ')}</p>
                <button class="btn btn-danger btn-sm revoke-token-btn" data-token-id="${token.id}">Revoke</button>
            </div>
        `;
            tokensBody.append(tokenHtml);
        });
    }

</script>






