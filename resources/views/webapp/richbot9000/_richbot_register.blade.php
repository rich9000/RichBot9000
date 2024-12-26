<!-- Register Section -->
<div class="col-md-12 hidden_richbot_logged_in" id="registerSection">
    <div class="card mb-5 mx-auto" id="register-info" style="max-width: 400px;">
        <div class="card-header">
            <h4>Register for RichBot9000</h4>
        </div>
        <div class="card-body">
            <form id="registerForm" autocomplete="off">
                @csrf

                <!-- Name -->
                <div class="form-group mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input id="register-name" name="name" type="text" class="form-control" required autofocus>
                </div>

                <!-- Email Address -->
                <div class="form-group mb-3">
                    <label for="register-email" class="form-label">Email</label>
                    <input id="register-email" name="email" type="email" class="form-control" required>
                </div>

                <!-- Phone Number -->
                <div class="form-group mb-3">
                    <label for="register-phone" class="form-label">Phone Number</label>
                    <input id="register-phone" name="phone" type="tel" class="form-control" pattern="[0-9]{10}" placeholder="1234567890" required>
                    <small class="form-text text-muted">Enter your 10-digit phone number without spaces or dashes.</small>
                </div>

                <!-- Password -->
                <div class="form-group mb-3">
                    <label for="register-password" class="form-label">Password</label>
                    <input id="register-password" name="password" type="password" class="form-control" required>
                </div>

                <!-- Confirm Password -->
                <div class="form-group mb-3">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required>
                </div>

                <!-- Register Button -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-block">
                        Register
                    </button>
                </div>

                <!-- Clear Form Button -->
                <div class="d-grid mt-3">
                    <button type="button" id="clearForm" class="btn btn-secondary btn-block">
                        Clear Form
                    </button>
                </div>


            </form>
            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    const form = document.getElementById('registerForm');

                    // Function to save form data to localStorage
                    const saveFormData = () => {

                        const formData = new FormData(form);
                        console.log('FormData Object:', formData);

                        const serializedForm = new URLSearchParams(formData).toString();
                        console.log('Serialized Form Data:', serializedForm);

                        localStorage.setItem('registerFormData', serializedForm);

                    };

                    // Function to load saved form data from localStorage
                    const loadFormData = () => {
                        const serializedForm = localStorage.getItem('registerFormData');
                        if (serializedForm) {
                            const params = new URLSearchParams(serializedForm);
                            params.forEach((value, key) => {
                                const input = document.querySelector(`[name="${key}"]`);
                                if (input) {
                                    if (input.type === 'checkbox') {
                                        input.checked = value === 'on';
                                    } else if (input.type === 'radio') {
                                        if (input.value === value) {
                                            input.checked = true;
                                        }
                                    } else {
                                        input.value = value;
                                    }
                                }
                            });
                        }
                    };

                    // Function to clear the form and remove saved data from localStorage
                    const clearFormData = () => {
                        form.reset();
                        localStorage.removeItem('registerFormData');
                    };

                    // Save form data on input and change events
                    form.addEventListener('input', saveFormData);
                    form.addEventListener('change', saveFormData);

                    // Load form data when the "Load Saved Data" button is clicked
                    // document.getElementById('loadForm').addEventListener('click', loadFormData);

                    // Clear form data when the "Clear Form" button is clicked
                    document.getElementById('clearForm').addEventListener('click', clearFormData);

                    // Optionally, you can load the form data automatically on page load
                    loadFormData();

                });

                document.getElementById('registerForm').addEventListener('submit', function(e) {
                    e.preventDefault();


                //    const data = {};
                //    const name = $('#register-name').val();
                //    const email = $('#register-email').val();
                //    const password = $('#register-password').val();
                //    const phone_number = $('#register-phone').val();
                //    const password_confirmation = $('#password_confirmation').val();
                    const data = {};
                    const name = document.getElementById('register-name').value;
                    const email = document.getElementById('register-email').value;
                    const password = document.getElementById('register-password').value;
                    const phone_number = document.getElementById('register-phone').value;
                    const password_confirmation = document.getElementById('password_confirmation').value;

                    fetch('/api/register', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                             'Accept': 'application/json'

                        },
                        body: JSON.stringify({ name, email, password, phone_number, password_confirmation })
                    })
                        .then(response => {
                            if (!response.ok) {
                                return response.json().then(err => { throw err; });
                            }
                            return response.json();
                        })
                        .then(async data => {
                            const email = document.getElementById('register-email').value;
                            const password = document.getElementById('register-password').value;

                            try {
                                const response = await axios.post('/api/login', { email, password });
                                appState.user = response.data.user;
                                if(!appState.user.roles) appState.user.roles = [];
                                appState.tokens.richbot = response.data.token;
                                appState.apiToken = response.data.token;

                                // Save state before reload
                                localStorage.setItem('app_state', JSON.stringify(appState));
                                
                                // Update UI before reload
                                updateUserUI();
                                showAlert('Registration successful! Redirecting to dashboard...');
                                
                                // Small delay to ensure UI updates and alert shows
                                setTimeout(() => {
                                    showSection('richbotSection');
                                    location.reload();
                                }, 500);

                            } catch (error) {
                                console.error('Login error after registration:', error);
                                showAlert('Registration successful but login failed. Please try logging in manually.', 'warning');
                            }
                        })
                        .catch(error => {
                            if (error.errors) {
                                // Display validation errors in the form
                                for (let field in error.errors) {
                                    const errorMessage = error.errors[field].join(', ');
                                    const errorElement = document.getElementById(`${field}-error`);

                                    //Todo: Make this work
                                    if (errorElement) {
                                        errorElement.textContent = errorMessage;
                                        errorElement.style.display = 'block';
                                    } else {
                                        alert(`(${field}) ${errorMessage}`);
                                    }
                                }
                            } else {
                                alert('Error Registering: ' + error.message);
                            }

                        });
                });

            </script>
        </div>
    </div>
</div>
