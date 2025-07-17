<?php
/**
 * WooCommerce Enhancement Class
 * Modifies WooCommerce add-to-cart functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class DPC_WooCommerce_Enhancement {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Add custom fields to product edit page
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_custom_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_custom_fields'));
        
        // Modify single product page
        add_action('woocommerce_single_product_summary', array($this, 'maybe_replace_add_to_cart'), 25);
        
        // Add custom columns to products admin list
        add_filter('manage_edit-product_columns', array($this, 'add_product_columns'));
        add_action('manage_product_posts_custom_column', array($this, 'populate_product_columns'), 10, 2);
        
        // Cart integration
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_cart_item_data'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_order_item_data'), 10, 4);
    }
    
    /**
     * Add custom fields to product edit page
     */
    public function add_custom_fields() {
        global $post;
        
        echo '<div class="options_group">';
        echo '<h3>' . __('Dynamic Product Configuration', 'dynamic-product-configurator') . '</h3>';
        
        // Enable/Disable
        woocommerce_wp_checkbox(array(
            'id' => '_dpc_enabled',
            'label' => __('Enable Brand/Model Selection', 'dynamic-product-configurator'),
            'description' => __('Replace default add-to-cart with brand/model selector', 'dynamic-product-configurator')
        ));
        
        // Brand
        woocommerce_wp_text_input(array(
            'id' => '_dpc_brand',
            'label' => __('Brand', 'dynamic-product-configurator'),
            'description' => __('Product brand (e.g., Samsung, Apple)', 'dynamic-product-configurator'),
            'placeholder' => 'Samsung'
        ));
        
        // Model
        woocommerce_wp_text_input(array(
            'id' => '_dpc_model',
            'label' => __('Model', 'dynamic-product-configurator'),
            'description' => __('Product model (e.g., Galaxy S8, iPhone 12)', 'dynamic-product-configurator'),
            'placeholder' => 'Galaxy S8'
        ));
        
        // Recommended Products
        woocommerce_wp_text_input(array(
            'id' => '_dpc_recommended_products',
            'label' => __('Recommended Product IDs', 'dynamic-product-configurator'),
            'description' => __('Comma-separated product IDs (e.g., 123,456,789)', 'dynamic-product-configurator'),
            'placeholder' => '123,456,789'
        ));
        
        // You May Be Interested Products
        woocommerce_wp_text_input(array(
            'id' => '_dpc_interested_products',
            'label' => __('You May Be Interested Product IDs', 'dynamic-product-configurator'),
            'description' => __('Comma-separated product IDs (e.g., 123,456,789)', 'dynamic-product-configurator'),
            'placeholder' => '123,456,789'
        ));
        
        echo '</div>';
    }
    
    /**
     * Save custom fields
     */
    public function save_custom_fields($post_id) {
        $fields = array(
            '_dpc_enabled',
            '_dpc_brand',
            '_dpc_model',
            '_dpc_recommended_products',
            '_dpc_interested_products'
        );
        
        foreach ($fields as $field) {
            if ($field === '_dpc_enabled') {
                $value = isset($_POST[$field]) ? 'yes' : 'no';
            } else {
                $value = isset($_POST[$field]) ? sanitize_text_field($_POST[$field]) : '';
            }
            update_post_meta($post_id, $field, $value);
        }
    }
    
    /**
     * Add custom columns to products admin list
     */
    public function add_product_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            
            if ($key === 'name') {
                $new_columns['dpc_enabled'] = __('DPC Enabled', 'dynamic-product-configurator');
                $new_columns['dpc_brand'] = __('Brand', 'dynamic-product-configurator');
                $new_columns['dpc_model'] = __('Model', 'dynamic-product-configurator');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Populate custom columns
     */
    public function populate_product_columns($column, $post_id) {
        switch ($column) {
            case 'dpc_enabled':
                $enabled = get_post_meta($post_id, '_dpc_enabled', true);
                if ($enabled === 'yes') {
                    echo '<span style="color: green;">✓ Enabled</span>';
                } else {
                    echo '<span style="color: red;">✗ Disabled</span>';
                }
                break;
                
            case 'dpc_brand':
                $brand = get_post_meta($post_id, '_dpc_brand', true);
                echo !empty($brand) ? esc_html($brand) : '-';
                break;
                
            case 'dpc_model':
                $model = get_post_meta($post_id, '_dpc_model', true);
                echo !empty($model) ? esc_html($model) : '-';
                break;
        }
    }
    
    /**
     * Maybe replace add to cart on single product page
     */
    public function maybe_replace_add_to_cart() {
        global $product;
        
        if (dpc_is_enabled($product->get_id())) {
            // Remove default add to cart
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
            
            // Add our custom selector
            $this->render_brand_model_selector($product->get_id());
        }
    }
    
    /**
     * Render brand/model selector
     */
    private function render_brand_model_selector($product_id) {
        $brand = dpc_get_product_brand($product_id);
        $model = dpc_get_product_model($product_id);
        $recommended = dpc_get_recommended_products($product_id);
        $interested = dpc_get_interested_products($product_id);
        
        // Get all available brands and models
        $all_brands = $this->get_all_brands();
        $all_models = $this->get_all_models();
        
        include DPC_PLUGIN_DIR . 'templates/brand-model-selector.php';
    }
    
    /**
     * Get all available brands
     */
    private function get_all_brands() {
        global $wpdb;
        
        $brands = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_dpc_brand' 
            AND meta_value != '' 
            ORDER BY meta_value
        ");
        
        return array_filter($brands);
    }
    
    /**
     * Get all available models
     */
    private function get_all_models() {
        global $wpdb;
        
        $models = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_dpc_model' 
            AND meta_value != '' 
            ORDER BY meta_value
        ");
        
        return array_filter($models);
    }
    
    /**
     * Add cart item data
     */
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        if (isset($_POST['dpc_brand']) && isset($_POST['dpc_model'])) {
            $cart_item_data['dpc_brand'] = sanitize_text_field($_POST['dpc_brand']);
            $cart_item_data['dpc_model'] = sanitize_text_field($_POST['dpc_model']);
        }
        
        return $cart_item_data;
    }
    
    /**
     * Display cart item data
     */
    public function display_cart_item_data($item_data, $cart_item) {
        if (isset($cart_item['dpc_brand'])) {
            $item_data[] = array(
                'key' => __('Brand', 'dynamic-product-configurator'),
                'value' => $cart_item['dpc_brand']
            );
        }
        
        if (isset($cart_item['dpc_model'])) {
            $item_data[] = array(
                'key' => __('Model', 'dynamic-product-configurator'),
                'value' => $cart_item['dpc_model']
            );
        }
        
        return $item_data;
    }
    
    /**
     * Save order item data
     */
    public function save_order_item_data($item, $cart_item_key, $values, $order) {
        if (isset($values['dpc_brand'])) {
            $item->add_meta_data(__('Brand', 'dynamic-product-configurator'), $values['dpc_brand']);
        }
        
        if (isset($values['dpc_model'])) {
            $item->add_meta_data(__('Model', 'dynamic-product-configurator'), $values['dpc_model']);
        }
    }
}