<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Opt-In for RichBot9000</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .opt-in-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .form-group label {
            font-weight: 500;
        }
        .btn-primary {
            width: 100%;
        }
        .text-muted {
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="opt-in-container">
        <h2 class="text-center mb-4">Opt-In for RichBot9000 Notifications</h2>
        <form id="optInForm" method="POST" action="/submit-opt-in">
            <div class="form-group">
                <label for="phoneNumber">Phone Number</label>
                <input type="tel" class="form-control" id="phoneNumber" name="phone_number" placeholder="Enter your phone number" required pattern="\d{10}" title="Please enter a valid 10-digit phone number">
            </div>
            <div class="form-group">
                <label for="emailAddress">Email Address</label>
                <input type="email" class="form-control" id="emailAddress" name="email_address" placeholder="Enter your email address" required>
            </div>

            <!-- Email Notifications Consent -->
            <div class="form-group">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="emailConsentCheck" name="email_consent">
                    <label class="form-check-label" for="emailConsentCheck">
                        I agree to receive email notifications from RichBot9000.
                    </label>
                </div>
            </div>

            <!-- SMS Notifications Consent -->
            <div class="form-group">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="smsConsentCheck" name="sms_consent">
                    <label class="form-check-label" for="smsConsentCheck">
                        I agree to receive text message notifications from RichBot9000. Message and data rates may apply.
                    </label>
                </div>
            </div>

            <!-- SMS Terms and Conditions -->
            <div class="form-group">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="consentCheck" name="consent_sms" required>
                    <label class="form-check-label" for="consentCheck">
                        I agree to the SMS Terms and Conditions for RichBot9000.
                        <a style="color: #007bff; text-decoration: underline; cursor: pointer;" data-toggle="collapse" data-target="#termsDetailsSMS" aria-expanded="false" aria-controls="termsDetailsSMS">
                            Show SMS Terms & Conditions
                        </a>
                    </label>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="form-group text-center">
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>

        <!-- Error Display -->
        <div id="formErrors" class="alert alert-danger mt-3" style="display: none;">
            <!-- Errors will be displayed here -->
        </div>

        <!-- Opt-Out Instructions -->
        <p class="text-center text-muted mt-4">
            You can opt out of receiving text notifications at any time by texting STOP to the number that sent you the message. For email notifications, you can unsubscribe by clicking the unsubscribe link at the bottom of any RichBot9000 email.
            <br><br>
            Text HELP for help or STOP to opt out. Standard message and data rates may apply.
        </p>

        <!-- SMS Terms and Conditions -->
        <div class="collapse mt-3" id="termsDetailsSMS">
            <div class="card card-body">
                <h4>RichBot9000 - SMS Terms & Conditions</h4>
                <ol>
                    <li>Program Description: This service will notify users about updates and reminders via SMS. Expect to receive timely updates regarding your interactions with RichBot9000.</li>
                    <li>Canceling SMS Service: Stop receiving SMS notifications anytime by texting "STOP" to our number. We will confirm your unsubscription via SMS. To rejoin, sign up again through the opt-in process.</li>
                    <li>Need Help? Reply with the keyword "HELP" for support, or contact us at support@richbot9000.com.</li>
                    <li>Carrier Liability: Carriers are not liable for any delayed or undelivered SMS messages.</li>
                    <li>Message and Data Rates: Standard message and data rates may apply. Contact your wireless provider if you have questions about your plan.</li>
                </ol>
            </div>
        </div>

        <!-- Privacy Policy -->
        <div class="text-center mt-4">
            <button class="btn btn-secondary" type="button" data-toggle="collapse" data-target="#privacyPolicy" aria-expanded="false" aria-controls="privacyPolicy">
                Show Privacy Policy
            </button>
        </div>

        <div class="collapse mt-4" id="privacyPolicy">
            <div class="card card-body">
                <h4>Privacy Policy for RichBot9000</h4>
                <p><strong>Effective Date:</strong> 8/25/2024</p>
                <h5>1. Introduction</h5>
                <p>
                    This Privacy Policy explains how we collect, use, disclose, and protect your personal information when you use our services, specifically in relation to receiving notifications from RichBot9000.
                </p>

                <h5>2. Information We Collect</h5>
                <h6>2.1. Personal Information</h6>
                <p>
                    We collect the following types of personal information from users who opt-in to receive notifications:
                <ul>
                    <li><strong>Phone Number:</strong> Used to send SMS notifications about updates and reminders.</li>
                    <li><strong>Email Address:</strong> Used to send email notifications.</li>
                    <li><strong>Communication Preferences:</strong> Your preferences regarding receiving SMS notifications or email messages.</li>
                </ul>
                </p>
                <h6>2.2. Usage Information</h6>
                <p>
                    We may collect information about how you interact with our notification services, including:
                <ul>
                    <li><strong>Log Data:</strong> Information such as the date, time, and content of messages sent to you or received from you.</li>
                    <li><strong>Opt-In/Opt-Out Data:</strong> Information on your choices regarding the receipt of notifications.</li>
                </ul>
                </p>

                <h5>3. How We Use Your Information</h5>
                <p>
                    We use the information collected to:
                <ul>
                    <li><strong>Provide Notifications:</strong> Send timely updates regarding your interactions with RichBot9000.</li>
                    <li><strong>Manage Preferences:</strong> Allow you to manage your preferences for receiving notifications.</li>
                    <li><strong>Improve Services:</strong> Analyze usage patterns to improve our notification services.</li>
                </ul>
                </p>

                <h5>4. Sharing Your Information</h5>
                <p>
                    We do not share your personal information with third parties except in the following cases:
                <ul>
                    <li><strong>Service Providers:</strong> We may share your information with third-party service providers (e.g., SMS and email providers) who assist us in delivering notifications.</li>
                    <li><strong>Legal Requirements:</strong> We may disclose your information if required by law or in response to legal processes.</li>
                </ul>
                </p>

                <h5>5. Security of Your Information</h5>
                <p>
                    We take the security of your personal information seriously and implement reasonable measures to protect it from unauthorized access, alteration, or disclosure. However, no method of transmission over the internet or electronic storage is completely secure, and we cannot guarantee absolute security.
                </p>

                <h5>6. Your Choices</h5>
                <p>
                    You have the right to:
                <ul>
                    <li><strong>Opt-Out:</strong> You can opt-out of receiving notifications at any time by following the opt-out instructions provided in the SMS messages or emails.</li>
                    <li><strong>Access and Correction:</strong> Request access to your personal information and ask us to correct any inaccuracies.</li>
                </ul>
                </p>

                <h5>7. Retention of Information</h5>
                <p>
                    We retain your personal information for as long as necessary to provide our services or as required by law. If you choose to opt-out, we will retain only the information necessary to ensure you do not receive further notifications unless otherwise required by law.
                </p>

                <h5>8. Changes to This Privacy Policy</h5>
                <p>
                    We may update this Privacy Policy from time to time to reflect changes in our practices or for other operational, legal, or regulatory reasons. We will notify you of any significant changes by posting the new policy on our website and updating the effective date.
                </p>

                <h5>9. Contact Us</h5>
                <p>
                    If you have any questions or concerns about this Privacy Policy or how your information is handled, please contact us at:
                    <br><strong>RichBot9000 Support Team</strong>
                    <br>Email: support@richbot9000.com
                </p>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    document.getElementById('optInForm').addEventListener('submit', function(event) {
        let errors = [];
        const phoneNumber = document.getElementById('phoneNumber').value;
        const email = document.getElementById('emailAddress').value;
        const smsConsent = document.getElementById('smsConsentCheck').checked;
        const emailConsent = document.getElementById('emailConsentCheck').checked;

        if (!/^\d{10}$/.test(phoneNumber)) {
            errors.push("Please enter a valid 10-digit phone number.");
        }

        if (!emailConsent && !smsConsent) {
            errors.push("You must opt-in to receive notifications via either email, SMS, or both.");
        }

        if (!document.getElementById('consentCheck').checked) {
            errors.push("You must agree to the SMS terms to receive notifications.");
        }

        if (errors.length > 0) {
            event.preventDefault();
            const errorsContainer = document.getElementById('formErrors');
            errorsContainer.innerHTML = errors.join("<br>");
            errorsContainer.style.display = 'block';
        }
    });
</script>
</body>
</html>
