<?php
/**
 * WooCommerce Product Parser Class
 * Parses existing WooCommerce products and extracts brand/model information
 * Fixed to work with actual product data structure
 */

if (!defined('ABSPATH')) {
    exit;
}

class DPC_WC_Product_Parser {
    
    private $brand_patterns = array(
        'samsung' => array(
            'patterns' => array('samsung', 'galaxy'),
            'label' => 'Samsung'
        ),
        'apple' => array(
            'patterns' => array('iphone', 'apple'),
            'label' => 'Apple'
        ),
        'oneplus' => array(
            'patterns' => array('oneplus', 'one plus'),
            'label' => 'OnePlus'
        ),
        'xiaomi' => array(
            'patterns' => array('xiaomi', 'redmi', 'mi'),
            'label' => 'Xiaomi'
        ),
        'oppo' => array(
            'patterns' => array('oppo'),
            'label' => 'Oppo'
        ),
        'vivo' => array(
            'patterns' => array('vivo'),
            'label' => 'Vivo'
        ),
        'realme' => array(
            'patterns' => array('realme'),
            'label' => 'Realme'
        ),
        'huawei' => array(
            'patterns' => array('huawei', 'honor'),
            'label' => 'Huawei'
        ),
        'google' => array(
            'patterns' => array('pixel', 'google'),
            'label' => 'Google'
        ),
        'motorola' => array(
            'patterns' => array('moto', 'motorola'),
            'label' => 'Motorola'
        ),
        'nokia' => array(
            'patterns' => array('nokia'),
            'label' => 'Nokia'
        ),
        'lg' => array(
            'patterns' => array('lg'),
            'label' => 'LG'
        ),
        'sony' => array(
            'patterns' => array('sony', 'xperia'),
            'label' => 'Sony'
        )
    );
    
