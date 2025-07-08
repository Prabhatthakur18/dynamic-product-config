<?php
/**
 * WooCommerce Integration Class
 * Handles integration with WooCommerce cart, checkout, and orders
 */

if (!defined('ABSPATH')) {
    exit;
}

class DPC_WooCommerce_Integration {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Product page integration
        add_action('woocommerce_single_product_summary', array($this, 'add_configurator_to_product'), 25);
        
        // Cart integration
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_custom_data_to_cart'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_custom_data_in_cart'), 10, 2);
        add_filter('woocommerce_cart_item_price', array($this, 'modify_cart_item_price'), 10, 3);
        add_action('woocommerce_before_calculate_totals', array($this, 'update_cart_item_prices'));
        
        // Checkout integration
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_custom_data_to_order'), 10, 4);
        
        // Admin order display
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_custom_data_in_admin'));
        
        // Product validation
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_add_to_cart'), 10, 3);
    }
    
    /**
     * Add configurator to product page
     */
    public function add_configurator_to_product() {
        global $product;
        
        if ($this->has_dynamic_configuration($product->get_id())) {
            echo '<div id="dynamic-product-configurator" class="dpc-configurator" data-product-id="' . esc_attr($product->get_id()) . '">';
            echo '<div class="dpc-loading">Loading configurator...</div>';
            echo '</div>';
            
            // Hide default add to cart button
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        }
    }
    
    /**
     * Add custom data to cart
     */
    public function add_custom_data_to_cart($cart_item_data, $product_id, $variation_id) {
        if (isset($_POST['dpc_attributes']) && !empty($_POST['dpc_attributes'])) {
            $attributes = json_decode(stripslashes($_POST['dpc_attributes']), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $cart_item_data['dpc_attributes'] = $attributes;
            }
        }
        
        if (isset($_POST['dpc_price_modifier'])) {
            $cart_item_data['dpc_price_modifier'] = floatval($_POST['dpc_price_modifier']);
        }
        
        if (isset($_POST['dpc_product_id'])) {
            $cart_item_data['dpc_product_id'] = sanitize_text_field($_POST['dpc_product_id']);
        }
        
        return $cart_item_data;
    }
    
    /**
     * Display custom data in cart
     */
    public function display_custom_data_in_cart($item_data, $cart_item) {
        if (isset($cart_item['dpc_attributes']) && is_array($cart_item['dpc_attributes'])) {
            foreach ($cart_item['dpc_attributes'] as $key => $value) {
                if (!empty($value)) {
                    $item_data[] = array(
                        'key' => sprintf(__('Select Your %s', 'dynamic-product-configurator'), ucfirst($key)),
                        'value' => esc_html($value),
                        'display' => ''
                    );
                }
            }
        }
        
        return $item_data;
    }
    
    /**
     * Update cart item prices
     */
    public function update_cart_item_prices($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['dpc_price_modifier'])) {
                $product = $cart_item['data'];
                $new_price = $product->get_regular_price() + $cart_item['dpc_price_modifier'];
                $product->set_price($new_price);
            }
        }
    }
    
    /**
     * Modify cart item price display
     */
    public function modify_cart_item_price($price, $cart_item, $cart_item_key) {
        if (isset($cart_item['dpc_price_modifier'])) {
            $product = $cart_item['data'];
            $new_price = $product->get_regular_price() + $cart_item['dpc_price_modifier'];
            return wc_price($new_price);
        }
        
        return $price;
    }
    
    /**
     * Save custom data to order
     */
    public function save_custom_data_to_order($item, $cart_item_key, $values, $order) {
        if (isset($values['dpc_attributes'])) {
            $item->add_meta_data('_dpc_attributes', $values['dpc_attributes']);
            
            // Add individual attributes for easier display
            foreach ($values['dpc_attributes'] as $key => $value) {
                if (!empty($value)) {
                    $item->add_meta_data(
                        sprintf(__('Select Your %s', 'dynamic-product-configurator'), ucfirst($key)),
                        $value
                    );
                }
            }
        }
        
        if (isset($values['dpc_price_modifier'])) {
            $item->add_meta_data('_dpc_price_modifier', $values['dpc_price_modifier']);
        }
        
        if (isset($values['dpc_product_id'])) {
            $item->add_meta_data('_dpc_product_id', $values['dpc_product_id']);
        }
    }
    
    /**
     * Display custom data in admin order
     */
    public function display_custom_data_in_admin($order) {
        $has_dpc_items = false;
        
        foreach ($order->get_items() as $item) {
            $dpc_attributes = $item->get_meta('_dpc_attributes');
            if (!empty($dpc_attributes)) {
                $has_dpc_items = true;
                break;
            }
        }
        
        if ($has_dpc_items) {
            echo '<h3>' . __('Dynamic Product Configuration', 'dynamic-product-configurator') . '</h3>';
            echo '<div class="dpc-order-details">';
            
            foreach ($order->get_items() as $item) {
                $dpc_attributes = $item->get_meta('_dpc_attributes');
                $dpc_product_id = $item->get_meta('_dpc_product_id');
                
                if (!empty($dpc_attributes)) {
                    echo '<div class="dpc-item-details">';
                    echo '<strong>' . esc_html($item->get_name()) . '</strong>';
                    if ($dpc_product_id) {
                        echo ' <small>(' . esc_html($dpc_product_id) . ')</small>';
                    }
                    echo '<ul>';
                    
                    foreach ($dpc_attributes as $key => $value) {
                        if (!empty($value)) {
                            echo '<li><strong>' . esc_html(ucfirst($key)) . ':</strong> ' . esc_html($value) . '</li>';
                        }
                    }
                    
                    echo '</ul>';
                    echo '</div>';
                }
            }
            
            echo '</div>';
        }
    }
    
    /**
     * Validate add to cart
     */
    public function validate_add_to_cart($passed, $product_id, $quantity) {
        if ($this->has_dynamic_configuration($product_id)) {
            // Get DPC product data
            $dpc_product_data = dpc_get_product_data($product_id);
            
            if ($dpc_product_data && !empty($dpc_product_data['product']->attribute_types)) {
                $required_attributes = explode(',', $dpc_product_data['product']->attribute_types);
                $provided_attributes = isset($_POST['dpc_attributes']) 
                    ? json_decode(stripslashes($_POST['dpc_attributes']), true) 
                    : array();
                
                foreach ($required_attributes as $attr_type) {
                    $attr_type = trim($attr_type);
                    if (empty($provided_attributes[$attr_type])) {
                        wc_add_notice(
                            sprintf(__('Please select your %s before adding to cart.', 'dynamic-product-configurator'), $attr_type),
                            'error'
                        );
                        $passed = false;
                    }
                }
            }
        }
        
        return $passed;
    }
    
    /**
     * Check if product has dynamic configuration
     */
    private function has_dynamic_configuration($product_id) {
        $dpc_enabled = get_post_meta($product_id, '_dpc_enabled', true);
        
        if ($dpc_enabled === 'yes') {
            return true;
        }
        
        // Also check our custom table
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}dpc_products WHERE wc_product_id = %d",
            $product_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Get cart item unique key
     */
    public function get_cart_item_key($product_id, $variation_id, $variation, $cart_item_data) {
        if (isset($cart_item_data['dpc_attributes'])) {
            return md5($product_id . serialize($cart_item_data['dpc_attributes']));
        }
        
        return '';
    }
}