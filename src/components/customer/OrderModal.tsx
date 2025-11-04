import React, { useState } from 'react';
import { X, Plus, Minus, ShoppingBag } from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import { Shop, MealType, Curry, Customization, Order, OrderItem } from '../../types';

interface OrderModalProps {
  shop: Shop;
  onClose: () => void;
}

export const OrderModal: React.FC<OrderModalProps> = ({ shop, onClose }) => {
  const { user } = useAuth();
  const [selectedMeal, setSelectedMeal] = useState<MealType | null>(null);
  const [selectedCurries, setSelectedCurries] = useState<Curry[]>([]);
  const [customizations, setCustomizations] = useState<{ [key: string]: number }>({});
  const [showCustomize, setShowCustomize] = useState(false);
  const [orderPlaced, setOrderPlaced] = useState(false);
  const [orderNumber, setOrderNumber] = useState('');

  const availableMeals = shop.mealTypes.filter(meal => meal.available);
  const availableCurries = shop.curries.filter(curry => curry.available);
  const availableCustomizations = shop.customizations.filter(custom => custom.available);

  const handleCurryToggle = (curry: Curry) => {
    setSelectedCurries(prev => {
      const existing = prev.find(c => c.id === curry.id);
      if (existing) {
        return prev.filter(c => c.id !== curry.id);
      }
      if (prev.length < 3) {
        return [...prev, curry];
      }
      return prev;
    });
  };

  const handleCustomizationChange = (customId: string, change: number) => {
    setCustomizations(prev => ({
      ...prev,
      [customId]: Math.max(0, (prev[customId] || 0) + change)
    }));
  };

  const calculateTotal = () => {
    if (!selectedMeal) return 0;
    
    let total = selectedMeal.price;
    
    // Add customizations
    Object.entries(customizations).forEach(([customId, quantity]) => {
      if (quantity > 0) {
        const custom = availableCustomizations.find(c => c.id === customId);
        if (custom) {
          total += custom.price * quantity;
        }
      }
    });
    
    return total;
  };

  const placeOrder = () => {
    if (!selectedMeal || selectedCurries.length === 0 || !user) return;
    
    const selectedCustomizations = Object.entries(customizations)
      .filter(([_, quantity]) => quantity > 0)
      .flatMap(([customId, quantity]) => {
        const custom = availableCustomizations.find(c => c.id === customId);
        return custom ? Array(quantity).fill(custom) : [];
      });

    const orderItem: OrderItem = {
      mealType: selectedMeal,
      curries: selectedCurries,
      customizations: selectedCustomizations,
      subtotal: calculateTotal()
    };

    const orderNum = `ORD${Date.now().toString().slice(-6)}`;
    
    const newOrder: Order = {
      id: Date.now().toString(),
      orderNumber: orderNum,
      customerId: user.id,
      customerName: user.name,
      merchantId: shop.id,
      merchantName: shop.name,
      items: [orderItem],
      total: calculateTotal(),
      status: 'pending',
      createdAt: new Date().toISOString()
    };

    // Save order
    const orders = JSON.parse(localStorage.getItem('orders') || '[]');
    orders.push(newOrder);
    localStorage.setItem('orders', JSON.stringify(orders));

    // Update shop order count
    const shops = JSON.parse(localStorage.getItem('shops') || '[]');
    const updatedShops = shops.map((s: Shop) => 
      s.id === shop.id 
        ? { ...s, ordersReceived: s.ordersReceived + 1 }
        : s
    );
    localStorage.setItem('shops', JSON.stringify(updatedShops));

    setOrderNumber(orderNum);
    setOrderPlaced(true);
  };

  if (orderPlaced) {
    return (
      <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div className="bg-white rounded-lg p-8 w-full max-w-md">
          <div className="text-center">
            <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
              <ShoppingBag className="h-6 w-6 text-green-600" />
            </div>
            
            <h3 className="text-lg font-medium text-gray-900 mb-2">Order Placed Successfully!</h3>
            
            <div className="bg-gray-50 rounded-lg p-4 mb-4 text-left">
              <h4 className="font-medium text-gray-900 mb-2">Order Receipt</h4>
              <p className="text-sm text-gray-600">Order Number: #{orderNumber}</p>
              <p className="text-sm text-gray-600">Restaurant: {shop.name}</p>
              
              <div className="mt-3">
                <p className="text-sm font-medium text-gray-900">Order Details:</p>
                <p className="text-sm text-gray-600">• {selectedMeal?.name}</p>
                <p className="text-sm text-gray-600">
                  • Curries: {selectedCurries.map(c => c.name).join(', ')}
                </p>
                {Object.entries(customizations).some(([_, qty]) => qty > 0) && (
                  <div className="text-sm text-gray-600">
                    • Extras: {Object.entries(customizations)
                      .filter(([_, quantity]) => quantity > 0)
                      .map(([customId, quantity]) => {
                        const custom = availableCustomizations.find(c => c.id === customId);
                        return custom ? `${custom.name} (${quantity})` : '';
                      })
                      .join(', ')
                    }
                  </div>
                )}
              </div>
              
              <p className="text-lg font-bold text-gray-900 mt-3 pt-3 border-t">
                Total: Rs. {calculateTotal()}
              </p>
            </div>
            
            <p className="text-sm text-gray-600 mb-4">
              Please save this receipt and present it when collecting your order.
            </p>
            
            <button
              onClick={onClose}
              className="w-full px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600 transition-colors"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex justify-between items-center p-6 border-b">
          <h2 className="text-xl font-bold text-gray-900">Order from {shop.name}</h2>
          <button onClick={onClose} className="text-gray-400 hover:text-gray-600">
            <X className="h-6 w-6" />
          </button>
        </div>

        <div className="p-6 space-y-6">
          {/* Meal Selection */}
          <div>
            <h3 className="text-lg font-medium text-gray-900 mb-3">Choose Your Meal</h3>
            <div className="grid gap-3">
              {availableMeals.map((meal) => (
                <button
                  key={meal.id}
                  onClick={() => setSelectedMeal(meal)}
                  className={`p-4 border rounded-lg text-left transition-colors ${
                    selectedMeal?.id === meal.id
                      ? 'border-orange-500 bg-orange-50'
                      : 'border-gray-300 hover:border-gray-400'
                  }`}
                >
                  <div className="flex justify-between items-center">
                    <div>
                      <h4 className="font-medium text-gray-900">{meal.name}</h4>
                      {meal.description && (
                        <p className="text-sm text-gray-600 mt-1">{meal.description}</p>
                      )}
                    </div>
                    <span className="font-bold text-gray-900">Rs. {meal.price}</span>
                  </div>
                </button>
              ))}
            </div>
          </div>

          {/* Curry Selection */}
          {selectedMeal && (
            <div>
              <h3 className="text-lg font-medium text-gray-900 mb-3">
                Select 3 Curries ({selectedCurries.length}/3)
              </h3>
              <div className="grid gap-2">
                {availableCurries.map((curry) => {
                  const isSelected = selectedCurries.some(c => c.id === curry.id);
                  const canSelect = selectedCurries.length < 3 || isSelected;
                  
                  return (
                    <button
                      key={curry.id}
                      onClick={() => handleCurryToggle(curry)}
                      disabled={!canSelect}
                      className={`p-3 border rounded-lg text-left transition-colors ${
                        isSelected
                          ? 'border-orange-500 bg-orange-50'
                          : canSelect
                          ? 'border-gray-300 hover:border-gray-400'
                          : 'border-gray-200 bg-gray-100 opacity-50 cursor-not-allowed'
                      }`}
                    >
                      <span className="font-medium text-gray-900">{curry.name}</span>
                    </button>
                  );
                })}
              </div>
            </div>
          )}

          {/* Customization */}
          {selectedMeal && selectedCurries.length > 0 && (
            <div>
              <button
                onClick={() => setShowCustomize(!showCustomize)}
                className="w-full p-4 border border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-gray-400 hover:text-gray-900 transition-colors"
              >
                + More Customize Options
              </button>

              {showCustomize && (
                <div className="mt-4 space-y-3">
                  {availableCustomizations.map((custom) => (
                    <div key={custom.id} className="flex items-center justify-between p-3 border rounded-lg">
                      <div className="flex-1">
                        <h4 className="font-medium text-gray-900">{custom.name}</h4>
                        <p className="text-sm text-gray-600">+Rs. {custom.price}</p>
                      </div>
                      
                      <div className="flex items-center space-x-3">
                        <button
                          onClick={() => handleCustomizationChange(custom.id, -1)}
                          className="p-1 rounded-full hover:bg-gray-100"
                          disabled={(customizations[custom.id] || 0) === 0}
                        >
                          <Minus className="h-4 w-4" />
                        </button>
                        
                        <span className="w-8 text-center font-medium">
                          {customizations[custom.id] || 0}
                        </span>
                        
                        <button
                          onClick={() => handleCustomizationChange(custom.id, 1)}
                          className="p-1 rounded-full hover:bg-gray-100"
                        >
                          <Plus className="h-4 w-4" />
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          {/* Order Summary */}
          {selectedMeal && selectedCurries.length > 0 && (
            <div className="border-t pt-6">
              <div className="bg-gray-50 rounded-lg p-4">
                <h4 className="font-medium text-gray-900 mb-2">Order Summary</h4>
                <p className="text-sm text-gray-600">• {selectedMeal.name}</p>
                <p className="text-sm text-gray-600">
                  • Curries: {selectedCurries.map(c => c.name).join(', ')}
                </p>
                {Object.entries(customizations).some(([_, qty]) => qty > 0) && (
                  <p className="text-sm text-gray-600">
                    • Extras: {Object.entries(customizations)
                      .filter(([_, quantity]) => quantity > 0)
                      .map(([customId, quantity]) => {
                        const custom = availableCustomizations.find(c => c.id === customId);
                        return custom ? `${custom.name} (${quantity})` : '';
                      })
                      .join(', ')
                    }
                  </p>
                )}
                
                <div className="flex justify-between items-center mt-3 pt-3 border-t">
                  <span className="text-lg font-bold text-gray-900">Total:</span>
                  <span className="text-lg font-bold text-gray-900">Rs. {calculateTotal()}</span>
                </div>
              </div>
              
              <button
                onClick={placeOrder}
                disabled={selectedCurries.length === 0}
                className="w-full mt-4 px-4 py-3 bg-orange-500 text-white rounded-md hover:bg-orange-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-medium"
              >
                Place Order - Rs. {calculateTotal()}
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};