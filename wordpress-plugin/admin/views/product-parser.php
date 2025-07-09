<?php
/**
 * Product Parser Admin View
 * FULLY AUTOMATED - No CSV uploads needed!
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="dpc-parser-section">
    <div class="dpc-hero-section">
        <h2><?php _e('🚀 Fully Automated Brand/Model System', 'dynamic-product-configurator'); ?></h2>
        <p class="dpc-hero-text"><?php _e('No CSV uploads needed! This system automatically analyzes your existing WooCommerce products and creates Brand/Model dropdowns instantly.', 'dynamic-product-configurator'); ?></p>
    </div>
    
    <div class="dpc-stats-grid">
        <div class="dpc-stat-card">
            <div class="dpc-stat-number" id="dpc-total-products">-</div>
            <div class="dpc-stat-label">Total Products</div>
        </div>
        <div class="dpc-stat-card">
            <div class="dpc-stat-number" id="dpc-parsed-products">-</div>
            <div class="dpc-stat-label">Parsed Products</div>
        </div>
        <div class="dpc-stat-card">
            <div class="dpc-stat-number" id="dpc-total-brands">-</div>
            <div class="dpc-stat-label">Available Brands</div>
        </div>
        <div class="dpc-stat-card">
            <div class="dpc-stat-number" id="dpc-total-models">-</div>
            <div class="dpc-stat-label">Available Models</div>
        </div>
    </div>
    
    <div class="dpc-action-section">
        <div class="dpc-action-card">
            <h3>🔍 Step 1: Parse Your Products</h3>
            <p>Automatically extract Brand and Model information from your existing product names.</p>
            <button type="button" id="dpc-parse-products" class="button button-primary button-large">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Parse All Products Now', 'dynamic-product-configurator'); ?>
            </button>
        </div>
        
        <div class="dpc-action-card">
            <h3>⚡ Step 2: Enable Configurator</h3>
            <p>Enable the dynamic configurator for all your mobile back cover products.</p>
            <button type="button" id="dpc-enable-all" class="button button-secondary button-large">
                <span class="dashicons dashicons-yes"></span>
                <?php _e('Enable for All Products', 'dynamic-product-configurator'); ?>
            </button>
        </div>
    </div>
    
    <div id="dpc-parsing-progress" style="display: none;">
        <div class="dpc-progress-container">
            <div class="dpc-progress-bar">
                <div class="dpc-progress-fill"></div>
            </div>
            <p id="dpc-progress-text">Processing your products...</p>
        </div>
    </div>
    
    <div id="dpc-parsing-results" style="display: none;">
        <div class="dpc-results-card">
            <h3><?php _e('✅ Parsing Complete!', 'dynamic-product-configurator'); ?></h3>
            <div id="dpc-results-content"></div>
        </div>
    </div>
    
    <div class="dpc-preview-section">
        <h3><?php _e('📱 Brand & Model Structure Preview', 'dynamic-product-configurator'); ?></h3>
        <div class="dpc-preview-grid">
            <div class="dpc-preview-column">
                <h4><?php _e('Available Brands', 'dynamic-product-configurator'); ?></h4>
                <div id="dpc-brands-list" class="dpc-brands-container">
                    <p class="dpc-placeholder">Parse products first to see available brands.</p>
                </div>
            </div>
            <div class="dpc-preview-column">
                <h4><?php _e('Models by Brand', 'dynamic-product-configurator'); ?></h4>
                <div id="dpc-models-list" class="dpc-models-container">
                    <p class="dpc-placeholder">Select a brand to see available models.</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dpc-info-section">
        <h3><?php _e('🎯 How It Works', 'dynamic-product-configurator'); ?></h3>
        <div class="dpc-info-grid">
            <div class="dpc-info-card">
                <div class="dpc-info-icon">🔍</div>
                <h4>Smart Analysis</h4>
                <p>Analyzes product names like "Samsung Galaxy S8 Plus Happy Yellow..." and extracts Brand = Samsung, Model = Galaxy S8 Plus</p>
            </div>
            <div class="dpc-info-card">
                <div class="dpc-info-icon">🎛️</div>
                <h4>Dynamic Dropdowns</h4>
                <p>Creates Brand dropdown (Samsung, Apple, OnePlus...) and Model dropdown that updates based on selected brand</p>
            </div>
            <div class="dpc-info-card">
                <div class="dpc-info-icon">⚡</div>
                <h4>Instant Integration</h4>
                <p>Automatically enables the configurator on your existing products - no manual work required!</p>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Load current stats on page load
    loadCurrentStats();
    
    // Parse products button
    $('#dpc-parse-products').on('click', function() {
        parseProducts();
    });
    
    // Enable all products button
    $('#dpc-enable-all').on('click', function() {
        enableAllProducts();
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
                    if (response.data.total_brands > 0) {
                        loadBrandsList();
                    }
                }
            }
        });
    }
    
    function parseProducts() {
        const $button = $('#dpc-parse-products');
        const $progress = $('#dpc-parsing-progress');
        const $results = $('#dpc-parsing-results');
        
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Parsing...');
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
                    loadCurrentStats();
                    loadBrandsList();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Error parsing products. Please try again.');
            },
            complete: function() {
                $button.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Parse All Products Now');
                $progress.hide();
            }
        });
    }
    
    function enableAllProducts() {
        const $button = $('#dpc-enable-all');
        
        if (!confirm('This will enable the dynamic configurator for all mobile back cover products. Continue?')) {
            return;
        }
        
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Enabling...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dpc_auto_enable_all_products',
                nonce: '<?php echo wp_create_nonce('dpc_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Success: ' + response.data.message);
                    loadCurrentStats();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Error enabling products. Please try again.');
            },
            complete: function() {
                $button.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Enable for All Products');
            }
        });
    }
    
    function showResults(data) {
        const $results = $('#dpc-parsing-results');
        const $content = $('#dpc-results-content');
        
        let html = '<div class="dpc-results-summary">';
        html += '<div class="dpc-result-stat"><strong>' + data.parsed_count + '</strong> products successfully parsed</div>';
        html += '<div class="dpc-result-stat"><strong>' + data.skipped_count + '</strong> products skipped (no brand/model found)</div>';
        html += '<div class="dpc-result-stat"><strong>' + data.total_products + '</strong> total products processed</div>';
        
        if (data.errors && data.errors.length > 0) {
            html += '<div class="dpc-errors-section">';
            html += '<h4>Sample Parsing Issues:</h4>';
            html += '<ul class="dpc-error-list">';
            data.errors.forEach(function(error) {
                html += '<li>' + error + '</li>';
            });
            html += '</ul>';
            html += '<p><em>These products will need manual configuration or better naming patterns.</em></p>';
            html += '</div>';
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
        
        if (brands.length === 0) {
            $brandsList.html('<p class="dpc-placeholder">No brands found. Parse products first.</p>');
            return;
        }
        
        let html = '<div class="dpc-brands-grid">';
        
        brands.forEach(function(brand) {
            html += '<div class="dpc-brand-item" data-brand="' + brand.brand + '">';
            html += '<div class="dpc-brand-name">' + brand.label + '</div>';
            html += '</div>';
        });
        
        html += '</div>';
        $brandsList.html(html);
        
        // Add click handlers for brands
        $('.dpc-brand-item').on('click', function() {
            const brand = $(this).data('brand');
            $('.dpc-brand-item').removeClass('active');
            $(this).addClass('active');
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
        
        let html = '<h5>Models for ' + brand + ':</h5>';
        html += '<div class="dpc-models-grid">';
        
        models.forEach(function(model) {
            html += '<div class="dpc-model-item">' + model.label + '</div>';
        });
        
        html += '</div>';
        $modelsList.html(html);
    }
    
    function updateStatsDisplay(stats) {
        $('#dpc-total-products').text(stats.total_wc_products);
        $('#dpc-parsed-products').text(stats.dpc_enabled_products);
        $('#dpc-total-brands').text(stats.total_brands);
        $('#dpc-total-models').text(stats.total_models);
    }
});
</script>

<style>
.dpc-parser-section {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin: 20px 0;
}

.dpc-hero-section {
    text-align: center;
    margin-bottom: 40px;
    padding: 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
}

.dpc-hero-section h2 {
    margin: 0 0 15px 0;
    font-size: 28px;
    font-weight: 600;
}

.dpc-hero-text {
    font-size: 16px;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
}

.dpc-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.dpc-stat-card {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 8px;
    text-align: center;
    border-left: 4px solid #0073aa;
}

.dpc-stat-number {
    font-size: 32px;
    font-weight: bold;
    color: #0073aa;
    margin-bottom: 8px;
}

.dpc-stat-label {
    font-size: 14px;
    color: #666;
    font-weight: 500;
}

.dpc-action-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

.dpc-action-card {
    background: #fff;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    transition: all 0.3s ease;
}

.dpc-action-card:hover {
    border-color: #0073aa;
    box-shadow: 0 4px 15px rgba(0,115,170,0.1);
}

.dpc-action-card h3 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 18px;
}

.dpc-action-card p {
    color: #666;
    margin-bottom: 20px;
    line-height: 1.5;
}

.button-large {
    padding: 12px 24px !important;
    font-size: 16px !important;
    height: auto !important;
}

.dpc-progress-container {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 8px;
    text-align: center;
    margin: 20px 0;
}

.dpc-progress-bar {
    width: 100%;
    height: 8px;
    background: #e1e5e9;
    border-radius: 4px;
    overflow: hidden;
    margin: 20px 0;
}

.dpc-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0073aa, #00a0d2);
    width: 0%;
    animation: dpc-progress-pulse 2s ease-in-out infinite;
}

@keyframes dpc-progress-pulse {
    0%, 100% { width: 10%; }
    50% { width: 90%; }
}

.dpc-results-card {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
    padding: 25px;
    border-radius: 8px;
    margin: 20px 0;
}

.dpc-results-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.dpc-result-stat {
    background: rgba(255,255,255,0.3);
    padding: 15px;
    border-radius: 6px;
    text-align: center;
}

.dpc-preview-section {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 2px solid #e1e5e9;
}

.dpc-preview-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-top: 20px;
}

.dpc-preview-column {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 8px;
}

.dpc-preview-column h4 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 16px;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.dpc-brands-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
}

.dpc-brand-item {
    background: #fff;
    padding: 12px;
    border-radius: 6px;
    text-align: center;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.dpc-brand-item:hover {
    border-color: #0073aa;
    background: #f0f8ff;
}

.dpc-brand-item.active {
    border-color: #0073aa;
    background: #0073aa;
    color: white;
}

.dpc-brand-name {
    font-weight: 500;
    font-size: 14px;
}

.dpc-models-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 8px;
}

.dpc-model-item {
    background: #fff;
    padding: 10px;
    border-radius: 4px;
    font-size: 13px;
    text-align: center;
    border-left: 3px solid #0073aa;
}

.dpc-info-section {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 2px solid #e1e5e9;
}

.dpc-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
    margin-top: 20px;
}

.dpc-info-card {
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 25px;
    text-align: center;
}

.dpc-info-icon {
    font-size: 32px;
    margin-bottom: 15px;
}

.dpc-info-card h4 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 16px;
}

.dpc-info-card p {
    color: #666;
    font-size: 14px;
    line-height: 1.5;
}

.dpc-placeholder {
    color: #999;
    font-style: italic;
    text-align: center;
    padding: 20px;
}

.dpc-errors-section {
    margin-top: 20px;
    padding: 15px;
    background: rgba(255,255,255,0.3);
    border-radius: 6px;
}

.dpc-error-list {
    max-height: 150px;
    overflow-y: auto;
    margin: 10px 0;
}

.dpc-error-list li {
    font-size: 13px;
    margin-bottom: 5px;
}

.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .dpc-action-section,
    .dpc-preview-grid,
    .dpc-info-grid {
        grid-template-columns: 1fr;
    }
    
    .dpc-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>