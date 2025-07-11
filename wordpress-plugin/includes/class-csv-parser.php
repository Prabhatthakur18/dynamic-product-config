<?php
/**
 * CSV Parser Class - Manual Upload System
 * Handles CSV file parsing for manual brand/model data
 */

if (!defined('ABSPATH')) {
    exit;
}

class DPC_CSV_Parser {
    
    private $product_manager;
    private $errors = array();
    private $success_count = 0;
    
    public function __construct() {
        $this->product_manager = new DPC_Product_Manager();
    }
    
    /**
     * Parse manual CSV file with comprehensive options
     */
    public function parse_manual_csv($file_path, $options = array()) {
        $this->reset_counters();
        
        // Default options
        $options = wp_parse_args($options, array(
            'product_action' => 'existing_only',
            'enable_configurator' => true,
            'overwrite_existing' => false,
            'create_categories' => true,
            'default_price' => 199,
            'default_category' => 'mobile-back-cover',
            'default_status' => 'publish'
        ));
        
        if (!file_exists($file_path)) {
            throw new Exception(__('Products CSV file not found', 'dynamic-product-configurator'));
        }
        
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            throw new Exception(__('Could not open products CSV file', 'dynamic-product-configurator'));
        }
        
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new Exception(__('Invalid CSV format - no headers found', 'dynamic-product-configurator'));
        }
        
        // Required headers for manual system
        $required_headers = array('wc_product_id', 'brand', 'model');
        $optional_headers = array('recommended_products', 'complementary_products');
        
        $missing_headers = array_diff($required_headers, $headers);
        
        if (!empty($missing_headers)) {
            fclose($handle);
            throw new Exception(sprintf(
                __('Missing required headers: %s', 'dynamic-product-configurator'),
                implode(', ', $missing_headers)
            ));
        }
        
        $row_number = 1;
        while (($data = fgetcsv($handle)) !== false) {
            $row_number++;
            
            try {
                $product_data = array_combine($headers, $data);
                
                // Validate required fields
                if (empty($product_data['wc_product_id'])) {
                    throw new Exception('WooCommerce Product ID is required');
                }
                
                if (empty($product_data['brand'])) {
                    throw new Exception('Brand is required');
                }
                
                if (empty($product_data['model'])) {
                    throw new Exception('Model is required');
                }
                
                // Handle product creation/updating based on options
                $wc_product = wc_get_product($product_data['wc_product_id']);
                
                if (!$wc_product) {
                    if ($options['product_action'] === 'existing_only') {
                        throw new Exception('WooCommerce product not found: ' . $product_data['wc_product_id']);
                    } else {
                        // Create new product
                        $wc_product = $this->create_woocommerce_product($product_data, $options);
                        if (!$wc_product) {
                            throw new Exception('Failed to create WooCommerce product: ' . $product_data['wc_product_id']);
                        }
                    }
                }
                
                // Generate DPC product ID
                $dpc_product_id = 'dpc-' . $product_data['wc_product_id'];
                
                // Prepare product data
                $processed_data = array(
                    'product_id' => $dpc_product_id,
                    'product_name' => $wc_product->get_name(),
                    'base_price' => $wc_product->get_regular_price() ?: $wc_product->get_price(),
                    'image_url' => wp_get_attachment_image_url($wc_product->get_image_id(), 'full'),
                    'category' => 'mobile-back-cover',
                    'attribute_types' => array('brand', 'model'),
                    'wc_product_id' => $product_data['wc_product_id'],
                    'brand' => trim($product_data['brand']),
                    'model' => trim($product_data['model']),
                    'recommended_products' => isset($product_data['recommended_products']) ? $product_data['recommended_products'] : '',
                    'complementary_products' => isset($product_data['complementary_products']) ? $product_data['complementary_products'] : ''
                );
                
                // Save product
                if ($this->save_manual_product($processed_data, $options)) {
                    $this->success_count++;
                } else {
                    throw new Exception('Failed to save product to database');
                }
                
            } catch (Exception $e) {
                $this->errors[] = sprintf(
                    __('Row %d: %s', 'dynamic-product-configurator'),
                    $row_number,
                    $e->getMessage()
                );
            }
        }
        
        fclose($handle);
        
        return array(
            'success_count' => $this->success_count,
            'errors' => count($this->errors),
            'error_details' => $this->errors
        );
    }
    
    /**
     * Create new WooCommerce product
     */
    private function create_woocommerce_product($product_data, $options) {
        $product = new WC_Product_Simple();
        
        // Set basic product data
        $product_name = $product_data['brand'] . ' ' . $product_data['model'] . ' Back Cover';
        $product->set_name($product_name);
        $product->set_status($options['default_status']);
        $product->set_regular_price($options['default_price']);
        $product->set_manage_stock(false);
        $product->set_stock_status('instock');
        
        // Set category
        if ($options['create_categories']) {
            $category_id = $this->get_or_create_category($options['default_category']);
            if ($category_id) {
                $product->set_category_ids(array($category_id));
            }
        }
        
        // Save product
        $product_id = $product->save();
        
        if ($product_id) {
            // Update the product data with the new ID
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'posts',
                array('ID' => $product_data['wc_product_id']),
                array('ID' => $product_id),
                array('%d'),
                array('%d')
            );
            
            return wc_get_product($product_id);
        }
        
        return false;
    }
    
    /**
     * Get or create product category
     */
    private function get_or_create_category($category_name) {
        $term = get_term_by('name', $category_name, 'product_cat');
        
        if (!$term) {
            $result = wp_insert_term($category_name, 'product_cat');
            if (!is_wp_error($result)) {
                return $result['term_id'];
            }
        } else {
            return $term->term_id;
        }
        
        return false;
    }
    
    /**
     * Save manually configured product
     */
    private function save_manual_product($data, $options) {
        global $wpdb;
        
        // Check if product already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dpc_products WHERE product_id = %s",
            $data['product_id']
        ));
        
        if ($existing && !$options['overwrite_existing']) {
            // Skip if exists and not overwriting
            return true;
        }
        
        // Save main product
        $result = $wpdb->replace(
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
        
        if ($result === false) {
            return false;
        }
        
        // Clear existing attributes if overwriting
        if ($existing && $options['overwrite_existing']) {
            $wpdb->delete(
                $wpdb->prefix . 'dpc_product_attributes',
                array('product_id' => $data['product_id']),
                array('%s')
            );
            
            $wpdb->delete(
                $wpdb->prefix . 'dpc_complementary_products',
                array('main_product_id' => $data['product_id']),
                array('%s')
            );
        }
        
        // Save brand attribute
        $wpdb->replace(
            $wpdb->prefix . 'dpc_product_attributes',
            array(
                'product_id' => $data['product_id'],
                'attribute_type' => 'brand',
                'attribute_value' => strtolower($data['brand']),
                'attribute_label' => $data['brand'],
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
                'attribute_value' => strtolower(str_replace(' ', '-', $data['model'])),
                'attribute_label' => $data['model'],
                'price_modifier' => 0
            ),
            array('%s', '%s', '%s', '%s', '%f')
        );
        
        // Save recommended products if provided
        if (!empty($data['recommended_products'])) {
            $recommended_ids = array_map('trim', explode(',', $data['recommended_products']));
            foreach ($recommended_ids as $rec_id) {
                if (!empty($rec_id)) {
                    $rec_product = wc_get_product($rec_id);
                    if ($rec_product) {
                        $wpdb->replace(
                            $wpdb->prefix . 'dpc_complementary_products',
                            array(
                                'main_product_id' => $data['product_id'],
                                'complementary_product_id' => 'rec-' . $rec_id,
                                'complementary_name' => $rec_product->get_name(),
                                'price' => $rec_product->get_regular_price() ?: $rec_product->get_price(),
                                'original_price' => null,
                                'image_url' => wp_get_attachment_image_url($rec_product->get_image_id(), 'full'),
                                'wc_product_id' => $rec_id,
                                'sort_order' => 1
                            ),
                            array('%s', '%s', '%s', '%f', '%f', '%s', '%d', '%d')
                        );
                    }
                }
            }
        }
        
        // Save complementary products if provided
        if (!empty($data['complementary_products'])) {
            $comp_ids = array_map('trim', explode(',', $data['complementary_products']));
            foreach ($comp_ids as $comp_id) {
                if (!empty($comp_id)) {
                    $comp_product = wc_get_product($comp_id);
                    if ($comp_product) {
                        $wpdb->replace(
                            $wpdb->prefix . 'dpc_complementary_products',
                            array(
                                'main_product_id' => $data['product_id'],
                                'complementary_product_id' => 'comp-' . $comp_id,
                                'complementary_name' => $comp_product->get_name(),
                                'price' => $comp_product->get_regular_price() ?: $comp_product->get_price(),
                                'original_price' => null,
                                'image_url' => wp_get_attachment_image_url($comp_product->get_image_id(), 'full'),
                                'wc_product_id' => $comp_id,
                                'sort_order' => 2
                            ),
                            array('%s', '%s', '%s', '%f', '%f', '%s', '%d', '%d')
                        );
                    }
                }
            }
        }
        
        // Enable DPC for this WooCommerce product
        if ($options['enable_configurator']) {
            update_post_meta($data['wc_product_id'], '_dpc_enabled', 'yes');
            update_post_meta($data['wc_product_id'], '_dpc_product_id', $data['product_id']);
        }
        
        return true;
    }
    
    /**
     * Reset counters
     */
    private function reset_counters() {
        $this->errors = array();
        $this->success_count = 0;
    }
    
    /**
     * Get import results
     */
    public function get_results() {
        return array(
            'success_count' => $this->success_count,
            'errors' => $this->errors
        );
    }
}