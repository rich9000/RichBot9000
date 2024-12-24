@extends('layouts.guest')

@section('content')

    <style>
        .hidden { display: none; }

        .navbar .nav-link.active {
            font-weight: bold; /* Bold text */
        }

        .navbar .nav-link:hover {
            font-weight: bold; /* Bold text */
        }
    </style>

    <header class="">
        <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">
                    Richbot 9000
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <!-- Navigation menu items to toggle sections -->
                        <li class="nav-item">
                            <a class="nav-link nav-section-shower active" href="#" data-section="publicContent">Public Content</a>
                        </li>
                        <li class="nav-item hidden_logged_in">
                            <a class="nav-link nav-section-shower" href="#" data-section="registerSection">Register</a>
                        </li>
                        <li class="nav-item hidden_logged_in">
                            <a class="nav-link nav-section-shower" href="#" data-section="loginSection">Login</a>
                        </li>
                        <li class="nav-item hidden_not_logged_in">
                            <a class="nav-link nav-section-shower" href="#" data-section="userProfile">User Profile</a>
                        </li>
                        <li class="nav-item hidden hidden_not_admin">
                            <a class="nav-link nav-section-shower" href="#" data-section="adminSection">Admin Section</a>
                        </li>
                        <li class="nav-item dropdown   hidden_not_logged_in">
                            <a class="nav-item nav-link dropdown-toggle" href="#" id="aiDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                AI
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="aiDropdown">
                                <li><a class="nav-content-loader dropdown-item" href="#" data-section="audio.content._dashboard" data-target="audio_content_section">Audio</a></li>
                                <li><a class="nav-content-loader dropdown-item" href="#" data-section="chat.content._index" data-target="chat_content_section">Chat</a></li>
                                <li><a class="nav-content-loader dropdown-item" href="#" data-section="assistant_functions.content._index" data-target="functions_content_section">Functions</a></li>
                                <li><a class="nav-content-loader dropdown-item" href="#" data-section="assistants.content._index" data-target="assistants_content_section">Assistants</a></li>

                            </ul>
                        </li>
                        <li class="nav-item  hidden_not_logged_in">
                            <a class="nav-link nav-content-loader" href="#" data-section="content.test" data-target="dynamic_content_section">Load Dynamic</a>
                        </li>
                        <li class="nav-item  hidden_not_logged_in">
                            <a class="nav-link nav-content-loader" href="#" data-section="pwa._rainbow_dash" data-target="rainbow_dash_section">Load Dynamic</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link nav-section-shower" href="#" data-section="debugSection">Debug Section</a>
                        </li>
                    </ul>
                    <div class="d-flex align-items-center">
                        <!-- User Dropdown Section -->

                        <div class=" hidden_logged_in">
                            <a class="nav-link nav-section-shower" href="#" data-section="loginSection">Login</a>
                            <a class="nav-link nav-section-shower" href="#" data-section="registerSection">Register</a>
                        </div>

                        <div class="dropdown hidden_not_logged_in">
                            <a class="dropdown-toggle nav-link" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="user-name-span"></span>
                                <span class="hidden_email_verified">
                                <i class="fas fa-envelope" style="color: red;"></i>
                            </span>
                                <span class="hidden_phone_verified">
                                    <i class="fas fa-phone" style="color: red;"></i>

                                </span>

                                <br/>
                                <small class="text-muted"><span class="user-email-span"></span></small>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item nav-section-shower" href="#" data-section="userProfile">User Profile</a></li>
                                <li><a class="dropdown-item" href="#" id="logoutButton">Logout</a></li>
                                <li class="hidden_email_verified"><a class="dropdown-item" href="#" id="resendVerificationLink">Resend Verification</a></li>
                            </ul>
                            <li class="nav-item">
                                <a class="nav-link" href="#" id="notificationLink" data-bs-toggle="modal" data-bs-target="#notificationModal">
                                    Notifications <span id="notificationCount" class="badge bg-danger">0</span>
                                </a>
                            </li>
                            <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="notificationModalLabel">Notifications</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div id="notificationList">
                                                <p>No notifications at this time.</p>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <div id="main-container" class="container main-container mt-3">

            <!-- Public Content Section -->
            <div class="col-md-3 content-section" id="publicContent">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Public Content</h4>
                    </div>
                    <div class="card-body">
                        Video could go here.
                    </div>
                </div>
            </div>

        @include('pwa._rainbow_dash')

        <!-- Register Section -->
        <div class="col-md-12 content-section hidden_logged_in" id="registerSection">
            <div class="card mb-4 col-6" id="register-info">
                <div class="card-header">
                    <h4>Register</h4>
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

                        $('#registerForm').on('submit', function(e) {
                            e.preventDefault();


                            const data = {};
                            const name = $('#register-name').val();
                            const email = $('#register-email').val();
                            const password = $('#register-password').val();
                            const phone_number = $('#register-phone').val();
                            const password_confirmation = $('#password_confirmation').val();

                            ajaxRequest(apiUrl + '/register', 'POST', { name, email, password, phone_number,password_confirmation }).then(data => {

                            }) .then(data => {
                                handleLogin(email, password)
                                    .then(response => {
                                        checkUser(response.token);

                                    });

                            }).catch(error => {
                                alert('Error Registering: ' + error.message);
                            });

                        });

                    </script>
                </div>
            </div>
        </div>

            <!-- Login Section -->
            <div class="col-md-3 content-section hidden_logged_in" id="loginSection">
                <div class="card mb-4" id="login-info">
                    <div class="card-header">
                        <h4>Login</h4>
                    </div>
                    <div class="card-body">
                        <form id="loginForm">
                            <div class="mb-3">
                                <input type="email" id="email" class="form-control" placeholder="Email" required>
                            </div>
                            <div class="mb-3">
                                <input type="password" id="password" class="form-control" placeholder="Password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- User Profile Section -->
            <div class="col-md-12 content-section hidden_not_logged_in" id="userProfile">
                @include('pwa._user_profile')




            </div>




        @include('pwa._admin_section')

            <!-- Debug Section -->
            <div class="col-12 content-section mt-3" id="debugSection">





                <div class="card mb-4" id="debug-section">
                    <div class="card-header">
                        <div>Local Storage Debug</div>
                    </div>
                    <div class="card-body">
                        <pre  id="localStorageDebug"></pre>
                        <button class="btn btn-secondary" id="clearLocalStorageButton">Clear LocalStorage</button>
                    </div>
                </div>
            </div>

    </div>




    <div class="card mb-4" id="appstate-debug-section">
        <div class="card-header">

            <button class="btn btn-link button" data-bs-toggle="collapse" data-bs-target="#debugCardBody" aria-expanded="false" aria-controls="debugCardBody">
                App State Debug
            </button>
        </div>
        <div id="debugCardBody" class="collapse">
            <div class="card-body">
                <div>AppState Debug</div>
                <pre id="appStateDebug"></pre>
                <button class="btn btn-primary" id="updateLocalDebugBtn">Update Debug</button>
            </div>
        </div>
    </div>



    <script src="/webapp_public/main.js?nocache={{time()}}"></script>

    <script>

        const saved_state = localStorage.getItem('app_state');

        let appState = {};

        console.log(saved_state);

        if(!saved_state){

            appState = {
                apiToken: null,
                user: null,
                dashUser: null,
                dashApiToken: null,
                users: [],
                current_thread: null, // Holds the current active thread
                threads: [], // List of all threads
                debug: false,
                current_content_section: 'publicContent',
            };

            console.log(appState);

        } else {

           appState = JSON.parse(saved_state);


        }

        console.log(appState);

        const token = appState.apiToken;

        if (token) {

            checkUser(token);

        } else {

            $('.hidden_logged_in').removeClass('hidden');
            $('.hidden_not_logged_in').addClass('hidden');

        }

        $(document).ready(function() {

            $('.content-section').hide();
            $('#publicContent').show();

            updateDisplay();

            updateLocalStorageDebug();
            logState();

        });
    </script>




@endsection
