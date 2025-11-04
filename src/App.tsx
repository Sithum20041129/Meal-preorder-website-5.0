import React, { useState } from 'react';
import { AuthContext, useAuthProvider } from './hooks/useAuth';
import { Layout } from './components/Layout';
import { LoginForm } from './components/auth/LoginForm';
import { RegisterForm } from './components/auth/RegisterForm';
import { MerchantApprovals } from './components/admin/MerchantApprovals';
import { MerchantDashboard } from './components/merchant/MerchantDashboard';
import { MenuManagement } from './components/merchant/MenuManagement';
import { OrderManagement } from './components/merchant/OrderManagement';
import { ShopBrowser } from './components/customer/ShopBrowser';
import { CustomerOrders } from './components/customer/CustomerOrders';

function App() {
  const authContext = useAuthProvider();
  const [showRegister, setShowRegister] = useState(false);
  const [activeTab, setActiveTab] = useState('');

  const { user, loading } = authContext;

  // Set default tab based on user role
  React.useEffect(() => {
    if (user && !activeTab) {
      switch (user.role) {
        case 'admin':
          setActiveTab('merchants');
          break;
        case 'merchant':
          setActiveTab('dashboard');
          break;
        case 'customer':
          setActiveTab('shops');
          break;
      }
    }
  }, [user, activeTab]);

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-100 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-orange-500 mx-auto mb-4"></div>
          <p className="text-gray-600">Loading...</p>
        </div>
      </div>
    );
  }

  const renderContent = () => {
    if (!user) {
      return showRegister ? (
        <RegisterForm onSwitchToLogin={() => setShowRegister(false)} />
      ) : (
        <LoginForm onSwitchToRegister={() => setShowRegister(true)} />
      );
    }

    switch (user.role) {
      case 'admin':
        switch (activeTab) {
          case 'merchants':
            return <MerchantApprovals />;
          case 'overview':
            return (
              <div className="bg-white rounded-lg shadow-md p-8 text-center">
                <h2 className="text-xl font-bold text-gray-900">Platform Overview</h2>
                <p className="text-gray-600 mt-2">Platform statistics and overview coming soon.</p>
              </div>
            );
          default:
            return <MerchantApprovals />;
        }
      
      case 'merchant':
        switch (activeTab) {
          case 'dashboard':
            return <MerchantDashboard />;
          case 'menu':
            return <MenuManagement />;
          case 'orders':
            return <OrderManagement />;
          default:
            return <MerchantDashboard />;
        }
      
      case 'customer':
        switch (activeTab) {
          case 'shops':
            return <ShopBrowser />;
          case 'orders':
            return <CustomerOrders />;
          default:
            return <ShopBrowser />;
        }
      
      default:
        return <div>Invalid user role</div>;
    }
  };

  return (
    <AuthContext.Provider value={authContext}>
      <Layout activeTab={activeTab} onTabChange={setActiveTab}>
        {renderContent()}
      </Layout>
    </AuthContext.Provider>
  );
}

export default App;