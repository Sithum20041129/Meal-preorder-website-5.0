import React, { useState, useEffect } from 'react';
import { Clock, CheckCircle, Eye, X } from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import { Order } from '../../types';

export const CustomerOrders: React.FC = () => {
  const { user } = useAuth();
  const [orders, setOrders] = useState<Order[]>([]);
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);

  useEffect(() => {
    if (!user) return;
    
    const allOrders = JSON.parse(localStorage.getItem('orders') || '[]');
    const userOrders = allOrders.filter((order: Order) => order.customerId === user.id);
    setOrders(userOrders.sort((a: Order, b: Order) => 
      new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
    ));
  }, [user]);

  const getStatusColor = (status: Order['status']) => {
    switch (status) {
      case 'pending': return 'bg-yellow-100 text-yellow-800';
      case 'preparing': return 'bg-blue-100 text-blue-800';
      case 'ready': return 'bg-green-100 text-green-800';
      case 'completed': return 'bg-gray-100 text-gray-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusMessage = (status: Order['status']) => {
    switch (status) {
      case 'pending': return 'Your order has been placed and is waiting to be prepared.';
      case 'preparing': return 'Your order is being prepared by the restaurant.';
      case 'ready': return 'Your order is ready for pickup!';
      case 'completed': return 'Order completed. Thank you for your order!';
      default: return '';
    }
  };

  return (
    <div className="space-y-6">
      <h2 className="text-2xl font-bold text-gray-900">My Orders</h2>
      
      {orders.length === 0 ? (
        <div className="bg-white rounded-lg shadow-md p-8 text-center">
          <Clock className="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">No Orders Yet</h3>
          <p className="text-gray-600">You haven't placed any orders yet. Browse restaurants to get started!</p>
        </div>
      ) : (
        <div className="space-y-4">
          {orders.map((order) => (
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
                  
                  <p className="text-gray-600 mb-2">{order.merchantName}</p>
                  
                  <div className="text-sm text-gray-600 mb-3">
                    <p>Ordered: {new Date(order.createdAt).toLocaleString()}</p>
                    {order.completedAt && (
                      <p>Completed: {new Date(order.completedAt).toLocaleString()}</p>
                    )}
                  </div>
                  
                  <div className="bg-gray-50 p-3 rounded-lg mb-3">
                    <p className="text-sm text-gray-700">{getStatusMessage(order.status)}</p>
                  </div>
                  
                  <div className="space-y-1">
                    {order.items.map((item, index) => (
                      <div key={index} className="text-sm">
                        <span className="font-medium text-gray-900">{item.mealType.name}</span>
                        {item.customizations.length > 0 && (
                          <span className="text-gray-600 ml-2">
                            (+{item.customizations.length} extras)
                          </span>
                        )}
                      </div>
                    ))}
                  </div>
                  
                  <p className="text-lg font-bold text-gray-900 mt-2">
                    Total: Rs. {order.total}
                  </p>
                </div>
                
                <button
                  onClick={() => setSelectedOrder(order)}
                  className="flex items-center px-3 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors"
                >
                  <Eye className="h-4 w-4 mr-1" />
                  View Details
                </button>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Order Detail Modal */}
      {selectedOrder && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 w-full max-w-lg max-h-[80vh] overflow-y-auto">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-lg font-medium text-gray-900">Order Receipt</h3>
              <button
                onClick={() => setSelectedOrder(null)}
                className="text-gray-400 hover:text-gray-600"
              >
                <X className="h-6 w-6" />
              </button>
            </div>
            
            <div className="space-y-4">
              <div className="bg-gray-50 p-4 rounded-lg">
                <h4 className="font-medium text-gray-900 mb-2">Order Information</h4>
                <p className="text-sm text-gray-600">Order Number: #{selectedOrder.orderNumber}</p>
                <p className="text-sm text-gray-600">Restaurant: {selectedOrder.merchantName}</p>
                <p className="text-sm text-gray-600">
                  Status: <span className="capitalize">{selectedOrder.status}</span>
                </p>
                <p className="text-sm text-gray-600">
                  Ordered: {new Date(selectedOrder.createdAt).toLocaleString()}
                </p>
                {selectedOrder.completedAt && (
                  <p className="text-sm text-gray-600">
                    Completed: {new Date(selectedOrder.completedAt).toLocaleString()}
                  </p>
                )}
              </div>
              
              <div>
                <h4 className="font-medium text-gray-900 mb-2">Order Details</h4>
                {selectedOrder.items.map((item, index) => (
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
                        <p className="text-sm font-medium text-gray-700">Extras:</p>
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
                  Total Amount: Rs. {selectedOrder.total}
                </p>
              </div>
              
              <div className="bg-blue-50 p-3 rounded-lg">
                <p className="text-sm text-blue-700">
                  {getStatusMessage(selectedOrder.status)}
                </p>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};