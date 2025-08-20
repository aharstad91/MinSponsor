/**
 * MinSponsor Settings JavaScript
 * 
 * Handles validation and product creation for MinSponsor settings
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Validation buttons
    $('.minsponsor-validate-btn').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var productType = $button.data('type');
        var $results = $('#validation-results');
        
        // Show loading state
        $button.prop('disabled', true).text('Validerer...');
        
        $.ajax({
            url: minsponsor_settings_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'minsponsor_validate_product',
                product_type: productType,
                nonce: minsponsor_settings_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showValidationResult($results, 'success', productType, response.data);
                } else {
                    showValidationResult($results, 'error', productType, response.data);
                }
            },
            error: function() {
                showValidationResult($results, 'error', productType, {
                    message: 'AJAX-feil oppstod'
                });
            },
            complete: function() {
                // Restore button
                $button.prop('disabled', false);
                if (productType === 'one_time') {
                    $button.text('Valider engangsprodukt');
                } else {
                    $button.text('Valider månedlig produkt');
                }
            }
        });
    });
    
    // Create product buttons
    $('.minsponsor-create-btn').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var productType = $button.data('type');
        
        if (!confirm('Er du sikker på at du vil opprette et nytt produkt?')) {
            return;
        }
        
        // Show loading state
        $button.prop('disabled', true).text('Oppretter...');
        
        $.ajax({
            url: minsponsor_settings_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'minsponsor_create_product',
                product_type: productType,
                nonce: minsponsor_settings_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showNotice('success', response.data.message);
                    
                    // Reload page to show new product in dropdown
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotice('error', response.data.message);
                    $button.prop('disabled', false).text($button.data('original-text'));
                }
            },
            error: function() {
                showNotice('error', 'AJAX-feil oppstod ved opprettelse av produkt');
                $button.prop('disabled', false).text($button.data('original-text'));
            }
        });
    });
    
    // Store original button text
    $('.minsponsor-create-btn').each(function() {
        $(this).data('original-text', $(this).text());
    });
    
    /**
     * Show validation result
     */
    function showValidationResult($container, type, productType, data) {
        var typeLabel = productType === 'one_time' ? 'Engangsprodukt' : 'Månedlig produkt';
        var icon = type === 'success' ? '✓' : '✗';
        var colorClass = type === 'success' ? 'color: #46b450;' : 'color: #dc3232;';
        
        var html = '<div style="' + colorClass + ' font-weight: bold; margin-bottom: 5px;">' +
                   icon + ' ' + typeLabel + ': ' + data.message + '</div>';
        
        if (type === 'success' && data.product_name) {
            html += '<div style="color: #666; font-size: 12px;">' +
                   'Produkt: ' + data.product_name;
            
            if (data.product_sku) {
                html += ' (SKU: ' + data.product_sku + ')';
            }
            
            if (data.product_price) {
                html += '<br>Pris: ' + data.product_price;
            }
            
            html += '</div>';
        }
        
        $container.html($container.html() + html);
    }
    
    /**
     * Show admin notice
     */
    function showNotice(type, message) {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut();
        }, 5000);
    }
    
    // Clear validation results when settings are saved
    $('form').on('submit', function() {
        $('#validation-results').empty();
    });
});
