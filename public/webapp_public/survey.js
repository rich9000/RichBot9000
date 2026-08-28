/**
 * Survey Management JavaScript
 */

class SurveyManager {
    constructor() {
        this.currentSurvey = null;
        this.currentCampaign = null;
        this.questionTypes = [
            { value: 'text', label: 'Text (Short Answer)' },
            { value: 'paragraph', label: 'Paragraph (Long Answer)' },
            { value: 'single_choice', label: 'Single Choice (Radio Buttons)' },
            { value: 'multiple_choice', label: 'Multiple Choice (Checkboxes)' },
            { value: 'rating', label: 'Rating Scale' },
            { value: 'date', label: 'Date' }
        ];
        
        // Store modal instances
        this.modals = {
            survey: null,
            question: null,
            campaign: null,
            confirmDelete: null,
            addContacts: null,
            startSurvey: null
        };
        
        // Store bound event handlers
        this.boundHandlers = {};
        
        this.initSurveyManagement();
    }

    initSurveyManagement() {
        // Initialize modals
        this.modals.survey = new bootstrap.Modal(document.getElementById('survey-modal'));
        this.modals.question = new bootstrap.Modal(document.getElementById('question-modal'));
        this.modals.campaign = new bootstrap.Modal(document.getElementById('campaign-modal'));
        this.modals.confirmDelete = new bootstrap.Modal(document.getElementById('confirm-delete-modal'));
        this.modals.addContacts = new bootstrap.Modal(document.getElementById('add-contacts-modal'));
        
        // Bind event handlers
        this.boundHandlers = {
            showCreateSurveyModal: this.showCreateSurveyModal.bind(this),
            saveSurvey: this.saveSurvey.bind(this),
            confirmDeleteSurvey: this.confirmDeleteSurvey.bind(this),
            backToSurveysList: this.backToSurveysList.bind(this),
            editSurvey: this.editSurvey.bind(this),
            showAddQuestionModal: this.showAddQuestionModal.bind(this),
            saveQuestion: this.saveQuestion.bind(this),
            toggleQuestionOptions: this.toggleQuestionOptions.bind(this),
            addQuestionOption: this.addQuestionOption.bind(this),
            showCreateCampaignModal: this.showCreateCampaignModal.bind(this),
            saveCampaign: this.saveCampaign.bind(this),
            executeDelete: this.executeDelete.bind(this),
            showAddContactsModal: this.showAddContactsModal.bind(this),
            addContactsToCampaign: this.addContactsToCampaign.bind(this),
            editCampaign: this.editCampaign.bind(this),
            showStartSurveyModal: this.showStartSurveyModal.bind(this),
            startSurveyCall: this.startSurveyCall.bind(this)
        };
        
        // Add event listeners
        const createSurveyBtn = document.getElementById('create-survey-btn');
        const saveSurveyBtn = document.getElementById('save-survey-btn');
        const deleteSurveyBtn = document.getElementById('delete-survey-btn');
        const backToSurveysBtn = document.getElementById('back-to-surveys-btn');
        const editSurveyBtn = document.getElementById('edit-survey-btn');
        const addQuestionBtn = document.getElementById('add-question-btn');
        const saveQuestionBtn = document.getElementById('save-question-btn');
        const questionTypeSelect = document.getElementById('question-type');
        const addOptionBtn = document.getElementById('add-option-btn');
        const createCampaignBtn = document.getElementById('create-campaign-btn');
        const saveCampaignBtn = document.getElementById('save-campaign-btn');
        const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
        const addContactBtn = document.getElementById('add-contact-btn');
        const addContactsBtn = document.getElementById('add-contacts-btn');
        const selectAllContacts = document.getElementById('select-all-contacts');
        const editCampaignBtn = document.getElementById('edit-campaign-btn');

        if (createSurveyBtn) createSurveyBtn.addEventListener('click', this.boundHandlers.showCreateSurveyModal);
        if (saveSurveyBtn) saveSurveyBtn.addEventListener('click', this.boundHandlers.saveSurvey);
        if (deleteSurveyBtn) deleteSurveyBtn.addEventListener('click', this.boundHandlers.confirmDeleteSurvey);
        if (backToSurveysBtn) backToSurveysBtn.addEventListener('click', this.boundHandlers.backToSurveysList);
        if (editSurveyBtn) editSurveyBtn.addEventListener('click', this.boundHandlers.editSurvey);
        if (addQuestionBtn) addQuestionBtn.addEventListener('click', this.boundHandlers.showAddQuestionModal);
        if (saveQuestionBtn) saveQuestionBtn.addEventListener('click', this.boundHandlers.saveQuestion);
        if (questionTypeSelect) questionTypeSelect.addEventListener('change', this.boundHandlers.toggleQuestionOptions);
        if (addOptionBtn) addOptionBtn.addEventListener('click', this.boundHandlers.addQuestionOption);
        if (createCampaignBtn) createCampaignBtn.addEventListener('click', this.boundHandlers.showCreateCampaignModal);
        if (saveCampaignBtn) saveCampaignBtn.addEventListener('click', this.boundHandlers.saveCampaign);
        if (confirmDeleteBtn) confirmDeleteBtn.addEventListener('click', this.boundHandlers.executeDelete);
        if (addContactBtn) addContactBtn.addEventListener('click', this.boundHandlers.showAddContactsModal);
        if (addContactsBtn) addContactsBtn.addEventListener('click', this.boundHandlers.addContactsToCampaign);
        if (editCampaignBtn) editCampaignBtn.addEventListener('click', () => this.editCampaign(this.currentCampaignId));
        if (selectAllContacts) {
            selectAllContacts.addEventListener('change', (e) => {
                document.querySelectorAll('#available-contacts-list input[type="checkbox"]')
                    .forEach(checkbox => checkbox.checked = e.target.checked);
            });
        }
        
        // Initialize Sortable for questions reordering
        if (window.Sortable && document.getElementById('questions-container')) {
            new Sortable(document.getElementById('questions-container'), {
                animation: 150,
                ghostClass: 'bg-light',
                onEnd: this.updateQuestionOrder.bind(this)
            });
        }
        
        // Load surveys
        this.loadSurveys();
    }

