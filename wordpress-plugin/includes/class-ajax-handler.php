<?php
/**
 * AJAX Handler Class
 * Handles all AJAX requests from the frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

class DPC_AJAX_Handler {
    
    private $product_manager;
    
    public function __construct() {
        $this->product_manager = new DPC_Product_Manager();
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // AJAX handlers for logged in users
        add_action('wp_ajax_dpc_get_product_data', array($this, 'get_product_data'));
        add_action('wp_ajax_dpc_add_to_cart', array($this, 'ajax_add_to_cart'));
        add_action('wp_ajax_dpc_submit_bulk_request', array($this, 'submit_bulk_request'));
        add_action('wp_ajax_dpc_search_products', array($this, 'search_products'));
        
        // AJAX handlers for non-logged in users
        add_action('wp_ajax_nopriv_dpc_get_product_data', array($this, 'get_product_data'));
        add_action('wp_ajax_nopriv_dpc_add_to_cart', array($this, 'ajax_add_to_cart'));
        add_action('wp_ajax_nopriv_dpc_submit_bulk_request', array($this, 'submit_bulk_request'));
        add_action('wp_ajax_nopriv_dpc_search_products', array($this, 'search_products'));
        
        // Product parsing AJAX handlers
        add_action('wp_ajax_dpc_get_parsing_stats', array($this, 'get_parsing_stats'));
        add_action('wp_ajax_dpc_parse_existing_products', array($this, 'parse_existing_products'));
        add_action('wp_ajax_dpc_auto_enable_all_products', array($this, 'auto_enable_all_products'));
        add_action('wp_ajax_dpc_get_brands_list', array($this, 'get_brands_list'));
        add_action('wp_ajax_dpc_get_models_for_brand', array($this, 'get_models_for_brand'));
    }
    
    /**
     * Get product data via AJAX
     */
    public function get_product_data() {
        check_ajax_referer('dpc_nonce', 'nonce');
        
        $product_id = sanitize_text_field($_POST['product_id']);
        
        if (empty($product_id)) {
            wp_send_json_error(array(
                'message' => __('Product ID is required', 'dynamic-product-configurator')
            ));
        }
        
        try {
            $product_data = dpc_get_product_data($product_id);
            
            if (!$product_data) {
                wp_send_json_error(array(
                    'message' => __('Product not found', 'dynamic-product-configurator')
                ));
            }
            
            $frontend_handler = new DPC_Frontend_Handler();
            $prepared_data = $frontend_handler->prepare_product_data($product_data);
            
            wp_send_json_success($prepared_data);
            
        } catch (Exception $e) {
            dpc_log('Error getting product data: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('Error loading product data', 'dynamic-product-configurator')
            ));
        }
    }
    
    /**
     * Add to cart via AJAX
     */
    public function ajax_add_to_cart() {
        check_ajax_referer('dpc_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        $attributes = sanitize_text_field($_POST['attributes']);
        $price_modifier = floatval($_POST['price_modifier']);
        $dpc_product_id = sanitize_text_field($_POST['dpc_product_id']);
        
        if (empty($product_id) || $quantity <= 0) {
            wp_send_json_error(array(
                'message' => __('Invalid product or quantity', 'dynamic-product-configurator')
            ));
        }
        
        try {
            // Validate product exists
            $product = wc_get_product($product_id);
            if (!$product) {
                wp_send_json_error(array(
                    'message' => __('Product not found', 'dynamic-product-configurator')
                ));
            }
            
            // Prepare cart item data
            $cart_item_data = array();
            
            if (!empty($attributes)) {
                $decoded_attributes = json_decode(stripslashes($attributes), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $cart_item_data['dpc_attributes'] = $decoded_attributes;
                }
            }
            
            if ($price_modifier != 0) {
                $cart_item_data['dpc_price_modifier'] = $price_modifier;
            }
            
            if (!empty($dpc_product_id)) {
                $cart_item_data['dpc_product_id'] = $dpc_product_id;
            }
            
            // Add to cart
            $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, 0, array(), $cart_item_data);
            
            if ($cart_item_key) {
                // Get updated cart data
                $cart_data = array(
                    'cart_item_key' => $cart_item_key,
                    'cart_count' => WC()->cart->get_cart_contents_count(),
                    'cart_total' => WC()->cart->get_cart_total(),
                    'cart_subtotal' => WC()->cart->get_cart_subtotal()
                );
                
                wp_send_json_success($cart_data);
            } else {
                wp_send_json_error(array(
                    'message' => __('Failed to add product to cart', 'dynamic-product-configurator')
                ));
            }
            
        } catch (Exception $e) {
            dpc_log('Error adding to cart: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('Error adding product to cart', 'dynamic-product-configurator')
            ));
        }
    }
    
    /**
     * Submit bulk purchase request
     */
    public function submit_bulk_request() {
        check_ajax_referer('dpc_nonce', 'nonce');
        
        $product_id = sanitize_text_field($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        $attributes = sanitize_text_field($_POST['attributes']);
        $contact_name = sanitize_text_field($_POST['contact_name']);
        $contact_email = sanitize_email($_POST['contact_email']);
        $contact_phone = sanitize_text_field($_POST['contact_phone']);
        $company = sanitize_text_field($_POST['company']);
        $message = sanitize_textarea_field($_POST['message']);
        
        // Validation
        if (empty($product_id) || empty($contact_name) || empty($contact_email)) {
            wp_send_json_error(array(
                'message' => __('Required fields are missing', 'dynamic-product-configurator')
            ));
        }
        
        if (!is_email($contact_email)) {
            wp_send_json_error(array(
                'message' => __('Invalid email address', 'dynamic-product-configurator')
            ));
        }
        
        if ($quantity < 100) {
            wp_send_json_error(array(
                'message' => __('Minimum quantity for bulk orders is 100', 'dynamic-product-configurator')
            ));
        }
        
        try {
            global $wpdb;
            
            $result = $wpdb->insert(
                $wpdb->prefix . 'dpc_bulk_requests',
                array(
                    'product_id' => $product_id,
                    'quantity' => $quantity,
                    'attributes' => $attributes,
                    'contact_name' => $contact_name,
                    'contact_email' => $contact_email,
                    'contact_phone' => $contact_phone,
                    'company' => $company,
                    'message' => $message,
                    'status' => 'pending'
                ),
                array('%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($result) {
                $request_id = $wpdb->insert_id;
                
                // Send notification email to admin
                $this->send_bulk_request_notification($request_id);
                
                // Send confirmation email to customer
                $this->send_bulk_request_confirmation($contact_email, $contact_name, $request_id);
                
                wp_send_json_success(array(
                    'message' => __('Bulk purchase request submitted successfully! We will contact you shortly.', 'dynamic-product-configurator'),
                    'request_id' => $request_id
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Failed to submit bulk purchase request', 'dynamic-product-configurator')
                ));
            }
            
        } catch (Exception $e) {
            dpc_log('Error submitting bulk request: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('Error submitting bulk purchase request', 'dynamic-product-configurator')
            ));
        }
    }
    
    /**
     * Search products
     */
    public function search_products() {
        check_ajax_referer('dpc_nonce', 'nonce');
        
        $search_term = sanitize_text_field($_POST['search_term']);
        $limit = intval($_POST['limit']) ?: 20;
        
        if (empty($search_term) || strlen($search_term) < 2) {
            wp_send_json_error(array(
                'message' => __('Search term must be at least 2 characters', 'dynamic-product-configurator')
            ));
        }
        
        try {
            $products = $this->product_manager->search_products($search_term, $limit);
            
            $formatted_products = array();
            foreach ($products as $product) {
                $formatted_products[] = array(
                    'id' => $product->product_id,
                    'name' => $product->product_name,
                    'price' => floatval($product->base_price),
                    'image' => $product->image_url,
                    'category' => $product->category
                );
            }
            
            wp_send_json_success($formatted_products);
            
        } catch (Exception $e) {
            dpc_log('Error searching products: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('Error searching products', 'dynamic-product-configurator')
            ));
        }
    }
    
    /**
     * Parse existing products
     */
    public function parse_existing_products() {
        check_ajax_referer('dpc_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $parser = new DPC_WC_Product_Parser();
        $parser->parse_existing_products();
    }
    
    /**
     * Auto enable all products
     */
    public function auto_enable_all_products() {
        check_ajax_referer('dpc_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $parser = new DPC_WC_Product_Parser();
        $parser->auto_enable_all_products();
    }
    
    /**
     * Get parsing statistics
     */
    public function get_parsing_stats() {
        check_ajax_referer('dpc_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $parser = new DPC_WC_Product_Parser();
        $stats = $parser->get_parsing_stats();
        
        wp_send_json_success($stats);
    }
    
    /**
     * Get brands list
     */
    public function get_brands_list() {
        check_ajax_referer('dpc_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $parser = new DPC_WC_Product_Parser();
        $brands = $parser->get_available_brands();
        
        wp_send_json_success($brands);
    }
    
    /**
     * Get models for specific brand
     */
    public function get_models_for_brand() {
        check_ajax_referer('dpc_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $brand = sanitize_text_field($_POST['brand']);
        
        if (empty($brand)) {
            wp_send_json_error('Brand is required');
        }
        
        $parser = new DPC_WC_Product_Parser();
        $models = $parser->get_models_for_brand($brand);
        
        wp_send_json_success($models);
    }
    
    /**
     * Send bulk request notification to admin
     */
    private function send_bulk_request_notification($request_id) {
        global $wpdb;
        
        $request = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dpc_bulk_requests WHERE id = %d",
            $request_id
        ));
        
        if (!$request) {
            return false;
        }
        
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('New Bulk Purchase Request #%d', 'dynamic-product-configurator'), $request_id);
        
        $message = sprintf(
            __("A new bulk purchase request has been submitted:\n\nRequest ID: %d\nProduct ID: %s\nQuantity: %d\nCustomer: %s\nEmail: %s\nPhone: %s\nCompany: %s\n\nMessage:\n%s\n\nPlease review and respond to this request.", 'dynamic-product-configurator'),
            $request_id,
            $request->product_id,
            $request->quantity,
            $request->contact_name,
            $request->contact_email,
            $request->contact_phone,
            $request->company,
            $request->message
        );
        
        return wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Send bulk request confirmation to customer
     */
    private function send_bulk_request_confirmation($email, $name, $request_id) {
        $subject = sprintf(__('Bulk Purchase Request Confirmation #%d', 'dynamic-product-configurator'), $request_id);
        
        $message = sprintf(
            __("Dear %s,\n\nThank you for your bulk purchase request #%d. We have received your inquiry and will contact you within 24 hours with a custom quote.\n\nOur team will review your requirements and provide you with the best possible pricing for your bulk order.\n\nBest regards,\n%s", 'dynamic-product-configurator'),
            $name,
            $request_id,
            get_bloginfo('name')
        );
        
        return wp_mail($email, $subject, $message);
    }
}