import React, { useState, useEffect } from 'react';
import { Clock, Check, X, Eye, User } from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import { Order } from '../../types';

export const OrderManagement: React.FC = () => {
  const { user } = useAuth();
  const [orders, setOrders] = useState<Order[]>([]);
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
  const [filter, setFilter] = useState<'all' | 'pending' | 'preparing' | 'ready' | 'completed'>('all');

  useEffect(() => {
    if (!user) return;
    
    const allOrders = JSON.parse(localStorage.getItem('orders') || '[]');
    const merchantOrders = allOrders.filter((order: Order) => order.merchantId === user.id);
    setOrders(merchantOrders.sort((a: Order, b: Order) => 
      new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
    ));
  }, [user]);

  const updateOrderStatus = (orderId: string, status: Order['status']) => {
    const allOrders = JSON.parse(localStorage.getItem('orders') || '[]');
    const updatedOrders = allOrders.map((order: Order) => 
      order.id === orderId 
        ? { 
            ...order, 
            status,
            ...(status === 'completed' && { completedAt: new Date().toISOString() })
          } 
        : order
    );
    localStorage.setItem('orders', JSON.stringify(updatedOrders));
    
    setOrders(orders.map(order => 
      order.id === orderId 
        ? { 
            ...order, 
            status,
            ...(status === 'completed' && { completedAt: new Date().toISOString() })
          } 
        : order
    ));
  };

  const filteredOrders = orders.filter(order => 
    filter === 'all' || order.status === filter
  );

  const getStatusColor = (status: Order['status']) => {
    switch (status) {
      case 'pending': return 'bg-yellow-100 text-yellow-800';
      case 'preparing': return 'bg-blue-100 text-blue-800';
      case 'ready': return 'bg-green-100 text-green-800';
      case 'completed': return 'bg-gray-100 text-gray-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h2 className="text-2xl font-bold text-gray-900">Order Management</h2>
        
        <div className="flex space-x-2">
          {['all', 'pending', 'preparing', 'ready', 'completed'].map((status) => (
            <button
              key={status}
              onClick={() => setFilter(status as any)}
              className={`px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                filter === status
                  ? 'bg-orange-500 text-white'
                  : 'bg-white text-gray-700 hover:bg-gray-100 border'
              }`}
            >
              {status.charAt(0).toUpperCase() + status.slice(1)}
              {status !== 'all' && (
                <span className="ml-2 bg-orange-100 text-orange-800 px-2 py-1 rounded-full text-xs">
                  {orders.filter(o => o.status === status).length}
                </span>
              )}
            </button>
          ))}
        </div>
      </div>

      {filteredOrders.length === 0 ? (
        <div className="bg-white rounded-lg shadow-md p-8 text-center">
          <Clock className="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">No Orders Found</h3>
          <p className="text-gray-600">
            {filter === 'all' ? 'No orders have been placed yet.' : `No ${filter} orders found.`}
          </p>
        </div>
      ) : (
        <div className="grid gap-4">
          {filteredOrders.map((order) => (
            <div key={order.id} className="bg-white rounded-lg shadow-md p-6 border-l-4 border-orange-500">
              <div className="flex justify-between items-start">
                <div className="flex-1">
                  <div className="flex items-center space-x-3 mb-2">
                    <h3 className="text-lg font-medium text-gray-900">
                      #{order.orderNumber}
                    </h3>
                    <span className={`inline-flex px-3 py-1 text-sm rounded-full ${getStatusColor(order.status)}`}>
                      {order.status}
                    </span>
                  </div>
                  
                  <div className="flex items-center text-sm text-gray-600 mb-2">
                    <User className="h-4 w-4 mr-1" />
                    {order.customerName}
                  </div>
                  
                  <div className="text-sm text-gray-600 mb-3">
                    <p>Ordered: {new Date(order.createdAt).toLocaleString()}</p>
                    {order.completedAt && (
                      <p>Completed: {new Date(order.completedAt).toLocaleString()}</p>
                    )}
                  </div>
                  
                  <div className="space-y-2">
                    {order.items.map((item, index) => (
                      <div key={index} className="text-sm">
                        <p className="font-medium text-gray-900">{item.mealType.name}</p>
                        <p className="text-gray-600">
                          Curries: {item.curries.map(c => c.name).join(', ')}
                        </p>
                        {item.customizations.length > 0 && (
                          <p className="text-gray-600">
                            Extras: {item.customizations.map(c => c.name).join(', ')}
                          </p>
                        )}
                      </div>
                    ))}
                  </div>
                  
                  <p className="text-lg font-bold text-gray-900 mt-3">
                    Total: Rs. {order.total}
                  </p>
                </div>
                
                <div className="flex flex-col space-y-2 ml-4">
                  <button
                    onClick={() => setSelectedOrder(order)}
                    className="flex items-center px-3 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors"
                  >
                    <Eye className="h-4 w-4 mr-1" />
                    View
                  </button>
                  
                  {order.status === 'pending' && (
                    <button
                      onClick={() => updateOrderStatus(order.id, 'preparing')}
                      className="flex items-center px-3 py-2 text-sm bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors"
                    >
                      <Clock className="h-4 w-4 mr-1" />
                      Start
                    </button>
                  )}
                  
                  {order.status === 'preparing' && (
                    <button
                      onClick={() => updateOrderStatus(order.id, 'ready')}
                      className="flex items-center px-3 py-2 text-sm bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors"
                    >
                      <Check className="h-4 w-4 mr-1" />
                      Ready
                    </button>
                  )}
                  
                  {order.status === 'ready' && (
                    <button
                      onClick={() => updateOrderStatus(order.id, 'completed')}
                      className="flex items-center px-3 py-2 text-sm bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors"
                    >
                      <Check className="h-4 w-4 mr-1" />
                      Complete
                    </button>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Order Detail Modal */}
      {selectedOrder && (
        <OrderDetailModal
          order={selectedOrder}
          onClose={() => setSelectedOrder(null)}
        />
      )}
    </div>
  );
};

interface OrderDetailModalProps {
  order: Order;
  onClose: () => void;
}

const OrderDetailModal: React.FC<OrderDetailModalProps> = ({ order, onClose }) => {
  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg p-6 w-full max-w-lg max-h-[80vh] overflow-y-auto">
        <div className="flex justify-between items-center mb-4">
          <h3 className="text-lg font-medium text-gray-900">Order Details</h3>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600"
          >
            <X className="h-6 w-6" />
          </button>
        </div>
        
        <div className="space-y-4">
          <div className="bg-gray-50 p-4 rounded-lg">
            <h4 className="font-medium text-gray-900">Order Information</h4>
            <p className="text-sm text-gray-600">Order Number: #{order.orderNumber}</p>
            <p className="text-sm text-gray-600">Customer: {order.customerName}</p>
            <p className="text-sm text-gray-600">
              Status: <span className="capitalize">{order.status}</span>
            </p>
            <p className="text-sm text-gray-600">
              Ordered: {new Date(order.createdAt).toLocaleString()}
            </p>
            {order.completedAt && (
              <p className="text-sm text-gray-600">
                Completed: {new Date(order.completedAt).toLocaleString()}
              </p>
            )}
          </div>
          
          <div>
            <h4 className="font-medium text-gray-900 mb-2">Order Items</h4>
            {order.items.map((item, index) => (
              <div key={index} className="border rounded-lg p-4 mb-4">
                <h5 className="font-medium text-gray-900">{item.mealType.name}</h5>
                <p className="text-sm text-gray-600 mb-2">Rs. {item.mealType.price}</p>
                
                <div className="space-y-1">
                  <p className="text-sm font-medium text-gray-700">Selected Curries:</p>
                  <ul className="text-sm text-gray-600 ml-4">
                    {item.curries.map((curry, curryIndex) => (
                      <li key={curryIndex}>• {curry.name}</li>
                    ))}
                  </ul>
                </div>
                
                {item.customizations.length > 0 && (
                  <div className="space-y-1 mt-2">
                    <p className="text-sm font-medium text-gray-700">Customizations:</p>
                    <ul className="text-sm text-gray-600 ml-4">
                      {item.customizations.map((custom, customIndex) => (
                        <li key={customIndex}>
                          • {custom.name} (+Rs. {custom.price})
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
                
                <p className="text-sm font-medium text-gray-900 mt-2">
                  Subtotal: Rs. {item.subtotal}
                </p>
              </div>
            ))}
          </div>
          
          <div className="border-t pt-4">
            <p className="text-lg font-bold text-gray-900">
              Total Amount: Rs. {order.total}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};