    loadSurveys() {
        // Show loading indicator
        document.getElementById('surveys-list').innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Loading surveys...</td></tr>';
        
        // Make API request using appState.apiToken
        fetch('/api/surveys', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load surveys');
            }
            return response.json();
        })
        .then(surveys => {
            this.renderSurveysList(surveys);
        })
        .catch(error => {
            document.getElementById('surveys-list').innerHTML = `<tr><td colspan="6" class="text-center text-danger">${error.message}</td></tr>`;
        });
    }

    renderSurveysList(surveys) {
        const tableBody = document.getElementById('surveys-list');
        const bulkActionsContainer = document.getElementById('bulk-actions-container');
        
        if (surveys.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center">No surveys found. Create your first survey!</td></tr>';
            if (bulkActionsContainer) bulkActionsContainer.classList.add('d-none');
            return;
        }
        
        // Show bulk actions container
        if (bulkActionsContainer) {
            bulkActionsContainer.classList.remove('d-none');
        }
        
        let html = '';
        
        surveys.forEach(survey => {
            html += `
                <tr data-survey-id="${survey.id}">
                    <td>
                        <input type="checkbox" class="survey-checkbox" value="${survey.id}">
                    </td>
                    <td>${this.escapeHtml(survey.title)}</td>
                    <td>${this.escapeHtml(survey.description ? survey.description.substring(0, 50) + (survey.description.length > 50 ? '...' : '') : '')}</td>
                    <td><span class="badge bg-${this.getStatusBadgeClass(survey.status)}">${survey.status}</span></td>
                    <td>${survey.questions ? survey.questions.length : 0}</td>
                    <td>${this.formatDate(survey.created_at)}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary view-survey" data-survey-id="${survey.id}" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-outline-primary edit-survey" data-survey-id="${survey.id}" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger delete-survey" data-survey-id="${survey.id}" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        tableBody.innerHTML = html;
        
        // Add event listeners for view buttons
        document.querySelectorAll('.view-survey').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const surveyId = button.getAttribute('data-survey-id');
                this.loadSurveyDetails(surveyId);
            });
        });

        // Add event listeners for edit buttons
        document.querySelectorAll('.edit-survey').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const surveyId = button.getAttribute('data-survey-id');
                this.editSurvey(surveyId);
            });
        });

        // Add event listeners for delete buttons
        document.querySelectorAll('.delete-survey').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const surveyId = button.getAttribute('data-survey-id');
                this.confirmDeleteSurvey(surveyId);
            });
        });

        // Add event listener for select all checkbox
        const selectAllCheckbox = document.getElementById('select-all-surveys');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                document.querySelectorAll('.survey-checkbox').forEach(checkbox => {
                    checkbox.checked = e.target.checked;
                });
                this.updateBulkActionsVisibility();
            });
        }

        // Add event listeners for individual checkboxes
        document.querySelectorAll('.survey-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.updateBulkActionsVisibility();
            });
        });
    }

    updateBulkActionsVisibility() {
        const selectedCount = document.querySelectorAll('.survey-checkbox:checked').length;
        const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
        const selectedCountSpan = document.getElementById('selected-count');
        
        if (bulkDeleteBtn) {
            bulkDeleteBtn.disabled = selectedCount === 0;
        }
        
        if (selectedCountSpan) {
            selectedCountSpan.textContent = selectedCount;
        }
    }

    confirmDeleteSurvey(surveyId = null) {
        let title, message, onConfirm;
        
        if (surveyId) {
            // Single survey delete
            const survey = this.currentSurvey;
            title = 'Delete Survey';
            message = `Are you sure you want to delete the survey "${survey.title}"? This will also delete all questions, campaigns, and responses. This action cannot be undone.`;
            onConfirm = () => this.deleteSurvey(surveyId);
        } else {
            // Bulk delete
            const selectedIds = Array.from(document.querySelectorAll('.survey-checkbox:checked'))
                .map(checkbox => checkbox.value);
            
            if (selectedIds.length === 0) {
                this.showAlert('Please select at least one survey to delete', 'warning');
                return;
            }
            
            title = 'Delete Selected Surveys';
            message = `Are you sure you want to delete ${selectedIds.length} selected survey(s)? This will also delete all questions, campaigns, and responses. This action cannot be undone.`;
            onConfirm = () => this.bulkDeleteSurveys(selectedIds);
        }
        
        // Set up confirmation modal
        document.getElementById('confirm-delete-title').textContent = title;
        document.getElementById('confirm-delete-message').textContent = message;
        
        // Set up delete button action
        document.getElementById('confirm-delete-btn').onclick = onConfirm;
        
        // Show modal
        this.modals.confirmDelete.show();
    }

    bulkDeleteSurveys(surveyIds) {
        // Disable delete button
        const deleteButton = document.getElementById('confirm-delete-btn');
        deleteButton.disabled = true;
        deleteButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...';
        
        // Make API request
        fetch('/api/surveys/bulk-delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ survey_ids: surveyIds })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to delete surveys');
            }
            
            // Hide modal
            this.modals.confirmDelete.hide();
            
            // Back to surveys list
            this.backToSurveysList();
            
            // Reload surveys
            this.loadSurveys();
            
            // Show success message
            this.showAlert(`${surveyIds.length} survey(s) deleted successfully`, 'success');
        })
        .catch(error => {
            this.showAlert(error.message, 'danger');
        })
        .finally(() => {
            // Re-enable delete button
            deleteButton.disabled = false;
            deleteButton.innerHTML = 'Delete';
        });
    }

    loadSurveyDetails(surveyId) {
        // Show loading indicator
        document.getElementById('survey-detail-title').textContent = 'Loading survey...';
        document.getElementById('survey-detail-section').classList.remove('d-none');
        document.getElementById('questions-container').innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div><p class="mt-2">Loading questions...</p></div>';
        
        // Make API request
        fetch(`/api/surveys/${surveyId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load survey details');
            }
            return response.json();
        })
        .then(survey => {
            console.log('survey',survey);

            console.log('surveyId',surveyId);
            // Set current survey before rendering details
            this.currentSurvey = survey;
            console.log('this.currentSurvey',this.currentSurvey);

            this.renderSurveyDetails(survey,surveyId);
        })
        .catch(error => {
            document.getElementById('questions-container').innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        });
    }

    showCreateSurveyModal() {
        if (!this.checkApiToken()) return;
        
        // Reset form
        document.getElementById('survey-form').reset();
        document.getElementById('survey-id').value = '';
        
        // Set modal title
        document.getElementById('survey-modal-label').textContent = 'Create Survey';
        
        // Show modal
        this.modals.survey.show();
    }

    checkApiToken() {
        if (!appState.apiToken) {
            this.showAlert('API token is missing. Please log in again.', 'danger');
            return false;
        }
        return true;
    }

    saveSurvey() {
        // Validate form
        const title = document.getElementById('survey-title').value.trim();
        if (!title) {
            this.showAlert('Survey title is required', 'danger');
            return;
        }
        
        const description = document.getElementById('survey-description').value.trim();
        const status = document.getElementById('survey-status').value;
        const surveyId = document.getElementById('survey-id').value;
        
        // Create survey data
        const surveyData = {
            title: title,
            description: description,
            status: status
        };
        
        // Disable save button
        const saveButton = document.getElementById('save-survey-btn');
        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
        
        // Determine if we're creating or updating
        const isUpdate = surveyId !== '';
        const url = isUpdate ? `/api/surveys/${surveyId}` : '/api/surveys';
        const method = isUpdate ? 'PUT' : 'POST';
        
        // Make API request
        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(surveyData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to save survey');
            }
            return response.json();
        })
        .then(data => {
            // Hide modal
            this.modals.survey.hide();
            
            // Reload surveys
            this.loadSurveys();
            
            // If updating current survey, reload details
            if (isUpdate && this.currentSurvey && this.currentSurvey.id == surveyId) {
                this.loadSurveyDetails(surveyId);
            }
            
            // Show success message
            this.showAlert(isUpdate ? 'Survey updated successfully' : 'Survey created successfully', 'success');
        })
        .catch(error => {
            this.showAlert(error.message, 'danger');
        })
        .finally(() => {
            // Re-enable save button
            saveButton.disabled = false;
            saveButton.innerHTML = 'Save';
        });
    }

    showAlert(message, type) {
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Add to container
        const container = document.querySelector('.container-fluid');
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            const alert = bootstrap.Alert.getInstance(alertDiv);
            if (alert) {
                alert.close();
            }
        }, 5000);
    }

    escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) {
            return '';
        }
        return String(unsafe)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    getStatusBadgeClass(status) {
        switch (status) {
            case 'active':
                return 'success';
            case 'draft':
                return 'warning';
            case 'archived':
                return 'secondary';
            default:
                return 'primary';
        }
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString();
    }

    formatDateRange(startDate, endDate) {
        if (!startDate && !endDate) return 'Not specified';
        if (!endDate) return `From ${this.formatDate(startDate)}`;
        if (!startDate) return `Until ${this.formatDate(endDate)}`;
        return `${this.formatDate(startDate)} - ${this.formatDate(endDate)}`;
    }

    backToSurveysList() {
        document.getElementById('survey-detail-section').classList.add('d-none');
        this.currentSurvey = null;
    }

    editSurvey() {
        if (!this.currentSurvey) {
            this.showAlert('No survey selected', 'danger');
            return;
        }
        
        // Populate form
        document.getElementById('survey-id').value = this.currentSurvey.id;
        document.getElementById('survey-title').value = this.currentSurvey.title;
        document.getElementById('survey-description').value = this.currentSurvey.description || '';
        document.getElementById('survey-status').value = this.currentSurvey.status;
        
        // Set modal title
        document.getElementById('survey-modal-label').textContent = 'Edit Survey';
        
        // Show modal
        this.modals.survey.show();
    }

    renderSurveyDetails(survey,surveyId) {
       
       
        // Update title
        document.getElementById('survey-detail-title').textContent = survey.title;
        
        // Show detail section
        const detailSection = document.getElementById('survey-detail-section');
        detailSection.classList.remove('d-none');
        
        // Set survey ID on action buttons after section is shown
        setTimeout(() => {
            const addQuestionBtn = document.getElementById('add-question-btn');
            const createCampaignBtn = document.getElementById('create-campaign-btn');
            
            if (addQuestionBtn) {
                addQuestionBtn.setAttribute('data-survey-id', surveyId);
            }
            if (createCampaignBtn) {
                createCampaignBtn.setAttribute('data-survey-id', surveyId);
            }
        }, 0);
        
        // Render questions
        this.renderQuestions(survey.questions || []);
        
        // Load campaigns
        this.loadCampaigns(surveyId);
    }

    renderQuestions(questions) {
        const container = document.getElementById('questions-container');
        container.innerHTML = '';
        
        if (questions.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No questions added yet. Click "Add Question" to create your first question.</div>';
            return;
        }
        
        questions.forEach((question, index) => {
            const questionHtml = this.buildQuestionHtml(question, index);
            container.insertAdjacentHTML('beforeend', questionHtml);
        });
    }

    buildQuestionHtml(question, index) {
        const questionType = this.questionTypes.find(qt => qt.value === question.question_type)?.label || question.question_type;
        const required = question.required ? '<span class="badge bg-danger ms-1">Required</span>' : '';
        
        let optionsHtml = '';
        if (['single_choice', 'multiple_choice'].includes(question.question_type)) {
            optionsHtml = '<div class="mt-2 small">';
            if (Array.isArray(question.options)) {
                question.options.forEach(option => {
                    optionsHtml += `<span class="badge bg-light text-dark me-1">${this.escapeHtml(option)}</span>`;
                });
            }
            optionsHtml += '</div>';
        } else if (question.question_type === 'rating') {
            const min = question.options?.min || 1;
            const max = question.options?.max || 5;
            optionsHtml = `<div class="mt-2 small">Rating scale: ${min} to ${max}</div>`;
        }
        
        return `
            <div class="list-group-item" data-question-id="${question.id}" data-order="${question.order || index + 1}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">${this.escapeHtml(question.question_text)}</h6>
                        <div>
                            <span class="badge bg-secondary">${questionType}</span>
                            ${required}
                        </div>
                        ${optionsHtml}
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-primary edit-question" data-question-id="${question.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-question" data-question-id="${question.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    loadCampaigns(surveyId) {
        if (!surveyId) {
            console.error('Survey ID is required to load campaigns');
            return;
        }

        // Show loading indicator in campaigns tab
        document.getElementById('campaigns-list').innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Loading campaigns...</td></tr>';
        
        // Make API request
        fetch(`/api/surveys/${surveyId}/campaigns`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load campaigns');
            }
            return response.json();
        })
        .then(campaigns => {
            this.renderCampaigns(campaigns);
        })
        .catch(error => {
            document.getElementById('campaigns-list').innerHTML = `<tr><td colspan="5" class="text-center text-danger">${error.message}</td></tr>`;
        });
    }

    renderCampaigns(campaigns) {
        const campaignsList = document.getElementById('campaigns-list');
        campaignsList.innerHTML = '';
        
        campaigns.forEach(campaign => {
            const row = document.createElement('tr');
            
            // Format dates
            const dates = this.formatDateRange(campaign.start_date, campaign.end_date);
            
            row.innerHTML = `
                <td>${this.escapeHtml(campaign.name)}</td>
                <td><span class="badge ${this.getCampaignStatusBadgeClass(campaign.status)}">${this.escapeHtml(campaign.status)}</span></td>
                <td>${dates}</td>
                <td>${campaign.survey_contacts_count || 0}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary view-campaign" data-campaign-id="${campaign.id}">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary edit-campaign" data-campaign-id="${campaign.id}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger delete-campaign" data-campaign-id="${campaign.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            
            // Add event listeners
            const viewBtn = row.querySelector('.view-campaign');
            const editBtn = row.querySelector('.edit-campaign');
            const deleteBtn = row.querySelector('.delete-campaign');
            
            viewBtn.addEventListener('click', () => this.viewCampaign(campaign.id));
            editBtn.addEventListener('click', () => this.editCampaign(campaign.id));
            deleteBtn.addEventListener('click', () => this.confirmDeleteCampaign(campaign.id));
            
            campaignsList.appendChild(row);
        });
    }

    viewCampaign(campaignId) {
        // Hide survey detail section
        document.getElementById('survey-detail-section').classList.add('d-none');
        
        // Show loading state
        this.showAlert('Loading campaign details...', 'info');
        
        // Fetch campaign details
        fetch(`/api/survey-campaigns/${campaignId}`, {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Failed to load campaign');
            return response.json();
        })
        .then(campaign => {
            this.renderCampaignDetails(campaign);
            this.loadCampaignContacts(campaignId);
            this.showAlert('', 'clear');
        })
        .catch(error => {
            this.showAlert(error.message, 'danger');
        });
    }

    renderCampaignDetails(campaign) {
        // Update title and show section
        document.getElementById('campaign-detail-title').textContent = campaign.name;
        document.getElementById('campaign-detail-section').classList.remove('d-none');
        
        // Update details tab
        document.getElementById('campaign-name').textContent = campaign.name;
        document.getElementById('campaign-description').textContent = campaign.description || 'No description';
        document.getElementById('campaign-status').innerHTML = `<span class="badge ${this.getCampaignStatusBadgeClass(campaign.status)}">${this.escapeHtml(campaign.status)}</span>`;
        document.getElementById('campaign-dates').textContent = this.formatDateRange(campaign.start_date, campaign.end_date);
        
        // Store current campaign ID
        this.currentCampaignId = campaign.id;

        // Add event listener for edit button
        const editCampaignBtn = document.getElementById('edit-campaign-btn');
        if (editCampaignBtn) {
            // Remove any existing listeners
            editCampaignBtn.replaceWith(editCampaignBtn.cloneNode(true));
            // Get the fresh reference after cloning
            const freshEditBtn = document.getElementById('edit-campaign-btn');
            freshEditBtn.addEventListener('click', () => {
                console.log('Edit campaign clicked for ID:', campaign.id);
                this.editCampaign(campaign.id);
            });
        }

        // Render responses if they exist
        if (campaign.responses && campaign.responses.length > 0) {
            const responsesList = document.getElementById('responses-list');
            if (responsesList) {
                responsesList.innerHTML = '';
                campaign.responses.forEach(response => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${response.id}</td>
                        <td>${this.formatDate(response.started_at)}</td>
                        <td>${response.completed_at ? this.formatDate(response.completed_at) : 'In Progress'}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary view-response" data-response-id="${response.id}">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    `;
                    responsesList.appendChild(row);

                    // Add event listener for view button
                    const viewBtn = row.querySelector('.view-response');
                    viewBtn.addEventListener('click', () => this.viewResponse(response.id));
                });
            }
        }
        
        // Set up back button
        document.getElementById('back-to-survey-btn').onclick = () => {
            document.getElementById('campaign-detail-section').classList.add('d-none');
            document.getElementById('survey-detail-section').classList.remove('d-none');
        };
    }

    viewResponse(responseId) {
        // Show loading state
        this.showAlert('Loading response details...', 'info');
        
        // Fetch response details
        fetch(`/api/survey-responses/${responseId}`, {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Failed to load response details');
            return response.json();
        })
        .then(response => {
            this.renderResponseDetails(response);
            this.showAlert('', 'clear');
        })
        .catch(error => {
            this.showAlert(error.message, 'danger');
        });
    }

    renderResponseDetails(response) {
        // Create or get response details container
        let container = document.getElementById('response-details-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'response-details-container';
            container.className = 'mt-4';
            document.getElementById('campaign-detail-section').appendChild(container);
        }

        // Build response details HTML
        let html = `
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Response Details</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('response-details-container').remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Started:</strong> ${this.formatDate(response.started_at)}
                        <br>
                        <strong>Completed:</strong> ${response.completed_at ? this.formatDate(response.completed_at) : 'In Progress'}
                    </div>
                    <h6>Answers:</h6>
                    <div class="list-group">
        `;

        // Add each answer
        if (response.answers && response.answers.length > 0) {
            response.answers.forEach(answer => {
                let answerText = '';
                if (answer.answer_data) {
                    answerText = Array.isArray(answer.answer_data) 
                        ? answer.answer_data.join(', ') 
                        : JSON.stringify(answer.answer_data);
                } else {
                    answerText = answer.answer_text || 'No answer provided';
                }

                html += `
                    <div class="list-group-item">
                        <h6 class="mb-1">${this.escapeHtml(answer.question.question_text)}</h6>
                        <p class="mb-1">${this.escapeHtml(answerText)}</p>
                        <small class="text-muted">Answered at: ${this.formatDate(answer.created_at)}</small>
                    </div>
                `;
            });
        } else {
            html += '<div class="list-group-item">No answers recorded yet</div>';
        }

        html += `
                    </div>
                </div>
            </div>
        `;

        container.innerHTML = html;
    }

    loadCampaignContacts(campaignId) {
        fetch(`/api/survey-campaigns/${campaignId}/contacts`, {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Failed to load contacts');
            return response.json();
        })
        .then(contacts => {
            this.renderCampaignContacts(contacts);
        })
        .catch(error => {
            this.showAlert('Failed to load campaign contacts: ' + error.message, 'danger');
        });
    }

    renderCampaignContacts(contacts) {
        const contactsList = document.getElementById('contacts-list');
        contactsList.innerHTML = '';
        
        if (!Array.isArray(contacts)) {
            console.error('Contacts is not an array:', contacts);
            return;
        }

        contacts.forEach(surveyContact => {
            if (!surveyContact) return;
            
            const contact = surveyContact.contact || {};
            const row = document.createElement('tr');
            
            const name = this.escapeHtml(contact.name || '');
            const email = this.escapeHtml(contact.email || '');
            const phone = this.escapeHtml(contact.phone || '');
            const status = this.escapeHtml(surveyContact.status || 'pending');
            
            row.innerHTML = `
                <td>${name}</td>
                <td>${email}</td>
                <td>${phone}</td>
                <td><span class="badge ${this.getContactStatusBadgeClass(status)}">${status}</span></td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-success start-survey" data-contact-id="${surveyContact.id}" title="Start Survey Call">
                            <i class="fas fa-phone"></i>
                        </button>
                        <button class="btn btn-outline-danger remove-contact" data-contact-id="${surveyContact.id}" title="Remove Contact">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </td>
            `;
            
            // Add event listeners
            const startSurveyBtn = row.querySelector('.start-survey');
            const removeBtn = row.querySelector('.remove-contact');
            
            startSurveyBtn.addEventListener('click', () => this.showStartSurveyModal(surveyContact));
            removeBtn.addEventListener('click', () => this.confirmRemoveContact(surveyContact.id));
            
            contactsList.appendChild(row);
        });
    }

    getContactStatusBadgeClass(status) {
        const classes = {
            'pending': 'bg-warning',
            'sent': 'bg-info',
            'completed': 'bg-success',
            'expired': 'bg-secondary'
        };
        return classes[status] || 'bg-secondary';
    }

    getCampaignStatusBadgeClass(status) {
        const classes = {
            'pending': 'bg-warning',
            'active': 'bg-success',
            'completed': 'bg-info',
            'archived': 'bg-secondary'
        };
        return classes[status] || 'bg-secondary';
    }

    confirmRemoveContact(surveyContactId) {
        if (confirm('Are you sure you want to remove this contact from the campaign?')) {
            this.removeContact(surveyContactId);
        }
    }

    removeContact(surveyContactId) {
        fetch(`/api/survey-campaigns/${this.currentCampaignId}/contacts/${surveyContactId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Failed to remove contact');
            this.loadCampaignContacts(this.currentCampaignId);
            this.showAlert('Contact removed successfully', 'success');
        })
        .catch(error => {
            this.showAlert('Failed to remove contact: ' + error.message, 'danger');
        });
    }

    showAddQuestionModal() {
        const addQuestionBtn = document.getElementById('add-question-btn');
        const surveyId = addQuestionBtn?.getAttribute('data-survey-id');
        
        if (!surveyId) {
            this.showAlert('Survey ID not found', 'danger');
            return;
        }
        
        // Reset form
        document.getElementById('question-form').reset();
        document.getElementById('question-id').value = '';
        document.getElementById('options-container').classList.add('d-none');
        document.getElementById('rating-container').classList.add('d-none');
        document.getElementById('options-list').innerHTML = `
            <div class="input-group mb-2">
                <input type="text" class="form-control option-input" placeholder="Option text">
                <button class="btn btn-outline-danger remove-option" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        // Set modal title
        document.getElementById('question-modal-label').textContent = 'Add Question';
        
        // Show modal
        this.modals.question.show();
    }

    toggleQuestionOptions() {
        const questionType = document.getElementById('question-type').value;
        const optionsContainer = document.getElementById('options-container');
        const ratingContainer = document.getElementById('rating-container');
        
        // Hide both containers initially
        optionsContainer.classList.add('d-none');
        ratingContainer.classList.add('d-none');
        
        // Show the appropriate container based on question type
        if (['single_choice', 'multiple_choice'].includes(questionType)) {
            optionsContainer.classList.remove('d-none');
        } else if (questionType === 'rating') {
            ratingContainer.classList.remove('d-none');
        }
    }

    addQuestionOption() {
        const optionsList = document.getElementById('options-list');
        const newOption = document.createElement('div');
        newOption.className = 'input-group mb-2';
        newOption.innerHTML = `
            <input type="text" class="form-control option-input" placeholder="Option text">
            <button class="btn btn-outline-danger remove-option" type="button">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        optionsList.appendChild(newOption);
        
        // Add event listener to the new remove button
        newOption.querySelector('.remove-option').addEventListener('click', function() {
            optionsList.removeChild(newOption);
        });
    }

    saveQuestion() {
        const addQuestionBtn = document.getElementById('add-question-btn');
        const surveyId = addQuestionBtn?.getAttribute('data-survey-id');
        
        if (!surveyId) {
            this.showAlert('Survey ID not found', 'danger');
            return;
        }
        
        // Validate form
        const questionText = document.getElementById('question-text').value.trim();
        if (!questionText) {
            this.showAlert('Question text is required', 'danger');
            return;
        }
        
        const questionType = document.getElementById('question-type').value;
        const required = document.getElementById('question-required').checked;
        const questionId = document.getElementById('question-id').value;
        
        // Collect options based on question type
        let options = null;
        if (['single_choice', 'multiple_choice'].includes(questionType)) {
            options = Array.from(document.querySelectorAll('.option-input'))
                .map(input => input.value.trim())
                .filter(value => value !== '');
        } else if (questionType === 'rating') {
            const min = parseInt(document.getElementById('rating-min').value) || 1;
            const max = parseInt(document.getElementById('rating-max').value) || 5;
            options = { min, max };
        }
        
        // Create question data
        const questionData = {
            question_text: questionText,
            question_type: questionType,
            required: required,
            options: options
        };
        
        // Disable save button
        const saveButton = document.getElementById('save-question-btn');
        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
        
        // Determine if we're creating or updating
        const isUpdate = questionId !== '';
        const url = isUpdate 
            ? `/api/surveys/${surveyId}/questions/${questionId}`
            : `/api/surveys/${surveyId}/questions`;
        const method = isUpdate ? 'PUT' : 'POST';
        
        // Make API request
        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(questionData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to save question');
            }
            return response.json();
        })
        .then(data => {
            // Hide modal
            this.modals.question.hide();
            
            // Reload survey details to get updated questions
            this.loadSurveyDetails(surveyId);
            
            // Show success message
            this.showAlert(isUpdate ? 'Question updated successfully' : 'Question created successfully', 'success');
        })
        .catch(error => {
            this.showAlert(error.message, 'danger');
        })
        .finally(() => {
            // Re-enable save button
            saveButton.disabled = false;
            saveButton.innerHTML = 'Save';
        });
    }

    editQuestion(questionId) {
        // Find the question in the current survey
        const question = this.currentSurvey.questions.find(q => q.id == questionId);
        if (!question) {
            this.showAlert('Question not found', 'danger');
            return;
        }
        
        // Populate form
        document.getElementById('question-id').value = question.id;
        document.getElementById('question-text').value = question.question_text;
        document.getElementById('question-type').value = question.question_type;
        document.getElementById('question-required').checked = question.required;
        
        // Reset options containers
        document.getElementById('options-container').classList.add('d-none');
        document.getElementById('rating-container').classList.add('d-none');
        document.getElementById('options-list').innerHTML = '';
        
        // Populate options based on question type
        if (['single_choice', 'multiple_choice'].includes(question.question_type)) {
            document.getElementById('options-container').classList.remove('d-none');
            
            if (Array.isArray(question.options)) {
                question.options.forEach(option => {
                    const optionHtml = `
                        <div class="input-group mb-2">
                            <input type="text" class="form-control option-input" placeholder="Option text" value="${this.escapeHtml(option)}">
                            <button class="btn btn-outline-danger remove-option" type="button">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                    document.getElementById('options-list').insertAdjacentHTML('beforeend', optionHtml);
                });
                
                // Add event listeners for remove buttons
                document.querySelectorAll('.remove-option').forEach(button => {
                    button.addEventListener('click', function() {
                        this.closest('.input-group').remove();
                    });
                });
            }
        } else if (question.question_type === 'rating') {
            document.getElementById('rating-container').classList.remove('d-none');
            
            if (question.options) {
                document.getElementById('rating-min').value = question.options.min || 1;
                document.getElementById('rating-max').value = question.options.max || 5;
            }
        }
        
        // Set modal title
        document.getElementById('question-modal-label').textContent = 'Edit Question';
        
        // Show modal
        this.modals.question.show();
    }

    confirmDeleteQuestion(questionId) {
        // Find the question in the current survey
        const question = this.currentSurvey.questions.find(q => q.id == questionId);
        if (!question) {
            this.showAlert('Question not found', 'danger');
            return;
        }
        
        // Set up confirmation modal
        document.getElementById('confirm-delete-title').textContent = 'Delete Question';
        document.getElementById('confirm-delete-message').textContent = `Are you sure you want to delete the question "${question.question_text}"? This action cannot be undone.`;
        
        // Set up delete button action
        document.getElementById('confirm-delete-btn').onclick = () => this.deleteQuestion(questionId);
        
        // Show modal
        this.modals.confirmDelete.show();
    }

    deleteQuestion(questionId) {
        // Disable delete button
        const deleteButton = document.getElementById('confirm-delete-btn');
        deleteButton.disabled = true;
        deleteButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...';
        
        // Make API request
        fetch(`/api/survey-questions/${questionId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to delete question');
            }
            
            // Hide modal
            this.modals.confirmDelete.hide();
            
            // Reload survey details to get updated questions
            this.loadSurveyDetails(this.currentSurvey.id);
            
            // Show success message
            this.showAlert('Question deleted successfully', 'success');
        })
        .catch(error => {
            this.showAlert(error.message, 'danger');
        })
        .finally(() => {
            // Re-enable delete button
            deleteButton.disabled = false;
            deleteButton.innerHTML = 'Delete';
        });
    }

    updateQuestionOrder() {
        if (!this.currentSurvey) return;
        
        const questions = [];
        document.querySelectorAll('#questions-container .list-group-item').forEach((item, index) => {
            questions.push({
                id: item.getAttribute('data-question-id'),
                order: index + 1
            });
        });
        
        // Make API request
        fetch(`/api/surveys/${this.currentSurvey.id}/questions/reorder`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ questions })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to update question order');
            }
            return response.json();
        })
        .then(data => {
            // Question order updated successfully
        })
        .catch(error => {
            this.showAlert(error.message, 'danger');
        });
    }

    showCreateCampaignModal() {
        const createCampaignBtn = document.getElementById('create-campaign-btn');
        const surveyId = createCampaignBtn?.getAttribute('data-survey-id');
        
        if (!surveyId) {
            this.showAlert('Survey ID not found', 'danger');
            return;
        }
        
        // Reset form
        document.getElementById('campaign-form').reset();
        document.getElementById('campaign-id').value = '';
        
        // Set modal title
        document.getElementById('campaign-modal-label').textContent = 'Create Campaign';
        
        // Show modal
        this.modals.campaign.show();
    }

    saveCampaign() {
        const form = document.getElementById('campaign-form');
        if (!form) {
            this.showAlert('Campaign form not found', 'danger');
            return;
        }

        const createCampaignBtn = document.getElementById('create-campaign-btn');
        const surveyId = createCampaignBtn?.getAttribute('data-survey-id');
        
        if (!surveyId) {
            this.showAlert('Survey ID not found', 'danger');
            return;
        }
        
        // Get form elements
        const nameInput = form.querySelector('#campaign-name');
        const descriptionInput = form.querySelector('#campaign-description');
        const startDateInput = form.querySelector('#campaign-start-date');
        const endDateInput = form.querySelector('#campaign-end-date');
        const statusInput = form.querySelector('#campaign-status');
        const campaignIdInput = form.querySelector('#campaign-id');
        
        if (!nameInput || !descriptionInput || !startDateInput || !endDateInput || !statusInput || !campaignIdInput) {
            this.showAlert('Required form elements not found', 'danger');
            return;
        }
        
        // Validate form
        const name = nameInput.value.trim();
        if (!name) {
            this.showAlert('Campaign name is required', 'danger');
            return;
        }
        
        const description = descriptionInput.value.trim();
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        const status = statusInput.value;
        const campaignId = campaignIdInput.value;
        
        // Validate dates
        if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
            this.showAlert('End date must be after start date', 'danger');
            return;
        }
        
        // Create campaign data
        const campaignData = {
            name: name,
            description: description,
            start_date: startDate || null,
            end_date: endDate || null,
            status: status
        };
        
        // Disable save button
        const saveButton = document.getElementById('save-campaign-btn');
        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
        
        // Determine if we're creating or updating
        const isUpdate = campaignId !== '';
        const url = isUpdate 
            ? `/api/survey-campaigns/${campaignId}`
            : `/api/surveys/${surveyId}/campaigns`;
        const method = isUpdate ? 'PUT' : 'POST';
        
        // Make API request
        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(campaignData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to save campaign');
            }
            return response.json();
        })
        .then(data => {
            // Hide modal
            this.modals.campaign.hide();
            
            // Reload campaigns
            this.loadCampaigns(surveyId);
            
            // Show success message
            this.showAlert(isUpdate ? 'Campaign updated successfully' : 'Campaign created successfully', 'success');
        })
        .catch(error => {
            this.showAlert(error.message, 'danger');
        })
        .finally(() => {
            // Re-enable save button
            saveButton.disabled = false;
            saveButton.innerHTML = 'Save';
        });
    }

    editCampaign(campaignId) {
        console.log('Editing campaign:', campaignId);
        // Make API request to get campaign details
        fetch(`/api/survey-campaigns/${campaignId}`, {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load campaign details');
            }
            return response.json();
        })
        .then(campaign => {
            console.log('Campaign data:', campaign);
            
            // Populate form
            const form = document.getElementById('campaign-form');
            if (!form) {
                console.error('Campaign form not found');
                return;
            }

            // Set form values
            form.querySelector('#campaign-id').value = campaign.id;
            form.querySelector('#campaign-name').value = campaign.name || '';
            form.querySelector('#campaign-description').value = campaign.description || '';
            
            // Format dates for input fields (YYYY-MM-DD)
            const startDate = campaign.start_date ? campaign.start_date.split('T')[0] : '';
            const endDate = campaign.end_date ? campaign.end_date.split('T')[0] : '';
            
            form.querySelector('#campaign-start-date').value = startDate;
            form.querySelector('#campaign-end-date').value = endDate;
            form.querySelector('#campaign-status').value = campaign.status || 'pending';
            
            // Set modal title
            document.getElementById('campaign-modal-label').textContent = 'Edit Campaign';
            
            // Show modal
            this.modals.campaign.show();
        })
        .catch(error => {
            console.error('Error loading campaign:', error);
            this.showAlert(error.message, 'danger');
        });
    }

    executeDelete() {
        // This function will be overridden by the specific delete functions
        // when they set up the confirmation dialog
    }

    showAddContactsModal() {
        if (!this.currentCampaignId) {
            this.showAlert('No campaign selected', 'danger');
            return;
        }

        // Initialize modal if not already done
        if (!this.modals.addContacts) {
            const modalElement = document.getElementById('add-contacts-modal');
            if (!modalElement) {
                this.showAlert('Add contacts modal not found', 'danger');
                return;
            }
            this.modals.addContacts = new bootstrap.Modal(modalElement);
        }

        // Show loading state
        const contactsList = document.getElementById('available-contacts-list');
        if (contactsList) {
            contactsList.innerHTML = '<tr><td colspan="4" class="text-center"><div class="spinner-border spinner-border-sm"></div> Loading contacts...</td></tr>';
        }

        // Load available contacts
        fetch('/api/contacts', {
            headers: {
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Failed to load contacts');
            return response.json();
        })
        .then(response => {
            // Handle both possible response formats (direct array or data property)
            const contacts = Array.isArray(response) ? response : (response.data || []);
            
            if (!Array.isArray(contacts)) {
                throw new Error('Invalid contacts data received');
            }
            
            this.renderAvailableContacts(contacts);
        })
        .catch(error => {
            console.error('Error loading contacts:', error);
            if (contactsList) {
                contactsList.innerHTML = `<tr><td colspan="4" class="text-danger">${error.message}</td></tr>`;
            }
        });

        // Reset form
        const form = document.getElementById('new-contact-form');
        if (form) {
            form.reset();
        }

        // Show modal
        this.modals.addContacts.show();
    }

    renderAvailableContacts(contacts) {
        const tbody = document.getElementById('available-contacts-list');
        tbody.innerHTML = '';

        if (!contacts || contacts.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">No contacts available</td></tr>';
            return;
        }

        contacts.forEach(contact => {
            if (!contact) return; // Skip if contact is null/undefined
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="checkbox" class="contact-checkbox" value="${contact.id || ''}"></td>
                <td>${this.escapeHtml(contact.name || '')}</td>
                <td>${this.escapeHtml(contact.email || '')}</td>
                <td>${this.escapeHtml(contact.phone || '')}</td>
            `;
            tbody.appendChild(row);
        });
    }

    addContactsToCampaign() {
        const addContactsBtn = document.getElementById('add-contacts-btn');
        addContactsBtn.disabled = true;
        addContactsBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding...';

        let contactData = {};

        // Get active tab
        const activeTab = document.querySelector('#contactsTabsContent .tab-pane.active');
        
        if (activeTab.id === 'existing-contacts') {
            // Handle existing contacts
            const selectedContacts = Array.from(document.querySelectorAll('#available-contacts-list input[type="checkbox"]:checked'))
                .map(checkbox => parseInt(checkbox.value));
            
            if (selectedContacts.length === 0) {
                this.showAlert('Please select at least one contact', 'danger');
                addContactsBtn.disabled = false;
                addContactsBtn.innerHTML = 'Add Contacts';
                return;
            }
            
            contactData.contact_ids = selectedContacts;
        } else if (activeTab.id === 'new-contact') {
            // Handle new contact
            const form = document.getElementById('new-contact-form');
            const name = form.querySelector('#contact-name').value.trim();
            const email = form.querySelector('#contact-email').value.trim();
            const phone = form.querySelector('#contact-phone').value.trim();

            console.log(name, email, phone);

            if (name) contactData.name = name;
            if (email) contactData.email = email;
            if (phone) contactData.phone = phone;

            // Validate that at least one of email or phone is provided
            if (!email && !phone) {
                this.showAlert('Please provide at least one of email or phone', 'danger');
                addContactsBtn.disabled = false;
                addContactsBtn.innerHTML = 'Add Contacts';
                return;
            }
        }

        // Make API request
        fetch(`/api/survey-campaigns/${this.currentCampaignId}/contacts`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(contactData)
        })
        .then(response => {
            if (!response.ok) throw new Error('Failed to add contacts');
            return response.json();
        })
        .then(data => {
            this.modals.addContacts.hide();
            this.loadCampaignContacts(this.currentCampaignId);
            this.showAlert(`${data.added_contacts.length} contacts added to campaign`, 'success');
        })
        .catch(error => {
            this.showAlert(error.message, 'danger');
        })
        .finally(() => {
            addContactsBtn.disabled = false;
            addContactsBtn.innerHTML = 'Add Contacts';
        });
    }

    showStartSurveyModal(surveyContact) {
        // Create modal if it doesn't exist
        if (!document.getElementById('start-survey-modal')) {
            const modalHtml = `
                <div class="modal fade" id="start-survey-modal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Start Survey Call</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="start-survey-form">
                                    <input type="hidden" id="survey-contact-id">
                                    <div class="mb-3">
                                        <label class="form-label">Survey Style</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="survey-style" id="style-casual" value="casual" checked>
                                            <label class="form-check-label" for="style-casual">
                                                Casual
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="survey-style" id="style-formal" value="formal">
                                            <label class="form-check-label" for="style-formal">
                                                Formal
                                            </label>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="confirm-start-survey">Start Call</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Initialize modal
            this.modals.startSurvey = new bootstrap.Modal(document.getElementById('start-survey-modal'));
            
            // Add event listener for start button
            document.getElementById('confirm-start-survey').addEventListener('click', () => {
                this.startSurveyCall(surveyContact.id);
            });
        }
        
        // Set contact ID
        document.getElementById('survey-contact-id').value = surveyContact.id;
        
        // Show modal
        this.modals.startSurvey.show();
    }

    startSurveyCall(surveyContactId) {
        const style = document.querySelector('input[name="survey-style"]:checked').value;
        const startButton = document.getElementById('confirm-start-survey');
        
        // Disable button and show loading state
        startButton.disabled = true;
        startButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Starting...';
        
        // Make API request
        fetch(`/api/survey-campaigns/${this.currentCampaignId}/contacts/${surveyContactId}/start-survey`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + appState.apiToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ style })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to start survey call');
            }
            return response.json();
        })
        .then(data => {
            // Hide modal
            this.modals.startSurvey.hide();
            
            // Show success message
            this.showAlert('Survey call started successfully', 'success');
            
            // Start call monitoring if call_sid is present
            if (data.data && data.data.call_sid) {
                this.startCallMonitoring(data.data.call_sid);
            }
            
            // Reload contacts to update status
            this.loadCampaignContacts(this.currentCampaignId);
        })
        .catch(error => {
            this.showAlert(error.message, 'danger');
        })
        .finally(() => {
            // Re-enable button
            startButton.disabled = false;
            startButton.innerHTML = 'Start Call';
        });
    }

    startCallMonitoring(callSid) {
        // Create call monitoring UI if it doesn't exist
        if (!document.getElementById('call-monitoring')) {
            const monitoringHtml = `
                <div id="call-monitoring" class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Call Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-phone-volume me-2"></i>
                            <span id="callStatusText">Initializing call...</span>
                        </div>
                        <div class="progress mb-3" style="height: 20px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 100%"></div>
                        </div>
                        <button id="endCallButton" class="btn btn-danger">
                            <i class="fas fa-phone-slash me-2"></i>End Call
                        </button>
                    </div>
                </div>
            `;
            document.querySelector('.container').insertAdjacentHTML('afterbegin', monitoringHtml);
            
            // Add event listener for end call button
            document.getElementById('endCallButton').addEventListener('click', () => this.endCall(callSid));
        }

        // Start polling for call status
        this.startStatusPolling(callSid);
    }

    startStatusPolling(callSid) {
        if (this.statusInterval) clearInterval(this.statusInterval);
        
        this.statusInterval = setInterval(async () => {
            try {
                const response = await fetch(`/api/bare/call/${callSid}/status`, {
                    headers: {
                        'Authorization': `Bearer ${appState.apiToken}`
                    }
                });

                if (!response.ok) throw new Error('Failed to get call status');
                
                const data = await response.json();
                
                // Update status display
                document.getElementById('callStatusText').textContent = data.status;
                
                // If call is finished, reset interface
                if (['completed', 'failed', 'no-answer'].includes(data.status)) {
                    clearInterval(this.statusInterval);
                    setTimeout(() => this.resetCallMonitoring(), 2000);
                }

            } catch (error) {
                console.error('Status polling error:', error);
            }
        }, 2000);
    }

    async endCall(callSid) {
        try {
            const response = await fetch(`/api/bare/call/${callSid}/end`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${appState.apiToken}`
                }
            });

            if (!response.ok) {
                throw new Error('Failed to end call');
            }

            document.getElementById('callStatusText').textContent = 'Call ended';
            setTimeout(() => this.resetCallMonitoring(), 2000);

        } catch (error) {
            console.error('Call end error:', error);
            this.showAlert('Failed to end call: ' + error.message, 'danger');
        }
    }

    resetCallMonitoring() {
        // Remove call monitoring UI
        const monitoring = document.getElementById('call-monitoring');
        if (monitoring) {
            monitoring.remove();
        }

        // Clear status interval
        if (this.statusInterval) {
            clearInterval(this.statusInterval);
            this.statusInterval = null;
        }
    }
}

// Initialize SurveyManager on page load
window.surveyManager = new SurveyManager(); 