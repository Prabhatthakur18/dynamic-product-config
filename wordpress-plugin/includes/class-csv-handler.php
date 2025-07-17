<?php
/**
 * CSV Handler Class
 * Handles CSV upload and processing for existing WooCommerce products
 */

if (!defined('ABSPATH')) {
    exit;
}

class DPC_CSV_Handler {
    
    public function __construct() {
        add_action('admin_post_dpc_upload_csv', array($this, 'handle_csv_upload'));
    }
    
    /**
     * Handle CSV upload
     */
    public function handle_csv_upload() {
        if (!wp_verify_nonce($_POST['dpc_nonce'], 'dpc_upload_csv')) {
            wp_die(__('Security check failed', 'dynamic-product-configurator'));
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions', 'dynamic-product-configurator'));
        }
        
        try {
            $options = array(
                'create_missing' => isset($_POST['create_missing_products']),
                'update_existing' => isset($_POST['update_existing_products']),
                'enable_all' => isset($_POST['enable_all_products']),
                'default_price' => floatval($_POST['default_price']) ?: 199,
                'default_category' => sanitize_text_field($_POST['default_category']) ?: 'mobile-back-cover'
            );
            
            if (isset($_FILES['products_csv']) && $_FILES['products_csv']['error'] === UPLOAD_ERR_OK) {
                $result = $this->process_csv($_FILES['products_csv']['tmp_name'], $options);
                
                if ($result['success'] > 0) {
                    wp_redirect(admin_url('admin.php?page=dpc-admin&message=success&count=' . $result['success']));
                } else {
                    wp_redirect(admin_url('admin.php?page=dpc-admin&message=error&error=' . urlencode('No products processed')));
                }
            } else {
                wp_redirect(admin_url('admin.php?page=dpc-admin&message=error&error=' . urlencode('No file uploaded')));
            }
            
        } catch (Exception $e) {
            wp_redirect(admin_url('admin.php?page=dpc-admin&message=error&error=' . urlencode($e->getMessage())));
        }
        
        exit;
    }
    
    /**
     * Process CSV file
     */
    private function process_csv($file_path, $options) {
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            throw new Exception(__('Could not open CSV file', 'dynamic-product-configurator'));
        }
        
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new Exception(__('Invalid CSV format', 'dynamic-product-configurator'));
        }
        
        // Expected headers
        $expected = array('wc_product_id', 'brand', 'model', 'recommended_products', 'interested_products');
        $missing = array_diff($expected, $headers);
        
        if (!empty($missing)) {
            fclose($handle);
            throw new Exception(sprintf(__('Missing CSV headers: %s', 'dynamic-product-configurator'), implode(', ', $missing)));
        }
        
        $success_count = 0;
        $row_number = 1;
        
        while (($data = fgetcsv($handle)) !== false) {
            $row_number++;
            
            try {
                $row_data = array_combine($headers, $data);
                
                if (empty($row_data['wc_product_id'])) {
                    continue; // Skip empty rows
                }
                
                $product_id = intval($row_data['wc_product_id']);
                $product = wc_get_product($product_id);
                
                // Create product if it doesn't exist and option is enabled
                if (!$product && $options['create_missing']) {
                    $product = $this->create_woocommerce_product($row_data, $options);
                }
                
                if (!$product) {
                    continue; // Skip if product doesn't exist and we're not creating
                }
                
                // Update product meta
                $this->update_product_meta($product_id, $row_data, $options);
                $success_count++;
                
            } catch (Exception $e) {
                // Log error but continue processing
                error_log('DPC CSV Error Row ' . $row_number . ': ' . $e->getMessage());
            }
        }
        
        fclose($handle);
        
        return array(
            'success' => $success_count,
            'errors' => 0
        );
    }
    
    /**
     * Create new WooCommerce product
     */
    private function create_woocommerce_product($row_data, $options) {
        $product = new WC_Product_Simple();
        
        // Set basic product data
        $product_name = $row_data['brand'] . ' ' . $row_data['model'] . ' Back Cover';
        $product->set_name($product_name);
        $product->set_status('publish');
        $product->set_regular_price($options['default_price']);
        $product->set_manage_stock(false);
        $product->set_stock_status('instock');
        
        // Set category
        $category_id = $this->get_or_create_category($options['default_category']);
        if ($category_id) {
            $product->set_category_ids(array($category_id));
        }
        
        // Save product
        $product_id = $product->save();
        
        if ($product_id) {
            return wc_get_product($product_id);
        }
        
        return false;
    }
    
    /**
     * Update product meta fields
     */
    private function update_product_meta($product_id, $row_data, $options) {
        // Enable DPC if option is set
        if ($options['enable_all']) {
            update_post_meta($product_id, '_dpc_enabled', 'yes');
        }
        
        // Update brand and model
        update_post_meta($product_id, '_dpc_brand', sanitize_text_field($row_data['brand']));
        update_post_meta($product_id, '_dpc_model', sanitize_text_field($row_data['model']));
        
        // Update recommended products
        if (!empty($row_data['recommended_products'])) {
            $recommended = str_replace(array('"', ' '), '', $row_data['recommended_products']);
            update_post_meta($product_id, '_dpc_recommended_products', $recommended);
        }
        
        // Update interested products
        if (!empty($row_data['interested_products'])) {
            $interested = str_replace(array('"', ' '), '', $row_data['interested_products']);
            update_post_meta($product_id, '_dpc_interested_products', $interested);
        }
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
}