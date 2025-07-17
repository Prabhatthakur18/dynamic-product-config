<?php
/**
 * AJAX Handler Class
 * Handles AJAX requests for DPC functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class DPC_AJAX_Handler {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // AJAX handlers for logged in users
        add_action('wp_ajax_dpc_get_models_for_brand', array($this, 'get_models_for_brand'));
        add_action('wp_ajax_dpc_get_products_by_brand_model', array($this, 'get_products_by_brand_model'));
        add_action('wp_ajax_dpc_bulk_enable_all', array($this, 'bulk_enable_all'));
        add_action('wp_ajax_dpc_bulk_disable_all', array($this, 'bulk_disable_all'));
        add_action('wp_ajax_dpc_bulk_clear_all', array($this, 'bulk_clear_all'));
        
        // AJAX handlers for non-logged in users
        add_action('wp_ajax_nopriv_dpc_get_models_for_brand', array($this, 'get_models_for_brand'));
        add_action('wp_ajax_nopriv_dpc_get_products_by_brand_model', array($this, 'get_products_by_brand_model'));
    }
    
    /**
     * Get models for specific brand
     */
    public function get_models_for_brand() {
        check_ajax_referer('dpc_nonce', 'nonce');
        
        $brand = sanitize_text_field($_POST['brand']);
        
        if (empty($brand)) {
            wp_send_json_error('Brand is required');
        }
        
        global $wpdb;
        
        $models = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm2.meta_value 
            FROM {$wpdb->postmeta} pm1
            INNER JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
            WHERE pm1.meta_key = '_dpc_brand' 
            AND pm1.meta_value = %s
            AND pm2.meta_key = '_dpc_model'
            AND pm2.meta_value != ''
            ORDER BY pm2.meta_value
        ", $brand));
        
        wp_send_json_success($models);
    }
    
    /**
     * Get products by brand and model
     */
    public function get_products_by_brand_model() {
        check_ajax_referer('dpc_nonce', 'nonce');
        
        $brand = sanitize_text_field($_POST['brand']);
        $model = sanitize_text_field($_POST['model']);
        
        if (empty($brand) || empty($model)) {
            wp_send_json_error('Brand and model are required');
        }
        
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_dpc_brand',
                    'value' => $brand,
                    'compare' => '='
                ),
                array(
                    'key' => '_dpc_model',
                    'value' => $model,
                    'compare' => '='
                ),
                array(
                    'key' => '_dpc_enabled',
                    'value' => 'yes',
                    'compare' => '='
                )
            )
        );
        
        $products = get_posts($args);
        $product_data = array();
        
        foreach ($products as $product) {
            $wc_product = wc_get_product($product->ID);
            if ($wc_product) {
                $product_data[] = array(
                    'id' => $product->ID,
                    'name' => $product->post_title,
                    'price' => $wc_product->get_price_html(),
                    'image' => get_the_post_thumbnail_url($product->ID, 'thumbnail'),
                    'url' => get_permalink($product->ID)
                );
            }
        }
        
        wp_send_json_success($product_data);
    }
    
    /**
     * Bulk enable DPC for all products with brand/model
     */
    public function bulk_enable_all() {
        check_ajax_referer('dpc_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        // Get all products that have both brand and model
        $product_ids = $wpdb->get_col("
            SELECT DISTINCT pm1.post_id 
            FROM {$wpdb->postmeta} pm1
            INNER JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
            INNER JOIN {$wpdb->posts} p ON pm1.post_id = p.ID
            WHERE pm1.meta_key = '_dpc_brand' 
            AND pm1.meta_value != ''
            AND pm2.meta_key = '_dpc_model'
            AND pm2.meta_value != ''
            AND p.post_type = 'product'
            AND p.post_status = 'publish'
        ");
        
        $count = 0;
        foreach ($product_ids as $product_id) {
            update_post_meta($product_id, '_dpc_enabled', 'yes');
            $count++;
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('DPC enabled for %d products', 'dynamic-product-configurator'), $count),
            'count' => $count
        ));
    }
    
    /**
     * Bulk disable DPC for all products
     */
    public function bulk_disable_all() {
        check_ajax_referer('dpc_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $count = $wpdb->query("
            UPDATE {$wpdb->postmeta} 
            SET meta_value = 'no' 
            WHERE meta_key = '_dpc_enabled'
        ");
        
        wp_send_json_success(array(
            'message' => sprintf(__('DPC disabled for %d products', 'dynamic-product-configurator'), $count),
            'count' => $count
        ));
    }
    
    /**
     * Bulk clear all DPC data
     */
    public function bulk_clear_all() {
        check_ajax_referer('dpc_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $meta_keys = array(
            '_dpc_enabled',
            '_dpc_brand',
            '_dpc_model',
            '_dpc_recommended_products',
            '_dpc_interested_products'
        );
        
        $count = 0;
        foreach ($meta_keys as $meta_key) {
            $deleted = $wpdb->query($wpdb->prepare("
                DELETE FROM {$wpdb->postmeta} 
                WHERE meta_key = %s
            ", $meta_key));
            $count += $deleted;
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('Cleared %d DPC data entries', 'dynamic-product-configurator'), $count),
            'count' => $count
        ));
    }
}