<?php
/**
 * Product Configurator Template
 * This template can be overridden by themes
 */

if (!defined('ABSPATH')) {
    exit;
}

$product_id = isset($args['product_id']) ? $args['product_id'] : get_the_ID();
$dpc_product_data = dpc_get_product_data($product_id);

if (!$dpc_product_data) {
    echo '<div class="dpc-error">' . __('Product configuration not found.', 'dynamic-product-configurator') . '</div>';
    return;
}

$product = $dpc_product_data['product'];
$attributes = $dpc_product_data['attributes'];
$complementary = $dpc_product_data['complementary'];

// Group attributes by type
$grouped_attributes = array();
foreach ($attributes as $attr) {
    if (!isset($grouped_attributes[$attr->attribute_type])) {
        $grouped_attributes[$attr->attribute_type] = array();
    }
    $grouped_attributes[$attr->attribute_type][] = $attr;
}
?>

<div class="dpc-configurator-template" data-product-id="<?php echo esc_attr($product_id); ?>" data-dpc-product-id="<?php echo esc_attr($product->product_id); ?>">
    
    <!-- Product Information -->
    <div class="dpc-product-header">
        <?php if ($product->image_url): ?>
        <div class="dpc-product-image">
            <img src="<?php echo esc_url($product->image_url); ?>" alt="<?php echo esc_attr($product->product_name); ?>" />
        </div>
        <?php endif; ?>
        
        <div class="dpc-product-info">
            <h2 class="dpc-product-title"><?php echo esc_html($product->product_name); ?></h2>
            <div class="dpc-product-price">
                <span class="dpc-currency"><?php echo get_woocommerce_currency_symbol(); ?></span>
                <span class="dpc-price-amount" id="dpc-current-price"><?php echo number_format($product->base_price, 2); ?></span>
            </div>
            <?php if ($product->category): ?>
            <div class="dpc-product-category">
                <span class="dpc-category-label"><?php _e('Category:', 'dynamic-product-configurator'); ?></span>
                <span class="dpc-category-value"><?php echo esc_html($product->category); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Configuration Form -->
    <form class="dpc-configuration-form" id="dpc-form-<?php echo esc_attr($product_id); ?>">
        
        <!-- Attribute Selectors -->
        <?php if (!empty($grouped_attributes)): ?>
        <div class="dpc-attributes-section">
            <h3 class="dpc-section-title"><?php _e('Configure Your Product', 'dynamic-product-configurator'); ?></h3>
            
            <?php 
            $attribute_types = explode(',', $product->attribute_types);
            foreach ($attribute_types as $attr_type): 
                $attr_type = trim($attr_type);
                if (isset($grouped_attributes[$attr_type])):
            ?>
            <div class="dpc-attribute-group" data-attribute-type="<?php echo esc_attr($attr_type); ?>">
                <label class="dpc-attribute-label" for="dpc-attr-<?php echo esc_attr($attr_type); ?>">
                    <?php echo sprintf(__('Select Your %s:', 'dynamic-product-configurator'), ucfirst($attr_type)); ?>
                    <span class="dpc-required">*</span>
                </label>
                
                <select class="dpc-attribute-select" id="dpc-attr-<?php echo esc_attr($attr_type); ?>" name="dpc_attributes[<?php echo esc_attr($attr_type); ?>]" data-attribute="<?php echo esc_attr($attr_type); ?>" required>
                    <option value=""><?php echo sprintf(__('Choose %s', 'dynamic-product-configurator'), $attr_type); ?></option>
                    <?php foreach ($grouped_attributes[$attr_type] as $option): ?>
                    <option value="<?php echo esc_attr($option->attribute_value); ?>" data-price-modifier="<?php echo esc_attr($option->price_modifier); ?>">
                        <?php echo esc_html($option->attribute_label); ?>
                        <?php if ($option->price_modifier != 0): ?>
                            (<?php echo $option->price_modifier > 0 ? '+' : ''; ?><?php echo wc_price($option->price_modifier); ?>)
                        <?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <div class="dpc-attribute-error" style="display: none;">
                    <?php echo sprintf(__('Please select your %s', 'dynamic-product-configurator'), $attr_type); ?>
                </div>
            </div>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>
        <?php endif; ?>

        <!-- Complementary Products -->
        <?php if (!empty($complementary)): ?>
        <div class="dpc-complementary-section">
            <h3 class="dpc-section-title"><?php _e('Add Complementary Items', 'dynamic-product-configurator'); ?></h3>
            
            <div class="dpc-complementary-grid">
                <?php foreach ($complementary as $comp): ?>
                <div class="dpc-complementary-item">
                    <label class="dpc-complementary-label">
                        <input type="checkbox" 
                               class="dpc-complementary-checkbox" 
                               name="dpc_complementary[]" 
                               value="<?php echo esc_attr($comp->complementary_product_id); ?>"
                               data-product-id="<?php echo esc_attr($comp->complementary_product_id); ?>"
                               data-price="<?php echo esc_attr($comp->price); ?>"
                               data-name="<?php echo esc_attr($comp->complementary_name); ?>">
                        
                        <div class="dpc-complementary-content">
                            <?php if ($comp->image_url): ?>
                            <div class="dpc-complementary-image">
                                <img src="<?php echo esc_url($comp->image_url); ?>" alt="<?php echo esc_attr($comp->complementary_name); ?>" />
                            </div>
                            <?php endif; ?>
                            
                            <div class="dpc-complementary-info">
                                <span class="dpc-complementary-name"><?php echo esc_html($comp->complementary_name); ?></span>
                                
                                <div class="dpc-complementary-price">
                                    <?php if ($comp->original_price && $comp->original_price > $comp->price): ?>
                                    <span class="dpc-original-price"><?php echo wc_price($comp->original_price); ?></span>
                                    <?php endif; ?>
                                    <span class="dpc-current-price"><?php echo wc_price($comp->price); ?></span>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quantity Selector -->
        <div class="dpc-quantity-section">
            <label class="dpc-quantity-label" for="dpc-quantity">
                <?php _e('Quantity:', 'dynamic-product-configurator'); ?>
            </label>
            <div class="dpc-quantity-controls">
                <button type="button" class="dpc-quantity-btn dpc-quantity-minus">-</button>
                <input type="number" id="dpc-quantity" name="dpc_quantity" value="1" min="1" max="999" class="dpc-quantity-input">
                <button type="button" class="dpc-quantity-btn dpc-quantity-plus">+</button>
            </div>
        </div>

        <!-- Price Summary -->
        <div class="dpc-price-summary">
            <div class="dpc-price-breakdown">
                <div class="dpc-price-line">
                    <span class="dpc-price-label"><?php _e('Base Price:', 'dynamic-product-configurator'); ?></span>
                    <span class="dpc-price-value" id="dpc-base-price"><?php echo wc_price($product->base_price); ?></span>
                </div>
                
                <div class="dpc-price-line dpc-attribute-modifiers" style="display: none;">
                    <span class="dpc-price-label"><?php _e('Attribute Modifiers:', 'dynamic-product-configurator'); ?></span>
                    <span class="dpc-price-value" id="dpc-attribute-total">+<?php echo wc_price(0); ?></span>
                </div>
                
                <div class="dpc-price-line dpc-complementary-total" style="display: none;">
                    <span class="dpc-price-label"><?php _e('Complementary Items:', 'dynamic-product-configurator'); ?></span>
                    <span class="dpc-price-value" id="dpc-complementary-price">+<?php echo wc_price(0); ?></span>
                </div>
                
                <div class="dpc-price-line dpc-total-line">
                    <span class="dpc-price-label"><?php _e('Total Price:', 'dynamic-product-configurator'); ?></span>
                    <span class="dpc-price-value dpc-total-price" id="dpc-total-price"><?php echo wc_price($product->base_price); ?></span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="dpc-actions">
            <button type="button" class="dpc-add-to-cart-btn" id="dpc-add-to-cart" data-product-id="<?php echo esc_attr($product_id); ?>" data-dpc-product-id="<?php echo esc_attr($product->product_id); ?>" disabled>
                <span class="dpc-btn-text"><?php _e('ADD TO CART', 'dynamic-product-configurator'); ?></span>
                <span class="dpc-btn-loading" style="display: none;"><?php _e('ADDING...', 'dynamic-product-configurator'); ?></span>
            </button>
            
            <button type="button" class="dpc-bulk-order-btn" id="dpc-bulk-order">
                <?php _e('Request Bulk Quote', 'dynamic-product-configurator'); ?>
            </button>
        </div>

        <!-- Hidden Fields -->
        <input type="hidden" name="dpc_product_id" value="<?php echo esc_attr($product->product_id); ?>">
        <input type="hidden" name="dpc_wc_product_id" value="<?php echo esc_attr($product_id); ?>">
        <?php wp_nonce_field('dpc_add_to_cart', 'dpc_nonce'); ?>
    </form>

    <!-- Configuration Summary (Hidden by default, shown when product is configured) -->
    <div class="dpc-configuration-summary" id="dpc-summary" style="display: none;">
        <h4><?php _e('Your Configuration:', 'dynamic-product-configurator'); ?></h4>
        <div class="dpc-summary-content"></div>
    </div>
