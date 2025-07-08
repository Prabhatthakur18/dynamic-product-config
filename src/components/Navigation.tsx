import React, { useState } from 'react';

export const Navigation: React.FC = () => {
  const [activeView, setActiveView] = useState('product');

  return (
    <nav className="bg-gray-100 border-b">
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
  );
};