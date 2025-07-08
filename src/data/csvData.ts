import { Product, ProductAttributeSet, CSVProductData } from '../types';

// Simulated CSV data parser - in WordPress this would parse actual CSV uploads
export const parseCSVData = (): CSVProductData => {
  // This would be replaced with actual CSV parsing logic in WordPress
  const csvProductsData = [
    {
      product_id: 'phone-case-001',
      product_name: '3 Color Gimp Snap Case',
      base_price: 99.00,
      image_url: 'https://images.pexels.com/photos/5081918/pexels-photo-5081918.jpeg?auto=compress&cs=tinysrgb&w=400',
      category: 'phone-case',
      attribute_types: ['brand', 'model']
    },
    {
      product_id: 'headphones-001',
      product_name: 'Wireless Earbuds Pro',
      base_price: 299.00,
      image_url: 'https://images.pexels.com/photos/3394650/pexels-photo-3394650.jpeg?auto=compress&cs=tinysrgb&w=400',
      category: 'audio',
      attribute_types: ['brand', 'color']
    }
  ];

  const csvAttributesData = [
    // Phone Case Attributes
    { product_id: 'phone-case-001', attribute_type: 'brand', attribute_value: 'vivo', attribute_label: 'Vivo', price_modifier: 0 },
    { product_id: 'phone-case-001', attribute_type: 'brand', attribute_value: 'samsung', attribute_label: 'Samsung', price_modifier: 0 },
    { product_id: 'phone-case-001', attribute_type: 'brand', attribute_value: 'apple', attribute_label: 'Apple', price_modifier: 25 },
    { product_id: 'phone-case-001', attribute_type: 'brand', attribute_value: 'xiaomi', attribute_label: 'Xiaomi', price_modifier: 0 },
    
    { product_id: 'phone-case-001', attribute_type: 'model', attribute_value: 'v15', attribute_label: 'V15', price_modifier: 0 },
    { product_id: 'phone-case-001', attribute_type: 'model', attribute_value: 'v20', attribute_label: 'V20', price_modifier: 50 },
    { product_id: 'phone-case-001', attribute_type: 'model', attribute_value: 'v25', attribute_label: 'V25', price_modifier: 100 },
    
    // Headphones Attributes
    { product_id: 'headphones-001', attribute_type: 'brand', attribute_value: 'sony', attribute_label: 'Sony', price_modifier: 0 },
    { product_id: 'headphones-001', attribute_type: 'brand', attribute_value: 'bose', attribute_label: 'Bose', price_modifier: 100 },
    { product_id: 'headphones-001', attribute_type: 'brand', attribute_value: 'apple', attribute_label: 'Apple', price_modifier: 150 },
    
    { product_id: 'headphones-001', attribute_type: 'color', attribute_value: 'black', attribute_label: 'Black', price_modifier: 0 },
    { product_id: 'headphones-001', attribute_type: 'color', attribute_value: 'white', attribute_label: 'White', price_modifier: 0 },
    { product_id: 'headphones-001', attribute_type: 'color', attribute_value: 'red', attribute_label: 'Red', price_modifier: 25 },
  ];

  const csvComplementaryData = [
    { main_product_id: 'phone-case-001', complementary_product_id: 'screen-guard-001', complementary_name: 'Add Flexible Glass Screen Guard', price: 29.00, original_price: 39.00, image_url: 'https://images.pexels.com/photos/5081918/pexels-photo-5081918.jpeg?auto=compress&cs=tinysrgb&w=200' },
    { main_product_id: 'phone-case-001', complementary_product_id: 'key-chain-001', complementary_name: 'Add Same Design Key Chain', price: 29.00, original_price: 39.00, image_url: 'https://images.pexels.com/photos/5081918/pexels-photo-5081918.jpeg?auto=compress&cs=tinysrgb&w=200' },
    { main_product_id: 'phone-case-001', complementary_product_id: 'phone-grip-001', complementary_name: 'Add Same Design Phone Grip', price: 49.00, original_price: 129.00, image_url: 'https://images.pexels.com/photos/5081918/pexels-photo-5081918.jpeg?auto=compress&cs=tinysrgb&w=200' },
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