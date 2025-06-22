/**
 * WP Chat AI - Admin JavaScript
 * 
 * Handles the admin interface functionality
 */

(function($) {
    'use strict';

    // Admin object
    const WPChatAIAdmin = {
        
        // Configuration
        config: {
            statusCheckInterval: null,
            isTraining: false,
            lastStatusCheck: 0
        },

        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initStatusCheck();
            this.initTooltips();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;

            // Train model button
            $('#wpcai-train-model').on('click', function(e) {
                e.preventDefault();
                self.startTraining();
            });

            // Refresh status button
            $('#wpcai-refresh-status').on('click', function(e) {
                e.preventDefault();
                self.checkTrainingStatus();
            });

            // Settings form validation
            $('form[action=""]').on('submit', function(e) {
                if (!self.validateSettings()) {
                    e.preventDefault();
                }
            });

            // Confidence threshold slider
            $('input[name="wpcai_confidence_threshold"]').on('input', function() {
                self.updateConfidenceDisplay($(this).val());
            });

            // Auto-save settings (debounced)
            let saveTimeout;
            $('.form-table input, .form-table select, .form-table textarea').on('change', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(function() {
                    self.autoSaveSettings();
                }, 2000);
            });
        },

        /**
         * Start model training
         */
        startTraining: function() {
            const self = this;
            const button = $('#wpcai-train-model');
            const messages = $('#wpcai-training-messages');

            if (this.config.isTraining) {
                return;
            }

            // Update UI
            button.prop('disabled', true).text(wpcai_admin.strings.training_started);
            this.config.isTraining = true;
            
            // Clear previous messages
            messages.empty();

            // Show loading message
            this.showMessage('info', wpcai_admin.strings.training_started);

            // Send AJAX request
            $.ajax({
                url: wpcai_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpcai_train_model',
                    nonce: wpcai_admin.train_nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showMessage('success', response.data.message);
                        
                        // Start status checking
                        self.startStatusChecking();
                    } else {
                        self.showMessage('error', response.data.message || wpcai_admin.strings.training_error);
                        self.resetTrainingButton();
                    }
                },
                error: function(xhr, status, error) {
                    self.showMessage('error', wpcai_admin.strings.training_error);
                    self.resetTrainingButton();
                    console.error('Training request failed:', error);
                }
            });
        },

        /**
         * Check training status
         */
        checkTrainingStatus: function() {
            const self = this;

            $.ajax({
                url: wpcai_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpcai_get_training_status',
                    nonce: wpcai_admin.status_nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateStatusDisplay(response.data);
                    } else {
                        console.error('Status check failed:', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Status request failed:', error);
                }
            });
        },

        /**
         * Update status display
         */
        updateStatusDisplay: function(data) {
            // Update status indicator
            const statusEl = $('.wpcai-status');
            statusEl.removeClass('wpcai-status-pending wpcai-status-training wpcai-status-completed wpcai-status-error')
                   .addClass('wpcai-status-' + data.status)
                   .text(this.capitalizeFirst(data.status));

            // Update progress information
            if (data.total_posts !== undefined) {
                $('td:contains("' + data.total_posts + '")').text(data.total_posts);
            }
            
            if (data.trained_posts !== undefined) {
                $('td:contains("' + data.trained_posts + '")').text(data.trained_posts);
            }
            
            if (data.progress !== undefined) {
                $('td:contains("' + data.progress + '%")').text(data.progress + '%');
                this.updateProgressBar(data.progress);
            }

            // Update last training time
            if (data.last_training) {
                const lastTrainingRow = $('th:contains("Last Training")').closest('tr');
                if (lastTrainingRow.length === 0) {
                    // Add row if it doesn't exist
                    $('.form-table').append(`
                        <tr>
                            <th scope="row">Last Training</th>
                            <td>${data.last_training}</td>
                        </tr>
                    `);
                } else {
                    lastTrainingRow.find('td').text(data.last_training);
                }
            }

            // Handle training completion
            if (data.status === 'completed') {
                this.resetTrainingButton();
                this.showMessage('success', 'Training completed successfully!');
                this.stopStatusChecking();
            } else if (data.status === 'error') {
                this.resetTrainingButton();
                this.showMessage('error', 'Training failed. Please try again.');
                this.stopStatusChecking();
            }

            this.config.lastStatusCheck = Date.now();
        },

        /**
         * Start automatic status checking
         */
        startStatusChecking: function() {
            const self = this;
            
            this.config.statusCheckInterval = setInterval(function() {
                self.checkTrainingStatus();
            }, 3000); // Check every 3 seconds
        },

        /**
         * Stop automatic status checking
         */
        stopStatusChecking: function() {
            if (this.config.statusCheckInterval) {
                clearInterval(this.config.statusCheckInterval);
                this.config.statusCheckInterval = null;
            }
        },

        /**
         * Reset training button
         */
        resetTrainingButton: function() {
            $('#wpcai-train-model').prop('disabled', false).text('Start Training');
            this.config.isTraining = false;
        },

        /**
         * Initialize status checking on page load
         */
        initStatusCheck: function() {
            // Check status immediately
            this.checkTrainingStatus();

            // If training is in progress, start automatic checking
            const currentStatus = $('.wpcai-status').text().toLowerCase();
            if (currentStatus === 'training') {
                this.config.isTraining = true;
                $('#wpcai-train-model').prop('disabled', true).text(wpcai_admin.strings.training_started);
                this.startStatusChecking();
            }
        },

        /**
         * Show message to user
         */
        showMessage: function(type, message) {
            const messages = $('#wpcai-training-messages');
            const messageEl = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);

            messages.append(messageEl);

            // Auto-dismiss after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(function() {
                    messageEl.fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }

            // Handle dismiss button
            messageEl.find('.notice-dismiss').on('click', function() {
                messageEl.fadeOut(function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Update progress bar
         */
        updateProgressBar: function(progress) {
            let progressBar = $('.wpcai-progress-bar');
            
            if (progressBar.length === 0) {
                // Create progress bar if it doesn't exist
                const progressContainer = $(`
                    <div class="wpcai-progress-container">
                        <div class="wpcai-progress-bar">
                            <div class="wpcai-progress-fill"></div>
                        </div>
                        <span class="wpcai-progress-text">${progress}%</span>
                    </div>
                `);
                
                $('th:contains("Progress")').closest('tr').find('td').append(progressContainer);
                progressBar = $('.wpcai-progress-bar');
            }

            // Update progress
            progressBar.find('.wpcai-progress-fill').css('width', progress + '%');
            progressBar.siblings('.wpcai-progress-text').text(progress + '%');
        },

        /**
         * Validate settings form
         */
        validateSettings: function() {
            let isValid = true;
            const errors = [];

            // Validate confidence threshold
            const confidence = parseFloat($('input[name="wpcai_confidence_threshold"]').val());
            if (isNaN(confidence) || confidence < 0 || confidence > 1) {
                errors.push('Confidence threshold must be between 0 and 1.');
                isValid = false;
            }

            // Validate max response length
            const maxLength = parseInt($('input[name="wpcai_max_response_length"]').val());
            if (isNaN(maxLength) || maxLength < 100 || maxLength > 2000) {
                errors.push('Max response length must be between 100 and 2000 characters.');
                isValid = false;
            }

            // Validate chat title
            const title = $('input[name="wpcai_chat_title"]').val().trim();
            if (title.length === 0) {
                errors.push('Chat title cannot be empty.');
                isValid = false;
            }

            // Show errors
            if (!isValid) {
                this.showValidationErrors(errors);
            }

            return isValid;
        },

        /**
         * Show validation errors
         */
        showValidationErrors: function(errors) {
            const errorList = errors.map(error => `<li>${error}</li>`).join('');
            const errorMessage = `
                <div class="notice notice-error">
                    <p><strong>Please fix the following errors:</strong></p>
                    <ul>${errorList}</ul>
                </div>
            `;

            $('.wrap h1').after(errorMessage);

            // Scroll to top
            $('html, body').animate({ scrollTop: 0 }, 500);
        },

        /**
         * Auto-save settings
         */
        autoSaveSettings: function() {
            // This could implement auto-save functionality
            // For now, just show a subtle indicator
            const indicator = $('<span class="wpcai-autosave-indicator">Saved</span>');
            $('.wrap h1').append(indicator);
            
            setTimeout(function() {
                indicator.fadeOut(function() {
                    $(this).remove();
                });
            }, 2000);
        },

        /**
         * Update confidence display
         */
        updateConfidenceDisplay: function(value) {
            let display = $('.wpcai-confidence-display');
            
            if (display.length === 0) {
                display = $('<span class="wpcai-confidence-display"></span>');
                $('input[name="wpcai_confidence_threshold"]').after(display);
            }

            const percentage = Math.round(value * 100);
            const quality = percentage > 70 ? 'High' : percentage > 40 ? 'Medium' : 'Low';
            
            display.text(` (${percentage}% - ${quality} Quality)`);
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            // Add tooltips to form fields
            $('input[name="wpcai_confidence_threshold"]').attr('title', 
                'Lower values show more results but may be less accurate. Higher values show fewer but more accurate results.');
            
            $('input[name="wpcai_max_response_length"]').attr('title', 
                'Maximum number of characters in chat responses. Longer responses provide more detail but may be harder to read.');
        },

        /**
         * Capitalize first letter
         */
        capitalizeFirst: function(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        WPChatAIAdmin.init();
    });

    // Expose to global scope for debugging
    window.WPChatAIAdmin = WPChatAIAdmin;

})(jQuery);

