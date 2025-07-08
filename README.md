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

```bash
# Clone the repository
git clone https://github.com/your-repo/dynamic-product-configurator.git
cd dynamic-product-configurator

# Install dependencies
npm install

# Build the React components
npm run build
```

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

### Step 3: CSV Data Preparation

Create your CSV files following these formats:

#### Products CSV (`products.csv`)
```csv
product_id,product_name,base_price,image_url,category,attribute_types
phone-case-001,3 Color Gimp Snap Case,99.00,https://example.com/image.jpg,phone-case,"brand,model"
headphones-001,Wireless Earbuds Pro,299.00,https://example.com/image2.jpg,audio,"brand,color"
```

#### Attributes CSV (`attributes.csv`)
```csv
product_id,attribute_type,attribute_value,attribute_label,price_modifier
phone-case-001,brand,vivo,Vivo,0
phone-case-001,brand,samsung,Samsung,0
phone-case-001,brand,apple,Apple,25
phone-case-001,model,v15,V15,0
phone-case-001,model,v20,V20,50
```

#### Complementary Products CSV (`complementary.csv`)
```csv
main_product_id,complementary_product_id,complementary_name,price,original_price,image_url
phone-case-001,screen-guard-001,Add Flexible Glass Screen Guard,29.00,39.00,https://example.com/guard.jpg
phone-case-001,key-chain-001,Add Same Design Key Chain,29.00,39.00,https://example.com/keychain.jpg
```

## 📊 CSV Upload Process

### Step 1: Access Admin Dashboard
1. Go to **WooCommerce → Product Configurator**
2. You'll see the CSV upload interface

### Step 2: Upload CSV Files
1. **Products CSV**: Required - Contains main product information
2. **Attributes CSV**: Required - Contains all product attributes and variations
3. **Complementary CSV**: Optional - Contains related/add-on products

### Step 3: Process and Verify
1. Click "Upload and Process CSV Files"
2. System will:
   - Parse CSV data
   - Create database entries
   - Generate WooCommerce products
   - Link relationships

## 🛠️ Development Setup

### Frontend Development

```bash
# Start development server
npm run dev

# Build for production
npm run build

# Run linting
npm run lint
```

### WordPress Development

```bash
# Watch for changes and rebuild
npm run watch

# Deploy to WordPress
npm run deploy
```

### File Structure

```
src/
├── components/           # React components
│   ├── ProductSelector.tsx
│   ├── CartSidebar.tsx
│   ├── CheckoutPage.tsx
│   └── BulkPurchaseModal.tsx
├── context/             # React context providers
│   └── CartContext.tsx
├── data/               # Data management
│   └── csvData.ts
├── types/              # TypeScript definitions
│   └── index.ts
└── hooks/              # Custom React hooks
    └── useCart.ts
```

## 🔧 Configuration

### Plugin Settings

Access plugin settings via **WooCommerce → Product Configurator → Settings**:

- **CSV Upload Limits**: Configure maximum file sizes
- **Price Display**: Set currency formatting
- **Attribute Validation**: Enable/disable required attributes
- **Bulk Purchase**: Configure minimum quantities

### Shortcode Usage

Display the configurator anywhere using shortcodes:

```php
// On any page or post
[dynamic_product_configurator product_id="phone-case-001"]

// In theme templates
<?php echo do_shortcode('[dynamic_product_configurator product_id="phone-case-001"]'); ?>
```

### Theme Integration

Add to product pages automatically:

```php
// In your theme's functions.php
add_action('woocommerce_single_product_summary', 'add_dynamic_configurator', 25);
```

## 🎨 Customization

### Styling

The plugin uses Tailwind CSS classes. Customize appearance by:

1. **Override CSS**:
   ```css
   .dpc-configurator {
     /* Your custom styles */
   }
   ```

2. **Modify React Components**:
   ```bash
   # Edit components in src/components/
   # Rebuild with npm run build
   ```

### Adding New Attribute Types

1. **Update CSV Structure**:
   ```csv
   product_id,attribute_type,attribute_value,attribute_label,price_modifier
   product-001,size,small,Small,0
   product-001,size,large,Large,10
   ```

2. **No Code Changes Required**: The system automatically handles new attribute types!

## 🔌 WooCommerce Integration

### Cart Integration
- Custom attributes stored as cart item meta
- Price modifications applied automatically
- Compatible with all cart functions

### Checkout Process
- Attributes display in cart/checkout
- Saved to order meta for fulfillment
- Works with all payment gateways

### Order Management
- Custom attributes visible in admin orders
- Exportable for fulfillment
- Compatible with order status workflows

## 📱 Frontend Features

### Product Configuration
- Dynamic dropdown population from CSV
- Real-time price updates
- Attribute validation
- Mobile-responsive design

### Shopping Cart
- Slide-out cart sidebar
- Quantity management
- Recommended products
- Seamless checkout flow

### Bulk Purchase System
- Minimum quantity thresholds
- Contact form integration
- Quote request system
- Admin notification system

## 🚀 Deployment

### Production Build

```bash
# Create production build
npm run build

# Optimize assets
npm run optimize

# Deploy to WordPress
npm run deploy:prod
```

### Server Requirements

- **PHP Memory**: Minimum 256MB (512MB recommended)
- **Upload Limits**: Increase for large CSV files
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

## 🔍 Troubleshooting

### Common Issues

#### CSV Upload Fails
```bash
# Check file permissions
chmod 755 wp-content/uploads/

# Increase upload limits in php.ini
upload_max_filesize = 64M
post_max_size = 64M
```

#### React Components Not Loading
```bash
# Rebuild assets
npm run build

# Check console for JavaScript errors
# Verify wp_enqueue_script is working
```

#### Database Errors
```sql
-- Check if tables exist
SHOW TABLES LIKE 'wp_dpc_%';

-- Recreate tables if needed
-- Deactivate and reactivate plugin
```

### Debug Mode

Enable debug mode in `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('DPC_DEBUG', true);
```

## 📚 API Reference

### PHP Hooks

```php
// Modify product data before display
add_filter('dpc_product_data', 'custom_product_modifier');

// Add custom attributes
add_action('dpc_after_attributes', 'add_custom_fields');

// Modify cart item data
add_filter('dpc_cart_item_data', 'custom_cart_modifier');
```

### JavaScript Events

```javascript
// Listen for product changes
document.addEventListener('dpc:product:changed', function(event) {
    console.log('Product changed:', event.detail);
});

// Listen for cart updates
document.addEventListener('dpc:cart:updated', function(event) {
    console.log('Cart updated:', event.detail);
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
- [ ] Multi-language support
- [ ] Advanced pricing rules
- [ ] Inventory management integration
- [ ] Analytics dashboard

### Version 2.1
- [ ] API endpoints for external integrations
- [ ] Mobile app compatibility
- [ ] Advanced bulk pricing tiers
- [ ] Custom attribute types

## 📈 Changelog

### Version 1.0.0 (Current)
- Initial release
- CSV-based product management
- WooCommerce integration
- React frontend
- Bulk purchase system

---

**Made with ❤️ for the WordPress community**

For more information, visit our [website](https://yourcompany.com) or check out the [live demo](https://demo.yourcompany.com).