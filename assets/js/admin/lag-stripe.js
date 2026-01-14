/**
 * MinSponsor - Lag Stripe Connect Admin JS
 * 
 * Handles AJAX interactions for Stripe Connect meta box on Lag CPT.
 * 
 * @package MinSponsor
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    const LagStripe = {
        
        /**
         * Initialize event handlers
         */
        init: function() {
            this.$container = $('.minsponsor-stripe-meta');
            if (!this.$container.length) return;
            
            this.lagId = this.$container.data('lag-id');
            this.$loading = this.$container.find('.minsponsor-stripe-loading');
            this.$message = this.$container.find('.minsponsor-stripe-message');
            this.$actions = this.$container.find('.minsponsor-stripe-actions');
            
            this.bindEvents();
        },
        
        /**
         * Bind click events
         */
        bindEvents: function() {
            this.$container.on('click', '.minsponsor-start-onboarding', this.startOnboarding.bind(this));
            this.$container.on('click', '.minsponsor-refresh-status', this.refreshStatus.bind(this));
            this.$container.on('click', '.minsponsor-copy-link', this.copyLink.bind(this));
        },
        
        /**
         * Show loading state
         */
        showLoading: function() {
            this.$actions.hide();
            this.$loading.show();
            this.$message.hide();
        },
        
        /**
         * Hide loading state
         */
        hideLoading: function() {
            this.$loading.hide();
            this.$actions.show();
        },
        
        /**
         * Show message
         */
        showMessage: function(text, type) {
            const bgColor = type === 'success' ? '#d4edda' : '#f8d7da';
            const textColor = type === 'success' ? '#155724' : '#721c24';
            
            this.$message
                .text(text)
                .css({ background: bgColor, color: textColor })
                .show();
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                this.$message.fadeOut();
            }, 5000);
        },
        
        /**
         * Start Stripe onboarding
         */
        startOnboarding: function(e) {
            e.preventDefault();
            
            if (!confirm('This will create a Stripe Express account for the team. Continue?')) {
                return;
            }
            
            this.showLoading();
            
            $.ajax({
                url: minsponsorLagStripe.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'minsponsor_start_onboarding',
                    nonce: minsponsorLagStripe.nonce,
                    lag_id: this.lagId
                },
                success: (response) => {
                    this.hideLoading();
                    
                    if (response.success) {
                        this.showMessage(response.data.message, 'success');
                        
                        // Open onboarding URL in new window
                        if (response.data.onboarding_url) {
                            window.open(response.data.onboarding_url, '_blank');
                        }
                        
                        // Reload page to show updated status
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        this.showMessage(response.data.message || 'An error occurred', 'error');
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showMessage('Network error - please try again', 'error');
                }
            });
        },
        
        /**
         * Refresh Stripe status
         */
        refreshStatus: function(e) {
            e.preventDefault();
            this.showLoading();
            
            $.ajax({
                url: minsponsorLagStripe.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'minsponsor_refresh_stripe_status',
                    nonce: minsponsorLagStripe.nonce,
                    lag_id: this.lagId
                },
                success: (response) => {
                    this.hideLoading();
                    
                    if (response.success) {
                        this.showMessage('Status updated: ' + response.data.message, 'success');
                        // Reload to show any status changes
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        this.showMessage(response.data.message || 'Could not update status', 'error');
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showMessage('Network error - please try again', 'error');
                }
            });
        },
        
        /**
         * Copy onboarding link to clipboard
         */
        copyLink: function(e) {
            e.preventDefault();
            const link = $(e.currentTarget).data('link');
            
            if (!link) {
                this.showMessage('No link to copy', 'error');
                return;
            }
            
            // Use modern clipboard API if available
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(link)
                    .then(() => {
                        this.showMessage('Link copied to clipboard!', 'success');
                    })
                    .catch(() => {
                        this.fallbackCopy(link);
                    });
            } else {
                this.fallbackCopy(link);
            }
        },
        
        /**
         * Fallback copy method for older browsers
         */
        fallbackCopy: function(text) {
            const $temp = $('<input>');
            $('body').append($temp);
            $temp.val(text).select();
            
            try {
                document.execCommand('copy');
                this.showMessage('Link copied to clipboard!', 'success');
            } catch (err) {
                this.showMessage('Could not copy - copy manually: ' + text, 'error');
            }
            
            $temp.remove();
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        LagStripe.init();
    });
    
})(jQuery);
