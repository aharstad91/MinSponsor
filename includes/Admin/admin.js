jQuery(document).ready(function($) {
    
    // Copy link functionality
    $('.minsponsor-copy-button').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const url = button.data('url');
        const originalText = button.text();
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function() {
                showCopyFeedback(button, 'Kopiert!');
            }).catch(function() {
                fallbackCopyText(url, button);
            });
        } else {
            fallbackCopyText(url, button);
        }
        
        function fallbackCopyText(text, button) {
            const textArea = $('<textarea>')
                .val(text)
                .css({
                    position: 'fixed',
                    left: '-999999px',
                    top: '-999999px'
                })
                .appendTo('body');
            
            textArea[0].focus();
            textArea[0].select();
            
            try {
                document.execCommand('copy');
                showCopyFeedback(button, 'Kopiert!');
            } catch (err) {
                showCopyFeedback(button, 'Feil');
            }
            
            textArea.remove();
        }
        
        function showCopyFeedback(button, message) {
            button.text(message).prop('disabled', true);
            
            setTimeout(function() {
                button.text(originalText).prop('disabled', false);
            }, 2000);
        }
    });
    
    // Regenerate QR codes
    $('#minsponsor-regenerate-qr').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const playerId = button.data('player-id');
        const spinner = $('#minsponsor-qr-spinner');
        
        button.prop('disabled', true);
        spinner.addClass('is-active');
        
        $.ajax({
            url: minsponsor_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'minsponsor_regenerate_qr',
                player_id: playerId,
                nonce: minsponsor_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update QR images
                    if (response.data.qr_urls.once) {
                        $('.minsponsor-qr-item:first .minsponsor-qr-image')
                            .attr('src', response.data.qr_urls.once + '?t=' + Date.now());
                        $('.minsponsor-qr-item:first a[download]')
                            .attr('href', response.data.qr_urls.once);
                    }
                    
                    if (response.data.qr_urls.month) {
                        $('.minsponsor-qr-item:last .minsponsor-qr-image')
                            .attr('src', response.data.qr_urls.month + '?t=' + Date.now());
                        $('.minsponsor-qr-item:last a[download]')
                            .attr('href', response.data.qr_urls.month);
                    }
                    
                    // Show success message
                    showMessage('QR-koder regenerert!', 'success');
                } else {
                    showMessage(response.data || 'Kunne ikke regenerere QR-koder.', 'error');
                }
            },
            error: function() {
                showMessage('En feil oppstod. Prøv igjen.', 'error');
            },
            complete: function() {
                button.prop('disabled', false);
                spinner.removeClass('is-active');
            }
        });
    });
    
    // Update links when default amount changes
    $('#minsponsor_default_amount').on('change', function() {
        const amount = $(this).val();
        const currentOnceUrl = $('.minsponsor-link-field:first .minsponsor-link-input').val();
        const currentMonthUrl = $('.minsponsor-link-field:last .minsponsor-link-input').val();
        
        if (currentOnceUrl && currentMonthUrl) {
            // Update URLs with new amount
            const onceUrl = updateUrlParameter(currentOnceUrl, 'amount', amount);
            const monthUrl = updateUrlParameter(currentMonthUrl, 'amount', amount);
            
            $('.minsponsor-link-field:first .minsponsor-link-input').val(onceUrl);
            $('.minsponsor-link-field:first .minsponsor-copy-button').data('url', onceUrl);
            
            $('.minsponsor-link-field:last .minsponsor-link-input').val(monthUrl);
            $('.minsponsor-link-field:last .minsponsor-copy-button').data('url', monthUrl);
        }
    });
    
    function updateUrlParameter(url, param, value) {
        const urlObj = new URL(url);
        
        if (value && value !== '') {
            urlObj.searchParams.set(param, value);
        } else {
            urlObj.searchParams.delete(param);
        }
        
        return urlObj.toString();
    }
    
    function showMessage(message, type) {
        const messageClass = type === 'success' ? 'notice-success' : 'notice-error';
        const messageHtml = '<div class="notice ' + messageClass + ' is-dismissible inline"><p>' + message + '</p></div>';
        
        $('.minsponsor-qr-actions').after(messageHtml);
        
        setTimeout(function() {
            $('.notice.inline').fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
});
