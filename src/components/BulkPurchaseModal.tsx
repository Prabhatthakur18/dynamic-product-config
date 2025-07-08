import React, { useState } from 'react';
import { X, Package, Phone, Mail, User, Building } from 'lucide-react';
import { CartItem, BulkPurchaseRequest } from '../types';

interface BulkPurchaseModalProps {
  product: CartItem;
  onClose: () => void;
}

export const BulkPurchaseModal: React.FC<BulkPurchaseModalProps> = ({ product, onClose }) => {
  const [bulkRequest, setBulkRequest] = useState<BulkPurchaseRequest>({
    productId: product.id,
    quantity: 100,
    attributes: product.selectedAttributes,
    contactInfo: {
      name: '',
      email: '',
      phone: '',
      company: '',
      message: ''
    }
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Handle bulk purchase request submission
    console.log('Bulk purchase request:', bulkRequest);
    alert('Bulk purchase request submitted successfully! We will contact you shortly.');
    onClose();
  };

  const handleInputChange = (field: string, value: string) => {
    setBulkRequest(prev => ({
      ...prev,
      contactInfo: {
        ...prev.contactInfo,
        [field]: value
      }
    }));
  };

  return (
    <>
      {/* Backdrop */}
      <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
          {/* Header */}
          <div className="flex items-center justify-between p-6 border-b">
            <div className="flex items-center space-x-3">
              <Package className="w-6 h-6 text-orange-500" />
              <h2 className="text-xl font-semibold">Bulk Purchase Request</h2>
            </div>
            <button
              onClick={onClose}
              className="p-2 hover:bg-gray-100 rounded-full transition-colors"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* Content */}
          <div className="p-6">
            {/* Product Summary */}
            <div className="bg-gray-50 rounded-lg p-4 mb-6">
              <div className="flex items-center space-x-4">
                <img 
                  src={product.image} 
                  alt={product.name}
                  className="w-16 h-16 object-cover rounded"
                />
                <div className="flex-1">
                  <h3 className="font-medium">{product.name}</h3>
                  {product.selectedAttributes.brand && (
                    <p className="text-sm text-gray-600">Brand: {product.selectedAttributes.brand}</p>
                  )}
                  {product.selectedAttributes.model && (
                    <p className="text-sm text-gray-600">Model: {product.selectedAttributes.model}</p>
                  )}
                  <p className="text-sm font-semibold text-orange-600 mt-1">
                    Unit Price: ₹{product.basePrice.toFixed(2)}
                  </p>
                </div>
              </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Quantity */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Bulk Quantity (Minimum 100 units)
                </label>
                <input
                  type="number"
                  min="100"
                  value={bulkRequest.quantity}
                  onChange={(e) => setBulkRequest(prev => ({ ...prev, quantity: parseInt(e.target.value) }))}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                  required
                />
                <p className="text-sm text-gray-600 mt-1">
                  Estimated Total: ₹{(product.basePrice * bulkRequest.quantity).toFixed(2)}
                </p>
              </div>

              {/* Contact Information */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    <User className="w-4 h-4 inline mr-1" />
                    Full Name *
                  </label>
                  <input
                    type="text"
                    value={bulkRequest.contactInfo.name}
                    onChange={(e) => handleInputChange('name', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    <Mail className="w-4 h-4 inline mr-1" />
                    Email Address *
                  </label>
                  <input
                    type="email"
                    value={bulkRequest.contactInfo.email}
                    onChange={(e) => handleInputChange('email', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    <Phone className="w-4 h-4 inline mr-1" />
                    Phone Number *
                  </label>
                  <input
                    type="tel"
                    value={bulkRequest.contactInfo.phone}
                    onChange={(e) => handleInputChange('phone', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    <Building className="w-4 h-4 inline mr-1" />
                    Company Name
                  </label>
                  <input
                    type="text"
                    value={bulkRequest.contactInfo.company}
                    onChange={(e) => handleInputChange('company', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                  />
                </div>
              </div>

              {/* Message */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Additional Message
                </label>
                <textarea
                  value={bulkRequest.contactInfo.message}
                  onChange={(e) => handleInputChange('message', e.target.value)}
                  rows={4}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                  placeholder="Please provide any additional requirements or questions..."
                />
              </div>

              {/* Info Box */}
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 className="font-medium text-blue-900 mb-2">How it works:</h4>
                <ul className="text-sm text-blue-800 space-y-1">
                  <li>• Submit your bulk purchase request with your requirements</li>
                  <li>• Our team will contact you within 24 hours</li>
                  <li>• We'll provide a custom quote with bulk pricing</li>
                  <li>• Special discounts available for large quantities</li>
                </ul>
              </div>

              {/* Action Buttons */}
              <div className="flex space-x-4">
                <button
                  type="submit"
                  className="flex-1 bg-orange-500 text-white py-3 rounded-lg font-semibold hover:bg-orange-600 transition-colors"
                >
                  Request Callback
                </button>
                <button
                  type="button"
                  onClick={onClose}
                  className="flex-1 bg-gray-100 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-200 transition-colors"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </>
  );
};