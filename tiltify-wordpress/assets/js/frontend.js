/**
 * Frontend JavaScript for Tiltify WordPress
 * Handles AJAX live updates and user interactions
 */

(function($) {
    'use strict';

    /**
     * Tiltify Frontend Handler
     */
    var TiltifyFrontend = {

        /**
         * Initialize the frontend functionality
         */
        init: function() {
            this.bindEvents();
            this.startLiveUpdates();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Handle visibility change to pause/resume updates when tab is hidden
            document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this));
            
            // Handle window focus/blur for additional performance optimization
            $(window).on('focus', this.handleWindowFocus.bind(this));
            $(window).on('blur', this.handleWindowBlur.bind(this));
            
            // Handle responsive updates on window resize
            $(window).on('resize', this.debounce(this.handleResize.bind(this), 250));
        },

        /**
         * Start live updates for elements that have live update enabled
         */
        startLiveUpdates: function() {
            var self = this;
            
            // Check if live updates are enabled
            if (!tiltifyAjax.live_updates_enabled) {
                return;
            }
            
            // Find all elements that need live updates
            var $liveElements = $('.tiltify-live-update');
            
            if ($liveElements.length === 0) {
                return;
            }

            // Group elements by campaign ID for efficient batch updates
            this.campaignGroups = {};
            $liveElements.each(function() {
                var $el = $(this);
                var campaignId = $el.data('campaign-id');
                
                if (campaignId) {
                    if (!self.campaignGroups[campaignId]) {
                        self.campaignGroups[campaignId] = [];
                    }
                    self.campaignGroups[campaignId].push($el);
                }
            });

            // Start the update interval
            this.updateInterval = setInterval(function() {
                if (!document.hidden && !self.isPaused) {
                    self.performLiveUpdates();
                }
            }, tiltifyAjax.refresh_interval || 30000);

            // Perform initial update after a short delay
            setTimeout(function() {
                self.performLiveUpdates();
            }, 2000);
        },

        /**
         * Perform live updates for all campaign groups
         */
        performLiveUpdates: function() {
            var self = this;
            
            Object.keys(this.campaignGroups).forEach(function(campaignId) {
                self.updateCampaignData(campaignId, self.campaignGroups[campaignId]);
            });
        },

        /**
         * Update data for a specific campaign
         */
        updateCampaignData: function(campaignId, elements) {
            var self = this;
            
            // Add loading state
            elements.forEach(function($el) {
                $el.addClass('tiltify-loading');
            });

            // Make AJAX request
            $.ajax({
                url: tiltifyAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'tiltify_update_data',
                    campaign_id: campaignId,
                    nonce: tiltifyAjax.nonce
                },
                timeout: 10000, // 10 second timeout
                success: function(response) {
                    if (response.success) {
                        self.updateElements(elements, response.data);
                    } else {
                        console.warn('Tiltify update failed:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Tiltify AJAX error:', error);
                    self.handleUpdateError(elements);
                },
                complete: function() {
                    // Remove loading state
                    elements.forEach(function($el) {
                        $el.removeClass('tiltify-loading');
                    });
                }
            });
        },

        /**
         * Update DOM elements with new data
         */
        updateElements: function(elements, data) {
            var self = this;
            
            elements.forEach(function($el) {
                var type = $el.data('type');
                
                switch (type) {
                    case 'amount':
                        self.updateAmount($el, data);
                        break;
                    case 'goal':
                        self.updateGoal($el, data);
                        break;
                    case 'progress':
                        self.updateProgress($el, data);
                        break;
                    case 'campaign_info':
                        self.updateCampaignInfo($el, data);
                        break;
                    case 'recent_donations':
                        self.updateRecentDonations($el, data);
                        break;
                    case 'widget':
                        self.updateWidget($el, data);
                        break;
                }
            });
        },

        /**
         * Update amount display
         */
        updateAmount: function($el, data) {
            var currentAmount = $el.text().replace(/[^0-9.]/g, '');
            var newAmount = data.amount_raised_formatted;
            
            if (currentAmount !== newAmount) {
                $el.fadeOut(200, function() {
                    $el.text(newAmount).fadeIn(200);
                });
            }
        },

        /**
         * Update goal display
         */
        updateGoal: function($el, data) {
            $el.text(data.goal_formatted);
        },

        /**
         * Update progress bar
         */
        updateProgress: function($el, data) {
            // Update amounts
            $el.find('.tiltify-raised').text(data.amount_raised_formatted);
            $el.find('.tiltify-goal-text').html('of ' + data.goal_formatted);
            $el.find('.tiltify-percentage').text('(' + data.percentage + '%)');
            
            // Animate progress bar
            var $progressFill = $el.find('.tiltify-progress-fill');
            $progressFill.animate({
                width: data.percentage + '%'
            }, 1000, 'easeOutQuart');
            
            // Update percentage display
            $el.find('.tiltify-progress-percentage').text(data.percentage + '%');
        },

        /**
         * Update campaign info display
         */
        updateCampaignInfo: function($el, data) {
            // Update progress within campaign info
            var $progress = $el.find('.tiltify-campaign-progress .tiltify-progress-container');
            if ($progress.length) {
                this.updateProgress($progress, data);
            }
            
            // Update stats
            $el.find('.tiltify-stat').text(data.total_donations + ' supporters');
        },

        /**
         * Update recent donations
         */
        updateRecentDonations: function($el, data) {
            // This would require a separate API call for donations
            // For now, we'll skip this to avoid too many API calls
        },

        /**
         * Update widget display
         */
        updateWidget: function($el, data) {
            // Update raised amount
            $el.find('.tiltify-raised-amount strong').text(data.amount_raised_formatted);
            
            // Update goal
            $el.find('.tiltify-goal-amount').html('of ' + data.goal_formatted + ' goal');
            
            // Update progress bar
            $el.find('.tiltify-progress-fill').animate({
                width: data.percentage + '%'
            }, 1000);
            
            // Update percentage
            $el.find('.tiltify-progress-percentage').text(data.percentage + '%');
            
            // Update supporter count
            $el.find('.tiltify-stat-value').text(this.formatNumber(data.total_donations));
        },

        /**
         * Handle update errors
         */
        handleUpdateError: function(elements) {
            // Optionally show error state or retry logic
            console.warn('Failed to update Tiltify data');
        },

        /**
         * Handle window visibility changes
         */
        handleVisibilityChange: function() {
            if (document.hidden) {
                this.pauseUpdates();
            } else {
                this.resumeUpdates();
                // Trigger immediate update when tab becomes visible
                setTimeout(this.performLiveUpdates.bind(this), 1000);
            }
        },

        /**
         * Handle window focus
         */
        handleWindowFocus: function() {
            this.resumeUpdates();
        },

        /**
         * Handle window blur
         */
        handleWindowBlur: function() {
            this.pauseUpdates();
        },

        /**
         * Handle window resize
         */
        handleResize: function() {
            // Trigger any responsive adjustments if needed
            this.adjustResponsiveElements();
        },

        /**
         * Pause live updates
         */
        pauseUpdates: function() {
            this.isPaused = true;
        },

        /**
         * Resume live updates
         */
        resumeUpdates: function() {
            this.isPaused = false;
        },

        /**
         * Stop all live updates
         */
        stopUpdates: function() {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
                this.updateInterval = null;
            }
        },

        /**
         * Adjust responsive elements
         */
        adjustResponsiveElements: function() {
            // Add any responsive adjustments here
            var $progressContainers = $('.tiltify-progress-container');
            
            $progressContainers.each(function() {
                var $container = $(this);
                var width = $container.width();
                
                if (width < 300) {
                    $container.addClass('tiltify-compact');
                } else {
                    $container.removeClass('tiltify-compact');
                }
            });
        },

        /**
         * Format numbers for display
         */
        formatNumber: function(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        },

        /**
         * Debounce function for performance
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
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        TiltifyFrontend.init();
    });

    /**
     * Cleanup on page unload
     */
    $(window).on('beforeunload', function() {
        TiltifyFrontend.stopUpdates();
    });

    // Make TiltifyFrontend globally available for debugging
    window.TiltifyFrontend = TiltifyFrontend;

})(jQuery);

/**
 * Add custom easing function for smooth animations
 */
jQuery.easing.easeOutQuart = function (x, t, b, c, d) {
    return -c * ((t=t/d-1)*t*t*t - 1) + b;
};