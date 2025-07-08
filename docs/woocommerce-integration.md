# WooCommerce Integration Guide

## Plugin Structure for WordPress/WooCommerce

### 1. Plugin Architecture
```
dynamic-product-configurator/
├── dynamic-product-configurator.php (Main plugin file)
├── includes/
│   ├── class-product-manager.php
│   ├── class-csv-parser.php
│   ├── class-woocommerce-integration.php
│   └── class-frontend-handler.php
├── admin/
│   ├── class-admin-page.php
│   └── views/
│       └── admin-dashboard.php
├── assets/
│   ├── js/
│   │   └── product-configurator.js (React build)
│   └── css/
│       └── styles.css
└── templates/
    └── product-configurator.php
```

### 2. Main Plugin File (dynamic-product-configurator.php)
```php
<?php
/**
 * Plugin Name: Dynamic Product Configurator
 * Description: CSV-based dynamic product attributes for WooCommerce
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>Dynamic Product Configurator requires WooCommerce to be installed and active.</p></div>';
    });
    return;
}

class DynamicProductConfigurator {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    public function init() {
        // Include required files
        require_once plugin_dir_path(__FILE__) . 'includes/class-product-manager.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-csv-parser.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-woocommerce-integration.php';
        require_once plugin_dir_path(__FILE__) . 'admin/class-admin-page.php';
        
        // Initialize components
        new DPC_Product_Manager();
        new DPC_WooCommerce_Integration();
        new DPC_Admin_Page();
    }
    
    public function enqueue_scripts() {
        if (is_product() || is_shop() || has_shortcode(get_post()->post_content, 'dynamic_product_configurator')) {
            wp_enqueue_script(
                'dpc-configurator',
                plugin_dir_url(__FILE__) . 'assets/js/product-configurator.js',
                array('wp-element', 'wp-api-fetch'),
                '1.0.0',
                true
            );
            
            wp_localize_script('dpc-configurator', 'dpcAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dpc_nonce'),
                'wc_ajax_url' => WC_AJAX::get_endpoint('%%endpoint%%')
            ));
        }
    }
    
    public function activate() {
        // Create database tables
        $this->create_tables();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Products table
        $sql1 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dpc_products (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            product_id varchar(100) NOT NULL,
            product_name varchar(255) NOT NULL,
            base_price decimal(10,2) NOT NULL,
            image_url varchar(500),
            category varchar(100),
            attribute_types text,
            wc_product_id bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY product_id (product_id)
        ) $charset_collate;";
        
        // Attributes table
        $sql2 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dpc_product_attributes (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            product_id varchar(100) NOT NULL,
            attribute_type varchar(100) NOT NULL,
            attribute_value varchar(100) NOT NULL,
            attribute_label varchar(255) NOT NULL,
            price_modifier decimal(10,2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id)
        ) $charset_collate;";
        
        // Complementary products table
        $sql3 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dpc_complementary_products (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            main_product_id varchar(100) NOT NULL,
            complementary_product_id varchar(100) NOT NULL,
            complementary_name varchar(255) NOT NULL,
            price decimal(10,2) NOT NULL,
            original_price decimal(10,2),
            image_url varchar(500),
            wc_product_id bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY main_product_id (main_product_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
    }
}

new DynamicProductConfigurator();
```

### 3. WooCommerce Integration Class
```php
<?php
class DPC_WooCommerce_Integration {
    
    public function __construct() {
        // Hook into WooCommerce
        add_action('woocommerce_single_product_summary', array($this, 'add_configurator_to_product'), 25);
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_custom_data_to_cart'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_custom_data_in_cart'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_custom_data_to_order'), 10, 4);
        add_filter('woocommerce_cart_item_price', array($this, 'modify_cart_item_price'), 10, 3);
        
        // AJAX handlers
        add_action('wp_ajax_dpc_get_product_data', array($this, 'get_product_data'));
        add_action('wp_ajax_nopriv_dpc_get_product_data', array($this, 'get_product_data'));
        add_action('wp_ajax_dpc_add_to_cart', array($this, 'ajax_add_to_cart'));
        add_action('wp_ajax_nopriv_dpc_add_to_cart', array($this, 'ajax_add_to_cart'));
    }
    
    public function add_configurator_to_product() {
        global $product;
        
        // Check if this product has dynamic configuration
        if ($this->has_dynamic_configuration($product->get_id())) {
            echo '<div id="dynamic-product-configurator" data-product-id="' . $product->get_id() . '"></div>';
        }
    }
    
    public function add_custom_data_to_cart($cart_item_data, $product_id, $variation_id) {
        if (isset($_POST['dpc_attributes'])) {
            $cart_item_data['dpc_attributes'] = sanitize_text_field($_POST['dpc_attributes']);
            $cart_item_data['dpc_price_modifier'] = floatval($_POST['dpc_price_modifier']);
        }
        return $cart_item_data;
    }
    
    public function display_custom_data_in_cart($item_data, $cart_item) {
        if (isset($cart_item['dpc_attributes'])) {
            $attributes = json_decode($cart_item['dpc_attributes'], true);
            foreach ($attributes as $key => $value) {
                $item_data[] = array(
                    'key' => ucfirst($key),
                    'value' => $value
                );
            }
        }
        return $item_data;
    }
    
    public function modify_cart_item_price($price, $cart_item, $cart_item_key) {
        if (isset($cart_item['dpc_price_modifier'])) {
            $product = $cart_item['data'];
            $new_price = $product->get_price() + $cart_item['dpc_price_modifier'];
            return wc_price($new_price);
        }
        return $price;
    }
    
    public function get_product_data() {
        check_ajax_referer('dpc_nonce', 'nonce');
        
        $product_id = sanitize_text_field($_POST['product_id']);
        
        // Get dynamic product data from database
        global $wpdb;
        
        $product_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dpc_products WHERE wc_product_id = %d",
            $product_id
        ));
        
        if ($product_data) {
            $attributes = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dpc_product_attributes WHERE product_id = %s",
                $product_data->product_id
            ));
            
            $complementary = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}dpc_complementary_products WHERE main_product_id = %s",
                $product_data->product_id
            ));
            
            wp_send_json_success(array(
                'product' => $product_data,
                'attributes' => $attributes,
                'complementary' => $complementary
            ));
        } else {
            wp_send_json_error('Product not found');
        }
    }
    
    public function ajax_add_to_cart() {
        check_ajax_referer('dpc_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        $attributes = sanitize_text_field($_POST['attributes']);
        $price_modifier = floatval($_POST['price_modifier']);
        
        $cart_item_data = array(
            'dpc_attributes' => $attributes,
            'dpc_price_modifier' => $price_modifier
        );
        
        $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, 0, array(), $cart_item_data);
        
        if ($cart_item_key) {
            wp_send_json_success(array(
                'cart_item_key' => $cart_item_key,
                'cart_count' => WC()->cart->get_cart_contents_count()
            ));
        } else {
            wp_send_json_error('Failed to add to cart');
        }
    }
    
    private function has_dynamic_configuration($product_id) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}dpc_products WHERE wc_product_id = %d",
            $product_id
        ));
        return $count > 0;
    }
}
```

