<?php
/**
 * WooCommerce Product Parser Class
 * Extracts brand and model information from existing WooCommerce products
 */

if (!defined('ABSPATH')) {
    exit;
}

class DPC_WC_Product_Parser {
    
    private $brand_patterns = array(
        'samsung' => array('samsung', 'galaxy'),
        'apple' => array('iphone', 'apple'),
        'oneplus' => array('oneplus', 'one plus'),
        'xiaomi' => array('xiaomi', 'redmi', 'mi'),
        'oppo' => array('oppo'),
        'vivo' => array('vivo'),
        'realme' => array('realme'),
        'huawei' => array('huawei', 'honor'),
        'google' => array('pixel', 'google'),
        'motorola' => array('moto', 'motorola'),
        'nokia' => array('nokia'),
        'lg' => array('lg'),
        'sony' => array('sony', 'xperia')
    );
    
    private $model_patterns = array(
        'samsung' => array(
            'galaxy-s8' => array('galaxy s8', 's8'),
            'galaxy-s8-plus' => array('galaxy s8 plus', 's8 plus', 's8+'),
            'galaxy-s9' => array('galaxy s9', 's9'),
            'galaxy-s9-plus' => array('galaxy s9 plus', 's9 plus', 's9+'),
            'galaxy-s10' => array('galaxy s10', 's10'),
            'galaxy-s20' => array('galaxy s20', 's20'),
            'galaxy-s21' => array('galaxy s21', 's21'),
            'galaxy-note-8' => array('note 8', 'note8'),
            'galaxy-note-9' => array('note 9', 'note9'),
            'galaxy-note-10' => array('note 10', 'note10'),
            'galaxy-a50' => array('galaxy a50', 'a50'),
            'galaxy-a51' => array('galaxy a51', 'a51'),
            'galaxy-m31' => array('galaxy m31', 'm31')
        ),
        'apple' => array(
            'iphone-12' => array('iphone 12', '12'),
            'iphone-12-pro' => array('iphone 12 pro', '12 pro'),
            'iphone-12-mini' => array('iphone 12 mini', '12 mini'),
            'iphone-11' => array('iphone 11', '11'),
            'iphone-11-pro' => array('iphone 11 pro', '11 pro'),
            'iphone-x' => array('iphone x', 'iphone 10'),
            'iphone-xs' => array('iphone xs', 'xs'),
            'iphone-8' => array('iphone 8', '8'),
            'iphone-7' => array('iphone 7', '7')
        ),
        'oneplus' => array(
            'oneplus-9' => array('oneplus 9', 'one plus 9', '9'),
            'oneplus-8' => array('oneplus 8', 'one plus 8', '8'),
            'oneplus-7' => array('oneplus 7', 'one plus 7', '7'),
            'oneplus-nord' => array('oneplus nord', 'nord')
        )
    );
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Add admin action to parse existing products
        add_action('wp_ajax_dpc_parse_existing_products', array($this, 'parse_existing_products'));
    }
    
    /**
     * Parse existing WooCommerce products and extract brand/model
     */
    public function parse_existing_products() {
        check_ajax_referer('dpc_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        try {
            $products = $this->get_woocommerce_products();
            $parsed_count = 0;
            $errors = array();
            
            foreach ($products as $product) {
                $result = $this->parse_single_product($product);
                if ($result['success']) {
                    $parsed_count++;
                } else {
                    $errors[] = $result['error'];
                }
            }
            
            wp_send_json_success(array(
                'parsed_count' => $parsed_count,
                'total_products' => count($products),
                'errors' => $errors
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Error parsing products: ' . $e->getMessage());
        }
    }
    
    /**
     * Get WooCommerce products from mobile back cover category
     */
    private function get_woocommerce_products() {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => array('mobile-back-cover', 'phone-case', 'mobile-cover'),
                    'operator' => 'IN'
                )
            )
        );
        
        return get_posts($args);
    }
    
    /**
     * Parse a single WooCommerce product
     */
    private function parse_single_product($wp_product) {
        $product_name = $wp_product->post_title;
        $product_id = $this->generate_product_id($product_name);
        
        // Extract brand and model
        $brand = $this->extract_brand($product_name);
        $model = $this->extract_model($product_name, $brand);
        
        if (!$brand || !$model) {
            return array(
                'success' => false,
                'error' => "Could not extract brand/model from: {$product_name}"
            );
        }
        
        // Get product details
        $wc_product = wc_get_product($wp_product->ID);
        $base_price = $wc_product->get_regular_price();
        $image_url = wp_get_attachment_image_url($wc_product->get_image_id(), 'full');
        
        // Save to DPC tables
        $this->save_parsed_product(array(
            'product_id' => $product_id,
            'product_name' => $product_name,
            'base_price' => $base_price,
            'image_url' => $image_url,
            'category' => 'mobile-back-cover',
            'wc_product_id' => $wp_product->ID,
            'brand' => $brand,
            'model' => $model
        ));
        
        return array('success' => true);
    }
    
    /**
     * Extract brand from product name
     */
    private function extract_brand($product_name) {
        $name_lower = strtolower($product_name);
        
        foreach ($this->brand_patterns as $brand => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos($name_lower, strtolower($pattern)) !== false) {
                    return $brand;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Extract model from product name based on brand
     */
    private function extract_model($product_name, $brand) {
        if (!isset($this->model_patterns[$brand])) {
            return null;
        }
        
        $name_lower = strtolower($product_name);
        
        foreach ($this->model_patterns[$brand] as $model => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos($name_lower, strtolower($pattern)) !== false) {
                    return $model;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Generate product ID from name
     */
    private function generate_product_id($product_name) {
        $id = strtolower($product_name);
        $id = preg_replace('/[^a-z0-9\s]/', '', $id);
        $id = preg_replace('/\s+/', '-', $id);
        $id = substr($id, 0, 50);
        return $id . '-' . time();
    }
    
    /**
     * Save parsed product to DPC tables
     */
    private function save_parsed_product($data) {
        global $wpdb;
        
        // Save main product
        $wpdb->replace(
            $wpdb->prefix . 'dpc_products',
            array(
                'product_id' => $data['product_id'],
                'product_name' => $data['product_name'],
                'base_price' => $data['base_price'],
                'image_url' => $data['image_url'],
                'category' => $data['category'],
                'attribute_types' => 'brand,model',
                'wc_product_id' => $data['wc_product_id']
            ),
            array('%s', '%s', '%f', '%s', '%s', '%s', '%d')
        );
        
        // Save brand attribute
        $wpdb->replace(
            $wpdb->prefix . 'dpc_product_attributes',
            array(
                'product_id' => $data['product_id'],
                'attribute_type' => 'brand',
                'attribute_value' => $data['brand'],
                'attribute_label' => ucfirst($data['brand']),
                'price_modifier' => 0
            ),
            array('%s', '%s', '%s', '%s', '%f')
        );
        
        // Save model attribute
        $wpdb->replace(
            $wpdb->prefix . 'dpc_product_attributes',
            array(
                'product_id' => $data['product_id'],
                'attribute_type' => 'model',
                'attribute_value' => $data['model'],
                'attribute_label' => $this->format_model_label($data['model']),
                'price_modifier' => 0
            ),
            array('%s', '%s', '%s', '%s', '%f')
        );
        
        // Enable DPC for this WooCommerce product
        update_post_meta($data['wc_product_id'], '_dpc_enabled', 'yes');
        update_post_meta($data['wc_product_id'], '_dpc_product_id', $data['product_id']);
    }
    
    /**
     * Format model label for display
     */
    private function format_model_label($model) {
        $formatted = str_replace('-', ' ', $model);
        $formatted = ucwords($formatted);
        return $formatted;
    }
    
    /**
     * Get available brands from parsed products
     */
    public function get_available_brands() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT DISTINCT attribute_value as brand, attribute_label as label 
             FROM {$wpdb->prefix}dpc_product_attributes 
             WHERE attribute_type = 'brand' 
             ORDER BY attribute_label"
        );
    }
    
    /**
     * Get available models for a specific brand
     */
    public function get_models_for_brand($brand) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT pa.attribute_value as model, pa.attribute_label as label 
             FROM {$wpdb->prefix}dpc_product_attributes pa
             INNER JOIN {$wpdb->prefix}dpc_product_attributes pb ON pa.product_id = pb.product_id
             WHERE pa.attribute_type = 'model' 
             AND pb.attribute_type = 'brand' 
             AND pb.attribute_value = %s
             ORDER BY pa.attribute_label",
            $brand
        ));
    }
}