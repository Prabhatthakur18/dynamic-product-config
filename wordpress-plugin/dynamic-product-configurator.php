<?php
/**
 * Plugin Name: Dynamic Product Configurator
 * Plugin URI: https://github.com/your-repo/dynamic-product-configurator
 * Description: CSV-based dynamic product attributes for WooCommerce with React frontend
 * Version: 1.0.0
 * Author: Your Company
 * Author URI: https://yourcompany.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dynamic-product-configurator
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('DPC_VERSION', '1.0.0');
define('DPC_PLUGIN_FILE', __FILE__);
define('DPC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DPC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DPC_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>Dynamic Product Configurator</strong> requires WooCommerce to be installed and active.</p></div>';
    });
    return;
}

/**
 * Main Plugin Class
 */
class DynamicProductConfigurator {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Include required files
        $this->include_files();
        
        // Initialize components
        new DPC_Product_Manager();
        new DPC_WooCommerce_Integration();
        new DPC_Admin_Page();
        new DPC_Frontend_Handler();
        new DPC_AJAX_Handler();
        
        // Add shortcode
        add_shortcode('dynamic_product_configurator', array($this, 'shortcode_handler'));
    }
    
    /**
     * Include required files
     */
    private function include_files() {
        require_once DPC_PLUGIN_DIR . 'includes/class-product-manager.php';
        require_once DPC_PLUGIN_DIR . 'includes/class-csv-parser.php';
        require_once DPC_PLUGIN_DIR . 'includes/class-woocommerce-integration.php';
        require_once DPC_PLUGIN_DIR . 'includes/class-frontend-handler.php';
        require_once DPC_PLUGIN_DIR . 'includes/class-ajax-handler.php';
        require_once DPC_PLUGIN_DIR . 'admin/class-admin-page.php';
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        if ($this->should_load_scripts()) {
            // Enqueue React build
            wp_enqueue_script(
                'dpc-configurator',
                DPC_PLUGIN_URL . 'assets/js/configurator.min.js',
                array('wp-element', 'wp-api-fetch'),
                DPC_VERSION,
                true
            );
            
            wp_enqueue_style(
                'dpc-configurator',
                DPC_PLUGIN_URL . 'assets/css/configurator.min.css',
                array(),
                DPC_VERSION
            );
            
            // Localize script
            wp_localize_script('dpc-configurator', 'dpcAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dpc_nonce'),
                'wc_ajax_url' => WC_AJAX::get_endpoint('%%endpoint%%'),
                'currency_symbol' => get_woocommerce_currency_symbol(),
                'currency_position' => get_option('woocommerce_currency_pos'),
                'thousand_separator' => wc_get_price_thousand_separator(),
                'decimal_separator' => wc_get_price_decimal_separator(),
                'decimals' => wc_get_price_decimals(),
            ));
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'dpc-admin') !== false) {
            wp_enqueue_script(
                'dpc-admin',
                DPC_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                DPC_VERSION,
                true
            );
            
            wp_enqueue_style(
                'dpc-admin',
                DPC_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                DPC_VERSION
            );
        }
    }
    
    /**
     * Check if scripts should be loaded
     */
    private function should_load_scripts() {
        global $post;
        
        // Load on product pages
        if (is_product()) {
            return true;
        }
        
        // Load on shop pages
        if (is_shop() || is_product_category() || is_product_tag()) {
            return true;
        }
        
        // Load if shortcode is present
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'dynamic_product_configurator')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Shortcode handler
     */
    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'product_id' => '',
            'type' => 'product', // 'product' or 'selector'
            'class' => '',
        ), $atts);
        
        ob_start();
        
        if ($atts['type'] === 'selector') {
            // Brand/Model selector
            ?>
            <div id="dynamic-product-selector" 
                 class="dpc-configurator dpc-brand-selector <?php echo esc_attr($atts['class']); ?>">
                <div class="dpc-loading">Loading brand selector...</div>
            </div>
            <?php
        } else {
            // Specific product configurator
            ?>
            <div id="dynamic-product-configurator" 
                 class="dpc-configurator <?php echo esc_attr($atts['class']); ?>" 
                 data-product-id="<?php echo esc_attr($atts['product_id']); ?>">
                <div class="dpc-loading">Loading configurator...</div>
            </div>
            <?php
        }
        
        return ob_get_clean();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        $this->create_tables();
        $this->create_upload_directory();
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'dynamic-product-configurator',
            false,
            dirname(DPC_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Create database tables
     */
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
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY product_id (product_id),
            KEY wc_product_id (wc_product_id)
        ) $charset_collate;";
        
        // Attributes table
        $sql2 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dpc_product_attributes (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            product_id varchar(100) NOT NULL,
            attribute_type varchar(100) NOT NULL,
            attribute_value varchar(100) NOT NULL,
            attribute_label varchar(255) NOT NULL,
            price_modifier decimal(10,2) DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY attribute_type (attribute_type),
            UNIQUE KEY unique_attribute (product_id, attribute_type, attribute_value)
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
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY main_product_id (main_product_id),
            UNIQUE KEY unique_complementary (main_product_id, complementary_product_id)
        ) $charset_collate;";
        
        // Bulk purchase requests table
        $sql4 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dpc_bulk_requests (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            product_id varchar(100) NOT NULL,
            quantity int(11) NOT NULL,
            attributes text,
            contact_name varchar(255) NOT NULL,
            contact_email varchar(255) NOT NULL,
            contact_phone varchar(50),
            company varchar(255),
            message text,
            status varchar(50) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
        dbDelta($sql4);
        
        // Update version
        update_option('dpc_db_version', DPC_VERSION);
    }
    
    /**
     * Create upload directory
     */
    private function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        $dpc_dir = $upload_dir['basedir'] . '/dpc-uploads';
        
        if (!file_exists($dpc_dir)) {
            wp_mkdir_p($dpc_dir);
            
            // Create .htaccess for security
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "<Files *.php>\n";
            $htaccess_content .= "deny from all\n";
            $htaccess_content .= "</Files>\n";
            
            file_put_contents($dpc_dir . '/.htaccess', $htaccess_content);
        }
    }
}

// Initialize the plugin
function dpc_init() {
    return DynamicProductConfigurator::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'dpc_init');

// Utility functions
function dpc_get_product_data($product_id) {
    global $wpdb;
    
    $product = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}dpc_products WHERE product_id = %s OR wc_product_id = %d",
        $product_id,
        $product_id
    ));
    
    if ($product) {
        $attributes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dpc_product_attributes WHERE product_id = %s ORDER BY sort_order ASC",
            $product->product_id
        ));
        
        $complementary = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dpc_complementary_products WHERE main_product_id = %s ORDER BY sort_order ASC",
            $product->product_id
        ));
        
        return array(
            'product' => $product,
            'attributes' => $attributes,
            'complementary' => $complementary
        );
    }
    
    return false;
}

function dpc_format_price($price) {
    return wc_price($price);
}

function dpc_log($message, $level = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[DPC] ' . $message);
    }
}