    private $model_patterns = array(
        'samsung' => array(
            'galaxy-s8' => array(
                'patterns' => array('galaxy s8', 's8'),
                'label' => 'Galaxy S8',
                'exclude' => array('plus', '+')
            ),
            'galaxy-s8-plus' => array(
                'patterns' => array('galaxy s8 plus', 's8 plus', 's8+'),
                'label' => 'Galaxy S8 Plus'
            ),
            'galaxy-s9' => array(
                'patterns' => array('galaxy s9', 's9'),
                'label' => 'Galaxy S9',
                'exclude' => array('plus', '+')
            ),
            'galaxy-s9-plus' => array(
                'patterns' => array('galaxy s9 plus', 's9 plus', 's9+'),
                'label' => 'Galaxy S9 Plus'
            ),
            'galaxy-s10' => array(
                'patterns' => array('galaxy s10', 's10'),
                'label' => 'Galaxy S10',
                'exclude' => array('plus', '+')
            ),
            'galaxy-s10-plus' => array(
                'patterns' => array('galaxy s10 plus', 's10 plus', 's10+'),
                'label' => 'Galaxy S10 Plus'
            ),
            'galaxy-s20' => array(
                'patterns' => array('galaxy s20', 's20'),
                'label' => 'Galaxy S20'
            ),
            'galaxy-s21' => array(
                'patterns' => array('galaxy s21', 's21'),
                'label' => 'Galaxy S21'
            ),
            'galaxy-note-8' => array(
                'patterns' => array('note 8', 'note8'),
                'label' => 'Galaxy Note 8'
            ),
            'galaxy-note-9' => array(
                'patterns' => array('note 9', 'note9'),
                'label' => 'Galaxy Note 9'
            ),
            'galaxy-note-10' => array(
                'patterns' => array('note 10', 'note10'),
                'label' => 'Galaxy Note 10'
            ),
            'galaxy-a50' => array(
                'patterns' => array('galaxy a50', 'a50'),
                'label' => 'Galaxy A50'
            ),
            'galaxy-a51' => array(
                'patterns' => array('galaxy a51', 'a51'),
                'label' => 'Galaxy A51'
            ),
            'galaxy-m31' => array(
                'patterns' => array('galaxy m31', 'm31'),
                'label' => 'Galaxy M31'
            )
        ),
        'apple' => array(
            'iphone-12' => array(
                'patterns' => array('iphone 12'),
                'label' => 'iPhone 12',
                'exclude' => array('pro', 'mini')
            ),
            'iphone-12-pro' => array(
                'patterns' => array('iphone 12 pro'),
                'label' => 'iPhone 12 Pro',
                'exclude' => array('max')
            ),
            'iphone-12-pro-max' => array(
                'patterns' => array('iphone 12 pro max'),
                'label' => 'iPhone 12 Pro Max'
            ),
            'iphone-12-mini' => array(
                'patterns' => array('iphone 12 mini'),
                'label' => 'iPhone 12 Mini'
            ),
            'iphone-11' => array(
                'patterns' => array('iphone 11'),
                'label' => 'iPhone 11',
                'exclude' => array('pro')
            ),
            'iphone-11-pro' => array(
                'patterns' => array('iphone 11 pro'),
                'label' => 'iPhone 11 Pro',
                'exclude' => array('max')
            ),
            'iphone-11-pro-max' => array(
                'patterns' => array('iphone 11 pro max'),
                'label' => 'iPhone 11 Pro Max'
            ),
            'iphone-x' => array(
                'patterns' => array('iphone x', 'iphone 10'),
                'label' => 'iPhone X',
                'exclude' => array('xs', 'xr')
            ),
            'iphone-xs' => array(
                'patterns' => array('iphone xs'),
                'label' => 'iPhone XS',
                'exclude' => array('max')
            ),
            'iphone-xs-max' => array(
                'patterns' => array('iphone xs max'),
                'label' => 'iPhone XS Max'
            ),
            'iphone-xr' => array(
                'patterns' => array('iphone xr'),
                'label' => 'iPhone XR'
            ),
            'iphone-8' => array(
                'patterns' => array('iphone 8'),
                'label' => 'iPhone 8',
                'exclude' => array('plus')
            ),
            'iphone-8-plus' => array(
                'patterns' => array('iphone 8 plus'),
                'label' => 'iPhone 8 Plus'
            ),
            'iphone-7' => array(
                'patterns' => array('iphone 7'),
                'label' => 'iPhone 7',
                'exclude' => array('plus')
            ),
            'iphone-7-plus' => array(
                'patterns' => array('iphone 7 plus'),
                'label' => 'iPhone 7 Plus'
            )
        ),
        'oneplus' => array(
            'oneplus-9' => array(
                'patterns' => array('oneplus 9', 'one plus 9'),
                'label' => 'OnePlus 9',
                'exclude' => array('pro')
            ),
            'oneplus-9-pro' => array(
                'patterns' => array('oneplus 9 pro', 'one plus 9 pro'),
                'label' => 'OnePlus 9 Pro'
            ),
            'oneplus-8' => array(
                'patterns' => array('oneplus 8', 'one plus 8'),
                'label' => 'OnePlus 8',
                'exclude' => array('pro')
            ),
            'oneplus-8-pro' => array(
                'patterns' => array('oneplus 8 pro', 'one plus 8 pro'),
                'label' => 'OnePlus 8 Pro'
            ),
            'oneplus-7' => array(
                'patterns' => array('oneplus 7', 'one plus 7'),
                'label' => 'OnePlus 7',
                'exclude' => array('pro')
            ),
            'oneplus-7-pro' => array(
                'patterns' => array('oneplus 7 pro', 'one plus 7 pro'),
                'label' => 'OnePlus 7 Pro'
            ),
            'oneplus-nord' => array(
                'patterns' => array('oneplus nord', 'one plus nord'),
                'label' => 'OnePlus Nord'
            )
        )
    );
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Add admin action to parse existing products
        add_action('wp_ajax_dpc_parse_existing_products', array($this, 'parse_existing_products'));
        add_action('wp_ajax_dpc_auto_enable_all_products', array($this, 'auto_enable_all_products'));
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
            // Get all WooCommerce products
            $products = $this->get_all_woocommerce_products();
            $parsed_count = 0;
            $errors = array();
            $skipped_count = 0;
            
            foreach ($products as $product) {
                $result = $this->parse_single_product($product);
                if ($result['success']) {
                    $parsed_count++;
                } elseif ($result['skipped']) {
                    $skipped_count++;
                } else {
                    $errors[] = $result['error'];
                }
            }
            
            wp_send_json_success(array(
                'parsed_count' => $parsed_count,
                'skipped_count' => $skipped_count,
                'total_products' => count($products),
                'errors' => array_slice($errors, 0, 10) // Limit errors to first 10
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Error parsing products: ' . $e->getMessage());
        }
    }
    
