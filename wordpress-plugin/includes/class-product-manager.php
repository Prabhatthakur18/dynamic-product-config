<?php
/**
 * Product Manager Class
 * Handles product data management and CRUD operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class DPC_Product_Manager {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Hook into WooCommerce product save
        add_action('woocommerce_process_product_meta', array($this, 'save_product_meta'));
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_product_fields'));
    }
    
    /**
     * Add custom fields to WooCommerce product edit page
     */
    public function add_product_fields() {
        global $post;
        
        echo '<div class="options_group">';
        
        woocommerce_wp_checkbox(array(
            'id' => '_dpc_enabled',
            'label' => __('Enable Dynamic Configuration', 'dynamic-product-configurator'),
            'description' => __('Enable CSV-based dynamic configuration for this product', 'dynamic-product-configurator')
        ));
        
        woocommerce_wp_text_input(array(
            'id' => '_dpc_product_id',
            'label' => __('DPC Product ID', 'dynamic-product-configurator'),
            'description' => __('Link this WooCommerce product to a CSV product ID', 'dynamic-product-configurator'),
            'placeholder' => 'e.g., phone-case-001'
        ));
        
        echo '</div>';
    }
    
    /**
     * Save custom product meta
     */
    public function save_product_meta($post_id) {
        $dpc_enabled = isset($_POST['_dpc_enabled']) ? 'yes' : 'no';
        $dpc_product_id = sanitize_text_field($_POST['_dpc_product_id']);
        
        update_post_meta($post_id, '_dpc_enabled', $dpc_enabled);
        update_post_meta($post_id, '_dpc_product_id', $dpc_product_id);
        
        // Update the link in our custom table
        if ($dpc_enabled === 'yes' && !empty($dpc_product_id)) {
            $this->link_wc_product($dpc_product_id, $post_id);
        }
    }
    
    /**
     * Link WooCommerce product to DPC product
     */
    public function link_wc_product($dpc_product_id, $wc_product_id) {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'dpc_products',
            array('wc_product_id' => $wc_product_id),
            array('product_id' => $dpc_product_id),
            array('%d'),
            array('%s')
        );
    }
    
    /**
     * Create or update product
     */
    public function save_product($product_data) {
        global $wpdb;
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}dpc_products WHERE product_id = %s",
            $product_data['product_id']
        ));
        
        $data = array(
            'product_id' => $product_data['product_id'],
            'product_name' => $product_data['product_name'],
            'base_price' => $product_data['base_price'],
            'image_url' => $product_data['image_url'],
            'category' => $product_data['category'],
            'attribute_types' => is_array($product_data['attribute_types']) 
                ? implode(',', $product_data['attribute_types']) 
                : $product_data['attribute_types']
        );
        
        if ($existing) {
            $result = $wpdb->update(
                $wpdb->prefix . 'dpc_products',
                $data,
                array('product_id' => $product_data['product_id']),
                array('%s', '%s', '%f', '%s', '%s', '%s'),
                array('%s')
            );
        } else {
            $result = $wpdb->insert(
                $wpdb->prefix . 'dpc_products',
                $data,
                array('%s', '%s', '%f', '%s', '%s', '%s')
            );
        }
        
        return $result !== false;
    }
    
    /**
     * Save product attribute
     */
    public function save_attribute($attribute_data) {
        global $wpdb;
        
        $data = array(
            'product_id' => $attribute_data['product_id'],
            'attribute_type' => $attribute_data['attribute_type'],
            'attribute_value' => $attribute_data['attribute_value'],
            'attribute_label' => $attribute_data['attribute_label'],
            'price_modifier' => $attribute_data['price_modifier'],
            'sort_order' => isset($attribute_data['sort_order']) ? $attribute_data['sort_order'] : 0
        );
        
        $result = $wpdb->replace(
            $wpdb->prefix . 'dpc_product_attributes',
            $data,
            array('%s', '%s', '%s', '%s', '%f', '%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Save complementary product
     */
    public function save_complementary($complementary_data) {
        global $wpdb;
        
        $data = array(
            'main_product_id' => $complementary_data['main_product_id'],
            'complementary_product_id' => $complementary_data['complementary_product_id'],
            'complementary_name' => $complementary_data['complementary_name'],
            'price' => $complementary_data['price'],
            'original_price' => isset($complementary_data['original_price']) ? $complementary_data['original_price'] : null,
            'image_url' => isset($complementary_data['image_url']) ? $complementary_data['image_url'] : '',
            'sort_order' => isset($complementary_data['sort_order']) ? $complementary_data['sort_order'] : 0
        );
        
        $result = $wpdb->replace(
            $wpdb->prefix . 'dpc_complementary_products',
            $data,
            array('%s', '%s', '%s', '%f', '%f', '%s', '%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get product by ID
     */
    public function get_product($product_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dpc_products WHERE product_id = %s OR wc_product_id = %d",
            $product_id,
            $product_id
        ));
    }
    
    /**
     * Get product attributes
     */
    public function get_product_attributes($product_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dpc_product_attributes WHERE product_id = %s ORDER BY sort_order ASC, attribute_label ASC",
            $product_id
        ));
    }
    
    /**
     * Get complementary products
     */
    public function get_complementary_products($product_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dpc_complementary_products WHERE main_product_id = %s ORDER BY sort_order ASC",
            $product_id
        ));
    }
    
    /**
     * Delete product and related data
     */
    public function delete_product($product_id) {
        global $wpdb;
        
        // Delete attributes
        $wpdb->delete(
            $wpdb->prefix . 'dpc_product_attributes',
            array('product_id' => $product_id),
            array('%s')
        );
        
        // Delete complementary products
        $wpdb->delete(
            $wpdb->prefix . 'dpc_complementary_products',
            array('main_product_id' => $product_id),
            array('%s')
        );
        
        // Delete product
        return $wpdb->delete(
            $wpdb->prefix . 'dpc_products',
            array('product_id' => $product_id),
            array('%s')
        );
    }
    
    /**
     * Get all products
     */
    public function get_all_products($limit = 50, $offset = 0) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dpc_products ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }
    
    /**
     * Search products
     */
    public function search_products($search_term, $limit = 20) {
        global $wpdb;
        
        $search_term = '%' . $wpdb->esc_like($search_term) . '%';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dpc_products 
             WHERE product_name LIKE %s OR product_id LIKE %s 
             ORDER BY product_name ASC LIMIT %d",
            $search_term,
            $search_term,
            $limit
        ));
    }
}