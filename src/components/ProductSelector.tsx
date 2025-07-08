import React, { useState, useEffect } from 'react';
import { ChevronDown } from 'lucide-react';
import { products, csvData } from '../data/csvData';
import { useCart } from '../context/CartContext';
import { Product } from '../types';

export const ProductSelector: React.FC = () => {
  const [selectedProduct, setSelectedProduct] = useState<Product>(products[0]);
  const [selectedAttributes, setSelectedAttributes] = useState<{ [key: string]: string }>({});
  const [selectedComplementary, setSelectedComplementary] = useState<string[]>([]);
  const { addToCart } = useCart();

  // Reset attributes when product changes
  useEffect(() => {
    setSelectedAttributes({});
    setSelectedComplementary([]);
  }, [selectedProduct]);

  const handleAttributeChange = (attributeType: string, value: string) => {
    setSelectedAttributes(prev => ({
      ...prev,
      [attributeType]: value
    }));
  };

  const handleAddToCart = () => {
    // Add main product with selected attributes
    addToCart(selectedProduct, selectedAttributes);

    // Add selected complementary products
    const complementaryForProduct = csvData.complementaryProducts[selectedProduct.id] || [];
    selectedComplementary.forEach(productId => {
      const product = complementaryForProduct.find(p => p.id === productId);
      if (product) {
        addToCart(product, {});
      }
    });
  };

  const toggleComplementary = (productId: string) => {
    setSelectedComplementary(prev => 
      prev.includes(productId) 
        ? prev.filter(id => id !== productId)
        : [...prev, productId]
    );
  };

  // Calculate total price including attribute modifiers
  const calculatePrice = () => {
    let totalPrice = selectedProduct.basePrice;
    
    selectedProduct.attributeTypes.forEach(attrType => {
      const selectedValue = selectedAttributes[attrType];
      if (selectedValue) {
        const attribute = selectedProduct.availableAttributes[attrType]?.find(
          attr => attr.value === selectedValue
        );
        if (attribute?.priceModifier) {
          totalPrice += attribute.priceModifier;
        }
      }
    });
    
    return totalPrice;
  };

  // Check if all required attributes are selected
  const isAddToCartEnabled = selectedProduct.attributeTypes.every(
    attrType => selectedAttributes[attrType]
  );

  const complementaryForProduct = csvData.complementaryProducts[selectedProduct.id] || [];

  return (
    <div className="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-6">
      <div className="space-y-6">
        {/* Product Selection */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Select Product:
          </label>
          <div className="relative">
            <select
              value={selectedProduct.id}
              onChange={(e) => {
                const product = products.find(p => p.id === e.target.value);
                if (product) setSelectedProduct(product);
              }}
              className="w-full p-3 border border-gray-300 rounded-lg appearance-none bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            >
              {products.map(product => (
                <option key={product.id} value={product.id}>
                  {product.name} - ₹{product.basePrice.toFixed(2)}
                </option>
              ))}
            </select>
            <ChevronDown className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 pointer-events-none" />
          </div>
        </div>

        {/* Product Image and Info */}
        <div className="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
          <img 
            src={selectedProduct.image} 
            alt={selectedProduct.name}
            className="w-20 h-20 object-cover rounded"
          />
          <div className="flex-1">
            <h3 className="font-semibold text-lg">{selectedProduct.name}</h3>
            <p className="text-2xl font-bold text-orange-600">₹{calculatePrice().toFixed(2)}</p>
            {calculatePrice() !== selectedProduct.basePrice && (
              <p className="text-sm text-gray-600">
                Base price: ₹{selectedProduct.basePrice.toFixed(2)}
              </p>
            )}
          </div>
        </div>

        {/* Dynamic Attribute Dropdowns */}
        {selectedProduct.attributeTypes.map(attributeType => (
          <div key={attributeType}>
            <label className="block text-sm font-medium text-gray-700 mb-2 capitalize">
              Select Your {attributeType}:
            </label>
            <div className="relative">
              <select
                value={selectedAttributes[attributeType] || ''}
                onChange={(e) => handleAttributeChange(attributeType, e.target.value)}
                className="w-full p-3 border border-gray-300 rounded-lg appearance-none bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
              >
                <option value="">Choose {attributeType}</option>
                {selectedProduct.availableAttributes[attributeType]?.map(option => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                    {option.priceModifier ? ` (+₹${option.priceModifier})` : ''}
                  </option>
                ))}
              </select>
              <ChevronDown className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 pointer-events-none" />
            </div>
          </div>
        ))}

        {/* Complementary Products */}
        {complementaryForProduct.length > 0 && (
          <div className="space-y-3">
            <h4 className="font-medium text-gray-900">Add Complementary Items:</h4>
            {complementaryForProduct.map(product => (
              <div key={product.id} className="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <div className="flex items-center space-x-3">
                  <input
                    type="checkbox"
                    id={product.id}
                    checked={selectedComplementary.includes(product.id)}
                    onChange={() => toggleComplementary(product.id)}
                    className="w-5 h-5 text-blue-600 rounded focus:ring-blue-500"
                  />
                  <label htmlFor={product.id} className="flex-1 cursor-pointer">
                    <span className="text-sm font-medium text-gray-900">{product.name}</span>
                  </label>
                </div>
                <div className="flex items-center space-x-2">
                  {product.originalPrice && (
                    <span className="text-sm text-gray-500 line-through">
                      ₹{product.originalPrice.toFixed(2)}
                    </span>
                  )}
                  <span className="text-sm font-bold text-gray-900">
                    ₹{product.basePrice.toFixed(2)}
                  </span>
                </div>
              </div>
            ))}
          </div>
        )}

        {/* Add to Cart Button */}
        <button
          onClick={handleAddToCart}
          disabled={!isAddToCartEnabled}
          className={`w-full py-3 px-4 rounded-lg font-semibold text-white transition-all duration-200 ${
            isAddToCartEnabled
              ? 'bg-orange-500 hover:bg-orange-600 active:bg-orange-700 shadow-md hover:shadow-lg'
              : 'bg-gray-300 cursor-not-allowed'
          }`}
        >
          ADD TO CART
        </button>

        {/* Debug Info */}
        <div className="text-xs text-gray-500 bg-gray-100 p-3 rounded">
          <strong>Selected Attributes:</strong> {JSON.stringify(selectedAttributes)}
          <br />
          <strong>Product Attribute Types:</strong> {selectedProduct.attributeTypes.join(', ')}
        </div>
      </div>
    </div>
  );
};