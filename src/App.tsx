import React, { useState } from 'react';
import { Header } from './components/Header';
import { ProductSelector } from './components/ProductSelector';
import { CartSidebar } from './components/CartSidebar';
import { CheckoutPage } from './components/CheckoutPage';
import { CartProvider, useCart } from './context/CartContext';

function AppContent() {
  const { activeView, setActiveView } = useCart();
  
  return (
    <div className="min-h-screen bg-gray-50">
      <div className="relative">
        <Header />
        
        {/* Demo Navigation */}
        <nav className="bg-white border-b">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="flex space-x-8">
              <button
                onClick={() => setActiveView('product')}
                className={`py-4 px-2 border-b-2 font-medium text-sm transition-colors ${
                  activeView === 'product'
                    ? 'border-orange-500 text-orange-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700'
                }`}
              >
                Product Page
              </button>
              <button
                onClick={() => setActiveView('checkout')}
                className={`py-4 px-2 border-b-2 font-medium text-sm transition-colors ${
                  activeView === 'checkout'
                    ? 'border-orange-500 text-orange-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700'
                }`}
              >
                Checkout Page
              </button>
            </div>
          </div>
        </nav>

        {/* Main Content */}
        <main className="flex-1">
          {activeView === 'product' ? (
            <div className="py-8">
              <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="text-center mb-8">
                  <h1 className="text-3xl font-bold text-gray-900 mb-2">
                    Product Configuration
                  </h1>
                  <p className="text-gray-600">
                    Select your product attributes and add complementary items
                  </p>
                </div>
                <ProductSelector />
              </div>
            </div>
          ) : (
            <CheckoutPage />
          )}
        </main>

        {/* Cart Sidebar */}
        <CartSidebar />
      </div>
    </div>
  );
}

function App() {
  return (
    <CartProvider>
      <AppContent />
    </CartProvider>
  );
}

export default App