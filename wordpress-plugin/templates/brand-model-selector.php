<?php
/**
 * Brand/Model Selector Template
 * Replaces WooCommerce add-to-cart form
 */

if (!defined('ABSPATH')) {
    exit;
}

global $product;
$product_id = $product->get_id();
$current_brand = dpc_get_product_brand($product_id);
$current_model = dpc_get_product_model($product_id);
?>

<div class="dpc-brand-model-selector" data-product-id="<?php echo esc_attr($product_id); ?>">
    
    <!-- Current Product Info -->
    <div class="dpc-current-product">
        <h4><?php _e('Configure Your Product', 'dynamic-product-configurator'); ?></h4>
        <?php if ($current_brand && $current_model): ?>
        <p class="dpc-current-selection">
            <?php printf(__('Current: %s %s', 'dynamic-product-configurator'), $current_brand, $current_model); ?>
        </p>
        <?php endif; ?>
    </div>

    <!-- Brand/Model Selection Form -->
    <form class="dpc-selection-form" method="post">
        
        <!-- Brand Selector -->
        <div class="dpc-field-group">
            <label for="dpc_brand"><?php _e('Select Brand:', 'dynamic-product-configurator'); ?></label>
            <select id="dpc_brand" name="dpc_brand" class="dpc-brand-select" required>
                <option value=""><?php _e('Choose Brand', 'dynamic-product-configurator'); ?></option>
                <?php foreach ($all_brands as $brand_option): ?>
                <option value="<?php echo esc_attr($brand_option); ?>" <?php selected($current_brand, $brand_option); ?>>
                    <?php echo esc_html($brand_option); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Model Selector -->
        <div class="dpc-field-group">
            <label for="dpc_model"><?php _e('Select Model:', 'dynamic-product-configurator'); ?></label>
            <select id="dpc_model" name="dpc_model" class="dpc-model-select" required>
                <option value=""><?php _e('Choose Model', 'dynamic-product-configurator'); ?></option>
                <?php foreach ($all_models as $model_option): ?>
                <option value="<?php echo esc_attr($model_option); ?>" <?php selected($current_model, $model_option); ?>>
                    <?php echo esc_html($model_option); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Quantity -->
        <div class="dpc-field-group">
            <label for="dpc_quantity"><?php _e('Quantity:', 'dynamic-product-configurator'); ?></label>
            <div class="dpc-quantity-controls">
                <button type="button" class="dpc-qty-minus">-</button>
                <input type="number" id="dpc_quantity" name="quantity" value="1" min="1" class="dpc-quantity-input">
                <button type="button" class="dpc-qty-plus">+</button>
            </div>
        </div>

        <!-- Add to Cart Button -->
        <div class="dpc-actions">
            <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product_id); ?>" class="dpc-add-to-cart-btn">
                <?php _e('ADD TO CART', 'dynamic-product-configurator'); ?>
            </button>
        </div>

        <?php wp_nonce_field('dpc_add_to_cart', 'dpc_nonce'); ?>
    </form>

    <!-- Recommended Products -->
    <?php if (!empty($recommended)): ?>
    <div class="dpc-recommended-products">
        <h5><?php _e('Recommended Products', 'dynamic-product-configurator'); ?></h5>
        <div class="dpc-products-grid">
            <?php foreach ($recommended as $rec_id): 
                $rec_product = wc_get_product(trim($rec_id));
                if ($rec_product): ?>
            <div class="dpc-product-card">
                <a href="<?php echo get_permalink($rec_id); ?>">
                    <?php echo $rec_product->get_image('thumbnail'); ?>
                    <h6><?php echo $rec_product->get_name(); ?></h6>
                    <span class="price"><?php echo $rec_product->get_price_html(); ?></span>
                </a>
            </div>
            <?php endif; endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- You May Be Interested -->
    <?php if (!empty($interested)): ?>
    <div class="dpc-interested-products">
        <h5><?php _e('You May Be Interested', 'dynamic-product-configurator'); ?></h5>
        <div class="dpc-products-grid">
            <?php foreach ($interested as $int_id): 
                $int_product = wc_get_product(trim($int_id));
                if ($int_product): ?>
            <div class="dpc-product-card">
                <a href="<?php echo get_permalink($int_id); ?>">
                    <?php echo $int_product->get_image('thumbnail'); ?>
                    <h6><?php echo $int_product->get_name(); ?></h6>
                    <span class="price"><?php echo $int_product->get_price_html(); ?></span>
                </a>
            </div>
            <?php endif; endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>