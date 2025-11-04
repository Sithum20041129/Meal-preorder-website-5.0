import React, { useState, useEffect } from 'react';
import { Plus, Edit2, Trash2, DollarSign, ToggleLeft, ToggleRight } from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import { Shop, MealType, Curry, Customization } from '../../types';

export const MenuManagement: React.FC = () => {
  const { user } = useAuth();
  const [shop, setShop] = useState<Shop | null>(null);
  const [editingItem, setEditingItem] = useState<any>(null);
  const [editingType, setEditingType] = useState<'meal' | 'curry' | 'customization' | null>(null);

  useEffect(() => {
    if (!user) return;
    
    const shops = JSON.parse(localStorage.getItem('shops') || '[]');
    const userShop = shops.find((s: Shop) => s.id === user.id);
    setShop(userShop);
  }, [user]);

  const updateShop = (updatedShop: Shop) => {
    const shops = JSON.parse(localStorage.getItem('shops') || '[]');
    const updatedShops = shops.map((s: Shop) => 
      s.id === user?.id ? updatedShop : s
    );
    localStorage.setItem('shops', JSON.stringify(updatedShops));
    setShop(updatedShop);
  };

  const handleEdit = (item: any, type: 'meal' | 'curry' | 'customization') => {
    setEditingItem(item);
    setEditingType(type);
  };

  const handleSave = (updatedItem: any) => {
    if (!shop) return;

    let updatedShop = { ...shop };
    
    if (editingType === 'meal') {
      updatedShop.mealTypes = shop.mealTypes.map(meal => 
        meal.id === updatedItem.id ? updatedItem : meal
      );
    } else if (editingType === 'curry') {
      updatedShop.curries = shop.curries.map(curry => 
        curry.id === updatedItem.id ? updatedItem : curry
      );
    } else if (editingType === 'customization') {
      updatedShop.customizations = shop.customizations.map(custom => 
        custom.id === updatedItem.id ? updatedItem : custom
      );
    }

    updateShop(updatedShop);
    setEditingItem(null);
    setEditingType(null);
  };

  const toggleAvailability = (itemId: string, type: 'meal' | 'curry' | 'customization') => {
    if (!shop) return;

    let updatedShop = { ...shop };
    
    if (type === 'meal') {
      updatedShop.mealTypes = shop.mealTypes.map(meal => 
        meal.id === itemId ? { ...meal, available: !meal.available } : meal
      );
    } else if (type === 'curry') {
      updatedShop.curries = shop.curries.map(curry => 
        curry.id === itemId ? { ...curry, available: !curry.available } : curry
      );
    } else if (type === 'customization') {
      updatedShop.customizations = shop.customizations.map(custom => 
        custom.id === itemId ? { ...custom, available: !custom.available } : custom
      );
    }

    updateShop(updatedShop);
  };

  if (!shop) {
    return <div>Loading...</div>;
  }

  return (
    <div className="space-y-8">
      <h2 className="text-2xl font-bold text-gray-900">Menu Management</h2>

      {/* Meal Types */}
      <div className="bg-white rounded-lg shadow-md p-6">
        <h3 className="text-lg font-medium text-gray-900 mb-4">Meal Types</h3>
        <div className="grid gap-4">
          {shop.mealTypes.map((meal) => (
            <div key={meal.id} className="flex items-center justify-between p-4 border rounded-lg">
              <div className="flex-1">
                <h4 className="font-medium text-gray-900">{meal.name}</h4>
                <p className="text-sm text-gray-600">Rs. {meal.price}</p>
                {meal.description && (
                  <p className="text-sm text-gray-500 mt-1">{meal.description}</p>
                )}
              </div>
              
              <div className="flex items-center space-x-2">
                <button
                  onClick={() => toggleAvailability(meal.id, 'meal')}
                  className="flex items-center"
                >
                  {meal.available ? (
                    <ToggleRight className="h-6 w-6 text-green-500" />
                  ) : (
                    <ToggleLeft className="h-6 w-6 text-gray-400" />
                  )}
                </button>
                
                <button
                  onClick={() => handleEdit(meal, 'meal')}
                  className="p-2 text-gray-600 hover:text-blue-600 transition-colors"
                >
                  <Edit2 className="h-4 w-4" />
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Curries */}
      <div className="bg-white rounded-lg shadow-md p-6">
        <h3 className="text-lg font-medium text-gray-900 mb-4">Available Curries</h3>
        <div className="grid gap-4">
          {shop.curries.map((curry) => (
            <div key={curry.id} className="flex items-center justify-between p-4 border rounded-lg">
              <div className="flex-1">
                <h4 className="font-medium text-gray-900">{curry.name}</h4>
              </div>
              
              <div className="flex items-center space-x-2">
                <button
                  onClick={() => toggleAvailability(curry.id, 'curry')}
                  className="flex items-center"
                >
                  {curry.available ? (
                    <ToggleRight className="h-6 w-6 text-green-500" />
                  ) : (
                    <ToggleLeft className="h-6 w-6 text-gray-400" />
                  )}
                </button>
                
                <button
                  onClick={() => handleEdit(curry, 'curry')}
                  className="p-2 text-gray-600 hover:text-blue-600 transition-colors"
                >
                  <Edit2 className="h-4 w-4" />
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Customizations */}
      <div className="bg-white rounded-lg shadow-md p-6">
        <h3 className="text-lg font-medium text-gray-900 mb-4">Customizations</h3>
        <div className="grid gap-4">
          {shop.customizations.map((custom) => (
            <div key={custom.id} className="flex items-center justify-between p-4 border rounded-lg">
              <div className="flex-1">
                <h4 className="font-medium text-gray-900">{custom.name}</h4>
                <p className="text-sm text-gray-600">Rs. {custom.price}</p>
                <span className="inline-flex px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 capitalize">
                  {custom.type}
                </span>
              </div>
              
              <div className="flex items-center space-x-2">
                <button
                  onClick={() => toggleAvailability(custom.id, 'customization')}
                  className="flex items-center"
                >
                  {custom.available ? (
                    <ToggleRight className="h-6 w-6 text-green-500" />
                  ) : (
                    <ToggleLeft className="h-6 w-6 text-gray-400" />
                  )}
                </button>
                
                <button
                  onClick={() => handleEdit(custom, 'customization')}
                  className="p-2 text-gray-600 hover:text-blue-600 transition-colors"
                >
                  <Edit2 className="h-4 w-4" />
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Edit Modal */}
      {editingItem && (
        <EditItemModal
          item={editingItem}
          type={editingType!}
          onSave={handleSave}
          onCancel={() => {
            setEditingItem(null);
            setEditingType(null);
          }}
        />
      )}
    </div>
  );
};

interface EditItemModalProps {
  item: any;
  type: 'meal' | 'curry' | 'customization';
  onSave: (item: any) => void;
  onCancel: () => void;
}

const EditItemModal: React.FC<EditItemModalProps> = ({ item, type, onSave, onCancel }) => {
  const [formData, setFormData] = useState(item);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSave(formData);
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 className="text-lg font-medium text-gray-900 mb-4">
          Edit {type === 'meal' ? 'Meal' : type === 'curry' ? 'Curry' : 'Customization'}
        </h3>
        
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Name
            </label>
            <input
              type="text"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
              required
            />
          </div>

          {(type === 'meal' || type === 'customization') && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Price (Rs.)
              </label>
              <input
                type="number"
                value={formData.price}
                onChange={(e) => setFormData({ ...formData, price: parseInt(e.target.value) })}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                required
              />
            </div>
          )}

          {type === 'meal' && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Description (Optional)
              </label>
              <textarea
                value={formData.description || ''}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                rows={3}
              />
            </div>
          )}

          {type === 'customization' && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Type
              </label>
              <select
                value={formData.type}
                onChange={(e) => setFormData({ ...formData, type: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
              >
                <option value="protein">Protein</option>
                <option value="curry">Curry</option>
                <option value="extra">Extra</option>
              </select>
            </div>
          )}

          <div className="flex space-x-3 pt-4">
            <button
              type="submit"
              className="flex-1 px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600 transition-colors"
            >
              Save Changes
            </button>
            <button
              type="button"
              onClick={onCancel}
              className="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};