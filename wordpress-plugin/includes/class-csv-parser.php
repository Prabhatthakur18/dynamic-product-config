<?php
/**
 * CSV Parser Class
 * Handles CSV file parsing and data import
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
     * Parse products CSV file
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
        
        // Validate required headers
        $required_headers = array('product_id', 'product_name', 'base_price', 'category', 'attribute_types');
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
                if (empty($product_data['product_id'])) {
                    throw new Exception('Product ID is required');
                }
                
                if (empty($product_data['product_name'])) {
                    throw new Exception('Product name is required');
                }
                
                if (!is_numeric($product_data['base_price']) || $product_data['base_price'] < 0) {
                    throw new Exception('Base price must be a valid positive number');
                }
                
                // Process attribute types
                if (!empty($product_data['attribute_types'])) {
                    $product_data['attribute_types'] = array_map('trim', explode(',', $product_data['attribute_types']));
                } else {
                    $product_data['attribute_types'] = array();
                }
                
                // Set defaults
                $product_data['image_url'] = isset($product_data['image_url']) ? $product_data['image_url'] : '';
                $product_data['base_price'] = floatval($product_data['base_price']);
                
                // Save product
                if ($this->product_manager->save_product($product_data)) {
                    $this->success_count++;
                    
                    // Create WooCommerce product if it doesn't exist
                    $this->create_woocommerce_product($product_data);
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
        
        if (!empty($this->errors)) {
            dpc_log('CSV import errors: ' . implode('; ', $this->errors));
        }
        
        return array(
            'success_count' => $this->success_count,
            'errors' => $this->errors
        );
    }
    
    /**
     * Parse attributes CSV file
     */
    public function parse_attributes_csv($file_path) {
        $this->reset_counters();
        
        if (!file_exists($file_path)) {
            throw new Exception(__('Attributes CSV file not found', 'dynamic-product-configurator'));
        }
        
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            throw new Exception(__('Could not open attributes CSV file', 'dynamic-product-configurator'));
        }
        
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new Exception(__('Invalid CSV format - no headers found', 'dynamic-product-configurator'));
        }
        
        // Validate required headers
        $required_headers = array('product_id', 'attribute_type', 'attribute_value', 'attribute_label');
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
                $attribute_data = array_combine($headers, $data);
                
                // Validate required fields
                if (empty($attribute_data['product_id'])) {
                    throw new Exception('Product ID is required');
                }
                
                if (empty($attribute_data['attribute_type'])) {
                    throw new Exception('Attribute type is required');
                }
                
                if (empty($attribute_data['attribute_value'])) {
                    throw new Exception('Attribute value is required');
                }
                
                if (empty($attribute_data['attribute_label'])) {
                    throw new Exception('Attribute label is required');
                }
                
                // Set defaults
                $attribute_data['price_modifier'] = isset($attribute_data['price_modifier']) 
                    ? floatval($attribute_data['price_modifier']) 
                    : 0;
                
                // Save attribute
                if ($this->product_manager->save_attribute($attribute_data)) {
                    $this->success_count++;
                } else {
                    throw new Exception('Failed to save attribute to database');
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
     * Parse complementary products CSV file
     */
    public function parse_complementary_csv($file_path) {
        $this->reset_counters();
        
        if (!file_exists($file_path)) {
            throw new Exception(__('Complementary products CSV file not found', 'dynamic-product-configurator'));
        }
        
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            throw new Exception(__('Could not open complementary products CSV file', 'dynamic-product-configurator'));
        }
        
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new Exception(__('Invalid CSV format - no headers found', 'dynamic-product-configurator'));
        }
        
        // Validate required headers
        $required_headers = array('main_product_id', 'complementary_product_id', 'complementary_name', 'price');
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
                $complementary_data = array_combine($headers, $data);
                
                // Validate required fields
                if (empty($complementary_data['main_product_id'])) {
                    throw new Exception('Main product ID is required');
                }
                
                if (empty($complementary_data['complementary_product_id'])) {
                    throw new Exception('Complementary product ID is required');
                }
                
                if (empty($complementary_data['complementary_name'])) {
                    throw new Exception('Complementary product name is required');
                }
                
                if (!is_numeric($complementary_data['price']) || $complementary_data['price'] < 0) {
                    throw new Exception('Price must be a valid positive number');
                }
                
                // Set defaults
                $complementary_data['price'] = floatval($complementary_data['price']);
                $complementary_data['original_price'] = isset($complementary_data['original_price']) 
                    ? floatval($complementary_data['original_price']) 
                    : null;
                $complementary_data['image_url'] = isset($complementary_data['image_url']) 
                    ? $complementary_data['image_url'] 
                    : '';
                
                // Save complementary product
                if ($this->product_manager->save_complementary($complementary_data)) {
                    $this->success_count++;
                } else {
                    throw new Exception('Failed to save complementary product to database');
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
     * Create WooCommerce product if it doesn't exist
     */
    private function create_woocommerce_product($product_data) {
        // Check if WooCommerce product already exists
        $existing_product = get_posts(array(
            'post_type' => 'product',
            'meta_query' => array(
                array(
                    'key' => '_dpc_product_id',
                    'value' => $product_data['product_id'],
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        if (!empty($existing_product)) {
            return; // Product already exists
        }
        
        // Create new WooCommerce product
        $product = new WC_Product_Simple();
        $product->set_name($product_data['product_name']);
        $product->set_regular_price($product_data['base_price']);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_manage_stock(false);
        $product->set_stock_status('instock');
        
        // Set category if provided
        if (!empty($product_data['category'])) {
            $term = get_term_by('slug', sanitize_title($product_data['category']), 'product_cat');
            if (!$term) {
                // Create category if it doesn't exist
                $term = wp_insert_term($product_data['category'], 'product_cat');
                if (!is_wp_error($term)) {
                    $term_id = $term['term_id'];
                } else {
                    $term_id = null;
                }
            } else {
                $term_id = $term->term_id;
            }
            
            if ($term_id) {
                $product->set_category_ids(array($term_id));
            }
        }
        
        $product_id = $product->save();
        
        if ($product_id) {
            // Add custom meta
            update_post_meta($product_id, '_dpc_enabled', 'yes');
            update_post_meta($product_id, '_dpc_product_id', $product_data['product_id']);
            
            // Link to our custom table
            $this->product_manager->link_wc_product($product_data['product_id'], $product_id);
            
            dpc_log("Created WooCommerce product {$product_id} for DPC product {$product_data['product_id']}");
        }
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