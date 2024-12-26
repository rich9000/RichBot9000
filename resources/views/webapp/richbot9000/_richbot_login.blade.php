<!-- resources/views/webapp/_richbot_login.blade.php -->
<div class="card shadow-sm hidden_richbot_logged_in mx-auto" style="max-width: 350px;">
    <div class="card-header bg-primary bg-gradient text-white py-2 d-flex align-items-center">
        <i class="fas fa-robot me-2"></i>
        <h6 class="mb-0">Sign In</h6>
    </div>
    <div class="card-body p-3"> 
        <form id="richbotLoginForm">
            <div class="mb-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input
                        type="email"
                        class="form-control form-control-sm"
                        id="richbotEmail"
                        placeholder="Enter email"
                        required
                    >
                </div>
            </div>
            <div class="mb-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input
                        type="password"
                        class="form-control form-control-sm"
                        id="richbotPassword"
                        placeholder="Password"
                        required
                    >
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-sm position-relative">
                    <span class="d-flex align-items-center justify-content-center">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Sign In
                        <div class="spinner-border spinner-border-sm ms-2 d-none" role="status" id="loginSpinner">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('richbotPassword');
        
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            togglePassword.querySelector('i').classList.toggle('fa-eye');
            togglePassword.querySelector('i').classList.toggle('fa-eye-slash');
        });

        // Richbot Login Form Submission
        const loginForm = document.getElementById('richbotLoginForm');
        const loginSpinner = document.getElementById('loginSpinner');

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitButton = loginForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            loginSpinner.classList.remove('d-none');

            const email = document.getElementById('richbotEmail').value;
            const password = document.getElementById('richbotPassword').value;

            try {
                const response = await axios.post('/api/login', {email, password});
                appState.user = response.data.user;
                if(!appState.user.roles) appState.user.roles = [];

                appState.tokens.richbot = response.data.token;
                appState.apiToken = response.data.token;

                localStorage.setItem('app_state', JSON.stringify(appState));
                
                // Update UI before reload
                updateUserUI();
                showAlert('Login successful! Redirecting to dashboard...');
                
                // Small delay to ensure UI updates and alert shows
                setTimeout(() => {
                    showSection('richbotSection');
                    location.reload();
                }, 500);

            } catch (error) {
                console.error('Login error:', error);
                submitButton.disabled = false;
                loginSpinner.classList.add('d-none');

                if (error.response?.status === 401) {
                    showAlert('Invalid email or password', 'danger');
                } else {
                    showAlert('An error occurred. Please try again.', 'danger');
                }
            }
        });
    });
</script>
