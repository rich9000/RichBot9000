<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - RichBot9000</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .verification-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }
        .verification-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
        .loading-spinner {
            display: none;
        }
        .success-message {
            display: none;
        }
        .error-message {
            display: none;
        }
    </style>
</head>
<body>
    <div class="verification-card">
        <div class="verification-icon">
            <i class="fas fa-envelope-open-text"></i>
        </div>
        
        <h2 class="mb-3">Email Verification</h2>
        
        <div id="loading" class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Verifying...</span>
            </div>
            <p class="mt-2">Verifying your email address...</p>
        </div>
        
        <div id="success" class="success-message">
            <div class="verification-icon">
                <i class="fas fa-check-circle text-success"></i>
            </div>
            <h3 class="text-success">Email Verified!</h3>
            <p class="text-muted">Your email has been successfully verified.</p>
            <button id="redirectBtn" class="btn btn-primary">Continue to RichBot9000</button>
        </div>
        
        <div id="error" class="error-message">
            <div class="verification-icon">
                <i class="fas fa-exclamation-circle text-danger"></i>
            </div>
            <h3 class="text-danger">Verification Failed</h3>
            <p id="errorMessage" class="text-muted">There was an error verifying your email.</p>
            <button id="retryBtn" class="btn btn-outline-primary">Try Again</button>
            <a href="/webapp" class="btn btn-link">Go to RichBot9000</a>
        </div>
    </div>

    <script>
        const token = '{{ $token }}';

        appState = localStorage.getItem('app_state') ? JSON.parse(localStorage.getItem('app_state')) : null;
        if (appState) {
            console.log('appState', appState);
            
        }

        const apiToken = localStorage.getItem('app_state') ? JSON.parse(localStorage.getItem('app_state')).apiToken : null;
        
        function showLoading() {
            document.getElementById('loading').style.display = 'block';
            document.getElementById('success').style.display = 'none';
            document.getElementById('error').style.display = 'none';
        }
        
        function showSuccess() {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('success').style.display = 'block';
            document.getElementById('error').style.display = 'none';
        }
        
        function showError(message) {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('success').style.display = 'none';
            document.getElementById('error').style.display = 'block';
            document.getElementById('errorMessage').textContent = message;
        }
        
        async function verifyEmail() {
            showLoading();
            
            try {
                // Try to get token from localStorage first, but don't require it
                let apiToken = null;
                try {
                    const appState = localStorage.getItem('app_state');
                    if (appState) {
                        const parsed = JSON.parse(appState);
                        apiToken = parsed.apiToken;
                    }
                } catch (e) {
                    // Ignore localStorage errors
                }
                
                const headers = {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                };
                
                // Only add Authorization header if we have a token
                if (apiToken) {
                    headers['Authorization'] = `Bearer ${apiToken}`;
                }
                
                const response = await fetch('/api/verify-email-public', {
                    method: 'POST',
                    headers: headers,
                    body: JSON.stringify({ token: token })
                });
                
                const data = await response.json();
                
                if (response.ok && data.success) {
                    showSuccess();
                    
                    // Update app state if available
                    try {
                        if (localStorage.getItem('app_state')) {
                            const appState = JSON.parse(localStorage.getItem('app_state'));
                            if (appState.user) {
                                appState.user.email_verified_at = new Date().toISOString();
                                localStorage.setItem('app_state', JSON.stringify(appState));
                            }
                        }
                    } catch (e) {
                        // Ignore localStorage errors
                    }
                } else {
                    showError(data.error || 'Invalid or expired verification token.');
                }
            } catch (error) {
                console.error('Verification error:', error);
                showError('Network error. Please try again.');
            }
        }
        
        // Auto-verify on page load
        document.addEventListener('DOMContentLoaded', function() {
            verifyEmail();
        });
        
        // Button event listeners
        document.getElementById('redirectBtn').addEventListener('click', function() {
            window.location.href = '/webapp';
        });
        
        document.getElementById('retryBtn').addEventListener('click', function() {
            verifyEmail();
        });
    </script>
</body>
</html> 