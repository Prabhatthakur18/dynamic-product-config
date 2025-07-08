import React, { useState } from 'react';
import { Minus, Plus, ArrowLeft, Tag } from 'lucide-react';
import { useCart } from '../context/CartContext';
import { recommendedProducts } from '../data/csvData';
import { BulkPurchaseModal } from './BulkPurchaseModal';

export const CheckoutPage: React.FC = () => {
  const { cartItems, updateQuantity, addToCart, getCartTotal, setActiveView } = useCart();
  const [couponCode, setCouponCode] = useState('');
  const [showBulkModal, setShowBulkModal] = useState(false);
  const [selectedBulkProduct, setSelectedBulkProduct] = useState<any>(null);

  const handleBulkPurchase = (product: any) => {
    setSelectedBulkProduct(product);
    setShowBulkModal(true);
  };

  const subtotal = getCartTotal();
  const shipping = 0; // Free shipping
  const total = subtotal;

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-6xl mx-auto px-4 py-8">
        {/* Progress Bar */}
        <div className="mb-8">
          <div className="flex items-center justify-center space-x-4 text-sm">
            <span className="text-gray-900 font-medium">SHOPPING CART</span>
            <span className="text-gray-400">›</span>
            <span className="text-gray-400">CHECKOUT DETAILS</span>
            <span className="text-gray-400">›</span>
            <span className="text-gray-400">ORDER COMPLETE</span>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main Content */}
          <div className="lg:col-span-2 space-y-6">
            {/* Cart Items */}
            <div className="bg-white rounded-lg shadow-sm p-6">
              <div className="grid grid-cols-5 gap-4 mb-4 text-sm font-medium text-gray-700 border-b pb-4">
                <div>PRODUCT</div>
                <div>PRICE</div>
                <div>QUANTITY</div>
                <div>SUBTOTAL</div>
                <div></div>
              </div>

              {cartItems.map((item, index) => (
                <div key={`${item.id}-${index}`} className="grid grid-cols-5 gap-4 items-center py-4 border-b">
                  <div className="flex items-center space-x-3">
                    <img 
                      src={item.image} 
                      alt={item.name}
                      className="w-16 h-16 object-cover rounded"
                    />
                    <div>
                      <h3 className="font-medium">{item.name}</h3>
                      <p className="text-sm text-blue-600 underline cursor-pointer">Edit options</p>
                      {item.selectedAttributes.brand && (
                        <p className="text-sm text-gray-600">
                          Select Your Brand: {item.selectedAttributes.brand}
                        </p>
                      )}
                      {item.selectedAttributes.model && (
                        <p className="text-sm text-gray-600">
                          Select Your Model: {item.selectedAttributes.model}
                        </p>
                      )}
                    </div>
                  </div>
                  <div className="font-semibold">₹{item.basePrice.toFixed(2)}</div>
                  <div className="flex items-center space-x-2">
                    <button
                      onClick={() => updateQuantity(item.id, item.selectedAttributes, item.quantity - 1)}
                      className="p-1 hover:bg-gray-100 rounded"
                    >
                      <Minus className="w-4 h-4" />
                    </button>
                    <input
                      type="number"
                      value={item.quantity}
                      onChange={(e) => updateQuantity(item.id, item.selectedAttributes, parseInt(e.target.value))}
                      className="w-16 text-center border rounded px-2 py-1"
                    />
                    <button
                      onClick={() => updateQuantity(item.id, item.selectedAttributes, item.quantity + 1)}
                      className="p-1 hover:bg-gray-100 rounded"
                    >
                      <Plus className="w-4 h-4" />
                    </button>
                  </div>
                  <div className="font-semibold">₹{(item.basePrice * item.quantity).toFixed(2)}</div>
                  <div>
                    <button
                      onClick={() => handleBulkPurchase(item)}
                      className="text-blue-600 hover:text-blue-800 text-sm font-medium"
                    >
                      Buy Bulk
                    </button>
                  </div>
                </div>
              ))}
            </div>

            {/* Continue Shopping */}
            <div className="flex items-center">
              <button 
                onClick={() => setActiveView('product')}
                className="flex items-center space-x-2 text-gray-700 hover:text-gray-900 border border-gray-300 px-4 py-2 rounded transition-colors"
              >
                <ArrowLeft className="w-4 h-4" />
                <span>CONTINUE SHOPPING</span>
              </button>
            </div>

            {/* Recommended Products */}
            <div className="bg-white rounded-lg shadow-sm p-6">
              <h3 className="text-lg font-medium mb-4">You may be interested in...</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {recommendedProducts.map(product => (
                  <div key={product.id} className="border rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div className="relative">
                      <img 
                        src={product.image} 
                        alt={product.name}
                        className="w-full h-32 object-cover rounded mb-3"
                      />
                      {product.stockStatus && (
                        <span className="absolute top-2 left-2 bg-orange-500 text-white text-xs px-2 py-1 rounded">
                          {product.stockStatus}
                        </span>
                      )}
                      {product.isSpecialOffer && (
                        <span className="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded">
                          SPECIAL OFFER
                        </span>
                      )}
                    </div>
                    <h4 className="font-medium text-sm mb-2">{product.name}</h4>
                    <div className="flex items-center justify-between mb-3">
                      <div className="flex items-center space-x-2">
                        {product.originalPrice && (
                          <span className="text-gray-500 line-through text-sm">
                            ₹{product.originalPrice.toFixed(2)}
                          </span>
                        )}
                        <span className="font-bold">₹{product.basePrice.toFixed(2)}</span>
                      </div>
                    </div>
                    <button
                      onClick={() => addToCart(product, {})}
                      className="w-full bg-black text-white py-2 rounded text-sm hover:bg-gray-800 transition-colors"
                    >
                      ADD TO CART
                    </button>
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* Cart Totals */}
          <div className="lg:col-span-1">
            <div className="bg-white rounded-lg shadow-sm p-6 sticky top-8">
              <h3 className="text-lg font-medium mb-4">CART TOTALS</h3>
              
              <div className="space-y-3 mb-4">
                <div className="flex justify-between">
                  <span>Subtotal</span>
                  <span className="font-semibold">₹{subtotal.toFixed(2)}</span>
                </div>
                <div className="flex justify-between">
                  <span>Shipping</span>
                  <span className="text-gray-600">Shipping costs are calculated during checkout.</span>
                </div>
                <div className="flex justify-between font-semibold text-lg border-t pt-3">
                  <span>Total</span>
                  <span>₹{total.toFixed(2)}</span>
                </div>
              </div>

              <button className="w-full bg-orange-500 text-white py-3 rounded-lg font-semibold hover:bg-orange-600 transition-colors mb-4">
                PROCEED TO CHECKOUT
              </button>

              {/* Coupon Section */}
              <div className="border-t pt-4">
                <div className="flex items-center space-x-2 mb-3">
                  <Tag className="w-4 h-4 text-gray-600" />
                  <span className="text-sm font-medium">Coupon</span>
                </div>
                <div className="flex space-x-2">
                  <input
                    type="text"
                    value={couponCode}
                    onChange={(e) => setCouponCode(e.target.value)}
                    placeholder="Coupon code"
                    className="flex-1 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                  />
                  <button className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    Apply coupon
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Bulk Purchase Modal */}
      {showBulkModal && selectedBulkProduct && (
        <BulkPurchaseModal
          product={selectedBulkProduct}
          onClose={() => setShowBulkModal(false)}
        />
      )}
    </div>
  );
};