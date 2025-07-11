<?php
/**
 * Admin Page Class
 * Handles admin dashboard and CSV upload functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class DPC_Admin_Page {
    
    private $csv_parser;
    
    public function __construct() {
        $this->csv_parser = new DPC_CSV_Parser();
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_dpc_upload_csv', array($this, 'handle_csv_upload'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Dynamic Product Configurator', 'dynamic-product-configurator'),
            __('Product Configurator', 'dynamic-product-configurator'),
            'manage_woocommerce',
            'dpc-admin',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'woocommerce',
            __('Bulk Requests', 'dynamic-product-configurator'),
            __('Bulk Requests', 'dynamic-product-configurator'),
            'manage_woocommerce',
            'dpc-bulk-requests',
            array($this, 'bulk_requests_page')
        );
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Register settings
        register_setting('dpc_settings', 'dpc_auto_create_wc_products');
        register_setting('dpc_settings', 'dpc_update_existing_products');
        register_setting('dpc_settings', 'dpc_default_category');
        register_setting('dpc_settings', 'dpc_default_price');
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'dpc-admin') !== false) {
            wp_enqueue_script('dpc-admin', DPC_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), DPC_VERSION, true);
            wp_enqueue_style('dpc-admin', DPC_PLUGIN_URL . 'assets/css/admin.css', array(), DPC_VERSION);
            
            wp_localize_script('dpc-admin', 'dpcAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dpc_nonce')
            ));
        }
    }
    
    /**
     * Display admin notices
     */
    public function admin_notices() {
        if (isset($_GET['page']) && $_GET['page'] === 'dpc-admin') {
            if (isset($_GET['message'])) {
                switch ($_GET['message']) {
                    case 'success':
                        $count = isset($_GET['count']) ? intval($_GET['count']) : 0;
                        echo '<div class="notice notice-success is-dismissible"><p>' . 
                             sprintf(__('CSV processed successfully! %d products configured.', 'dynamic-product-configurator'), $count) . 
                             '</p></div>';
                        break;
                    case 'error':
                        $error = isset($_GET['error']) ? urldecode($_GET['error']) : __('Unknown error occurred', 'dynamic-product-configurator');
                        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
                        break;
                    case 'partial':
                        $success = isset($_GET['success']) ? intval($_GET['success']) : 0;
                        $errors = isset($_GET['errors']) ? intval($_GET['errors']) : 0;
                        echo '<div class="notice notice-warning is-dismissible"><p>' . 
                             sprintf(__('CSV processed with some issues. %d successful, %d errors.', 'dynamic-product-configurator'), $success, $errors) . 
                             '</p></div>';
                        break;
                }
            }
        }
    }
    
    /**
     * Main admin page
     */
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
                    <?php _e('📦 Products', 'dynamic-product-configurator'); ?>
                </a>
                <a href="?page=dpc-admin&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('⚙️ Settings', 'dynamic-product-configurator'); ?>
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
                    case 'settings':
                        $this->render_settings_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render upload tab
     */
    private function render_upload_tab() {
        ?>
        <div class="dpc-upload-section">
            <h2><?php _e('📤 Upload Product Configuration CSV', 'dynamic-product-configurator'); ?></h2>
            <p class="description"><?php _e('Upload your CSV file with WooCommerce Product IDs, brands, models, and related products.', 'dynamic-product-configurator'); ?></p>
            
            <!-- Upload Form -->
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
                                <?php _e('Required: CSV file with columns: wc_product_id, brand, model, recommended_products, complementary_products', 'dynamic-product-configurator'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <!-- Upload Options -->
                <div class="dpc-upload-options">
                    <h3><?php _e('📋 Upload Options', 'dynamic-product-configurator'); ?></h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Product Creation', 'dynamic-product-configurator'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="radio" name="product_action" value="existing_only" checked>
                                        <?php _e('🔄 Update Existing Products Only', 'dynamic-product-configurator'); ?>
                                    </label>
                                    <p class="description"><?php _e('Only configure products that already exist in WooCommerce', 'dynamic-product-configurator'); ?></p>
                                    
                                    <label>
                                        <input type="radio" name="product_action" value="create_missing">
                                        <?php _e('➕ Create Missing Products', 'dynamic-product-configurator'); ?>
                                    </label>
                                    <p class="description"><?php _e('Create new WooCommerce products for IDs that don\'t exist', 'dynamic-product-configurator'); ?></p>
                                    
                                    <label>
                                        <input type="radio" name="product_action" value="update_and_create">
                                        <?php _e('🔄➕ Update Existing & Create Missing', 'dynamic-product-configurator'); ?>
                                    </label>
                                    <p class="description"><?php _e('Update existing products and create new ones as needed', 'dynamic-product-configurator'); ?></p>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Configuration Options', 'dynamic-product-configurator'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="enable_configurator" value="1" checked>
                                        <?php _e('✅ Enable Configurator for All Products', 'dynamic-product-configurator'); ?>
                                    </label>
                                    <br>
                                    <label>
                                        <input type="checkbox" name="overwrite_existing" value="1">
                                        <?php _e('🔄 Overwrite Existing Configuration', 'dynamic-product-configurator'); ?>
                                    </label>
                                    <br>
                                    <label>
                                        <input type="checkbox" name="create_categories" value="1" checked>
                                        <?php _e('📁 Auto-create Product Categories', 'dynamic-product-configurator'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- New Product Defaults (shown when creating products) -->
                    <div id="new-product-defaults" style="display: none;">
                        <h4><?php _e('🆕 New Product Defaults', 'dynamic-product-configurator'); ?></h4>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Default Price', 'dynamic-product-configurator'); ?></th>
                                <td>
                                    <input type="number" name="default_price" value="199" step="0.01" min="0" class="small-text">
                                    <span class="description"><?php _e('Default price for new products', 'dynamic-product-configurator'); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Default Category', 'dynamic-product-configurator'); ?></th>
                                <td>
                                    <?php
                                    wp_dropdown_categories(array(
                                        'taxonomy' => 'product_cat',
                                        'name' => 'default_category',
                                        'selected' => get_option('dpc_default_category'),
                                        'show_option_none' => __('Mobile Back Cover', 'dynamic-product-configurator'),
                                        'option_none_value' => 'mobile-back-cover'
                                    ));
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Product Status', 'dynamic-product-configurator'); ?></th>
                                <td>
                                    <select name="default_status">
                                        <option value="publish"><?php _e('Published', 'dynamic-product-configurator'); ?></option>
                                        <option value="draft"><?php _e('Draft', 'dynamic-product-configurator'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php submit_button(__('🚀 Upload and Process CSV', 'dynamic-product-configurator'), 'primary', 'submit', false, array('class' => 'button-primary button-hero')); ?>
            </form>
            
            <!-- CSV Format Help -->
            <div class="dpc-csv-format-help">
                <h3><?php _e('📋 CSV Format Requirements', 'dynamic-product-configurator'); ?></h3>
                
                <div class="dpc-format-example">
                    <h4><?php _e('Required Columns:', 'dynamic-product-configurator'); ?></h4>
                    <ul>
                        <li><strong>wc_product_id</strong> - WooCommerce Product ID (e.g., 22181)</li>
                        <li><strong>brand</strong> - Brand name (e.g., Samsung)</li>
                        <li><strong>model</strong> - Model name (e.g., Galaxy S8)</li>
                        <li><strong>recommended_products</strong> - Comma-separated Product IDs (e.g., "22182,22188")</li>
                        <li><strong>complementary_products</strong> - Comma-separated Product IDs (e.g., "22184,22185")</li>
                    </ul>
                    
                    <h4><?php _e('Example CSV Content:', 'dynamic-product-configurator'); ?></h4>
                    <pre class="dpc-csv-example">wc_product_id,brand,model,recommended_products,complementary_products
22181,Samsung,Galaxy S8,"22182,22188","22184,22185"
22182,Samsung,Galaxy S8 Plus,"22181,22188","22184,22185"
22188,Samsung,Galaxy S9,"22189,22190","22191,22192"</pre>
                    
                    <p class="description">
                        <strong><?php _e('💡 Tips:', 'dynamic-product-configurator'); ?></strong>
                        <br>• <?php _e('Use double quotes around comma-separated values', 'dynamic-product-configurator'); ?>
                        <br>• <?php _e('Product IDs must exist in WooCommerce (unless creating new products)', 'dynamic-product-configurator'); ?>
                        <br>• <?php _e('Leave recommended_products or complementary_products empty if not needed', 'dynamic-product-configurator'); ?>
                    </p>
                </div>
                
                <div class="dpc-download-sample">
                    <h4><?php _e('📥 Download Sample File', 'dynamic-product-configurator'); ?></h4>
                    <a href="<?php echo DPC_PLUGIN_URL; ?>sample-csv-files/manual-products.csv" class="button button-secondary" download>
                        <?php _e('📄 Download Sample CSV', 'dynamic-product-configurator'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Show/hide new product defaults based on selection
            $('input[name="product_action"]').change(function() {
                if ($(this).val() === 'existing_only') {
                    $('#new-product-defaults').hide();
                } else {
                    $('#new-product-defaults').show();
                }
            });
            
            // Form validation
            $('.dpc-upload-form').submit(function() {
                var file = $('#products_csv')[0].files[0];
                if (!file) {
                    alert('Please select a CSV file to upload.');
                    return false;
                }
                
                if (!file.name.toLowerCase().endsWith('.csv')) {
                    alert('Please select a valid CSV file.');
                    return false;
                }
                
                // Show loading state
                $(this).find('input[type="submit"]').val('Processing...').prop('disabled', true);
                return true;
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render products tab
     */
    private function render_products_tab() {
        global $wpdb;
        
        // Get statistics
        $total_products = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}dpc_products");
        $total_brands = $wpdb->get_var("SELECT COUNT(DISTINCT attribute_value) FROM {$wpdb->prefix}dpc_product_attributes WHERE attribute_type = 'brand'");
        $total_models = $wpdb->get_var("SELECT COUNT(DISTINCT attribute_value) FROM {$wpdb->prefix}dpc_product_attributes WHERE attribute_type = 'model'");
        
        // Get recent products
        $recent_products = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dpc_products ORDER BY created_at DESC LIMIT 10");
        ?>
        <div class="dpc-products-section">
            <h2><?php _e('📦 Product Configuration Overview', 'dynamic-product-configurator'); ?></h2>
            
            <!-- Statistics Cards -->
            <div class="dpc-stats-cards">
                <div class="dpc-stat-card">
                    <div class="dpc-stat-number"><?php echo number_format($total_products); ?></div>
                    <div class="dpc-stat-label"><?php _e('Configured Products', 'dynamic-product-configurator'); ?></div>
                </div>
                <div class="dpc-stat-card">
                    <div class="dpc-stat-number"><?php echo number_format($total_brands); ?></div>
                    <div class="dpc-stat-label"><?php _e('Brands', 'dynamic-product-configurator'); ?></div>
                </div>
                <div class="dpc-stat-card">
                    <div class="dpc-stat-number"><?php echo number_format($total_models); ?></div>
                    <div class="dpc-stat-label"><?php _e('Models', 'dynamic-product-configurator'); ?></div>
                </div>
            </div>
            
            <!-- Search and Actions -->
            <div class="dpc-products-actions">
                <div class="dpc-search-box">
                    <input type="text" id="dpc-search-products" placeholder="<?php _e('Search products...', 'dynamic-product-configurator'); ?>" class="regular-text">
                    <button type="button" class="button" id="dpc-search-btn"><?php _e('🔍 Search', 'dynamic-product-configurator'); ?></button>
                </div>
                
                <div class="dpc-bulk-actions">
                    <select id="dpc-bulk-action">
                        <option value=""><?php _e('Bulk Actions', 'dynamic-product-configurator'); ?></option>
                        <option value="enable"><?php _e('Enable Configurator', 'dynamic-product-configurator'); ?></option>
                        <option value="disable"><?php _e('Disable Configurator', 'dynamic-product-configurator'); ?></option>
                        <option value="delete"><?php _e('Remove Configuration', 'dynamic-product-configurator'); ?></option>
                    </select>
                    <button type="button" class="button" id="dpc-apply-bulk"><?php _e('Apply', 'dynamic-product-configurator'); ?></button>
                </div>
            </div>
            
            <!-- Products Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all">
                        </td>
                        <th><?php _e('Product ID', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Product Name', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Brand', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Model', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Price', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('WC Product', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Status', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Actions', 'dynamic-product-configurator'); ?></th>
                    </tr>
                </thead>
                <tbody id="dpc-products-list">
                    <?php if (empty($recent_products)): ?>
                    <tr>
                        <td colspan="9" class="dpc-no-products">
                            <div class="dpc-empty-state">
                                <h3><?php _e('No Products Configured Yet', 'dynamic-product-configurator'); ?></h3>
                                <p><?php _e('Upload a CSV file to start configuring your products.', 'dynamic-product-configurator'); ?></p>
                                <a href="?page=dpc-admin&tab=upload" class="button button-primary">
                                    <?php _e('📤 Upload CSV', 'dynamic-product-configurator'); ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($recent_products as $product): 
                            // Get brand and model
                            $brand = $wpdb->get_var($wpdb->prepare(
                                "SELECT attribute_label FROM {$wpdb->prefix}dpc_product_attributes WHERE product_id = %s AND attribute_type = 'brand'",
                                $product->product_id
                            ));
                            $model = $wpdb->get_var($wpdb->prepare(
                                "SELECT attribute_label FROM {$wpdb->prefix}dpc_product_attributes WHERE product_id = %s AND attribute_type = 'model'",
                                $product->product_id
                            ));
                            
                            // Check if WC product exists
                            $wc_product = null;
                            if ($product->wc_product_id) {
                                $wc_product = wc_get_product($product->wc_product_id);
                            }
                        ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="product_ids[]" value="<?php echo esc_attr($product->product_id); ?>">
                            </th>
                            <td><code><?php echo esc_html($product->product_id); ?></code></td>
                            <td>
                                <strong><?php echo esc_html($product->product_name); ?></strong>
                                <?php if ($product->image_url): ?>
                                <br><small><a href="<?php echo esc_url($product->image_url); ?>" target="_blank"><?php _e('View Image', 'dynamic-product-configurator'); ?></a></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($brand ?: '-'); ?></td>
                            <td><?php echo esc_html($model ?: '-'); ?></td>
                            <td><?php echo wc_price($product->base_price); ?></td>
                            <td>
                                <?php if ($wc_product): ?>
                                    <a href="<?php echo admin_url('post.php?post=' . $product->wc_product_id . '&action=edit'); ?>" target="_blank">
                                        #<?php echo $product->wc_product_id; ?>
                                    </a>
                                    <br><small class="dpc-status-active"><?php _e('✅ Active', 'dynamic-product-configurator'); ?></small>
                                <?php else: ?>
                                    <span class="dpc-status-missing">❌ <?php _e('Missing', 'dynamic-product-configurator'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($wc_product && get_post_meta($product->wc_product_id, '_dpc_enabled', true) === 'yes'): ?>
                                    <span class="dpc-status-enabled">✅ <?php _e('Enabled', 'dynamic-product-configurator'); ?></span>
                                <?php else: ?>
                                    <span class="dpc-status-disabled">⚠️ <?php _e('Disabled', 'dynamic-product-configurator'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="button button-small dpc-view-product" data-product-id="<?php echo esc_attr($product->product_id); ?>">
                                    <?php _e('👁️ View', 'dynamic-product-configurator'); ?>
                                </button>
                                <?php if ($wc_product): ?>
                                <a href="<?php echo admin_url('post.php?post=' . $product->wc_product_id . '&action=edit'); ?>" class="button button-small" target="_blank">
                                    <?php _e('✏️ Edit', 'dynamic-product-configurator'); ?>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if (!empty($recent_products)): ?>
            <p class="description">
                <?php printf(__('Showing %d most recent products. Use search to find specific products.', 'dynamic-product-configurator'), count($recent_products)); ?>
            </p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render settings tab
     */
    private function render_settings_tab() {
        ?>
        <div class="dpc-settings-section">
            <h2><?php _e('⚙️ Plugin Settings', 'dynamic-product-configurator'); ?></h2>
            
            <form method="post" action="options.php">
                <?php settings_fields('dpc_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Default Product Settings', 'dynamic-product-configurator'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="dpc_auto_create_wc_products" value="1" <?php checked(get_option('dpc_auto_create_wc_products', 1)); ?>>
                                    <?php _e('Auto-create WooCommerce products during CSV import', 'dynamic-product-configurator'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="dpc_update_existing_products" value="1" <?php checked(get_option('dpc_update_existing_products', 1)); ?>>
                                    <?php _e('Update existing products during CSV import', 'dynamic-product-configurator'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Default Category', 'dynamic-product-configurator'); ?></th>
                        <td>
                            <?php
                            wp_dropdown_categories(array(
                                'taxonomy' => 'product_cat',
                                'name' => 'dpc_default_category',
                                'selected' => get_option('dpc_default_category'),
                                'show_option_none' => __('Mobile Back Cover', 'dynamic-product-configurator'),
                                'option_none_value' => 'mobile-back-cover'
                            ));
                            ?>
                            <p class="description"><?php _e('Default category for new products', 'dynamic-product-configurator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Default Price', 'dynamic-product-configurator'); ?></th>
                        <td>
                            <input type="number" name="dpc_default_price" value="<?php echo esc_attr(get_option('dpc_default_price', 199)); ?>" step="0.01" min="0" class="small-text">
                            <span class="description"><?php _e('Default price for new products', 'dynamic-product-configurator'); ?></span>
                        </td>
                    </tr>
                </table>
                
                <h3><?php _e('Frontend Display', 'dynamic-product-configurator'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Shortcode Usage', 'dynamic-product-configurator'); ?></th>
                        <td>
                            <p><strong><?php _e('Brand/Model Selector:', 'dynamic-product-configurator'); ?></strong></p>
                            <code>[dynamic_product_configurator type="selector"]</code>
                            <p class="description"><?php _e('Shows brand/model dropdowns for product selection', 'dynamic-product-configurator'); ?></p>
                            
                            <p><strong><?php _e('Specific Product Configurator:', 'dynamic-product-configurator'); ?></strong></p>
                            <code>[dynamic_product_configurator product_id="123"]</code>
                            <p class="description"><?php _e('Shows configurator for a specific product', 'dynamic-product-configurator'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Bulk requests page
     */
    public function bulk_requests_page() {
        global $wpdb;
        
        $requests = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}dpc_bulk_requests ORDER BY created_at DESC LIMIT 50"
        );
        ?>
        <div class="wrap">
            <h1><?php _e('📋 Bulk Purchase Requests', 'dynamic-product-configurator'); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Product', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Quantity', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Customer', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Contact', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Status', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Date', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Actions', 'dynamic-product-configurator'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="8" class="dpc-no-requests">
                            <div class="dpc-empty-state">
                                <h3><?php _e('No Bulk Requests Yet', 'dynamic-product-configurator'); ?></h3>
                                <p><?php _e('Bulk purchase requests will appear here when customers submit them.', 'dynamic-product-configurator'); ?></p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                        <tr>
                            <td>#<?php echo $request->id; ?></td>
                            <td><?php echo esc_html($request->product_id); ?></td>
                            <td><?php echo number_format($request->quantity); ?></td>
                            <td>
                                <strong><?php echo esc_html($request->contact_name); ?></strong>
                                <?php if ($request->company): ?>
                                <br><small><?php echo esc_html($request->company); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="mailto:<?php echo esc_attr($request->contact_email); ?>"><?php echo esc_html($request->contact_email); ?></a>
                                <?php if ($request->contact_phone): ?>
                                <br><small><?php echo esc_html($request->contact_phone); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="dpc-status dpc-status-<?php echo esc_attr($request->status); ?>">
                                    <?php echo esc_html(ucfirst($request->status)); ?>
                                </span>
                            </td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($request->created_at)); ?></td>
                            <td>
                                <button type="button" class="button button-small dpc-view-request" data-request-id="<?php echo $request->id; ?>">
                                    <?php _e('👁️ View', 'dynamic-product-configurator'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Handle CSV upload
     */
    public function handle_csv_upload() {
        if (!wp_verify_nonce($_POST['dpc_nonce'], 'dpc_upload_csv')) {
            wp_die(__('Security check failed', 'dynamic-product-configurator'));
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions', 'dynamic-product-configurator'));
        }
        
        try {
            // Get upload options
            $product_action = sanitize_text_field($_POST['product_action']);
            $enable_configurator = isset($_POST['enable_configurator']);
            $overwrite_existing = isset($_POST['overwrite_existing']);
            $create_categories = isset($_POST['create_categories']);
            $default_price = floatval($_POST['default_price']) ?: 199;
            $default_category = sanitize_text_field($_POST['default_category']);
            $default_status = sanitize_text_field($_POST['default_status']);
            
            // Process CSV file
            if (isset($_FILES['products_csv']) && $_FILES['products_csv']['error'] === UPLOAD_ERR_OK) {
                $result = $this->csv_parser->parse_manual_csv(
                    $_FILES['products_csv']['tmp_name'],
                    array(
                        'product_action' => $product_action,
                        'enable_configurator' => $enable_configurator,
                        'overwrite_existing' => $overwrite_existing,
                        'create_categories' => $create_categories,
                        'default_price' => $default_price,
                        'default_category' => $default_category,
                        'default_status' => $default_status
                    )
                );
                
                if ($result['errors'] > 0 && $result['success'] > 0) {
                    // Partial success
                    wp_redirect(admin_url('admin.php?page=dpc-admin&message=partial&success=' . $result['success'] . '&errors=' . $result['errors']));
                } elseif ($result['errors'] > 0) {
                    // All errors
                    wp_redirect(admin_url('admin.php?page=dpc-admin&message=error&error=' . urlencode('Failed to process CSV file')));
                } else {
                    // All success
                    wp_redirect(admin_url('admin.php?page=dpc-admin&message=success&count=' . $result['success']));
                }
            } else {
                wp_redirect(admin_url('admin.php?page=dpc-admin&message=error&error=' . urlencode('No file uploaded or upload error')));
            }
            
        } catch (Exception $e) {
            dpc_log('CSV upload error: ' . $e->getMessage());
            wp_redirect(admin_url('admin.php?page=dpc-admin&message=error&error=' . urlencode($e->getMessage())));
        }
        
        exit;
    }
}