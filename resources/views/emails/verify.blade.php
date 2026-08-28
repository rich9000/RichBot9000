<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Email Verification - RichBot9000</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .button {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background: #0056b3;
        }
        .token-box {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin: 20px 0;
            font-family: monospace;
            font-size: 18px;
            font-weight: bold;
            color: #495057;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>RichBot9000 Email Verification</h1>
    </div>
    
    <div class="content">
        <h2>Hello!</h2>
        
        <p>Thank you for registering with RichBot9000. To complete your registration, please verify your email address.</p>
        
        <p>You can verify your email in one of two ways:</p>
        
        <h3>Option 1: Click the verification link</h3>
        <a href="{{ url('/verify-email/' . $token) }}" class="button">Verify Email Address</a>
        
        <h3>Option 2: Use the verification code</h3>
        <p>If the link doesn't work, you can manually enter this code in your RichBot9000 dashboard:</p>
        <div class="token-box">{{ $token }}</div>
        
        <p><strong>Important:</strong> This verification code will expire in 1 hour for security reasons.</p>
        
        <p>If you didn't create an account with RichBot9000, you can safely ignore this email.</p>
    </div>
    
    <div class="footer">
        <p>This email was sent from RichBot9000. Please do not reply to this email.</p>
        <p>If you have any questions, please contact our support team.</p>
    </div>
</body>
</html>



