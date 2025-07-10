<?php
/**
 * Frontend Handler Class
 * Handles frontend display and user interactions
 */

if (!defined('ABSPATH')) {
    exit;
}

class DPC_Frontend_Handler {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Add body class for DPC pages
        add_filter('body_class', array($this, 'add_body_class'));
        
        // Add inline styles for loading state
        add_action('wp_head', array($this, 'add_inline_styles'));
        
        // Handle shortcode rendering
        add_filter('the_content', array($this, 'process_shortcodes'));
    }
    
    /**
     * Add body class for DPC pages
     */
    public function add_body_class($classes) {
        if (is_product()) {
            global $post;
            $dpc_enabled = get_post_meta($post->ID, '_dpc_enabled', true);
            
            if ($dpc_enabled === 'yes') {
                $classes[] = 'dpc-enabled';
            }
        }
        
        return $classes;
    }
    
    /**
     * Add inline styles for loading state
     */
    public function add_inline_styles() {
        ?>
        <style>
        .dpc-configurator {
            min-height: 400px;
            position: relative;
        }
        
        .dpc-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 200px;
            color: #666;
            font-size: 16px;
        }
        
        .dpc-loading:before {
            content: '';
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #666;
            border-radius: 50%;
            animation: dpc-spin 1s linear infinite;
            margin-right: 10px;
        }
        
        @keyframes dpc-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .dpc-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        .dpc-success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        /* Hide default WooCommerce add to cart for DPC products */
        .dpc-enabled .single_add_to_cart_button {
            display: none !important;
        }
        
        .dpc-enabled .quantity {
            display: none !important;
        }
        </style>
        <?php
    }
    
    /**
     * Process shortcodes in content
     */
    public function process_shortcodes($content) {
        // Check if we have DPC shortcodes
        if (has_shortcode($content, 'dynamic_product_configurator')) {
            // Ensure scripts are loaded
            wp_enqueue_script('dpc-configurator');
            wp_enqueue_style('dpc-configurator');
        }
        
        return $content;
    }
    
    /**
     * Render product configurator
     */
    public function render_configurator($product_id = null, $attributes = array()) {
        if (!$product_id) {
            global $post;
            $product_id = $post->ID;
        }
        
        $dpc_product_data = dpc_get_product_data($product_id);
        
        if (!$dpc_product_data) {
            return '<div class="dpc-error">' . __('Product configuration not found.', 'dynamic-product-configurator') . '</div>';
        }
        
        ob_start();
        ?>
        <div id="dynamic-product-configurator-<?php echo esc_attr($product_id); ?>" 
             class="dpc-configurator" 
             data-product-id="<?php echo esc_attr($product_id); ?>"
             data-dpc-product-id="<?php echo esc_attr($dpc_product_data['product']->product_id); ?>">
            <div class="dpc-loading"><?php _e('Loading configurator...', 'dynamic-product-configurator'); ?></div>
        </div>
        
        <script type="application/json" id="dpc-product-data-<?php echo esc_attr($product_id); ?>">
        <?php echo wp_json_encode($this->prepare_product_data($dpc_product_data)); ?>
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Prepare product data for frontend
     */
    public function prepare_product_data($dpc_product_data) {
        $product = $dpc_product_data['product'];
        $attributes = $dpc_product_data['attributes'];
        $complementary = $dpc_product_data['complementary'];
        
        // Group attributes by type
        $grouped_attributes = array();
        foreach ($attributes as $attr) {
            if (!isset($grouped_attributes[$attr->attribute_type])) {
                $grouped_attributes[$attr->attribute_type] = array();
            }
            
            $grouped_attributes[$attr->attribute_type][] = array(
                'value' => $attr->attribute_value,
                'label' => $attr->attribute_label,
                'priceModifier' => floatval($attr->price_modifier)
            );
        }
        
        // Prepare complementary products
        $complementary_products = array();
        foreach ($complementary as $comp) {
            $complementary_products[] = array(
                'id' => $comp->complementary_product_id,
                'name' => $comp->complementary_name,
                'price' => floatval($comp->price),
                'originalPrice' => $comp->original_price ? floatval($comp->original_price) : null,
                'image' => $comp->image_url
            );
        }
        
        return array(
            'product' => array(
                'id' => $product->product_id,
                'name' => $product->product_name,
                'basePrice' => floatval($product->base_price),
                'image' => $product->image_url,
                'category' => $product->category,
                'attributeTypes' => explode(',', $product->attribute_types),
                'wcProductId' => $product->wc_product_id
            ),
            'attributes' => $grouped_attributes,
            'complementary' => $complementary_products,
            'currency' => array(
                'symbol' => get_woocommerce_currency_symbol(),
                'position' => get_option('woocommerce_currency_pos'),
                'thousandSeparator' => wc_get_price_thousand_separator(),
                'decimalSeparator' => wc_get_price_decimal_separator(),
                'decimals' => wc_get_price_decimals()
            )
        );
    }
    
    /**
     * Get product configuration template
     */
    public function get_template($template_name, $args = array()) {
        $template_path = DPC_PLUGIN_DIR . 'templates/' . $template_name . '.php';
        
        if (file_exists($template_path)) {
            extract($args);
            include $template_path;
        } else {
            echo '<div class="dpc-error">' . sprintf(__('Template %s not found.', 'dynamic-product-configurator'), $template_name) . '</div>';
        }
    }
}