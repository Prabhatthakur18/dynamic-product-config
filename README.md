# Dynamic Product Configurator for WooCommerce

A powerful WordPress plugin that enables CSV-based dynamic product configuration with React frontend, seamlessly integrating with WooCommerce for a complete e-commerce solution.

## 🌟 Features

- **CSV-Based Product Management**: Upload products, attributes, and complementary items via CSV files
- **Dynamic Attribute Selection**: Configurable dropdowns based on product specifications
- **Real-time Price Calculation**: Automatic price updates based on selected attributes
- **Complementary Products**: Suggest related items during configuration
- **Bulk Purchase Requests**: Built-in bulk ordering system with contact forms
- **WooCommerce Integration**: Full compatibility with WooCommerce cart, checkout, and order management
- **Responsive Design**: Mobile-first design with modern UI/UX
- **Admin Dashboard**: Easy-to-use admin interface for CSV uploads and management

## 📋 Requirements

### WordPress Environment
- WordPress 5.0 or higher
- WooCommerce 4.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

### Development Environment
- Node.js 16+ and npm
- Modern browser with ES6+ support

## 🚀 Installation

### Step 1: Download and Setup

1. **Download the Plugin Files**:
   - Copy the entire `wordpress-plugin` folder to your WordPress plugins directory
   - Rename it to `dynamic-product-configurator`

### Step 2: WordPress Plugin Installation

1. **Upload Plugin Files**:
   ```
   /wp-content/plugins/dynamic-product-configurator/
   ├── dynamic-product-configurator.php
   ├── includes/
   ├── admin/
   ├── assets/
   └── templates/
   ```

2. **Activate Plugin**:
   - Go to WordPress Admin → Plugins
   - Find "Dynamic Product Configurator"
   - Click "Activate"

3. **Verify Installation**:
   - Check WooCommerce → Product Configurator appears in menu
   - Database tables are created automatically

### Step 3: Product Parsing (NEW APPROACH!)

**No CSV uploads needed!** The plugin now automatically parses your existing WooCommerce products:

1. **Go to WooCommerce → Product Configurator → 🚀 Auto Parser**
2. **Click "Parse All Products Now"** - This will automatically:
   - Scan all your existing WooCommerce products
   - Extract Brand and Model from product names
   - Create the brand/model dropdown structure
   - Enable the configurator for matching products

3. **Click "Enable for All Products"** to activate the configurator

### Step 4: Frontend Usage

#### Option 1: Brand/Model Selector (Recommended)
Use this shortcode to show a brand/model selector:
```php
[dynamic_product_configurator type="selector"]
```

This will show:
1. **Brand Dropdown**: Samsung, Apple, OnePlus, etc.
2. **Model Dropdown**: Updates based on selected brand
3. **Product Grid**: Shows all products for selected brand+model
4. **Add to Cart**: Direct add to cart for each product

#### Option 2: Specific Product Configurator
For individual product pages:
```php
[dynamic_product_configurator product_id="123"]
```

## 🔄 **How the Parsing Works**

The plugin intelligently parses your product names to extract brand and model:

### **Supported Brands & Models**
- **Samsung**: Galaxy S8, S8 Plus, S9, S9 Plus, S10, S20, S21, Note series, A series, M series
- **Apple**: iPhone 12, 12 Pro, 12 Mini, 11, 11 Pro, X, XS, 8, 7
- **OnePlus**: OnePlus 9, 8, 7, Nord
- **Xiaomi**: Redmi, Mi series
- **Other Brands**: Oppo, Vivo, Realme, Huawei, Google Pixel, Motorola, Nokia, LG, Sony

### **Example Parsing**
```
Product Name: "Samsung Galaxy S8 Happy Yellow Smiley Face..."
→ Brand: Samsung
→ Model: Galaxy S8

Product Name: "Samsung Galaxy S9 Plus Happy Friendship Day..."
→ Brand: Samsung  
→ Model: Galaxy S9 Plus
```

## 📱 Frontend User Experience

