// Dynamic Product Configurator Frontend JavaScript

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize DPC functionality
    initializeDPC();
    
    function initializeDPC() {
        // Quantity controls
        $('.dpc-qty-minus').on('click', function() {
            const input = $(this).siblings('.dpc-quantity-input');
            const currentVal = parseInt(input.val()) || 1;
            if (currentVal > 1) {
                input.val(currentVal - 1);
            }
        });
        
        $('.dpc-qty-plus').on('click', function() {
            const input = $(this).siblings('.dpc-quantity-input');
            const currentVal = parseInt(input.val()) || 1;
            input.val(currentVal + 1);
        });
        
        // Brand/Model change handlers
        $('.dpc-brand-select, .dpc-model-select').on('change', function() {
            validateForm();
        });
        
        // Form validation
        validateForm();
        
        // Form submission
        $('.dpc-selection-form').on('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                alert('Please select both brand and model before adding to cart.');
                return false;
            }
        });
    }
    
    function validateForm() {
        const brand = $('.dpc-brand-select').val();
        const model = $('.dpc-model-select').val();
        const submitBtn = $('.dpc-add-to-cart-btn');
        
        if (brand && model) {
            submitBtn.prop('disabled', false);
            return true;
        } else {
            submitBtn.prop('disabled', true);
            return false;
        }
    }
    
    // Add body class when DPC is active
    if ($('.dpc-brand-model-selector').length > 0) {
        $('body').addClass('dpc-active');
        $('.product').addClass('dpc-enabled');
    }
});