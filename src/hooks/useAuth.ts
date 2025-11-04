import { useState, useEffect, createContext, useContext } from 'react';
import { User } from '../types';

interface AuthContext {
  user: User | null;
  login: (email: string, password: string) => Promise<boolean>;
  register: (userData: Partial<User>, password: string) => Promise<boolean>;
  logout: () => void;
  loading: boolean;
}

const AuthContext = createContext<AuthContext | null>(null);

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
};

export const useAuthProvider = (): AuthContext => {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Check for existing session
    const savedUser = localStorage.getItem('user');
    if (savedUser) {
      setUser(JSON.parse(savedUser));
    }
    
    // Initialize demo accounts if not exists
    const users = JSON.parse(localStorage.getItem('users') || '[]');
    if (users.length === 0) {
      const demoUsers = [
        {
          id: 'admin',
          email: 'admin@mealorder.com',
          name: 'Platform Admin',
          role: 'admin',
          approved: true,
          createdAt: new Date().toISOString(),
          password: 'admin123'
        },
        {
          id: 'customer1',
          email: 'customer@demo.com',
          name: 'Demo Customer',
          role: 'customer',
          approved: true,
          createdAt: new Date().toISOString(),
          password: 'demo123'
        },
        {
          id: 'merchant1',
          email: 'merchant@demo.com',
          name: 'Demo Restaurant Owner',
          role: 'merchant',
          approved: true,
          createdAt: new Date().toISOString(),
          password: 'demo123',
          shopName: 'Spice Garden Restaurant',
          location: 'Main Street, Downtown',
          phone: '+1 234-567-8900',
          isOpen: true,
          acceptingOrders: true,
          ordersReceived: 5,
          orderLimit: 25
        }
      ];
      localStorage.setItem('users', JSON.stringify(demoUsers));
      
      // Initialize demo shop for the merchant
      const demoShop = {
        id: 'merchant1',
        name: 'Spice Garden Restaurant',
        location: 'Main Street, Downtown',
        phone: '+1 234-567-8900',
        isOpen: true,
        acceptingOrders: true,
        ordersReceived: 5,
        orderLimit: 25,
        closingTime: '21:00',
        mealTypes: [
          { id: '1', name: 'Vegetarian Rice', price: 250, available: true, description: 'Fresh vegetables with aromatic basmati rice' },
          { id: '2', name: 'Chicken Rice', price: 350, available: true, description: 'Tender chicken pieces with spiced rice' },
          { id: '3', name: 'Fish Rice', price: 400, available: true, description: 'Fresh fish curry with fragrant rice' },
          { id: '4', name: 'Egg Rice', price: 300, available: true, description: 'Scrambled eggs with seasoned rice' }
        ],
        curries: [
          { id: '1', name: 'Dhal Curry', available: true },
          { id: '2', name: 'Vegetable Curry', available: true },
          { id: '3', name: 'Potato Curry', available: true },
          { id: '4', name: 'Chicken Curry', available: true },
          { id: '5', name: 'Fish Curry', available: true },
          { id: '6', name: 'Coconut Curry', available: false }
        ],
        customizations: [
          { id: '1', name: 'Extra Chicken Piece', price: 100, available: true, type: 'protein' },
          { id: '2', name: 'Extra Fish Piece', price: 150, available: true, type: 'protein' },
          { id: '3', name: 'Extra Curry Portion', price: 50, available: true, type: 'curry' },
          { id: '4', name: 'Extra Rice', price: 30, available: true, type: 'extra' },
          { id: '5', name: 'Spicy Level +1', price: 0, available: true, type: 'extra' }
        ]
      };
      localStorage.setItem('shops', JSON.stringify([demoShop]));
      
      // Add some demo orders
      const demoOrders = [
        {
          id: 'order1',
          orderNumber: 'ORD001234',
          customerId: 'customer1',
          customerName: 'Demo Customer',
          merchantId: 'merchant1',
          merchantName: 'Spice Garden Restaurant',
          items: [{
            mealType: { id: '2', name: 'Chicken Rice', price: 350, available: true },
            curries: [
              { id: '1', name: 'Dhal Curry', available: true },
              { id: '4', name: 'Chicken Curry', available: true },
              { id: '2', name: 'Vegetable Curry', available: true }
            ],
            customizations: [
              { id: '1', name: 'Extra Chicken Piece', price: 100, available: true, type: 'protein' }
            ],
            subtotal: 450
          }],
          total: 450,
          status: 'preparing',
          createdAt: new Date(Date.now() - 30 * 60 * 1000).toISOString() // 30 minutes ago
        }
      ];
      localStorage.setItem('orders', JSON.stringify(demoOrders));
    }
    
    setLoading(false);
  }, []);

  const login = async (email: string, password: string): Promise<boolean> => {
    setLoading(true);
    
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    // Get users from localStorage
    const users = JSON.parse(localStorage.getItem('users') || '[]');
    const foundUser = users.find((u: User & { password: string }) => 
      u.email === email && u.password === password
    );
    
    if (foundUser && (foundUser.role !== 'merchant' || foundUser.approved)) {
      const { password: _, ...userWithoutPassword } = foundUser;
      setUser(userWithoutPassword);
      localStorage.setItem('user', JSON.stringify(userWithoutPassword));
      setLoading(false);
      return true;
    }
    
    setLoading(false);
    return false;
  };

  const register = async (userData: Partial<User>, password: string): Promise<boolean> => {
    setLoading(true);
    
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    const users = JSON.parse(localStorage.getItem('users') || '[]');
    
    // Check if email exists
    if (users.find((u: User) => u.email === userData.email)) {
      setLoading(false);
      return false;
    }
    
    const newUser = {
      ...userData,
      id: Date.now().toString(),
      createdAt: new Date().toISOString(),
      approved: userData.role === 'merchant' ? false : true,
      password
    };
    
    users.push(newUser);
    localStorage.setItem('users', JSON.stringify(users));
    
    // Initialize admin user if first user
    if (users.length === 1) {
      const adminUser = {
        id: 'admin',
        email: 'admin@mealorder.com',
        name: 'Admin',
        role: 'admin',
        approved: true,
        createdAt: new Date().toISOString(),
        password: 'admin123'
      };
      users.push(adminUser);
      localStorage.setItem('users', JSON.stringify(users));
    }
    
    if (userData.role !== 'merchant') {
      const { password: _, ...userWithoutPassword } = newUser;
      setUser(userWithoutPassword);
      localStorage.setItem('user', JSON.stringify(userWithoutPassword));
    }
    
    setLoading(false);
    return true;
  };

  const logout = () => {
    setUser(null);
    localStorage.removeItem('user');
  };

  return {
    user,
    login,
    register,
    logout,
    loading
  };
};

export { AuthContext };