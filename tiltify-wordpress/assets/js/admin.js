/**
 * Admin JavaScript for Tiltify WordPress
 * Handles admin functionality like connection testing and cache clearing
 */

(function($) {
    'use strict';

    /**
     * Tiltify Admin Handler
     */
    var TiltifyAdmin = {

        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Test connection button
            $('#tiltify-test-connection').on('click', this.testConnection.bind(this));
            
            // Clear cache button
            $('#tiltify-clear-cache').on('click', this.clearCache.bind(this));
            
            // Auto-save settings when typing (with debounce)
            $('#tiltify_client_id, #tiltify_client_secret, #tiltify_campaign_id').on('input', 
                this.debounce(this.validateFields.bind(this), 500)
            );
            
            // Form submission validation
            $('form').on('submit', this.validateFormSubmission.bind(this));
            
            // Show/hide API token
            this.setupPasswordToggle();
        },

        /**
         * Test API connection
         */
        testConnection: function(e) {
            e.preventDefault();
            
            var $button = $('#tiltify-test-connection');
            var $result = $('#tiltify-test-result');
            var clientId = $('#tiltify_client_id').val();
            var clientSecret = $('#tiltify_client_secret').val();
            var campaignId = $('#tiltify_campaign_id').val();
            
            // Validate inputs
            if (!campaignId.trim()) {
                this.showResult($result, 'error', 'Campaign ID is required');
                return;
            }
            
            // Show loading state
            $button.addClass('tiltify-loading').prop('disabled', true);
            $result.removeClass('success error').addClass('loading')
                   .text('Testing connection...');
            
            // Make AJAX request
            $.ajax({
                url: tiltifyAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'tiltify_test_connection',
                    client_id: clientId,
                    client_secret: clientSecret,
                    campaign_id: campaignId,
                    nonce: tiltifyAdmin.nonce
                },
                timeout: 15000,
                success: function(response) {
                    if (response.success) {
                        var message = response.message;
                        if (response.campaign_name) {
                            message += ' Campaign: ' + response.campaign_name;
                        }
                        TiltifyAdmin.showResult($result, 'success', message);
                    } else {
                        TiltifyAdmin.showResult($result, 'error', response.message || 'Connection failed');
                    }
                },
                error: function(xhr, status, error) {
                    var message = 'Connection failed';
                    if (status === 'timeout') {
                        message = 'Connection timeout - please try again';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    TiltifyAdmin.showResult($result, 'error', message);
                },
                complete: function() {
                    $button.removeClass('tiltify-loading').prop('disabled', false);
                    $result.removeClass('loading');
                }
            });
        },

        /**
         * Clear cache
         */
        clearCache: function(e) {
            e.preventDefault();
            
            var $button = $('#tiltify-clear-cache');
            var $result = $('#tiltify-cache-result');
            
            // Show loading state
            $button.addClass('tiltify-loading').prop('disabled', true);
            $result.removeClass('success error').addClass('loading')
                   .text('Clearing cache...');
            
            // Make AJAX request
            $.ajax({
                url: tiltifyAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'tiltify_clear_cache',
                    nonce: tiltifyAdmin.nonce
                },
                timeout: 10000,
                success: function(response) {
                    if (response.success) {
                        TiltifyAdmin.showResult($result, 'success', response.data.message);
                    } else {
                        TiltifyAdmin.showResult($result, 'error', 'Failed to clear cache');
                    }
                },
                error: function(xhr, status, error) {
                    TiltifyAdmin.showResult($result, 'error', 'Failed to clear cache');
                },
                complete: function() {
                    $button.removeClass('tiltify-loading').prop('disabled', false);
                    $result.removeClass('loading');
                }
            });
        },

        /**
         * Validate form fields
         */
        validateFields: function() {
            var $clientId = $('#tiltify_client_id');
            var $campaignId = $('#tiltify_campaign_id');
            
            // Campaign ID validation
            var campaignId = $campaignId.val().trim();
            if (campaignId) {
                if (!/^[a-zA-Z0-9\-_]+$/.test(campaignId)) {
                    this.showFieldError($campaignId, 'Campaign ID contains invalid characters');
                } else {
                    this.clearFieldError($campaignId);
                }
            } else {
                this.clearFieldError($campaignId);
            }
            
            // Client ID validation (optional)
            var clientId = $clientId.val().trim();
            if (clientId && clientId.length < 5) {
                this.showFieldError($clientId, 'Client ID appears to be too short');
            } else {
                this.clearFieldError($clientId);
            }
        },

        /**
         * Validate form submission
         */
        validateFormSubmission: function(e) {
            var hasErrors = false;
            var $campaignId = $('#tiltify_campaign_id');
            
            // Campaign ID is required
            if (!$campaignId.val().trim()) {
                this.showFieldError($campaignId, 'Campaign ID is required');
                hasErrors = true;
            }
            
            if (hasErrors) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $('.form-table').offset().top - 50
                }, 500);
            }
        },

        /**
         * Setup password field toggle
         */
        setupPasswordToggle: function() {
            var $clientSecretField = $('#tiltify_client_secret');
            var $toggleButton = $('<button type="button" class="button" style="margin-left: 5px;">Show</button>');
            
            $clientSecretField.after($toggleButton);
            
            $toggleButton.on('click', function() {
                var $field = $clientSecretField;
                var currentType = $field.attr('type');
                
                if (currentType === 'password') {
                    $field.attr('type', 'text');
                    $(this).text('Hide');
                } else {
                    $field.attr('type', 'password');
                    $(this).text('Show');
                }
            });
        },

        /**
         * Show result message
         */
        showResult: function($element, type, message) {
            $element.removeClass('success error loading')
                    .addClass(type)
                    .text(message);
            
            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(function() {
                    $element.fadeOut();
                }, 5000);
            }
        },

        /**
         * Show field error
         */
        showFieldError: function($field, message) {
            $field.addClass('error');
            
            // Remove existing error message
            $field.siblings('.field-error').remove();
            
            // Add new error message
            var $error = $('<div class="field-error" style="color: #d63638; font-size: 12px; margin-top: 3px;">' + message + '</div>');
            $field.after($error);
        },

        /**
         * Clear field error
         */
        clearFieldError: function($field) {
            $field.removeClass('error');
            $field.siblings('.field-error').remove();
        },

        /**
         * Debounce function
         */
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            type = type || 'success';
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.wrap h1').after($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Handle manual dismiss
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            });
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        TiltifyAdmin.init();
    });

    // Make TiltifyAdmin globally available
    window.TiltifyAdmin = TiltifyAdmin;

})(jQuery);