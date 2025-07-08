jQuery(document).ready(function($) {
    'use strict';
    
    // CSV upload form handling
    $('.dpc-upload-form').on('submit', function(e) {
        const form = $(this);
        const submitBtn = form.find('input[type="submit"]');
        
        // Validate files
        const productsFile = form.find('#products_csv')[0].files[0];
        const attributesFile = form.find('#attributes_csv')[0].files[0];
        
        if (!productsFile || !attributesFile) {
            alert('Please select both Products CSV and Attributes CSV files.');
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        submitBtn.val('Processing...').prop('disabled', true);
        
        // Add progress indicator
        if (!form.find('.dpc-progress').length) {
            form.append('<div class="dpc-progress"><div class="dpc-progress-bar"></div></div>');
        }
    });
    
    // Product search functionality
    $('#dpc-search-btn').on('click', function() {
        const searchTerm = $('#dpc-search-products').val();
        if (searchTerm.length < 2) {
            alert('Please enter at least 2 characters to search.');
            return;
        }
        
        searchProducts(searchTerm);
    });
    
    $('#dpc-search-products').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            $('#dpc-search-btn').click();
        }
    });
    
    // View product details
    $(document).on('click', '.dpc-view-product', function() {
        const productId = $(this).data('product-id');
        viewProductDetails(productId);
    });
    
    // View bulk request details
    $(document).on('click', '.dpc-view-request', function() {
        const requestId = $(this).data('request-id');
        viewBulkRequestDetails(requestId);
    });
    
    function searchProducts(searchTerm) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dpc_search_products',
                search_term: searchTerm,
                nonce: dpcAjax.nonce
            },
            beforeSend: function() {
                $('#dpc-search-btn').text('Searching...').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    updateProductsList(response.data);
                } else {
                    alert('Error searching products: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('Error searching products. Please try again.');
            },
            complete: function() {
                $('#dpc-search-btn').text('Search').prop('disabled', false);
            }
        });
    }
    
    function updateProductsList(products) {
        const tbody = $('#dpc-products-list');
        tbody.empty();
        
        if (products.length === 0) {
            tbody.append('<tr><td colspan="7">No products found.</td></tr>');
            return;
        }
        
        products.forEach(function(product) {
            const row = $('<tr>');
            row.append('<td><code>' + escapeHtml(product.id) + '</code></td>');
            row.append('<td><strong>' + escapeHtml(product.name) + '</strong></td>');
            row.append('<td>₹' + product.price.toFixed(2) + '</td>');
            row.append('<td>' + escapeHtml(product.category) + '</td>');
            row.append('<td>-</td>'); // Attributes column
            row.append('<td>-</td>'); // WC Product column
            row.append('<td><button type="button" class="button button-small dpc-view-product" data-product-id="' + escapeHtml(product.id) + '">View</button></td>');
            
            tbody.append(row);
        });
    }
    
    function viewProductDetails(productId) {
        // Create modal dialog
        const modal = $('<div class="dpc-modal">');
        const modalContent = $('<div class="dpc-modal-content">');
        
        modalContent.html('<div class="dpc-modal-header"><h3>Product Details</h3><span class="dpc-modal-close">&times;</span></div><div class="dpc-modal-body">Loading...</div>');
        modal.append(modalContent);
        $('body').append(modal);
        
        // Load product data
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dpc_get_product_data',
                product_id: productId,
                nonce: dpcAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayProductDetails(modalContent.find('.dpc-modal-body'), response.data);
                } else {
                    modalContent.find('.dpc-modal-body').html('<p class="error">Error loading product details.</p>');
                }
            },
            error: function() {
                modalContent.find('.dpc-modal-body').html('<p class="error">Error loading product details.</p>');
            }
        });
        
        // Modal close handlers
        modal.find('.dpc-modal-close').on('click', function() {
            modal.remove();
        });
        
        modal.on('click', function(e) {
            if (e.target === modal[0]) {
                modal.remove();
            }
        });
    }
    
    function displayProductDetails(container, data) {
        const product = data.product;
        const attributes = data.attributes;
        const complementary = data.complementary;
        
        let html = '<div class="dpc-product-details">';
        
        // Product info
        html += '<h4>Product Information</h4>';
        html += '<table class="widefat">';
        html += '<tr><td><strong>Product ID:</strong></td><td>' + escapeHtml(product.id) + '</td></tr>';
        html += '<tr><td><strong>Name:</strong></td><td>' + escapeHtml(product.name) + '</td></tr>';
        html += '<tr><td><strong>Base Price:</strong></td><td>₹' + product.basePrice.toFixed(2) + '</td></tr>';
        html += '<tr><td><strong>Category:</strong></td><td>' + escapeHtml(product.category) + '</td></tr>';
        html += '<tr><td><strong>Attribute Types:</strong></td><td>' + product.attributeTypes.join(', ') + '</td></tr>';
        html += '</table>';
        
        // Attributes
        if (Object.keys(attributes).length > 0) {
            html += '<h4>Attributes</h4>';
            for (const attrType in attributes) {
                html += '<h5>' + escapeHtml(attrType.charAt(0).toUpperCase() + attrType.slice(1)) + '</h5>';
                html += '<ul>';
                attributes[attrType].forEach(function(attr) {
                    html += '<li>' + escapeHtml(attr.label) + ' (' + escapeHtml(attr.value) + ')';
                    if (attr.priceModifier !== 0) {
                        html += ' - Price modifier: ₹' + attr.priceModifier.toFixed(2);
                    }
                    html += '</li>';
                });
                html += '</ul>';
            }
        }
        
        // Complementary products
        if (complementary.length > 0) {
            html += '<h4>Complementary Products</h4>';
            html += '<ul>';
            complementary.forEach(function(comp) {
                html += '<li>' + escapeHtml(comp.name) + ' - ₹' + comp.price.toFixed(2);
                if (comp.originalPrice) {
                    html += ' (was ₹' + comp.originalPrice.toFixed(2) + ')';
                }
                html += '</li>';
            });
            html += '</ul>';
        }
        
        html += '</div>';
        
        container.html(html);
    }
    
    function viewBulkRequestDetails(requestId) {
        // Similar modal implementation for bulk requests
        // This would show detailed information about the bulk purchase request
        alert('Bulk request details for ID: ' + requestId + ' (Feature to be implemented)');
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});

// Add CSS for modals
const modalCSS = `
.dpc-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dpc-modal-content {
    background: white;
    border-radius: 4px;
    max-width: 800px;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.dpc-modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dpc-modal-header h3 {
    margin: 0;
}

.dpc-modal-close {
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.dpc-modal-close:hover {
    color: #000;
}

.dpc-modal-body {
    padding: 20px;
}

.dpc-progress {
    margin-top: 10px;
    background: #f0f0f0;
    border-radius: 4px;
    overflow: hidden;
}

.dpc-progress-bar {
    height: 4px;
    background: #0073aa;
    animation: dpc-progress 2s linear infinite;
}

@keyframes dpc-progress {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
`;

// Inject CSS
const style = document.createElement('style');
style.textContent = modalCSS;
document.head.appendChild(style);