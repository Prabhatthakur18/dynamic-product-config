<?php
/**
 * Admin Page Class
 * WordPress admin interface for managing DPC
 */

if (!defined('ABSPATH')) {
    exit;
}

class DPC_Admin_Page {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Dynamic Product Configurator', 'dynamic-product-configurator'),
            __('Product Configurator', 'dynamic-product-configurator'),
            'manage_woocommerce',
            'dpc-admin',
            array($this, 'admin_page')
        );
    }
    
    public function admin_notices() {
        if (isset($_GET['page']) && $_GET['page'] === 'dpc-admin') {
            if (isset($_GET['message'])) {
                switch ($_GET['message']) {
                    case 'success':
                        $count = isset($_GET['count']) ? intval($_GET['count']) : 0;
                        echo '<div class="notice notice-success is-dismissible"><p>' . 
                             sprintf(__('CSV processed successfully! %d products updated.', 'dynamic-product-configurator'), $count) . 
                             '</p></div>';
                        break;
                    case 'error':
                        $error = isset($_GET['error']) ? urldecode($_GET['error']) : __('Unknown error', 'dynamic-product-configurator');
                        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
                        break;
                }
            }
        }
    }
    
    public function admin_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'upload';
        ?>
        <div class="wrap">
            <h1><?php _e('Dynamic Product Configurator', 'dynamic-product-configurator'); ?></h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=dpc-admin&tab=upload" class="nav-tab <?php echo $active_tab === 'upload' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('📤 CSV Upload', 'dynamic-product-configurator'); ?>
                </a>
                <a href="?page=dpc-admin&tab=products" class="nav-tab <?php echo $active_tab === 'products' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('📦 Manage Products', 'dynamic-product-configurator'); ?>
                </a>
                <a href="?page=dpc-admin&tab=bulk-actions" class="nav-tab <?php echo $active_tab === 'bulk-actions' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('⚡ Bulk Actions', 'dynamic-product-configurator'); ?>
                </a>
            </nav>
            
            <div class="tab-content">
                <?php
                switch ($active_tab) {
                    case 'upload':
                        $this->render_upload_tab();
                        break;
                    case 'products':
                        $this->render_products_tab();
                        break;
                    case 'bulk-actions':
                        $this->render_bulk_actions_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    private function render_upload_tab() {
        ?>
        <div class="dpc-upload-section">
            <h2><?php _e('📤 Upload CSV to Configure Products', 'dynamic-product-configurator'); ?></h2>
            <p class="description"><?php _e('Upload a CSV file to add brand/model information and recommendations to your existing WooCommerce products.', 'dynamic-product-configurator'); ?></p>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data" class="dpc-upload-form">
                <?php wp_nonce_field('dpc_upload_csv', 'dpc_nonce'); ?>
                <input type="hidden" name="action" value="dpc_upload_csv">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="products_csv"><?php _e('📄 Products CSV File', 'dynamic-product-configurator'); ?></label>
                        </th>
                        <td>
                            <input type="file" name="products_csv" id="products_csv" accept=".csv" required class="regular-text">
                            <p class="description">
                                <?php _e('CSV with columns: wc_product_id, brand, model, recommended_products, interested_products', 'dynamic-product-configurator'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <div class="dpc-upload-options">
                    <h3><?php _e('📋 Upload Options', 'dynamic-product-configurator'); ?></h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Product Actions', 'dynamic-product-configurator'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="update_existing_products" value="1" checked>
                                        <?php _e('🔄 Update Existing Products', 'dynamic-product-configurator'); ?>
                                    </label>
                                    <p class="description"><?php _e('Update brand/model info for existing WooCommerce products', 'dynamic-product-configurator'); ?></p>
                                    
                                    <label>
                                        <input type="checkbox" name="create_missing_products" value="1">
                                        <?php _e('➕ Create Missing Products', 'dynamic-product-configurator'); ?>
                                    </label>
                                    <p class="description"><?php _e('Create new WooCommerce products for IDs that don\'t exist', 'dynamic-product-configurator'); ?></p>
                                    
                                    <label>
                                        <input type="checkbox" name="enable_all_products" value="1" checked>
                                        <?php _e('✅ Enable DPC for All Products', 'dynamic-product-configurator'); ?>
                                    </label>
                                    <p class="description"><?php _e('Automatically enable brand/model selector for all processed products', 'dynamic-product-configurator'); ?></p>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('New Product Defaults', 'dynamic-product-configurator'); ?></th>
                            <td>
                                <label>
                                    <?php _e('Default Price:', 'dynamic-product-configurator'); ?>
                                    <input type="number" name="default_price" value="199" step="0.01" min="0" class="small-text">
                                </label>
                                <br><br>
                                <label>
                                    <?php _e('Default Category:', 'dynamic-product-configurator'); ?>
                                    <input type="text" name="default_category" value="Mobile Back Cover" class="regular-text">
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button(__('🚀 Upload and Process CSV', 'dynamic-product-configurator'), 'primary', 'submit', false, array('class' => 'button-primary button-hero')); ?>
            </form>
            
            <div class="dpc-csv-format-help">
                <h3><?php _e('📋 CSV Format Example', 'dynamic-product-configurator'); ?></h3>
                <pre class="dpc-csv-example">wc_product_id,brand,model,recommended_products,interested_products
22181,Samsung,Galaxy S8,"22182,22188","22184,22185"
22182,Samsung,Galaxy S8 Plus,"22181,22188","22184,22185"
22188,Samsung,Galaxy S9,"22189,22190","22191,22192"</pre>
                
                <p><strong><?php _e('💡 Tips:', 'dynamic-product-configurator'); ?></strong></p>
                <ul>
                    <li><?php _e('wc_product_id: Your existing WooCommerce Product ID', 'dynamic-product-configurator'); ?></li>
                    <li><?php _e('brand: Product brand (Samsung, Apple, etc.)', 'dynamic-product-configurator'); ?></li>
                    <li><?php _e('model: Product model (Galaxy S8, iPhone 12, etc.)', 'dynamic-product-configurator'); ?></li>
                    <li><?php _e('recommended_products: Comma-separated product IDs to show as recommendations', 'dynamic-product-configurator'); ?></li>
                    <li><?php _e('interested_products: Comma-separated product IDs for "You may be interested" section', 'dynamic-product-configurator'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    private function render_products_tab() {
        // Get products with DPC enabled
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 50,
            'meta_query' => array(
                array(
                    'key' => '_dpc_enabled',
                    'value' => 'yes',
                    'compare' => '='
                )
            )
        );
        
        $products = get_posts($args);
        
        ?>
        <div class="dpc-products-section">
            <h2><?php _e('📦 Products with DPC Enabled', 'dynamic-product-configurator'); ?></h2>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Product ID', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Product Name', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Brand', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Model', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Recommended', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Interested', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Actions', 'dynamic-product-configurator'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <p><?php _e('No products with DPC enabled yet.', 'dynamic-product-configurator'); ?></p>
                            <a href="?page=dpc-admin&tab=upload" class="button button-primary">
                                <?php _e('📤 Upload CSV to Get Started', 'dynamic-product-configurator'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo $product->ID; ?></td>
                            <td><strong><?php echo esc_html($product->post_title); ?></strong></td>
                            <td><?php echo esc_html(get_post_meta($product->ID, '_dpc_brand', true)); ?></td>
                            <td><?php echo esc_html(get_post_meta($product->ID, '_dpc_model', true)); ?></td>
                            <td>
                                <?php 
                                $recommended = get_post_meta($product->ID, '_dpc_recommended_products', true);
                                echo !empty($recommended) ? count(explode(',', $recommended)) . ' products' : '-';
                                ?>
                            </td>
                            <td>
                                <?php 
                                $interested = get_post_meta($product->ID, '_dpc_interested_products', true);
                                echo !empty($interested) ? count(explode(',', $interested)) . ' products' : '-';
                                ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('post.php?post=' . $product->ID . '&action=edit'); ?>" class="button button-small">
                                    <?php _e('✏️ Edit', 'dynamic-product-configurator'); ?>
                                </a>
                                <a href="<?php echo get_permalink($product->ID); ?>" class="button button-small" target="_blank">
                                    <?php _e('👁️ View', 'dynamic-product-configurator'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    private function render_bulk_actions_tab() {
        ?>
        <div class="dpc-bulk-actions-section">
            <h2><?php _e('⚡ Bulk Actions', 'dynamic-product-configurator'); ?></h2>
            
            <div class="dpc-bulk-action-cards">
                
                <!-- Enable DPC for All Products -->
                <div class="dpc-action-card">
                    <h3><?php _e('✅ Enable DPC for All Products', 'dynamic-product-configurator'); ?></h3>
                    <p><?php _e('Enable brand/model selector for all WooCommerce products that have brand and model information.', 'dynamic-product-configurator'); ?></p>
                    <button type="button" class="button button-primary" onclick="dpcBulkAction('enable_all')">
                        <?php _e('Enable for All', 'dynamic-product-configurator'); ?>
                    </button>
                </div>
                
                <!-- Disable DPC for All Products -->
                <div class="dpc-action-card">
                    <h3><?php _e('❌ Disable DPC for All Products', 'dynamic-product-configurator'); ?></h3>
                    <p><?php _e('Disable brand/model selector and restore default WooCommerce add-to-cart for all products.', 'dynamic-product-configurator'); ?></p>
                    <button type="button" class="button button-secondary" onclick="dpcBulkAction('disable_all')">
                        <?php _e('Disable for All', 'dynamic-product-configurator'); ?>
                    </button>
                </div>
                
                <!-- Clear All DPC Data -->
                <div class="dpc-action-card">
                    <h3><?php _e('🗑️ Clear All DPC Data', 'dynamic-product-configurator'); ?></h3>
                    <p><?php _e('Remove all brand, model, and recommendation data from products. This action cannot be undone.', 'dynamic-product-configurator'); ?></p>
                    <button type="button" class="button button-link-delete" onclick="dpcBulkAction('clear_all')">
                        <?php _e('Clear All Data', 'dynamic-product-configurator'); ?>
                    </button>
                </div>
                
            </div>
            
            <!-- Statistics -->
            <div class="dpc-statistics">
                <h3><?php _e('📊 Statistics', 'dynamic-product-configurator'); ?></h3>
                <?php
                global $wpdb;
                
                $total_products = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'");
                $dpc_enabled = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_dpc_enabled' AND meta_value = 'yes'");
                $with_brand = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_dpc_brand' AND meta_value != ''");
                $with_model = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_dpc_model' AND meta_value != ''");
                ?>
                
                <div class="dpc-stats-grid">
                    <div class="dpc-stat-item">
                        <span class="dpc-stat-number"><?php echo number_format($total_products); ?></span>
                        <span class="dpc-stat-label"><?php _e('Total Products', 'dynamic-product-configurator'); ?></span>
                    </div>
                    <div class="dpc-stat-item">
                        <span class="dpc-stat-number"><?php echo number_format($dpc_enabled); ?></span>
                        <span class="dpc-stat-label"><?php _e('DPC Enabled', 'dynamic-product-configurator'); ?></span>
                    </div>
                    <div class="dpc-stat-item">
                        <span class="dpc-stat-number"><?php echo number_format($with_brand); ?></span>
                        <span class="dpc-stat-label"><?php _e('With Brand', 'dynamic-product-configurator'); ?></span>
                    </div>
                    <div class="dpc-stat-item">
                        <span class="dpc-stat-number"><?php echo number_format($with_model); ?></span>
                        <span class="dpc-stat-label"><?php _e('With Model', 'dynamic-product-configurator'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function dpcBulkAction(action) {
            if (action === 'clear_all') {
                if (!confirm('<?php _e('Are you sure? This will remove all DPC data and cannot be undone.', 'dynamic-product-configurator'); ?>')) {
                    return;
                }
            }
            
            // Implement AJAX call for bulk actions
            alert('Bulk action: ' + action + ' (to be implemented)');
        }
        </script>
        <?php
    }
}