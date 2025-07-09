import { Product, ProductAttributeSet, CSVProductData } from '../types';

// Simulated CSV data parser - in WordPress this would parse actual CSV uploads
export const parseCSVData = (): CSVProductData => {
  // This would be replaced with actual CSV parsing logic in WordPress
  const csvProductsData = [
    {
      product_id: 'samsung-s8-case-001',
      product_name: 'Samsung Galaxy S8 Back Cover',
      base_price: 199.00,
      image_url: 'https://gizmobitz.com/wp-content/uploads/2025/06/Samsung-Galaxy-S8-happy-yellow-smiley-face-wearing-glasses-giving-thumbs-up.jpg',
      category: 'mobile-back-cover',
      attribute_types: ['brand', 'model']
    },
    {
      product_id: 'samsung-s9-case-001',
      product_name: 'Samsung Galaxy S9 Back Cover',
      base_price: 199.00,
      image_url: 'https://gizmobitz.com/wp-content/uploads/2025/06/Samsung-Galaxy-S9-happy-friendship-day-text-with-suitable-image-friendship-celebration.jpg',
      category: 'mobile-back-cover',
      attribute_types: ['brand', 'model']
    }
  ];

  const csvAttributesData = [
    // Samsung S8 Case Attributes
    { product_id: 'samsung-s8-case-001', attribute_type: 'brand', attribute_value: 'samsung', attribute_label: 'Samsung', price_modifier: 0 },
    { product_id: 'samsung-s8-case-001', attribute_type: 'model', attribute_value: 'galaxy-s8', attribute_label: 'Galaxy S8', price_modifier: 0 },
    
    // Samsung S9 Case Attributes
    { product_id: 'samsung-s9-case-001', attribute_type: 'brand', attribute_value: 'samsung', attribute_label: 'Samsung', price_modifier: 0 },
    { product_id: 'samsung-s9-case-001', attribute_type: 'model', attribute_value: 'galaxy-s9', attribute_label: 'Galaxy S9', price_modifier: 0 },
    
    // Additional brands and models can be added here
    { product_id: 'apple-iphone12-case-001', attribute_type: 'brand', attribute_value: 'apple', attribute_label: 'Apple', price_modifier: 25 },
    { product_id: 'apple-iphone12-case-001', attribute_type: 'model', attribute_value: 'iphone-12', attribute_label: 'iPhone 12', price_modifier: 50 },
  ];

  const csvComplementaryData = [
    { main_product_id: 'samsung-s8-case-001', complementary_product_id: 'screen-guard-s8', complementary_name: 'Samsung Galaxy S8 Screen Guard', price: 29.00, original_price: 39.00, image_url: 'https://example.com/screen-guard.jpg' },
    { main_product_id: 'samsung-s8-case-001', complementary_product_id: 'phone-grip-s8', complementary_name: 'Samsung Galaxy S8 Phone Grip', price: 49.00, original_price: 129.00, image_url: 'https://example.com/phone-grip.jpg' },
    { main_product_id: 'samsung-s9-case-001', complementary_product_id: 'screen-guard-s9', complementary_name: 'Samsung Galaxy S9 Screen Guard', price: 29.00, original_price: 39.00, image_url: 'https://example.com/screen-guard-s9.jpg' },
  ];

  // Process the data into our structure
  const products: Product[] = csvProductsData.map(productData => {
    const productAttributes: ProductAttributeSet = {};
    
    // Group attributes by type for this product
    productData.attribute_types.forEach(attrType => {
      productAttributes[attrType] = csvAttributesData
        .filter(attr => attr.product_id === productData.product_id && attr.attribute_type === attrType)
        .map(attr => ({
          value: attr.attribute_value,
          label: attr.attribute_label,
          priceModifier: attr.price_modifier
        }));
    });

    return {
      id: productData.product_id,
      name: productData.product_name,
      image: productData.image_url,
      basePrice: productData.base_price,
      category: productData.category,
      attributeTypes: productData.attribute_types,
      availableAttributes: productAttributes
    };
  });

  // Process complementary products
  const complementaryProducts: { [productId: string]: Product[] } = {};
  csvComplementaryData.forEach(comp => {
    if (!complementaryProducts[comp.main_product_id]) {
      complementaryProducts[comp.main_product_id] = [];
    }
    
    complementaryProducts[comp.main_product_id].push({
      id: comp.complementary_product_id,
      name: comp.complementary_name,
      image: comp.image_url,
      basePrice: comp.price,
      originalPrice: comp.original_price,
      category: 'accessory',
      attributeTypes: [],
      availableAttributes: {},
      isComplementary: true
    });
  });

  // Recommended products (could also come from CSV)
  const recommendedProducts: Product[] = [
    {
      id: 'cable-protector',
      name: 'Spiral Cable Protector – Multicolor',
      image: 'https://images.pexels.com/photos/5081918/pexels-photo-5081918.jpeg?auto=compress&cs=tinysrgb&w=200',
      basePrice: 29.00,
      originalPrice: 199.00,
      category: 'accessory',
      attributeTypes: [],
      availableAttributes: {},
      isRecommended: true,
      isSpecialOffer: true
    },
    {
      id: 'charging-cable',
      name: '4 in1 Super Fast Charging Data Cable',
      image: 'https://images.pexels.com/photos/5081918/pexels-photo-5081918.jpeg?auto=compress&cs=tinysrgb&w=200',
      basePrice: 129.00,
      originalPrice: 1199.00,
      category: 'accessory',
      attributeTypes: [],
      availableAttributes: {},
      isRecommended: true,
      stockStatus: 'Only Few Left'
    }
  ];

  return {
    products,
    complementaryProducts,
    recommendedProducts
  };
};

// Export the parsed data
export const csvData = parseCSVData();
export const products = csvData.products;
export const mainProduct = products[0]; // First product as main for demo
export const complementaryProducts = csvData.complementaryProducts[mainProduct.id] || [];
export const recommendedProducts = csvData.recommendedProducts;

// Legacy export for backward compatibility
export const productAttributes = mainProduct.availableAttributes;