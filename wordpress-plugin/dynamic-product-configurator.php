<?php
/**
 * Plugin Name: Dynamic Product Configurator for WooCommerce
 * Plugin URI: https://github.com/your-repo/dynamic-product-configurator
 * Description: Enhances WooCommerce add-to-cart with brand/model selection and product recommendations
 * Version: 1.0.0
 * Author: Your Company
 * Author URI: https://yourcompany.com
 * License: GPL v2 or later
 * Text Domain: dynamic-product-configurator
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
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }
    
    public function init() {
        // Include required files
        $this->include_files();
        
        // Initialize components
        new DPC_WooCommerce_Enhancement();
        new DPC_Admin_Page();
        new DPC_CSV_Handler();
        new DPC_AJAX_Handler();
        new DPC_Frontend_Handler();
    }
    
    private function include_files() {
        require_once DPC_PLUGIN_DIR . 'includes/class-woocommerce-enhancement.php';
        require_once DPC_PLUGIN_DIR . 'includes/class-csv-handler.php';
        require_once DPC_PLUGIN_DIR . 'includes/class-ajax-handler.php';
        require_once DPC_PLUGIN_DIR . 'includes/class-frontend-handler.php';
        require_once DPC_PLUGIN_DIR . 'admin/class-admin-page.php';
    }
    
    public function enqueue_scripts() {
        if (is_product() || is_shop() || is_product_category()) {
            wp_enqueue_script(
                'dpc-frontend',
                DPC_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery', 'wc-add-to-cart'),
                DPC_VERSION,
                true
            );
            
            wp_enqueue_style(
                'dpc-frontend',
                DPC_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                DPC_VERSION
            );
            
            wp_localize_script('dpc-frontend', 'dpcAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dpc_nonce'),
                'currency_symbol' => get_woocommerce_currency_symbol(),
            ));
        }
    }
    
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'dpc-admin') !== false || strpos($hook, 'post.php') !== false || strpos($hook, 'post-new.php') !== false) {
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
    
    public function activate() {
        $this->add_product_meta_fields();
        flush_rewrite_rules();
    }
    
    /**
     * Add custom meta fields to all existing WooCommerce products
     */
    private function add_product_meta_fields() {
        global $wpdb;
        
        // Get all WooCommerce products
        $products = get_posts(array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($products as $product) {
            // Add default meta fields if they don't exist
            if (!get_post_meta($product->ID, '_dpc_enabled', true)) {
                update_post_meta($product->ID, '_dpc_enabled', 'no');
            }
            if (!get_post_meta($product->ID, '_dpc_brand', true)) {
                update_post_meta($product->ID, '_dpc_brand', '');
            }
            if (!get_post_meta($product->ID, '_dpc_model', true)) {
                update_post_meta($product->ID, '_dpc_model', '');
            }
            if (!get_post_meta($product->ID, '_dpc_recommended_products', true)) {
                update_post_meta($product->ID, '_dpc_recommended_products', '');
            }
            if (!get_post_meta($product->ID, '_dpc_interested_products', true)) {
                update_post_meta($product->ID, '_dpc_interested_products', '');
            }
        }
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(
            'dynamic-product-configurator',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    DynamicProductConfigurator::get_instance();
});

// Utility functions
function dpc_get_product_brand($product_id) {
    return get_post_meta($product_id, '_dpc_brand', true);
}

function dpc_get_product_model($product_id) {
    return get_post_meta($product_id, '_dpc_model', true);
}

function dpc_get_recommended_products($product_id) {
    $recommended = get_post_meta($product_id, '_dpc_recommended_products', true);
    return !empty($recommended) ? explode(',', $recommended) : array();
}

function dpc_get_interested_products($product_id) {
    $interested = get_post_meta($product_id, '_dpc_interested_products', true);
    return !empty($interested) ? explode(',', $interested) : array();
}

function dpc_is_enabled($product_id) {
    return get_post_meta($product_id, '_dpc_enabled', true) === 'yes';
}