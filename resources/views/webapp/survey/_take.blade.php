<!-- resources/views/webapp/survey/take.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .survey-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px 0;
        }
        .question-card {
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .question-card .card-header {
            border-radius: 10px 10px 0 0;
            font-weight: 600;
        }
        .required-mark {
            color: #dc3545;
            margin-left: 5px;
        }
        .progress {
            height: 8px;
            margin-bottom: 20px;
        }
        .rating-container {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 10px;
        }
        .rating-scale {
            display: flex;
            gap: 10px;
        }
        .rating-option {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .rating-option input {
            margin-bottom: 5px;
        }
        .rating-label {
            font-size: 12px;
            color: #6c757d;
        }
        .survey-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .survey-footer {
            text-align: center;
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        .thank-you-container {
            text-align: center;
            padding: 50px 20px;
        }
        .thank-you-icon {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="survey-container" id="survey-container">
        <!-- Loading Spinner -->
        <div class="text-center py-5" id="loading-container">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading survey...</p>
        </div>
        
        <!-- Call Status Section -->
        <div class="container mx-auto px-4 py-8">
            <div class="mb-6 bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold mb-4">Call Status</h2>
                <div class="flex items-center space-x-4">
                    <div id="callStatus" class="text-lg font-semibold">Not Connected</div>
                    <div id="callDuration" class="text-gray-600">00:00:00</div>
                </div>
                <div class="mt-4">
                    <button id="endCall" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600" disabled>
                        End Call
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Survey Form (Hidden until loaded) -->
        <div id="survey-form-container" style="display: none;">
            <div class="survey-header">
                <h1 id="survey-title">Survey Title</h1>
                <p id="survey-description" class="text-muted">Survey description will appear here.</p>
            </div>
            
            <div class="progress mb-4">
                <div class="progress-bar" id="survey-progress" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            
            <form id="survey-form">
                <input type="hidden" id="survey-contact-id">
                <input type="hidden" id="started-at" value="">
                
                <div id="questions-container">
                    <!-- Questions will be populated here via JavaScript -->
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-secondary" id="prev-btn" disabled>
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <button type="button" class="btn btn-primary" id="next-btn">
                        Next <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="submit" class="btn btn-success" id="submit-btn" style="display: none;">
                        Submit <i class="fas fa-check"></i>
                    </button>
                </div>
            </form>
            
            <div class="survey-footer">
                <p>Thank you for your participation.</p>
            </div>
        </div>
        
        <!-- Thank You Page (Hidden until submission) -->
        <div class="thank-you-container" id="thank-you-container" style="display: none;">
            <div class="thank-you-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Thank You!</h2>
            <p class="lead">Your response has been submitted successfully.</p>
            <p class="text-muted">We appreciate your time and feedback.</p>
        </div>
        
        <!-- Error Page (Hidden unless there's an error) -->
        <div class="alert alert-danger" id="error-container" style="display: none;">
            <h4><i class="fas fa-exclamation-triangle"></i> Error</h4>
            <p id="error-message">There was a problem loading the survey. Please try again later.</p>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Get survey ID and contact ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const surveyContactId = urlParams.get('id');
        let currentQuestionIndex = 0;
        let questions = [];
        let startTime = new Date();
        let callStartTime = null;
        let callDurationInterval = null;
        let currentCallId = null;
        
        $(document).ready(function() {
            if (!surveyContactId) {
                showError("Invalid survey link. Please check the URL and try again.");
                return;
            }
            
            document.getElementById('started-at').value = startTime.toISOString();
            document.getElementById('survey-contact-id').value = surveyContactId;
            
            // Load survey data
            loadSurvey();
            
            // Form navigation
            $('#next-btn').on('click', function() {
                if (validateCurrentQuestion()) {
                    showNextQuestion();
                }
            });
            
            $('#prev-btn').on('click', function() {
                showPreviousQuestion();
            });
            
            // Form submission
            $('#survey-form').on('submit', function(e) {
                e.preventDefault();
                if (validateCurrentQuestion()) {
                    submitSurvey();
                }
            });
        });
        
        function loadSurvey() {
            fetch(`/api/survey-contacts/${surveyContactId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Survey not found or no longer available');
                    }
                    return response.json();
                })
                .then(data => {
                    setupSurvey(data);
                    if (data.call_sid) {
                        currentCallId = data.call_sid;
                        startCallStatusPolling();
                    }
                })
                .catch(error => {
                    showError(error.message);
                });
        }
        
        function setupSurvey(data) {
            const survey = data.campaign.survey;
            questions = survey.questions;
            
            // Set survey title and description
            $('#survey-title').text(survey.title);
            $('#survey-description').text(survey.description || '');
            
            // Generate question HTML
            if (questions.length === 0) {
                showError("This survey doesn't contain any questions.");
                return;
            }
            
            // Build each question in the container but only show the first one
            const container = $('#questions-container');
            container.empty();
            
            questions.forEach((question, index) => {
                const questionHtml = buildQuestionHtml(question, index);
                container.append(questionHtml);
                
                // Hide all questions except the first one
                if (index > 0) {
                    $(`#question-${index}`).hide();
                }
            });
            
            // Show survey form, hide loading
            $('#loading-container').hide();
            $('#survey-form-container').show();
            
            updateProgress();
        }
        
        function buildQuestionHtml(question, index) {
            const isRequired = question.required ? '<span class="required-mark">*</span>' : '';
            
            let inputHtml = '';
            switch (question.question_type) {
                case 'text':
                    inputHtml = `<input type="text" class="form-control" name="question_${question.id}" id="q_${question.id}" ${question.required ? 'required' : ''}>`;
                    break;
                    
                case 'paragraph':
                    inputHtml = `<textarea class="form-control" name="question_${question.id}" id="q_${question.id}" rows="3" ${question.required ? 'required' : ''}></textarea>`;
                    break;
                    
                case 'single_choice':
                    inputHtml = '<div class="mt-2">';
                    question.options.forEach((option, i) => {
                        inputHtml += `
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="question_${question.id}" id="q_${question.id}_${i}" value="${option}" ${question.required ? 'required' : ''}>
                                <label class="form-check-label" for="q_${question.id}_${i}">${option}</label>
                            </div>
                        `;
                    });
                    inputHtml += '</div>';
                    break;
                    
                case 'multiple_choice':
                    inputHtml = '<div class="mt-2">';
                    question.options.forEach((option, i) => {
                        inputHtml += `
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="question_${question.id}" id="q_${question.id}_${i}" value="${option}">
                                <label class="form-check-label" for="q_${question.id}_${i}">${option}</label>
                            </div>
                        `;
                    });
                    inputHtml += '</div>';
                    break;
                    
                case 'rating':
                    const min = question.options?.min || 1;
                    const max = question.options?.max || 5;
                    
                    inputHtml = '<div class="rating-container mt-3">';
                    inputHtml += '<div class="rating-scale">';
                    
                    for (let i = min; i <= max; i++) {
                        inputHtml += `
                            <div class="rating-option">
                                <input class="form-check-input" type="radio" name="question_${question.id}" id="q_${question.id}_${i}" value="${i}" ${question.required ? 'required' : ''}>
                                <label class="rating-label" for="q_${question.id}_${i}">${i}</label>
                            </div>
                        `;
                    }
                    
                    inputHtml += '</div></div>';
                    break;
                    
                case 'date':
                    inputHtml = `<input type="date" class="form-control" name="question_${question.id}" id="q_${question.id}" ${question.required ? 'required' : ''}>`;
                    break;
            }
            
            return `
                <div class="card question-card" id="question-${index}">
                    <div class="card-header bg-light">
                        ${question.question_text} ${isRequired}
                    </div>
                    <div class="card-body">
                        ${inputHtml}
                        <div class="invalid-feedback">Please answer this question.</div>
                    </div>
                </div>
            `;
        }
        
        function showNextQuestion() {
            // Hide current question
            $(`#question-${currentQuestionIndex}`).hide();
            
            // Show next question
            currentQuestionIndex++;
            $(`#question-${currentQuestionIndex}`).show();
            
            // Update navigation buttons
            updateNavigationButtons();
            updateProgress();
        }
        
        function showPreviousQuestion() {
            // Hide current question
            $(`#question-${currentQuestionIndex}`).hide();
            
            // Show previous question
            currentQuestionIndex--;
            $(`#question-${currentQuestionIndex}`).show();
            
            // Update navigation buttons
            updateNavigationButtons();
            updateProgress();
        }
        
        function updateNavigationButtons() {
            // Enable/disable Previous button
            $('#prev-btn').prop('disabled', currentQuestionIndex === 0);
            
            // Show/hide Next and Submit buttons
            if (currentQuestionIndex === questions.length - 1) {
                $('#next-btn').hide();
                $('#submit-btn').show();
            } else {
                $('#next-btn').show();
                $('#submit-btn').hide();
            }
        }
        
        function updateProgress() {
            const progress = ((currentQuestionIndex + 1) / questions.length) * 100;
            $('#survey-progress').css('width', `${progress}%`).attr('aria-valuenow', progress);
        }
        
        function validateCurrentQuestion() {
            const currentQuestion = questions[currentQuestionIndex];
            
            if (!currentQuestion.required) {
                return true;
            }
            
            let isValid = false;
            const questionType = currentQuestion.question_type;
            
            switch (questionType) {
                case 'text':
                case 'paragraph':
                case 'date':
                    isValid = $(`#q_${currentQuestion.id}`).val().trim() !== '';
                    break;
                    
                case 'single_choice':
                case 'rating':
                    isValid = $(`input[name="question_${currentQuestion.id}"]:checked`).length > 0;
                    break;
                    
                case 'multiple_choice':
                    isValid = $(`input[name="question_${currentQuestion.id}"]:checked`).length > 0;
                    break;
            }
            
            if (!isValid) {
                $(`#question-${currentQuestionIndex} .invalid-feedback`).show();
                
                // Highlight the input
                if (questionType === 'text' || questionType === 'paragraph' || questionType === 'date') {
                    $(`#q_${currentQuestion.id}`).addClass('is-invalid');
                }
            } else {
                $(`#question-${currentQuestionIndex} .invalid-feedback`).hide();
                
                if (questionType === 'text' || questionType === 'paragraph' || questionType === 'date') {
                    $(`#q_${currentQuestion.id}`).removeClass('is-invalid');
                }
            }
            
            return isValid;
        }
        
        function submitSurvey() {
            const surveyContactId = $('#survey-contact-id').val();
            const startedAt = $('#started-at').val();
            
            // Collect all answers
            const answers = [];
            
            questions.forEach(question => {
                let answerData = null;
                let answerText = '';
                
                switch (question.question_type) {
                    case 'text':
                    case 'paragraph':
                    case 'date':
                        answerText = $(`#q_${question.id}`).val().trim();
                        break;
                        
                    case 'single_choice':
                    case 'rating':
                        const selectedValue = $(`input[name="question_${question.id}"]:checked`).val();
                        if (selectedValue) {
                            answerText = selectedValue;
                        }
                        break;
                        
                    case 'multiple_choice':
                        const selectedValues = [];
                        $(`input[name="question_${question.id}"]:checked`).each(function() {
                            selectedValues.push($(this).val());
                        });
                        answerText = selectedValues.join(", ");
                        answerData = selectedValues;
                        break;
                }
                
                answers.push({
                    question_id: question.id,
                    answer_text: answerText,
                    answer_data: answerData
                });
            });
            
            // Disable form submission to prevent double-submit
            $('#submit-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...');
            
            // Send the data to the server
            fetch(`/api/survey-contacts/${surveyContactId}/response`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    started_at: startedAt,
                    answers: answers
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to submit survey');
                }
                return response.json();
            })
            .then(data => {
                // Show thank you message
                $('#survey-form-container').hide();
                $('#thank-you-container').show();
            })
            .catch(error => {
                // Re-enable the submit button
                $('#submit-btn').prop('disabled', false).html('Submit <i class="fas fa-check"></i>');
                showError(error.message);
            });
        }
        
        function showError(message) {
            $('#loading-container').hide();
            $('#survey-form-container').hide();
            $('#error-message').text(message);
            $('#error-container').show();
        }

        function updateCallStatus(status) {
            document.getElementById('callStatus').textContent = status;
            if (status === 'in-progress' && !callStartTime) {
                callStartTime = new Date();
                startCallDurationTimer();
                document.getElementById('endCall').disabled = false;
            } else if (['completed', 'failed', 'no-answer'].includes(status)) {
                stopCallDurationTimer();
                document.getElementById('endCall').disabled = true;
            }
        }

        function startCallDurationTimer() {
            if (callDurationInterval) clearInterval(callDurationInterval);
            callDurationInterval = setInterval(() => {
                const duration = Math.floor((new Date() - callStartTime) / 1000);
                const hours = Math.floor(duration / 3600);
                const minutes = Math.floor((duration % 3600) / 60);
                const seconds = duration % 60;
                document.getElementById('callDuration').textContent = 
                    `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }, 1000);
        }

        function stopCallDurationTimer() {
            if (callDurationInterval) {
                clearInterval(callDurationInterval);
                callDurationInterval = null;
            }
        }

        async function endCall() {
            if (!currentCallId) return;

            try {
                const response = await fetch(`/api/bare/call/${currentCallId}/end`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${appState.apiToken}`
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to end call');
                }

                updateCallStatus('Call ended');
                stopCallDurationTimer();
                document.getElementById('endCall').disabled = true;

            } catch (error) {
                console.error('Call end error:', error);
                showError('Failed to end call: ' + error.message);
            }
        }

        // Add event listener for end call button
        document.getElementById('endCall').addEventListener('click', endCall);

        function startCallStatusPolling() {
            if (!currentCallId) return;
            
            const pollInterval = setInterval(async () => {
                try {
                    const response = await fetch(`/api/bare/call/${currentCallId}/status`, {
                        headers: {
                            'Authorization': `Bearer ${appState.apiToken}`
                        }
                    });

                    if (!response.ok) throw new Error('Failed to get call status');
                    
                    const data = await response.json();
                    updateCallStatus(data.status);
                    
                    if (['completed', 'failed', 'no-answer'].includes(data.status)) {
                        clearInterval(pollInterval);
                    }
                } catch (error) {
                    console.error('Status polling error:', error);
                }
            }, 2000);
        }
    </script>
</body>
</html> 