### **Brand/Model Selector Flow**
1. **User sees brand dropdown** with all available brands
2. **Selects brand** (e.g., Samsung)
3. **Model dropdown populates** with Samsung models (S8, S9, S10, etc.)
4. **Selects model** (e.g., Galaxy S8)
5. **Product grid appears** showing all Galaxy S8 back covers
6. **User clicks "Add to Cart"** on desired product
7. **Product added to WooCommerce cart** with brand/model attributes

### **Benefits of This Approach**
- ✅ **No CSV uploads needed**
- ✅ **Works with existing products**
- ✅ **User-friendly brand/model selection**
- ✅ **Automatic product discovery**
- ✅ **Full WooCommerce integration**
- ✅ **Mobile responsive design**

## 🔧 Configuration

### Plugin Settings

Access plugin settings via **WooCommerce → Product Configurator → Settings**:

- **Auto-parsing**: Enable/disable automatic product parsing
- **Brand patterns**: Customize brand detection patterns
- **Model patterns**: Customize model detection patterns
- **Frontend display**: Configure how the selector appears

### Shortcode Usage

**Brand/Model Selector** (recommended for main pages):
```php
[dynamic_product_configurator type="selector"]
```

**Specific Product Configurator** (for individual products):
```php
[dynamic_product_configurator product_id="123"]
```

**With custom CSS class**:
```php
[dynamic_product_configurator type="selector" class="my-custom-class"]
```

### Theme Integration

Add to any page or post:
```php
// In theme templates
<?php echo do_shortcode('[dynamic_product_configurator type="selector"]'); ?>
```

Auto-enable on product pages:
```php
// In your theme's functions.php
add_action('woocommerce_single_product_summary', 'add_dynamic_configurator', 25);
function add_dynamic_configurator() {
    echo do_shortcode('[dynamic_product_configurator type="selector"]');
}
```

## 🎨 Customization

### Styling

The plugin uses clean, modern CSS. Customize appearance by:

1. **Override CSS**:
   ```css
   .dpc-brand-model-selector {
     /* Your custom styles */
     background: #f5f5f5;
     border-radius: 15px;
   }
   
   .dpc-product-card {
     /* Customize product cards */
     border: 3px solid #your-color;
   }
   ```

2. **Custom Brand/Model Patterns**:
   ```php
   // Add to your theme's functions.php
   add_filter('dpc_brand_patterns', 'custom_brand_patterns');
   function custom_brand_patterns($patterns) {
       $patterns['custom-brand'] = array(
           'patterns' => array('custom', 'brand'),
           'label' => 'Custom Brand'
       );
       return $patterns;
   }
   ```

## 🔌 WooCommerce Integration

### Cart Integration
- Brand and model stored as cart item meta
- Displays in cart: "Brand: Samsung, Model: Galaxy S8"
- Compatible with all cart functions

### Checkout Process
- Attributes display in cart/checkout
- Saved to order meta for fulfillment
- Works with all payment gateways

### Order Management
- Brand/model visible in admin orders
- Exportable for fulfillment
- Compatible with order status workflows

## 📱 Frontend Features

### Brand/Model Selector
- Clean, modern dropdown interface
- Dynamic model loading based on brand
- Real-time product filtering
- Mobile-responsive design

### Product Grid
- Card-based product display
- Product images and pricing
- Direct add to cart functionality
- Hover effects and animations

### Shopping Cart Integration
- Seamless WooCommerce cart integration
- Brand/model attributes preserved
- Compatible with all cart plugins

## 🚀 Performance & Optimization

### Database Optimization
- Efficient product parsing
- Indexed database queries
- Minimal database overhead

### Frontend Performance
- Lightweight JavaScript
- CSS-only animations
- Optimized AJAX requests
- Responsive image loading

### Caching Compatibility
- Works with all major caching plugins
- AJAX-based dynamic content
- Cache-friendly static assets

## 🔍 Troubleshooting

### Common Issues

#### Products Not Parsing
```bash
# Check if products have recognizable brand/model patterns
# Go to Admin → Product Configurator → Auto Parser
# Review parsing results and errors
```

#### Brand/Model Dropdowns Empty
```bash
# Ensure products were parsed successfully
# Check WooCommerce → Product Configurator → Auto Parser
# Verify database tables were created
```

