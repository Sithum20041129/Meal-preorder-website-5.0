import React, { useState, useEffect } from 'react';
import { Check, X, Store, MapPin, Phone, Mail } from 'lucide-react';
import { User } from '../../types';

export const MerchantApprovals: React.FC = () => {
  const [pendingMerchants, setPendingMerchants] = useState<(User & { password?: string, shopName?: string, location?: string, phone?: string })[]>([]);

  useEffect(() => {
    const users = JSON.parse(localStorage.getItem('users') || '[]');
    const pending = users.filter((user: User & { approved?: boolean }) => 
      user.role === 'merchant' && !user.approved
    );
    setPendingMerchants(pending);
  }, []);

  const handleApproval = (merchantId: string, approved: boolean) => {
    const users = JSON.parse(localStorage.getItem('users') || '[]');
    const updatedUsers = users.map((user: User & { approved?: boolean }) => 
      user.id === merchantId ? { ...user, approved } : user
    );
    localStorage.setItem('users', JSON.stringify(updatedUsers));
    
    // Update local state
    setPendingMerchants(prev => prev.filter(m => m.id !== merchantId));
    
    // Initialize merchant data if approved
    if (approved) {
      const merchant = users.find((user: User) => user.id === merchantId);
      if (merchant) {
        const shops = JSON.parse(localStorage.getItem('shops') || '[]');
        const newShop = {
          id: merchantId,
          name: merchant.shopName || merchant.name + "'s Shop",
          location: merchant.location || 'Location not set',
          phone: merchant.phone || '',
          isOpen: false,
          acceptingOrders: true,
          ordersReceived: 0,
          orderLimit: 20,
          mealTypes: [
            { id: '1', name: 'Vegetarian Rice', price: 250, available: true },
            { id: '2', name: 'Chicken Rice', price: 350, available: true },
            { id: '3', name: 'Fish Rice', price: 400, available: true },
            { id: '4', name: 'Egg Rice', price: 300, available: true }
          ],
          curries: [
            { id: '1', name: 'Dhal Curry', available: true },
            { id: '2', name: 'Vegetable Curry', available: true },
            { id: '3', name: 'Potato Curry', available: true },
            { id: '4', name: 'Chicken Curry', available: true },
            { id: '5', name: 'Fish Curry', available: true }
          ],
          customizations: [
            { id: '1', name: 'Extra Chicken Piece', price: 100, available: true, type: 'protein' },
            { id: '2', name: 'Extra Fish Piece', price: 150, available: true, type: 'protein' },
            { id: '3', name: 'Extra Curry', price: 50, available: true, type: 'curry' },
            { id: '4', name: 'Extra Rice', price: 30, available: true, type: 'extra' }
          ]
        };
        shops.push(newShop);
        localStorage.setItem('shops', JSON.stringify(shops));
      }
    }
  };

  if (pendingMerchants.length === 0) {
    return (
      <div className="bg-white rounded-lg shadow-md p-8 text-center">
        <Store className="h-12 w-12 text-gray-400 mx-auto mb-4" />
        <h3 className="text-lg font-medium text-gray-900 mb-2">No Pending Approvals</h3>
        <p className="text-gray-600">All merchant applications have been processed.</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <h2 className="text-2xl font-bold text-gray-900">Merchant Approvals</h2>
      
      <div className="grid gap-6">
        {pendingMerchants.map((merchant) => (
          <div key={merchant.id} className="bg-white rounded-lg shadow-md p-6 border-l-4 border-orange-500">
            <div className="flex justify-between items-start">
              <div className="flex-1">
                <h3 className="text-lg font-medium text-gray-900 mb-2">
                  {merchant.shopName || 'Shop Name Not Provided'}
                </h3>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                  <div className="flex items-center">
                    <Mail className="h-4 w-4 mr-2" />
                    {merchant.email}
                  </div>
                  
                  <div className="flex items-center">
                    <Store className="h-4 w-4 mr-2" />
                    Owner: {merchant.name}
                  </div>
                  
                  {merchant.location && (
                    <div className="flex items-center">
                      <MapPin className="h-4 w-4 mr-2" />
                      {merchant.location}
                    </div>
                  )}
                  
                  {merchant.phone && (
                    <div className="flex items-center">
                      <Phone className="h-4 w-4 mr-2" />
                      {merchant.phone}
                    </div>
                  )}
                </div>
                
                <div className="mt-3 text-xs text-gray-500">
                  Applied: {new Date(merchant.createdAt).toLocaleDateString()}
                </div>
              </div>
              
              <div className="flex space-x-2 ml-4">
                <button
                  onClick={() => handleApproval(merchant.id, true)}
                  className="flex items-center px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors"
                >
                  <Check className="h-4 w-4 mr-1" />
                  Approve
                </button>
                
                <button
                  onClick={() => handleApproval(merchant.id, false)}
                  className="flex items-center px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors"
                >
                  <X className="h-4 w-4 mr-1" />
                  Reject
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};