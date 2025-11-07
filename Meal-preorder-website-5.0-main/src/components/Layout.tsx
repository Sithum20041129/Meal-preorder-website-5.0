import React, { ReactNode } from 'react';
import { User, ShoppingBag, Users, LogOut, Store } from 'lucide-react';
import { useAuth } from '../hooks/useAuth';

interface LayoutProps {
  children: ReactNode;
  activeTab?: string;
  onTabChange?: (tab: string) => void;
}

export const Layout: React.FC<LayoutProps> = ({ children, activeTab, onTabChange }) => {
  const { user, logout } = useAuth();

  const getNavItems = () => {
    if (!user) return [];
    
    switch (user.role) {
      case 'admin':
        return [
          { id: 'merchants', label: 'Merchant Approvals', icon: Users },
          { id: 'overview', label: 'Platform Overview', icon: Store },
          { id: 'admins', label: 'Admin Management', icon: Users }
        ];
      case 'merchant':
        return [
          { id: 'dashboard', label: 'Dashboard', icon: Store },
          { id: 'menu', label: 'Manage Menu', icon: ShoppingBag },
          { id: 'orders', label: 'Orders', icon: User }
        ];
      case 'customer':
        return [
          { id: 'shops', label: 'Browse Shops', icon: Store },
          { id: 'orders', label: 'My Orders', icon: ShoppingBag }
        ];
      default:
        return [];
    }
  };

  const navItems = getNavItems();

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center">
              <ShoppingBag className="h-8 w-8 text-orange-500" />
              <h1 className="ml-2 text-xl font-bold text-gray-900">MealOrder</h1>
            </div>
            
            {user && (
              <div className="flex items-center space-x-4">
                <span className="text-sm text-gray-600">
                  Welcome, {user.name}
                </span>
                <span className="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 capitalize">
                  {user.role}
                </span>
                <button
                  onClick={logout}
                  className="flex items-center text-gray-600 hover:text-gray-900 transition-colors"
                >
                  <LogOut className="h-4 w-4" />
                </button>
              </div>
            )}
          </div>
        </div>
      </header>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {user && navItems.length > 0 && (
          <nav className="mb-8">
            <div className="flex space-x-1 bg-white p-1 rounded-lg shadow-sm">
              {navItems.map((item) => (
                <button
                  key={item.id}
                  onClick={() => onTabChange?.(item.id)}
                  className={`flex items-center px-4 py-2 rounded-md transition-colors ${
                    activeTab === item.id
                      ? 'bg-orange-500 text-white'
                      : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                  }`}
                >
                  <item.icon className="h-4 w-4 mr-2" />
                  {item.label}
                </button>
              ))}
            </div>
          </nav>
        )}
        
        {children}
      </div>
    </div>
  );
};