</div>

<script type="application/json" id="dpc-product-data-<?php echo esc_attr($product_id); ?>">
<?php
// Prepare data for JavaScript
$js_data = array(
    'product' => array(
        'id' => $product->product_id,
        'name' => $product->product_name,
        'basePrice' => floatval($product->base_price),
        'image' => $product->image_url,
        'category' => $product->category,
        'attributeTypes' => explode(',', $product->attribute_types),
        'wcProductId' => $product_id
    ),
    'attributes' => array(),
    'complementary' => array(),
    'currency' => array(
        'symbol' => get_woocommerce_currency_symbol(),
        'position' => get_option('woocommerce_currency_pos'),
        'thousandSeparator' => wc_get_price_thousand_separator(),
        'decimalSeparator' => wc_get_price_decimal_separator(),
        'decimals' => wc_get_price_decimals()
    )
);

// Prepare attributes
foreach ($grouped_attributes as $attr_type => $attrs) {
    $js_data['attributes'][$attr_type] = array();
    foreach ($attrs as $attr) {
        $js_data['attributes'][$attr_type][] = array(
            'value' => $attr->attribute_value,
            'label' => $attr->attribute_label,
            'priceModifier' => floatval($attr->price_modifier)
        );
    }
}

// Prepare complementary products
foreach ($complementary as $comp) {
    $js_data['complementary'][] = array(
        'id' => $comp->complementary_product_id,
        'name' => $comp->complementary_name,
        'price' => floatval($comp->price),
        'originalPrice' => $comp->original_price ? floatval($comp->original_price) : null,
        'image' => $comp->image_url
    );
}

echo wp_json_encode($js_data);
?>
</script>