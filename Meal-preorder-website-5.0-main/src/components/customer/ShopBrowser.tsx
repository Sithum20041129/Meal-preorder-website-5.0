import React, { useState, useEffect } from 'react';
import { Store, Clock, MapPin, Phone, AlertCircle, Users } from 'lucide-react';
import { Shop } from '../../types';
import { OrderModal } from './OrderModal';

export const ShopBrowser: React.FC = () => {
  const [shops, setShops] = useState<Shop[]>([]);
  const [selectedShop, setSelectedShop] = useState<Shop | null>(null);

  useEffect(() => {
    const savedShops = JSON.parse(localStorage.getItem('shops') || '[]');
    setShops(savedShops);
  }, []);

  const getShopStatus = (shop: Shop) => {
    if (!shop.isOpen) {
      return { text: 'Closed', color: 'text-red-600', bgColor: 'bg-red-100' };
    }
    
    if (!shop.acceptingOrders) {
      return { text: 'Busy Hours', color: 'text-yellow-600', bgColor: 'bg-yellow-100' };
    }
    
    if (shop.orderLimit && shop.ordersReceived >= shop.orderLimit) {
      return { text: 'Order Limit Reached', color: 'text-orange-600', bgColor: 'bg-orange-100' };
    }
    
    return { text: 'Open', color: 'text-green-600', bgColor: 'bg-green-100' };
  };

  const canOrder = (shop: Shop) => {
    return shop.isOpen && shop.acceptingOrders && 
           (!shop.orderLimit || shop.ordersReceived < shop.orderLimit);
  };

  return (
    <div className="space-y-6">
      <h2 className="text-2xl font-bold text-gray-900">Browse Restaurants</h2>
      
      {shops.length === 0 ? (
        <div className="bg-white rounded-lg shadow-md p-8 text-center">
          <Store className="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">No Restaurants Available</h3>
          <p className="text-gray-600">No restaurants have registered yet. Please check back later.</p>
        </div>
      ) : (
        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          {shops.map((shop) => {
            const status = getShopStatus(shop);
            const orderEnabled = canOrder(shop);
            
            return (
              <div key={shop.id} className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <div className="p-6">
                  <div className="flex items-start justify-between mb-3">
                    <h3 className="text-lg font-medium text-gray-900">{shop.name}</h3>
                    <span className={`inline-flex px-2 py-1 text-xs rounded-full ${status.bgColor} ${status.color}`}>
                      {status.text}
                    </span>
                  </div>
                  
                  <div className="space-y-2 text-sm text-gray-600 mb-4">
                    <div className="flex items-center">
                      <MapPin className="h-4 w-4 mr-2" />
                      {shop.location}
                    </div>
                    
                    {shop.phone && (
                      <div className="flex items-center">
                        <Phone className="h-4 w-4 mr-2" />
                        {shop.phone}
                      </div>
                    )}
                    
                    {shop.closingTime && (
                      <div className="flex items-center">
                        <Clock className="h-4 w-4 mr-2" />
                        Closes at {shop.closingTime}
                      </div>
                    )}
                    
                    {shop.orderLimit && (
                      <div className="flex items-center">
                        <Users className="h-4 w-4 mr-2" />
                        Orders: {shop.ordersReceived}/{shop.orderLimit}
                        <div className="ml-2 flex-1 bg-gray-200 rounded-full h-2 max-w-[100px]">
                          <div 
                            className="bg-orange-500 h-2 rounded-full transition-all"
                            style={{ width: `${Math.min((shop.ordersReceived / shop.orderLimit) * 100, 100)}%` }}
                          ></div>
                        </div>
                      </div>
                    )}
                  </div>
                  
                  {!orderEnabled && (
                    <div className="mb-4 p-3 bg-blue-50 rounded-lg">
                      <div className="flex items-start">
                        <AlertCircle className="h-4 w-4 text-blue-500 mr-2 mt-0.5 flex-shrink-0" />
                        <div className="text-sm text-blue-700">
                          {!shop.isOpen && "Restaurant is currently closed."}
                          {shop.isOpen && !shop.acceptingOrders && 
                            "Since the system is new to this restaurant, they're currently in busy hours. Please try again later."
                          }
                          {shop.isOpen && shop.acceptingOrders && shop.orderLimit && shop.ordersReceived >= shop.orderLimit &&
                            `Since the system is new to this restaurant, the number of orders through online is limited to ${shop.orderLimit}. They will increase the limit soon.`
                          }
                        </div>
                      </div>
                    </div>
                  )}
                  
                  <button
                    onClick={() => orderEnabled && setSelectedShop(shop)}
                    disabled={!orderEnabled}
                    className={`w-full px-4 py-2 rounded-md font-medium transition-colors ${
                      orderEnabled
                        ? 'bg-orange-500 text-white hover:bg-orange-600'
                        : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                    }`}
                  >
                    {orderEnabled ? 'Order Now' : 'Currently Unavailable'}
                  </button>
                </div>
              </div>
            );
          })}
        </div>
      )}
      
      {selectedShop && (
        <OrderModal
          shop={selectedShop}
          onClose={() => setSelectedShop(null)}
        />
      )}
    </div>
  );
};