#### JavaScript Errors
```bash
# Check browser console for errors
# Verify jQuery and WooCommerce scripts are loaded
# Clear cache and test again
```

### Debug Mode

Enable debug mode in `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('DPC_DEBUG', true);
```

## 🚀 Deployment

### Production Build

1. **Upload plugin files** to `/wp-content/plugins/dynamic-product-configurator/`
2. **Activate plugin** in WordPress admin
3. **Run product parser** to extract brand/model data
4. **Add shortcode** to desired pages
5. **Test functionality** with your products

### Server Requirements

- **PHP Memory**: Minimum 256MB (512MB recommended)
- **Database**: Ensure adequate storage for product data
- **Database**: Ensure adequate storage for product data

### Performance Optimization

1. **Enable Caching**:
   ```php
   // Add to wp-config.php
   define('WP_CACHE', true);
   ```

2. **Optimize Database**:
   ```sql
   -- Add indexes for better performance
   ALTER TABLE wp_dpc_products ADD INDEX idx_product_id (product_id);
   ALTER TABLE wp_dpc_product_attributes ADD INDEX idx_product_attr (product_id, attribute_type);
   ```

## 📚 API Reference

### PHP Hooks

```php
// Modify product data before display
add_filter('dpc_product_data', 'custom_product_modifier');

// Add custom brand patterns
add_filter('dpc_brand_patterns', 'custom_brand_patterns');

// Modify cart item data
add_filter('dpc_cart_item_data', 'custom_cart_modifier');
```

### JavaScript Events

```javascript
// Listen for brand selection
document.addEventListener('dpc:brand:selected', function(event) {
    console.log('Brand selected:', event.detail);
});

// Listen for model selection
document.addEventListener('dpc:model:selected', function(event) {
    console.log('Model selected:', event.detail);
});
```

## 🤝 Contributing

### Development Workflow

1. **Fork the repository**
2. **Create feature branch**: `git checkout -b feature/amazing-feature`
3. **Make changes and test**
4. **Commit changes**: `git commit -m 'Add amazing feature'`
5. **Push to branch**: `git push origin feature/amazing-feature`
6. **Open Pull Request**

### Code Standards

- **PHP**: Follow WordPress Coding Standards
- **JavaScript/TypeScript**: ESLint configuration provided
- **CSS**: Use Tailwind CSS classes when possible
- **Documentation**: Update README for new features

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

### Documentation
- [Plugin Documentation](docs/)
- [API Reference](docs/api.md)
- [Troubleshooting Guide](docs/troubleshooting.md)

### Community
- [GitHub Issues](https://github.com/your-repo/issues)
- [WordPress.org Support](https://wordpress.org/support/plugin/dynamic-product-configurator)
- [Discord Community](https://discord.gg/your-discord)

### Professional Support
For custom development and priority support:
- Email: support@yourcompany.com
- Website: https://yourcompany.com/support

## 🎯 Roadmap

### Version 2.0
- [ ] **Advanced Brand Detection**: AI-powered brand/model recognition
- [ ] **Multi-language Support**: Translate brand/model names
- [ ] **Custom Attributes**: Add color, size, material options
- [ ] **Analytics Dashboard**: Track popular brand/model combinations

### Version 2.1
- [ ] **API Endpoints**: REST API for external integrations
- [ ] **Mobile App**: React Native companion app
- [ ] **Advanced Filtering**: Filter by price, features, etc.
- [ ] **Bulk Operations**: Bulk enable/disable products

## 📈 Changelog

### Version 1.0.0 (Current)
- ✅ **Automatic Product Parsing**: No CSV uploads needed
- ✅ **Brand/Model Detection**: Smart pattern recognition
- ✅ **Frontend Selector**: User-friendly brand/model dropdowns
- WooCommerce integration
- ✅ **Mobile Responsive**: Works on all devices
- ✅ **Performance Optimized**: Fast loading and smooth interactions

---

**🚀 Ready to revolutionize your mobile back cover store!**

Transform your WooCommerce store with intelligent brand/model selection that makes shopping effortless for your customers.