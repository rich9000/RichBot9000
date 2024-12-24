<div class="card mb-4" id="user-info">
    <div class="card-header">
        <h4>User Profile</h4>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs" id="profileTabs" role="tablist">
            <li class="nav-item active" role="presentation">
                <a class="nav-link active" id="profile-info-tab" data-bs-toggle="tab" href="#profile_info" role="tab" aria-controls="profile-info" aria-selected="true"><span class="text-dark" style="color: black;">Info</span></a>            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link " id="notifications-tab" data-bs-toggle="tab" href="#notifications" role="tab" aria-controls="notifications" aria-selected="false"><span class="text-dark" style="color: black;">Notifications</span></a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="tokens-tab" data-bs-toggle="tab" href="#tokens" role="tab" aria-controls="tokens" aria-selected="false"><span class="text-dark" style="color: black;">Tokens</span></a>
            </li>

            
            <li class="nav-item" role="presentation">
                <a class="nav-link" style="color: black;" id="password-tab" data-bs-toggle="tab" href="#password_tab" role="tab" aria-controls="password" aria-selected="false">
                <span class="text-dark" style="color: black;">Password</span>
                </a>            </li>
        </ul>
        <div class="tab-content" id="profileTabContent">


                 <div class="tab-pane fade active" id="profile_info" role="tabpanel" aria-labelledby="profile-info-tab">
                <div class="row">
                Info goes here.
                </div>
            </div>

            <div class="tab-pane fade" id="notifications" role="tabpanel" aria-labelledby="notifications-tab">
                <div class="row pt-2">


            @include('webapp.richbot9000._richbot_verify_phone')
            @include('webapp.richbot9000._richbot_verify_email')

                </div>
            </div>
            <div class="tab-pane fade" id="password_tab" role="tabpanel" aria-labelledby="password-tab">
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
  




















    // Load tokens on click
    document.getElementById('loadTokensBtn').addEventListener('click', function() {
        ajaxRequest('https://richbot9000.com/api/user/tokens', 'GET')
            .then(data => {
                loadTokens(data.tokens);
            })
            .catch(err => {
                console.error('Error loading tokens:', err);
            });
    });

    // Revoke all tokens on click
    document.getElementById('revokeAllTokensBtn').addEventListener('click', function() {
        ajaxRequest('https://richbot9000.com/api/user/tokens', 'DELETE')
            .then(response => {
                alert('All tokens revoked successfully.');
                document.getElementById('tokens-body').innerHTML = '';
            })
            .catch(err => {
                console.error('Error revoking all tokens:', err);
            });
    });

    // Function to revoke a single token
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('revoke-token-btn')) {
            const tokenId = event.target.getAttribute('data-token-id');
            ajaxRequest(`https://richbot9000.com/api/user/tokens/${tokenId}`, 'DELETE')
                .then(response => {
                    alert('Token revoked successfully.');
                    const tokenElement = document.getElementById(`token-${tokenId}`);
                    if (tokenElement) {
                        tokenElement.remove();
                    }
                })
                .catch(err => {
                    console.error('Error revoking token:', err);
                });
        }
    });


    // Function to load tokens into the div
    function loadTokens(tokens) {
        const tokensBody = document.getElementById('tokens-body');
        tokensBody.innerHTML = ''; // Clear existing tokens

        if (tokens.length === 0) {
            const noTokensMessage = document.createElement('p');
            noTokensMessage.textContent = 'No tokens available.';
            tokensBody.appendChild(noTokensMessage);
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
            tokensBody.insertAdjacentHTML('beforeend', tokenHtml);
        });
    }


</script>






