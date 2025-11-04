import React, { useState, useEffect } from 'react';
import { Store, Clock, Users, DollarSign, ToggleLeft, ToggleRight, Settings } from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import { Shop, Order } from '../../types';

export const MerchantDashboard: React.FC = () => {
  const { user } = useAuth();
  const [shop, setShop] = useState<Shop | null>(null);
  const [orders, setOrders] = useState<Order[]>([]);
  const [stats, setStats] = useState({
    todayOrders: 0,
    todayRevenue: 0,
    pendingOrders: 0
  });

  useEffect(() => {
    if (!user) return;
    
    // Load shop data
    const shops = JSON.parse(localStorage.getItem('shops') || '[]');
    const userShop = shops.find((s: Shop) => s.id === user.id);
    setShop(userShop);
    
    // Load orders
    const allOrders = JSON.parse(localStorage.getItem('orders') || '[]');
    const shopOrders = allOrders.filter((order: Order) => order.merchantId === user.id);
    setOrders(shopOrders);
    
    // Calculate stats
    const today = new Date().toDateString();
    const todayOrders = shopOrders.filter((order: Order) => 
      new Date(order.createdAt).toDateString() === today
    );
    
    setStats({
      todayOrders: todayOrders.length,
      todayRevenue: todayOrders.reduce((sum: number, order: Order) => sum + order.total, 0),
      pendingOrders: shopOrders.filter((order: Order) => order.status === 'pending').length
    });
  }, [user]);

  const updateShopStatus = (updates: Partial<Shop>) => {
    if (!shop || !user) return;
    
    const shops = JSON.parse(localStorage.getItem('shops') || '[]');
    const updatedShops = shops.map((s: Shop) => 
      s.id === user.id ? { ...s, ...updates } : s
    );
    localStorage.setItem('shops', JSON.stringify(updatedShops));
    setShop({ ...shop, ...updates });
  };

  if (!shop) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-center">
          <Store className="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-600">Loading shop data...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-white rounded-lg shadow-md p-6">
        <h2 className="text-2xl font-bold text-gray-900 mb-2">{shop.name}</h2>
        <p className="text-gray-600">{shop.location}</p>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-white rounded-lg shadow-md p-6">
          <div className="flex items-center">
            <Users className="h-8 w-8 text-blue-500" />
            <div className="ml-3">
              <p className="text-sm font-medium text-gray-600">Today's Orders</p>
              <p className="text-2xl font-bold text-gray-900">{stats.todayOrders}</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-md p-6">
          <div className="flex items-center">
            <DollarSign className="h-8 w-8 text-green-500" />
            <div className="ml-3">
              <p className="text-sm font-medium text-gray-600">Today's Revenue</p>
              <p className="text-2xl font-bold text-gray-900">Rs. {stats.todayRevenue}</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-md p-6">
          <div className="flex items-center">
            <Clock className="h-8 w-8 text-orange-500" />
            <div className="ml-3">
              <p className="text-sm font-medium text-gray-600">Pending Orders</p>
              <p className="text-2xl font-bold text-gray-900">{stats.pendingOrders}</p>
            </div>
          </div>
        </div>
      </div>

      {/* Shop Controls */}
      <div className="bg-white rounded-lg shadow-md p-6">
        <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
          <Settings className="h-5 w-5 mr-2" />
          Shop Controls
        </h3>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="font-medium text-gray-900">Shop Status</p>
              <p className="text-sm text-gray-600">
                {shop.isOpen ? 'Shop is currently open' : 'Shop is currently closed'}
              </p>
            </div>
            <button
              onClick={() => updateShopStatus({ isOpen: !shop.isOpen })}
              className="flex items-center text-lg"
            >
              {shop.isOpen ? (
                <ToggleRight className="h-8 w-8 text-green-500" />
              ) : (
                <ToggleLeft className="h-8 w-8 text-gray-400" />
              )}
            </button>
          </div>

          <div className="flex items-center justify-between">
            <div>
              <p className="font-medium text-gray-900">Accept Orders</p>
              <p className="text-sm text-gray-600">
                {shop.acceptingOrders ? 'Accepting online orders' : 'Not accepting orders'}
              </p>
            </div>
            <button
              onClick={() => updateShopStatus({ acceptingOrders: !shop.acceptingOrders })}
              className="flex items-center text-lg"
            >
              {shop.acceptingOrders ? (
                <ToggleRight className="h-8 w-8 text-green-500" />
              ) : (
                <ToggleLeft className="h-8 w-8 text-gray-400" />
              )}
            </button>
          </div>
        </div>

        <div className="mt-6">
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Daily Order Limit: {shop.ordersReceived} / {shop.orderLimit}
          </label>
          <input
            type="number"
            value={shop.orderLimit}
            onChange={(e) => updateShopStatus({ orderLimit: parseInt(e.target.value) || 0 })}
            className="w-32 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            min="0"
          />
          <div className="mt-2 w-full bg-gray-200 rounded-full h-2">
            <div 
              className="bg-orange-500 h-2 rounded-full transition-all"
              style={{ width: `${Math.min((shop.ordersReceived / shop.orderLimit) * 100, 100)}%` }}
            ></div>
          </div>
        </div>

        <div className="mt-6">
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Closing Time (Optional)
          </label>
          <input
            type="time"
            value={shop.closingTime || ''}
            onChange={(e) => updateShopStatus({ closingTime: e.target.value })}
            className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
          />
        </div>
      </div>

      {/* Recent Orders */}
      <div className="bg-white rounded-lg shadow-md p-6">
        <h3 className="text-lg font-medium text-gray-900 mb-4">Recent Orders</h3>
        
        {orders.length === 0 ? (
          <p className="text-gray-600 text-center py-8">No orders yet today</p>
        ) : (
          <div className="space-y-4">
            {orders.slice(0, 5).map((order) => (
              <div key={order.id} className="flex items-center justify-between p-4 border rounded-lg">
                <div>
                  <p className="font-medium text-gray-900">#{order.orderNumber}</p>
                  <p className="text-sm text-gray-600">{order.customerName}</p>
                  <p className="text-sm text-gray-500">
                    {new Date(order.createdAt).toLocaleTimeString()}
                  </p>
                </div>
                <div className="text-right">
                  <p className="font-medium text-gray-900">Rs. {order.total}</p>
                  <span className={`inline-flex px-2 py-1 text-xs rounded-full ${
                    order.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                    order.status === 'preparing' ? 'bg-blue-100 text-blue-800' :
                    order.status === 'ready' ? 'bg-green-100 text-green-800' :
                    'bg-gray-100 text-gray-800'
                  }`}>
                    {order.status}
                  </span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};