    /**
     * Auto-enable DPC for all mobile back cover products
     */
    public function auto_enable_all_products() {
        check_ajax_referer('dpc_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        try {
            $products = $this->get_all_woocommerce_products();
            $enabled_count = 0;
            
            foreach ($products as $product) {
                // Enable DPC for this product
                update_post_meta($product->ID, '_dpc_enabled', 'yes');
                $enabled_count++;
            }
            
            wp_send_json_success(array(
                'enabled_count' => $enabled_count,
                'message' => "DPC enabled for {$enabled_count} products"
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Error enabling products: ' . $e->getMessage());
        }
    }
    
    /**
     * Get all WooCommerce products (not just mobile back covers)
     */
    private function get_all_woocommerce_products() {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_dpc_enabled',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_dpc_enabled',
                    'value' => 'yes',
                    'compare' => '!='
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
        $product_id = $this->generate_product_id($product_name, $wp_product->ID);
        
        // Extract brand and model
        $brand = $this->extract_brand($product_name);
        $model = $this->extract_model($product_name, $brand);
        
        if (!$brand || !$model) {
            return array(
                'success' => false,
                'skipped' => true,
                'error' => "Could not extract brand/model from: {$product_name}"
            );
        }
        
        // Get product details
        $wc_product = wc_get_product($wp_product->ID);
        $base_price = $wc_product->get_regular_price() ?: $wc_product->get_price();
        $image_id = $wc_product->get_image_id();
        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'full') : '';
        
        // Get category
        $categories = wp_get_post_terms($wp_product->ID, 'product_cat');
        $category = !empty($categories) ? $categories[0]->slug : 'mobile-back-cover';
        
        // Save to DPC tables
        $this->save_parsed_product(array(
            'product_id' => $product_id,
            'product_name' => $product_name,
            'base_price' => $base_price,
            'image_url' => $image_url,
            'category' => $category,
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
        
        foreach ($this->brand_patterns as $brand => $brand_data) {
            foreach ($brand_data['patterns'] as $pattern) {
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
        
        // Sort models by specificity (longer patterns first)
        $models = $this->model_patterns[$brand];
        uksort($models, function($a, $b) use ($models) {
            $a_patterns = implode(' ', $models[$a]['patterns']);
            $b_patterns = implode(' ', $models[$b]['patterns']);
            return strlen($b_patterns) - strlen($a_patterns);
        });
        
        foreach ($models as $model => $model_data) {
            foreach ($model_data['patterns'] as $pattern) {
                if (strpos($name_lower, strtolower($pattern)) !== false) {
                    // Check for exclusions
                    if (isset($model_data['exclude'])) {
                        $excluded = false;
                        foreach ($model_data['exclude'] as $exclude_pattern) {
                            if (strpos($name_lower, strtolower($exclude_pattern)) !== false) {
                                $excluded = true;
                                break;
                            }
                        }
                        if ($excluded) {
                            continue;
                        }
                    }
                    return $model;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Generate product ID from name and WC ID
     */
    private function generate_product_id($product_name, $wc_id) {
        $id = strtolower($product_name);
        $id = preg_replace('/[^a-z0-9\s]/', '', $id);
        $id = preg_replace('/\s+/', '-', $id);
        $id = substr($id, 0, 50);
        return $id . '-' . $wc_id;
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
        $brand_label = $this->brand_patterns[$data['brand']]['label'];
        $wpdb->replace(
            $wpdb->prefix . 'dpc_product_attributes',
            array(
                'product_id' => $data['product_id'],
                'attribute_type' => 'brand',
                'attribute_value' => $data['brand'],
                'attribute_label' => $brand_label,
                'price_modifier' => 0
            ),
            array('%s', '%s', '%s', '%s', '%f')
        );
        
        // Save model attribute
        $model_label = $this->model_patterns[$data['brand']][$data['model']]['label'];
        $wpdb->replace(
            $wpdb->prefix . 'dpc_product_attributes',
            array(
                'product_id' => $data['product_id'],
                'attribute_type' => 'model',
                'attribute_value' => $data['model'],
                'attribute_label' => $model_label,
                'price_modifier' => 0
            ),
            array('%s', '%s', '%s', '%s', '%f')
        );
        
        // Enable DPC for this WooCommerce product
        update_post_meta($data['wc_product_id'], '_dpc_enabled', 'yes');
        update_post_meta($data['wc_product_id'], '_dpc_product_id', $data['product_id']);
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
    
    /**
     * Get parsing statistics
     */
    public function get_parsing_stats() {
        global $wpdb;
        
        // Get total WooCommerce products
        $total_wc_products = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'"
        );
        
        // Get DPC enabled products
        $dpc_enabled_products = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}dpc_products"
        );
        
        // Get total brands
        $total_brands = $wpdb->get_var(
            "SELECT COUNT(DISTINCT attribute_value) FROM {$wpdb->prefix}dpc_product_attributes WHERE attribute_type = 'brand'"
        );
        
        // Get total models
        $total_models = $wpdb->get_var(
            "SELECT COUNT(DISTINCT attribute_value) FROM {$wpdb->prefix}dpc_product_attributes WHERE attribute_type = 'model'"
        );
        
        return array(
            'total_wc_products' => intval($total_wc_products),
            'dpc_enabled_products' => intval($dpc_enabled_products),
            'total_brands' => intval($total_brands),
            'total_models' => intval($total_models)
        );
    }
    
    /**
     * Get products by brand and model
     */
    public function get_products_by_brand_model($brand, $model) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.* FROM {$wpdb->prefix}dpc_products p
             INNER JOIN {$wpdb->prefix}dpc_product_attributes pb ON p.product_id = pb.product_id
             INNER JOIN {$wpdb->prefix}dpc_product_attributes pm ON p.product_id = pm.product_id
             WHERE pb.attribute_type = 'brand' AND pb.attribute_value = %s
             AND pm.attribute_type = 'model' AND pm.attribute_value = %s",
            $brand,
            $model
        ));
    }
}