// Add admin styles
jQuery(document).ready(function($) {
    // Inject admin CSS
    const adminCSS = `
        <style>
            .wpcai-status {
                padding: 4px 12px;
                border-radius: 12px;
                font-weight: 600;
                font-size: 12px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .wpcai-status-pending {
                background: #ffc107;
                color: #856404;
            }
            
            .wpcai-status-training {
                background: #007cba;
                color: white;
                animation: pulse 2s infinite;
            }
            
            .wpcai-status-completed {
                background: #28a745;
                color: white;
            }
            
            .wpcai-status-error {
                background: #dc3545;
                color: white;
            }
            
            @keyframes pulse {
                0% { opacity: 1; }
                50% { opacity: 0.7; }
                100% { opacity: 1; }
            }
            
            .wpcai-progress-container {
                margin-top: 8px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .wpcai-progress-bar {
                flex: 1;
                height: 8px;
                background: #e9ecef;
                border-radius: 4px;
                overflow: hidden;
            }
            
            .wpcai-progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #007cba, #0056b3);
                transition: width 0.3s ease;
                border-radius: 4px;
            }
            
            .wpcai-progress-text {
                font-size: 12px;
                font-weight: 600;
                color: #6c757d;
                min-width: 40px;
            }
            
            .wpcai-autosave-indicator {
                margin-left: 10px;
                padding: 2px 8px;
                background: #28a745;
                color: white;
                border-radius: 4px;
                font-size: 12px;
                font-weight: 600;
            }
            
            .wpcai-confidence-display {
                font-size: 12px;
                color: #6c757d;
                font-style: italic;
            }
            
            .wpcai-training-info {
                margin-top: 30px;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
                border-left: 4px solid #007cba;
            }
            
            .wpcai-training-info h2 {
                margin-top: 0;
                color: #007cba;
            }
            
            .wpcai-training-info p {
                margin-bottom: 10px;
                line-height: 1.6;
            }
            
            #wpcai-training-messages .notice {
                margin: 10px 0;
            }
            
            .wp-list-table .wpcai-confidence-score {
                font-weight: 600;
            }
            
            .wp-list-table .wpcai-confidence-score.high {
                color: #28a745;
            }
            
            .wp-list-table .wpcai-confidence-score.medium {
                color: #ffc107;
            }
            
            .wp-list-table .wpcai-confidence-score.low {
                color: #dc3545;
            }
        </style>
    `;
    
    $('head').append(adminCSS);
});