### 4. Admin Dashboard for CSV Upload
```php
<?php
class DPC_Admin_Page {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_dpc_upload_csv', array($this, 'handle_csv_upload'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Dynamic Product Configurator',
            'Product Configurator',
            'manage_woocommerce',
            'dpc-admin',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Dynamic Product Configurator</h1>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('dpc_upload_csv', 'dpc_nonce'); ?>
                <input type="hidden" name="action" value="dpc_upload_csv">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Products CSV</th>
                        <td>
                            <input type="file" name="products_csv" accept=".csv" required>
                            <p class="description">Upload products CSV file</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Attributes CSV</th>
                        <td>
                            <input type="file" name="attributes_csv" accept=".csv" required>
                            <p class="description">Upload attributes CSV file</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Complementary Products CSV</th>
                        <td>
                            <input type="file" name="complementary_csv" accept=".csv">
                            <p class="description">Upload complementary products CSV file (optional)</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Upload and Process CSV Files'); ?>
            </form>
        </div>
        <?php
    }
    
    public function handle_csv_upload() {
        if (!wp_verify_nonce($_POST['dpc_nonce'], 'dpc_upload_csv')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Insufficient permissions');
        }
        
        // Process CSV files
        $csv_parser = new DPC_CSV_Parser();
        
        try {
            if (isset($_FILES['products_csv'])) {
                $csv_parser->parse_products_csv($_FILES['products_csv']['tmp_name']);
            }
            
            if (isset($_FILES['attributes_csv'])) {
                $csv_parser->parse_attributes_csv($_FILES['attributes_csv']['tmp_name']);
            }
            
            if (isset($_FILES['complementary_csv'])) {
                $csv_parser->parse_complementary_csv($_FILES['complementary_csv']['tmp_name']);
            }
            
            wp_redirect(admin_url('admin.php?page=dpc-admin&message=success'));
        } catch (Exception $e) {
            wp_redirect(admin_url('admin.php?page=dpc-admin&message=error&error=' . urlencode($e->getMessage())));
        }
        exit;
    }
}
```

### 5. Frontend Integration
The React component would be built and enqueued as a WordPress script that:

1. **Replaces WooCommerce's default add-to-cart form**
2. **Integrates with WooCommerce cart system**
3. **Uses WordPress AJAX for all interactions**
4. **Maintains WooCommerce pricing and checkout flow**

### 6. Key Integration Points

#### A. Product Creation
- CSV upload creates both database entries AND WooCommerce products
- Links dynamic products to WooCommerce product IDs
- Maintains WooCommerce inventory, pricing, and shipping

#### B. Cart Integration
- Uses WooCommerce's cart system
- Stores custom attributes as cart item meta
- Modifies pricing based on attribute selections
- Preserves all WooCommerce cart functionality

#### C. Checkout Compatibility
- Custom attributes appear in cart/checkout
- Saved to order meta for fulfillment
- Compatible with all WooCommerce payment gateways
- Works with WooCommerce subscriptions, bookings, etc.

### 7. Shortcode Support
```php
// Allow placement anywhere with shortcode
add_shortcode('dynamic_product_configurator', function($atts) {
    $atts = shortcode_atts(array(
        'product_id' => '',
    ), $atts);
    
    return '<div id="dynamic-product-configurator" data-product-id="' . $atts['product_id'] . '"></div>';
});
```

## Benefits of This Approach

1. **Full WooCommerce Compatibility**: Works with all WooCommerce features
2. **No Core Modifications**: Extends rather than replaces WooCommerce
3. **Flexible Deployment**: Can be used on product pages or anywhere via shortcode
4. **Maintains Data Integrity**: All orders, customers, and analytics work normally
5. **Plugin Ecosystem Compatible**: Works with other WooCommerce plugins

This approach gives you the dynamic CSV-based product configuration while maintaining full WooCommerce compatibility and leveraging all its built-in features for payments, shipping, taxes, and order management.