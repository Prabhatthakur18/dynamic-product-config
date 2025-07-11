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
     * Parse products CSV file with manual brand/model data
     */
    public function parse_products_csv($file_path) {
        $this->reset_counters();
        
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
        $required_headers = array('wc_product_id', 'brand', 'model', 'recommended_products', 'complementary_products');
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
                
                // Get WooCommerce product data
                $wc_product = wc_get_product($product_data['wc_product_id']);
                if (!$wc_product) {
                    throw new Exception('WooCommerce product not found: ' . $product_data['wc_product_id']);
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
                if ($this->save_manual_product($processed_data)) {
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
            'errors' => $this->errors
        );
    }
    
    /**
     * Save manually configured product
     */
    private function save_manual_product($data) {
        global $wpdb;
        
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
        update_post_meta($data['wc_product_id'], '_dpc_enabled', 'yes');
        update_post_meta($data['wc_product_id'], '_dpc_product_id', $data['product_id']);
        
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