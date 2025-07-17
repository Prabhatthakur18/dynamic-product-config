<?php
/**
 * Frontend Handler Class
 * Handles frontend display and functionality
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
        
        // Shortcode support
        add_shortcode('dpc_brand_selector', array($this, 'brand_selector_shortcode'));
        add_shortcode('dpc_product_grid', array($this, 'product_grid_shortcode'));
    }
    
    /**
     * Add body class for DPC pages
     */
    public function add_body_class($classes) {
        if (is_product()) {
            global $post;
            if (dpc_is_enabled($post->ID)) {
                $classes[] = 'dpc-enabled';
            }
        }
        
        return $classes;
    }
    
    /**
     * Brand selector shortcode
     */
    public function brand_selector_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_all_products' => 'yes',
            'products_per_page' => 12
        ), $atts);
        
        ob_start();
        $this->render_brand_selector($atts);
        return ob_get_clean();
    }
    
    /**
     * Product grid shortcode
     */
    public function product_grid_shortcode($atts) {
        $atts = shortcode_atts(array(
            'brand' => '',
            'model' => '',
            'products_per_page' => 12
        ), $atts);
        
        ob_start();
        $this->render_product_grid($atts);
        return ob_get_clean();
    }
    
    /**
     * Render brand selector
     */
    private function render_brand_selector($atts) {
        global $wpdb;
        
        // Get all brands
        $brands = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_dpc_brand' 
            AND meta_value != '' 
            ORDER BY meta_value
        ");
        
        ?>
        <div class="dpc-brand-selector-widget">
            <h3><?php _e('Select Your Device', 'dynamic-product-configurator'); ?></h3>
            
            <form class="dpc-brand-model-form" method="get">
                <div class="dpc-selector-row">
                    <div class="dpc-field">
                        <label for="dpc_brand_filter"><?php _e('Brand:', 'dynamic-product-configurator'); ?></label>
                        <select id="dpc_brand_filter" name="brand">
                            <option value=""><?php _e('All Brands', 'dynamic-product-configurator'); ?></option>
                            <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo esc_attr($brand); ?>" <?php selected(isset($_GET['brand']) ? $_GET['brand'] : '', $brand); ?>>
                                <?php echo esc_html($brand); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="dpc-field">
                        <label for="dpc_model_filter"><?php _e('Model:', 'dynamic-product-configurator'); ?></label>
                        <select id="dpc_model_filter" name="model">
                            <option value=""><?php _e('All Models', 'dynamic-product-configurator'); ?></option>
                        </select>
                    </div>
                    
                    <div class="dpc-field">
                        <button type="submit" class="button"><?php _e('Filter Products', 'dynamic-product-configurator'); ?></button>
                    </div>
                </div>
            </form>
            
            <div id="dpc-filtered-products">
                <?php $this->render_product_grid($atts); ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#dpc_brand_filter').on('change', function() {
                const brand = $(this).val();
                const modelSelect = $('#dpc_model_filter');
                
                modelSelect.html('<option value=""><?php _e('Loading...', 'dynamic-product-configurator'); ?></option>');
                
                if (brand) {
                    $.post(dpcAjax.ajaxurl, {
                        action: 'dpc_get_models_for_brand',
                        brand: brand,
                        nonce: dpcAjax.nonce
                    }, function(response) {
                        if (response.success) {
                            let options = '<option value=""><?php _e('All Models', 'dynamic-product-configurator'); ?></option>';
                            response.data.forEach(function(model) {
                                options += '<option value="' + model + '">' + model + '</option>';
                            });
                            modelSelect.html(options);
                        }
                    });
                } else {
                    modelSelect.html('<option value=""><?php _e('All Models', 'dynamic-product-configurator'); ?></option>');
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render product grid
     */
    private function render_product_grid($atts) {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => intval($atts['products_per_page']),
            'meta_query' => array(
                array(
                    'key' => '_dpc_enabled',
                    'value' => 'yes',
                    'compare' => '='
                )
            )
        );
        
        // Add brand filter
        if (!empty($atts['brand']) || !empty($_GET['brand'])) {
            $brand = !empty($atts['brand']) ? $atts['brand'] : $_GET['brand'];
            $args['meta_query'][] = array(
                'key' => '_dpc_brand',
                'value' => sanitize_text_field($brand),
                'compare' => '='
            );
        }
        
        // Add model filter
        if (!empty($atts['model']) || !empty($_GET['model'])) {
            $model = !empty($atts['model']) ? $atts['model'] : $_GET['model'];
            $args['meta_query'][] = array(
                'key' => '_dpc_model',
                'value' => sanitize_text_field($model),
                'compare' => '='
            );
        }
        
        $products = get_posts($args);
        
        if (empty($products)) {
            echo '<p>' . __('No products found matching your criteria.', 'dynamic-product-configurator') . '</p>';
            return;
        }
        
        echo '<div class="dpc-products-grid">';
        
        foreach ($products as $product) {
            $wc_product = wc_get_product($product->ID);
            if (!$wc_product) continue;
            
            $brand = get_post_meta($product->ID, '_dpc_brand', true);
            $model = get_post_meta($product->ID, '_dpc_model', true);
            
            ?>
            <div class="dpc-product-card">
                <a href="<?php echo get_permalink($product->ID); ?>">
                    <?php echo get_the_post_thumbnail($product->ID, 'medium'); ?>
                    <h4><?php echo esc_html($product->post_title); ?></h4>
                    <?php if ($brand && $model): ?>
                    <p class="dpc-product-meta"><?php echo esc_html($brand . ' ' . $model); ?></p>
                    <?php endif; ?>
                    <span class="dpc-product-price"><?php echo $wc_product->get_price_html(); ?></span>
                </a>
            </div>
            <?php
        }
        
        echo '</div>';
    }
}