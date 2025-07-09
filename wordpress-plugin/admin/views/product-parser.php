<?php
/**
 * Product Parser Admin View
 * Interface for parsing existing WooCommerce products
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="dpc-parser-section">
    <h2><?php _e('Parse Existing WooCommerce Products', 'dynamic-product-configurator'); ?></h2>
    <p><?php _e('This tool will analyze your existing WooCommerce products and automatically extract Brand and Model information to create dynamic dropdowns.', 'dynamic-product-configurator'); ?></p>
    
    <div class="dpc-parser-stats">
        <div class="dpc-stat-box">
            <h3>Current Status</h3>
            <div id="dpc-current-stats">
                <p>Loading...</p>
            </div>
        </div>
    </div>
    
    <div class="dpc-parser-actions">
        <button type="button" id="dpc-parse-products" class="button button-primary">
            <span class="dashicons dashicons-update"></span>
            <?php _e('Parse All Products', 'dynamic-product-configurator'); ?>
        </button>
        
        <button type="button" id="dpc-preview-parsing" class="button">
            <span class="dashicons dashicons-visibility"></span>
            <?php _e('Preview Parsing Results', 'dynamic-product-configurator'); ?>
        </button>
    </div>
    
    <div id="dpc-parsing-progress" style="display: none;">
        <div class="dpc-progress-bar">
            <div class="dpc-progress-fill"></div>
        </div>
        <p id="dpc-progress-text">Processing products...</p>
    </div>
    
    <div id="dpc-parsing-results" style="display: none;">
        <h3><?php _e('Parsing Results', 'dynamic-product-configurator'); ?></h3>
        <div id="dpc-results-content"></div>
    </div>
    
    <div class="dpc-brand-model-preview">
        <h3><?php _e('Brand & Model Structure Preview', 'dynamic-product-configurator'); ?></h3>
        <div class="dpc-preview-grid">
            <div class="dpc-preview-column">
                <h4><?php _e('Available Brands', 'dynamic-product-configurator'); ?></h4>
                <div id="dpc-brands-list">
                    <p>No brands found. Parse products first.</p>
                </div>
            </div>
            <div class="dpc-preview-column">
                <h4><?php _e('Models by Brand', 'dynamic-product-configurator'); ?></h4>
                <div id="dpc-models-list">
                    <p>Select a brand to see models.</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dpc-manual-mapping">
        <h3><?php _e('Manual Brand/Model Mapping', 'dynamic-product-configurator'); ?></h3>
        <p><?php _e('For products that couldn\'t be automatically parsed, you can manually map them:', 'dynamic-product-configurator'); ?></p>
        
        <table class="wp-list-table widefat fixed striped" id="dpc-manual-mapping-table">
            <thead>
                <tr>
                    <th><?php _e('Product Name', 'dynamic-product-configurator'); ?></th>
                    <th><?php _e('Detected Brand', 'dynamic-product-configurator'); ?></th>
                    <th><?php _e('Detected Model', 'dynamic-product-configurator'); ?></th>
                    <th><?php _e('Manual Override', 'dynamic-product-configurator'); ?></th>
                    <th><?php _e('Actions', 'dynamic-product-configurator'); ?></th>
                </tr>
            </thead>
            <tbody id="dpc-manual-mapping-body">
                <tr>
                    <td colspan="5"><?php _e('Run parsing first to see unmapped products.', 'dynamic-product-configurator'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Load current stats
    loadCurrentStats();
    
    // Parse products button
    $('#dpc-parse-products').on('click', function() {
        parseProducts();
    });
    
    // Preview parsing button
    $('#dpc-preview-parsing').on('click', function() {
        previewParsing();
    });
    
    function loadCurrentStats() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dpc_get_parsing_stats',
                nonce: '<?php echo wp_create_nonce('dpc_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    updateStatsDisplay(response.data);
                }
            }
        });
    }
    
    function parseProducts() {
        const $button = $('#dpc-parse-products');
        const $progress = $('#dpc-parsing-progress');
        const $results = $('#dpc-parsing-results');
        
        $button.prop('disabled', true);
        $progress.show();
        $results.hide();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dpc_parse_existing_products',
                nonce: '<?php echo wp_create_nonce('dpc_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showResults(response.data);
                    loadBrandsList();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Error parsing products. Please try again.');
            },
            complete: function() {
                $button.prop('disabled', false);
                $progress.hide();
            }
        });
    }
    
    function showResults(data) {
        const $results = $('#dpc-parsing-results');
        const $content = $('#dpc-results-content');
        
        let html = '<div class="dpc-results-summary">';
        html += '<p><strong>Parsing Complete!</strong></p>';
        html += '<p>Successfully parsed: ' + data.parsed_count + ' out of ' + data.total_products + ' products</p>';
        
        if (data.errors.length > 0) {
            html += '<h4>Errors:</h4><ul>';
            data.errors.forEach(function(error) {
                html += '<li>' + error + '</li>';
            });
            html += '</ul>';
        }
        
        html += '</div>';
        
        $content.html(html);
        $results.show();
    }
    
    function loadBrandsList() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dpc_get_brands_list',
                nonce: '<?php echo wp_create_nonce('dpc_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    updateBrandsList(response.data);
                }
            }
        });
    }
    
    function updateBrandsList(brands) {
        const $brandsList = $('#dpc-brands-list');
        let html = '<ul class="dpc-brands-ul">';
        
        brands.forEach(function(brand) {
            html += '<li><a href="#" class="dpc-brand-link" data-brand="' + brand.brand + '">' + brand.label + '</a></li>';
        });
        
        html += '</ul>';
        $brandsList.html(html);
        
        // Add click handlers for brands
        $('.dpc-brand-link').on('click', function(e) {
            e.preventDefault();
            const brand = $(this).data('brand');
            loadModelsForBrand(brand);
        });
    }
    
    function loadModelsForBrand(brand) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dpc_get_models_for_brand',
                brand: brand,
                nonce: '<?php echo wp_create_nonce('dpc_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    updateModelsList(response.data, brand);
                }
            }
        });
    }
    
    function updateModelsList(models, brand) {
        const $modelsList = $('#dpc-models-list');
        let html = '<h5>Models for ' + brand + ':</h5><ul>';
        
        models.forEach(function(model) {
            html += '<li>' + model.label + '</li>';
        });
        
        html += '</ul>';
        $modelsList.html(html);
    }
    
    function updateStatsDisplay(stats) {
        const $stats = $('#dpc-current-stats');
        let html = '<p><strong>Total WooCommerce Products:</strong> ' + stats.total_wc_products + '</p>';
        html += '<p><strong>DPC Enabled Products:</strong> ' + stats.dpc_enabled_products + '</p>';
        html += '<p><strong>Available Brands:</strong> ' + stats.total_brands + '</p>';
        html += '<p><strong>Available Models:</strong> ' + stats.total_models + '</p>';
        
        $stats.html(html);
    }
});
</script>

<style>
.dpc-parser-section {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-top: 20px;
}

.dpc-parser-stats {
    margin: 20px 0;
}

.dpc-stat-box {
    background: #f9f9f9;
    padding: 15px;
    border-left: 4px solid #0073aa;
    margin-bottom: 20px;
}

.dpc-parser-actions {
    margin: 20px 0;
}

.dpc-parser-actions .button {
    margin-right: 10px;
}

.dpc-progress-bar {
    width: 100%;
    height: 20px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
    margin: 10px 0;
}

.dpc-progress-fill {
    height: 100%;
    background: #0073aa;
    width: 0%;
    animation: dpc-progress-pulse 2s ease-in-out infinite;
}

@keyframes dpc-progress-pulse {
    0%, 100% { width: 10%; }
    50% { width: 90%; }
}

.dpc-preview-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.dpc-preview-column {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
}

.dpc-brands-ul {
    list-style: none;
    padding: 0;
}

.dpc-brands-ul li {
    margin-bottom: 5px;
}

.dpc-brand-link {
    text-decoration: none;
    color: #0073aa;
    font-weight: 500;
}

.dpc-brand-link:hover {
    text-decoration: underline;
}

.dpc-manual-mapping {
    margin-top: 30px;
}

.dpc-results-summary {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}
</style>