import React from 'react';
import { X, Plus, Minus, ShoppingCart } from 'lucide-react';
import { useCart } from '../context/CartContext';
import { recommendedProducts } from '../data/csvData';

export const CartSidebar: React.FC = () => {
  const { 
    cartItems, 
    isCartOpen, 
    closeCart, 
    addToCart, 
    updateQuantity, 
    getCartTotal,
    goToCheckout,
    goToCart
  } = useCart();

  return (
    <>
      {/* Backdrop */}
      {isCartOpen && (
        <div 
          className="fixed inset-0 bg-black bg-opacity-50 z-40 transition-opacity"
          onClick={closeCart}
        />
      )}

      {/* Sidebar */}
      <div className={`fixed right-0 top-0 h-full w-96 bg-white shadow-xl z-50 transform transition-transform duration-300 ease-in-out ${
        isCartOpen ? 'translate-x-0' : 'translate-x-full'
      }`}>
        <div className="flex flex-col h-full">
          {/* Header */}
          <div className="flex items-center justify-between p-4 border-b">
            <h2 className="text-lg font-semibold">CART</h2>
            <button
              onClick={closeCart}
              className="p-2 hover:bg-gray-100 rounded-full transition-colors"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* Cart Items */}
          <div className="flex-1 overflow-y-auto p-4">
            {cartItems.length === 0 ? (
              <div className="text-center py-8">
                <ShoppingCart className="w-12 h-12 text-gray-400 mx-auto mb-2" />
                <p className="text-gray-500">Your cart is empty</p>
              </div>
            ) : (
              <div className="space-y-4">
                {cartItems.map((item, index) => (
                  <div key={`${item.id}-${index}`} className="flex items-center space-x-3 p-3 border rounded-lg">
                    <img 
                      src={item.image} 
                      alt={item.name}
                      className="w-12 h-12 object-cover rounded"
                    />
                    <div className="flex-1">
                      <h4 className="font-medium text-sm">{item.name}</h4>
                      {item.selectedAttributes.brand && (
                        <p className="text-xs text-gray-600">
                          Select Your Brand: {item.selectedAttributes.brand}
                        </p>
                      )}
                      {item.selectedAttributes.model && (
                        <p className="text-xs text-gray-600">
                          Select Your Model: {item.selectedAttributes.model}
                        </p>
                      )}
                      <div className="flex items-center justify-between mt-2">
                        <div className="flex items-center space-x-2">
                          <button
                            onClick={() => updateQuantity(item.id, item.selectedAttributes, item.quantity - 1)}
                            className="p-1 hover:bg-gray-100 rounded"
                          >
                            <Minus className="w-4 h-4" />
                          </button>
                          <span className="text-sm font-medium">{item.quantity}</span>
                          <button
                            onClick={() => updateQuantity(item.id, item.selectedAttributes, item.quantity + 1)}
                            className="p-1 hover:bg-gray-100 rounded"
                          >
                            <Plus className="w-4 h-4" />
                          </button>
                        </div>
                        <span className="font-semibold">₹{item.basePrice.toFixed(2)}</span>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}

            {/* Recommended Products */}
            {cartItems.length > 0 && (
              <div className="mt-6">
                <h3 className="text-sm font-medium text-gray-700 mb-3">
                  You may be interested in...
                </h3>
                <div className="space-y-3">
                  {recommendedProducts.map(product => (
                    <div key={product.id} className="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 transition-colors">
                      <div className="flex items-center space-x-3">
                        <img 
                          src={product.image} 
                          alt={product.name}
                          className="w-10 h-10 object-cover rounded"
                        />
                        <div className="flex-1">
                          <h4 className="text-sm font-medium">{product.name}</h4>
                          <div className="flex items-center space-x-2">
                            {product.originalPrice && (
                              <span className="text-xs text-gray-500 line-through">
                                ₹{product.originalPrice.toFixed(2)}
                              </span>
                            )}
                            <span className="text-sm font-bold">
                              ₹{product.basePrice.toFixed(2)}
                            </span>
                          </div>
                          {product.stockStatus && (
                            <span className="text-xs text-orange-500 font-medium">
                              {product.stockStatus}
                            </span>
                          )}
                        </div>
                      </div>
                      <button
                        onClick={() => addToCart(product, {})}
                        className="bg-black text-white px-3 py-1 rounded text-sm hover:bg-gray-800 transition-colors"
                      >
                        + ADD
                      </button>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* Footer */}
          {cartItems.length > 0 && (
            <div className="p-4 border-t space-y-3">
              <div className="flex justify-between items-center">
                <span className="font-semibold">Subtotal:</span>
                <span className="font-bold text-lg">₹{getCartTotal().toFixed(2)}</span>
              </div>
              <button 
                onClick={goToCheckout}
                className="w-full bg-orange-500 text-white py-3 rounded-lg font-semibold hover:bg-orange-600 transition-colors"
              >
                CHECKOUT
              </button>
            </div>
          )}
        </div>
      </div>
    </>
  );
};