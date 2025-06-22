/**
 * WP Chat AI - Public JavaScript
 * 
 * Handles the frontend chat interface functionality
 */

(function($) {
    'use strict';

    // Chat widget object
    const WPChatAI = {
        
        // Configuration
        config: {
            isOpen: false,
            isMinimized: false,
            isLoading: false,
            messageHistory: [],
            typingIndicatorTimeout: null,
            autoCloseTimeout: null
        },

        // DOM elements
        elements: {
            widget: null,
            toggle: null,
            window: null,
            messages: null,
            form: null,
            input: null,
            submit: null,
            submitText: null,
            submitLoading: null,
            minimize: null,
            close: null
        },

        /**
         * Initialize the chat widget
         */
        init: function() {
            this.bindElements();
            this.bindEvents();
            this.loadMessageHistory();
            this.setupAutoClose();
        },

        /**
         * Bind DOM elements
         */
        bindElements: function() {
            this.elements.widget = $('#wpcai-chat-widget');
            this.elements.toggle = $('#wpcai-chat-toggle');
            this.elements.window = $('#wpcai-chat-window');
            this.elements.messages = $('#wpcai-chat-messages');
            this.elements.form = $('#wpcai-chat-form');
            this.elements.input = $('#wpcai-chat-input');
            this.elements.submit = $('#wpcai-chat-submit');
            this.elements.submitText = this.elements.submit.find('.wpcai-submit-text');
            this.elements.submitLoading = this.elements.submit.find('.wpcai-submit-loading');
            this.elements.minimize = $('#wpcai-chat-minimize');
            this.elements.close = $('#wpcai-chat-close');
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;

            // Toggle chat window
            this.elements.toggle.on('click', function(e) {
                e.preventDefault();
                self.toggleChat();
            });

            // Minimize chat
            this.elements.minimize.on('click', function(e) {
                e.preventDefault();
                self.minimizeChat();
            });

            // Close chat
            this.elements.close.on('click', function(e) {
                e.preventDefault();
                self.closeChat();
            });

            // Form submission
            this.elements.form.on('submit', function(e) {
                e.preventDefault();
                self.sendMessage();
            });

            // Input events
            this.elements.input.on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });

            this.elements.input.on('input', function() {
                self.handleTyping();
            });

            // Click outside to close (optional)
            $(document).on('click', function(e) {
                if (self.config.isOpen && 
                    !self.elements.widget.is(e.target) && 
                    self.elements.widget.has(e.target).length === 0) {
                    // Uncomment to enable click-outside-to-close
                    // self.closeChat();
                }
            });

            // Escape key to close
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.config.isOpen) {
                    self.closeChat();
                }
            });

            // Window resize handler
            $(window).on('resize', function() {
                self.adjustChatPosition();
            });
        },

        /**
         * Toggle chat window open/closed
         */
        toggleChat: function() {
            if (this.config.isOpen) {
                this.closeChat();
            } else {
                this.openChat();
            }
        },

        /**
         * Open chat window
         */
        openChat: function() {
            if (this.config.isOpen) return;

            this.config.isOpen = true;
            this.config.isMinimized = false;

            this.elements.window.css('display', 'flex').hide().fadeIn(300);
            this.elements.toggle.addClass('wpcai-chat-open');
            
            // Focus input after animation
            setTimeout(() => {
                this.elements.input.focus();
            }, 350);

            // Clear auto-close timeout
            if (this.config.autoCloseTimeout) {
                clearTimeout(this.config.autoCloseTimeout);
            }

            // Track opening
            this.trackEvent('chat_opened');
        },

        /**
         * Close chat window
         */
        closeChat: function() {
            if (!this.config.isOpen) return;

            this.config.isOpen = false;
            this.config.isMinimized = false;

            this.elements.window.fadeOut(300);
            this.elements.toggle.removeClass('wpcai-chat-open');

            // Setup auto-close timeout
            this.setupAutoClose();

            // Track closing
            this.trackEvent('chat_closed');
        },

        /**
         * Minimize chat window
         */
        minimizeChat: function() {
            if (!this.config.isOpen) return;

            this.config.isMinimized = true;
            this.elements.window.slideUp(300);
            this.elements.toggle.addClass('wpcai-chat-minimized');

            // Track minimizing
            this.trackEvent('chat_minimized');
        },

        /**
         * Send a message
         */
        sendMessage: function() {
            const message = this.elements.input.val().trim();

            if (!message || this.config.isLoading) {
                return;
            }

            // Validate message length
            if (message.length > 500) {
                this.showError(wpcai_public.strings.error);
                return;
            }

            // Add user message to chat
            this.addMessage(message, 'user');

            // Clear input
            this.elements.input.val('');

            // Show loading state
            this.setLoadingState(true);

            // Send AJAX request
            this.sendAjaxRequest(message);

            // Track message sent
            this.trackEvent('message_sent', { message_length: message.length });
        },

        /**
         * Send AJAX request to backend
         */
        sendAjaxRequest: function(question) {
            const self = this;

            $.ajax({
                url: wpcai_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpcai_ask_question',
                    question: question,
                    nonce: wpcai_public.ask_nonce
                },
                timeout: 30000, // 30 seconds timeout
                success: function(response) {
                    self.setLoadingState(false);

                    if (response.success) {
                        self.addMessage(response.data.answer, 'bot', {
                            confidence: response.data.confidence,
                            source_title: response.data.source_title,
                            source_url: response.data.source_url
                        });

                        // Track successful response
                        self.trackEvent('response_received', {
                            confidence: response.data.confidence
                        });
                    } else {
                        self.showError(response.data.message || wpcai_public.strings.error);
                    }
                },
                error: function(xhr, status, error) {
                    self.setLoadingState(false);
                    
                    let errorMessage = wpcai_public.strings.error;
                    
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Please try again.';
                    } else if (xhr.status === 0) {
                        errorMessage = 'Network error. Please check your connection.';
                    }
                    
                    self.showError(errorMessage);

                    // Track error
                    self.trackEvent('request_error', {
                        status: status,
                        error: error
                    });
                }
            });
        },

        /**
         * Add a message to the chat
         */
        addMessage: function(content, type, metadata = {}) {
            const messageEl = $('<div>').addClass('wpcai-message wpcai-message-' + type);
            const contentEl = $('<div>').addClass('wpcai-message-content');

            // Add message content
            contentEl.html(this.formatMessageContent(content, type, metadata));
            messageEl.append(contentEl);

            // Add timestamp
            const timestamp = $('<div>').addClass('wpcai-message-timestamp')
                .text(this.formatTimestamp(new Date()));
            messageEl.append(timestamp);

            // Add to messages container with animation
            this.elements.messages.append(messageEl);
            messageEl.hide().slideDown(300);

            // Scroll to bottom
            this.scrollToBottom();

            // Save to history
            this.config.messageHistory.push({
                content: content,
                type: type,
                timestamp: Date.now(),
                metadata: metadata
            });

            // Limit history size
            if (this.config.messageHistory.length > 50) {
                this.config.messageHistory = this.config.messageHistory.slice(-50);
            }

            this.saveMessageHistory();
        },

        /**
         * Format message content
         */
        formatMessageContent: function(content, type, metadata) {
            let formattedContent = this.escapeHtml(content);

            // Convert line breaks to <br>
            formattedContent = formattedContent.replace(/\n/g, '<br>');

            // Add source information for bot messages
            if (type === 'bot' && metadata.source_title && metadata.source_url) {
                const sourceLink = `<a href="${this.escapeHtml(metadata.source_url)}" target="_blank" rel="noopener">${this.escapeHtml(metadata.source_title)}</a>`;
                formattedContent = formattedContent.replace(
                    new RegExp(`Source: ${this.escapeHtml(metadata.source_title)}`, 'g'),
                    `Source: ${sourceLink}`
                );
            }

            // Add confidence indicator for bot messages
            if (type === 'bot' && metadata.confidence !== undefined) {
                const confidenceClass = metadata.confidence > 0.7 ? 'high' : 
                                      metadata.confidence > 0.4 ? 'medium' : 'low';
                const confidenceEl = `<div class="wpcai-confidence wpcai-confidence-${confidenceClass}" title="Confidence: ${Math.round(metadata.confidence * 100)}%"></div>`;
                formattedContent += confidenceEl;
            }

            return formattedContent;
        },

        /**
         * Show error message
         */
        showError: function(message) {
            this.addMessage(message, 'error');
        },

        /**
         * Set loading state
         */
        setLoadingState: function(loading) {
            this.config.isLoading = loading;

            if (loading) {
                this.elements.submit.prop('disabled', true);
                this.elements.submitText.hide();
                this.elements.submitLoading.show();
                this.elements.input.prop('disabled', true);
                
                // Add typing indicator
                this.addTypingIndicator();
            } else {
                this.elements.submit.prop('disabled', false);
                this.elements.submitText.show();
                this.elements.submitLoading.hide();
                this.elements.input.prop('disabled', false);
                
                // Remove typing indicator
                this.removeTypingIndicator();
            }
        },

        /**
         * Add typing indicator
         */
        addTypingIndicator: function() {
            if (this.elements.messages.find('.wpcai-typing-indicator').length > 0) {
                return;
            }

            const typingEl = $(`
                <div class="wpcai-message wpcai-message-bot wpcai-typing-indicator">
                    <div class="wpcai-message-content">
                        <div class="wpcai-typing-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            `);

            this.elements.messages.append(typingEl);
            typingEl.hide().slideDown(300);
            this.scrollToBottom();
        },

        /**
         * Remove typing indicator
         */
        removeTypingIndicator: function() {
            this.elements.messages.find('.wpcai-typing-indicator').slideUp(300, function() {
                $(this).remove();
            });
        },

        /**
         * Handle typing events
         */
        handleTyping: function() {
            // Clear existing timeout
            if (this.config.typingIndicatorTimeout) {
                clearTimeout(this.config.typingIndicatorTimeout);
            }

            // Set new timeout
            this.config.typingIndicatorTimeout = setTimeout(() => {
                // Could add "user is typing" indicator here if needed
            }, 1000);
        },

        /**
         * Scroll messages to bottom
         */
        scrollToBottom: function() {
            const messagesEl = this.elements.messages[0];
            messagesEl.scrollTop = messagesEl.scrollHeight;
        },

        /**
         * Adjust chat position for mobile
         */
        adjustChatPosition: function() {
            if ($(window).width() <= 480) {
                this.elements.widget.addClass('wpcai-mobile');
            } else {
                this.elements.widget.removeClass('wpcai-mobile');
            }
        },

        /**
         * Setup auto-close timeout
         */
        setupAutoClose: function() {
            // Auto-close after 5 minutes of inactivity
            this.config.autoCloseTimeout = setTimeout(() => {
                if (this.config.isOpen) {
                    this.closeChat();
                }
            }, 300000); // 5 minutes
        },

        /**
         * Load message history from localStorage
         */
        loadMessageHistory: function() {
            try {
                const history = localStorage.getItem('wpcai_message_history');
                if (history) {
                    this.config.messageHistory = JSON.parse(history);
                    
                    // Restore recent messages (last 10)
                    const recentMessages = this.config.messageHistory.slice(-10);
                    
                    // Clear welcome message if we have history
                    if (recentMessages.length > 0) {
                        this.elements.messages.empty();
                    }
                    
                    recentMessages.forEach(msg => {
                        this.addMessageToDOM(msg.content, msg.type, msg.metadata || {});
                    });
                }
            } catch (e) {
                console.warn('Failed to load chat history:', e);
            }
        },

        /**
         * Save message history to localStorage
         */
        saveMessageHistory: function() {
            try {
                localStorage.setItem('wpcai_message_history', JSON.stringify(this.config.messageHistory));
            } catch (e) {
                console.warn('Failed to save chat history:', e);
            }
        },

        /**
         * Add message to DOM without animation (for history restoration)
         */
        addMessageToDOM: function(content, type, metadata) {
            const messageEl = $('<div>').addClass('wpcai-message wpcai-message-' + type);
            const contentEl = $('<div>').addClass('wpcai-message-content');

            contentEl.html(this.formatMessageContent(content, type, metadata));
            messageEl.append(contentEl);

            this.elements.messages.append(messageEl);
        },

        /**
         * Format timestamp
         */
        formatTimestamp: function(date) {
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },

        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Track events (for analytics)
         */
        trackEvent: function(event, data = {}) {
            // Could integrate with Google Analytics, etc.
            if (typeof gtag !== 'undefined') {
                gtag('event', event, {
                    event_category: 'wp_chat_ai',
                    ...data
                });
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        WPChatAI.init();
    });

    // Expose to global scope for debugging
    window.WPChatAI = WPChatAI;

})(jQuery);

