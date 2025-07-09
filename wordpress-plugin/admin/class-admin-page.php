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
        register_setting('dpc_settings', 'dpc_max_upload_size');
        register_setting('dpc_settings', 'dpc_auto_create_wc_products');
        register_setting('dpc_settings', 'dpc_default_category');
    }
    
    /**
     * Display admin notices
     */
    public function admin_notices() {
        if (isset($_GET['page']) && $_GET['page'] === 'dpc-admin') {
            if (isset($_GET['message'])) {
                switch ($_GET['message']) {
                    case 'success':
                        echo '<div class="notice notice-success is-dismissible"><p>' . __('CSV files processed successfully!', 'dynamic-product-configurator') . '</p></div>';
                        break;
                    case 'error':
                        $error = isset($_GET['error']) ? urldecode($_GET['error']) : __('Unknown error occurred', 'dynamic-product-configurator');
                        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
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
                    <?php _e('CSV Upload', 'dynamic-product-configurator'); ?>
                </a>
                <a href="?page=dpc-admin&tab=products" class="nav-tab <?php echo $active_tab === 'products' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Products', 'dynamic-product-configurator'); ?>
                </a>
                <a href="?page=dpc-admin&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Settings', 'dynamic-product-configurator'); ?>
                </a>
                <a href="?page=dpc-admin&tab=parser" class="nav-tab <?php echo $active_tab === 'parser' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Product Parser', 'dynamic-product-configurator'); ?>
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
                    case 'parser':
                        $this->render_parser_tab();
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
            <h2><?php _e('Upload CSV Files', 'dynamic-product-configurator'); ?></h2>
            <p><?php _e('Upload your CSV files to import products, attributes, and complementary products.', 'dynamic-product-configurator'); ?></p>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data" class="dpc-upload-form">
                <?php wp_nonce_field('dpc_upload_csv', 'dpc_nonce'); ?>
                <input type="hidden" name="action" value="dpc_upload_csv">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="products_csv"><?php _e('Products CSV', 'dynamic-product-configurator'); ?></label>
                        </th>
                        <td>
                            <input type="file" name="products_csv" id="products_csv" accept=".csv" required>
                            <p class="description">
                                <?php _e('Required: Contains main product information (product_id, product_name, base_price, category, attribute_types)', 'dynamic-product-configurator'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="attributes_csv"><?php _e('Attributes CSV', 'dynamic-product-configurator'); ?></label>
                        </th>
                        <td>
                            <input type="file" name="attributes_csv" id="attributes_csv" accept=".csv" required>
                            <p class="description">
                                <?php _e('Required: Contains product attributes and variations (product_id, attribute_type, attribute_value, attribute_label, price_modifier)', 'dynamic-product-configurator'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="complementary_csv"><?php _e('Complementary Products CSV', 'dynamic-product-configurator'); ?></label>
                        </th>
                        <td>
                            <input type="file" name="complementary_csv" id="complementary_csv" accept=".csv">
                            <p class="description">
                                <?php _e('Optional: Contains related/add-on products (main_product_id, complementary_product_id, complementary_name, price)', 'dynamic-product-configurator'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <div class="dpc-upload-options">
                    <label>
                        <input type="checkbox" name="create_wc_products" value="1" checked>
                        <?php _e('Automatically create WooCommerce products', 'dynamic-product-configurator'); ?>
                    </label>
                    <br>
                    <label>
                        <input type="checkbox" name="update_existing" value="1">
                        <?php _e('Update existing products', 'dynamic-product-configurator'); ?>
                    </label>
                </div>
                
                <?php submit_button(__('Upload and Process CSV Files', 'dynamic-product-configurator'), 'primary', 'submit', false); ?>
            </form>
            
            <div class="dpc-csv-format-help">
                <h3><?php _e('CSV Format Examples', 'dynamic-product-configurator'); ?></h3>
                
                <h4><?php _e('Products CSV Format:', 'dynamic-product-configurator'); ?></h4>
                <pre>product_id,product_name,base_price,image_url,category,attribute_types
phone-case-001,3 Color Gimp Snap Case,99.00,https://example.com/image.jpg,phone-case,"brand,model"
headphones-001,Wireless Earbuds Pro,299.00,https://example.com/image2.jpg,audio,"brand,color"</pre>
                
                <h4><?php _e('Attributes CSV Format:', 'dynamic-product-configurator'); ?></h4>
                <pre>product_id,attribute_type,attribute_value,attribute_label,price_modifier
phone-case-001,brand,vivo,Vivo,0
phone-case-001,brand,samsung,Samsung,0
phone-case-001,brand,apple,Apple,25
phone-case-001,model,v15,V15,0
phone-case-001,model,v20,V20,50</pre>
                
                <h4><?php _e('Complementary Products CSV Format:', 'dynamic-product-configurator'); ?></h4>
                <pre>main_product_id,complementary_product_id,complementary_name,price,original_price,image_url
phone-case-001,screen-guard-001,Add Flexible Glass Screen Guard,29.00,39.00,https://example.com/guard.jpg
phone-case-001,key-chain-001,Add Same Design Key Chain,29.00,39.00,https://example.com/keychain.jpg</pre>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render products tab
     */
    private function render_products_tab() {
        $product_manager = new DPC_Product_Manager();
        $products = $product_manager->get_all_products(50);
        ?>
        <div class="dpc-products-section">
            <h2><?php _e('Manage Products', 'dynamic-product-configurator'); ?></h2>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <input type="text" id="dpc-search-products" placeholder="<?php _e('Search products...', 'dynamic-product-configurator'); ?>" class="regular-text">
                    <button type="button" class="button" id="dpc-search-btn"><?php _e('Search', 'dynamic-product-configurator'); ?></button>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Product ID', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Name', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Base Price', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Category', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Attributes', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('WC Product', 'dynamic-product-configurator'); ?></th>
                        <th><?php _e('Actions', 'dynamic-product-configurator'); ?></th>
                    </tr>
                </thead>
                <tbody id="dpc-products-list">
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><code><?php echo esc_html($product->product_id); ?></code></td>
                        <td>
                            <strong><?php echo esc_html($product->product_name); ?></strong>
                            <?php if ($product->image_url): ?>
                            <br><small><a href="<?php echo esc_url($product->image_url); ?>" target="_blank"><?php _e('View Image', 'dynamic-product-configurator'); ?></a></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo wc_price($product->base_price); ?></td>
                        <td><?php echo esc_html($product->category); ?></td>
                        <td><?php echo esc_html($product->attribute_types); ?></td>
                        <td>
                            <?php if ($product->wc_product_id): ?>
                                <a href="<?php echo admin_url('post.php?post=' . $product->wc_product_id . '&action=edit'); ?>" target="_blank">
                                    #<?php echo $product->wc_product_id; ?>
                                </a>
                            <?php else: ?>
                                <span class="dashicons dashicons-minus"></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="button button-small dpc-view-product" data-product-id="<?php echo esc_attr($product->product_id); ?>">
                                <?php _e('View', 'dynamic-product-configurator'); ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render settings tab
     */
    private function render_settings_tab() {
        ?>
        <div class="dpc-settings-section">
            <h2><?php _e('Settings', 'dynamic-product-configurator'); ?></h2>
            
            <form method="post" action="options.php">
                <?php settings_fields('dpc_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Maximum Upload Size', 'dynamic-product-configurator'); ?></th>
                        <td>
                            <input type="number" name="dpc_max_upload_size" value="<?php echo esc_attr(get_option('dpc_max_upload_size', 64)); ?>" min="1" max="512">
                            <span class="description"><?php _e('MB (Maximum size for CSV uploads)', 'dynamic-product-configurator'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Auto-create WooCommerce Products', 'dynamic-product-configurator'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="dpc_auto_create_wc_products" value="1" <?php checked(get_option('dpc_auto_create_wc_products', 1)); ?>>
                                <?php _e('Automatically create WooCommerce products during CSV import', 'dynamic-product-configurator'); ?>
                            </label>
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
                                'show_option_none' => __('Select a category', 'dynamic-product-configurator'),
                                'option_none_value' => ''
                            ));
                            ?>
                            <p class="description"><?php _e('Default category for products without a specified category', 'dynamic-product-configurator'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render parser tab
     */
    private function render_parser_tab() {
        include DPC_PLUGIN_DIR . 'admin/views/product-parser.php';
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
            <h1><?php _e('Bulk Purchase Requests', 'dynamic-product-configurator'); ?></h1>
            
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
                                <?php _e('View', 'dynamic-product-configurator'); ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
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
            $results = array();
            
            // Process products CSV
            if (isset($_FILES['products_csv']) && $_FILES['products_csv']['error'] === UPLOAD_ERR_OK) {
                $result = $this->csv_parser->parse_products_csv($_FILES['products_csv']['tmp_name']);
                $results['products'] = $result;
            }
            
            // Process attributes CSV
            if (isset($_FILES['attributes_csv']) && $_FILES['attributes_csv']['error'] === UPLOAD_ERR_OK) {
                $result = $this->csv_parser->parse_attributes_csv($_FILES['attributes_csv']['tmp_name']);
                $results['attributes'] = $result;
            }
            
            // Process complementary CSV
            if (isset($_FILES['complementary_csv']) && $_FILES['complementary_csv']['error'] === UPLOAD_ERR_OK) {
                $result = $this->csv_parser->parse_complementary_csv($_FILES['complementary_csv']['tmp_name']);
                $results['complementary'] = $result;
            }
            
            // Check for errors
            $has_errors = false;
            $error_messages = array();
            
            foreach ($results as $type => $result) {
                if (!empty($result['errors'])) {
                    $has_errors = true;
                    $error_messages[] = sprintf(__('%s: %s', 'dynamic-product-configurator'), ucfirst($type), implode(', ', $result['errors']));
                }
            }
            
            if ($has_errors) {
                $error_message = implode(' | ', $error_messages);
                wp_redirect(admin_url('admin.php?page=dpc-admin&message=error&error=' . urlencode($error_message)));
            } else {
                wp_redirect(admin_url('admin.php?page=dpc-admin&message=success'));
            }
            
        } catch (Exception $e) {
            dpc_log('CSV upload error: ' . $e->getMessage());
            wp_redirect(admin_url('admin.php?page=dpc-admin&message=error&error=' . urlencode($e->getMessage())));
        }
        
        exit;
    }
}