<!-- resources/views/webapp/_rainbow_dash_login.blade.php -->
<div class="card shadow-sm">
    <div class="card-header bg-success text-white">
        <h5><i class="fas fa-tachometer-alt"></i> Rainbow Dashboard Login</h5>
    </div>
    <div class="card-body">
        <form id="rainbowLoginForm">
            <div class="mb-3">
                <label for="rainbowEmail" class="form-label">Email address</label>
                <input
                    type="email"
                    class="form-control"
                    id="rainbowEmail"
                    placeholder="Enter email"
                    required
                >
            </div>
            <div class="mb-3">
                <label for="rainbowPassword" class="form-label">Password</label>
                <input
                    type="password"
                    class="form-control"
                    id="rainbowPassword"
                    placeholder="Password"
                    required
                >
            </div>
            <button type="submit" class="btn btn-success w-100">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
    </div>
</div>
