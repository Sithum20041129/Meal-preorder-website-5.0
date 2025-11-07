export interface User {
  id: string;
  email: string;
  name: string;
  role: 'customer' | 'merchant' | 'admin';
  approved?: boolean;
  createdAt: string;
}

export interface Merchant extends User {
  shopName: string;
  location: string;
  phone: string;
  isOpen: boolean;
  closingTime?: string;
  orderLimit?: number;
  ordersReceived: number;
  acceptingOrders: boolean;
}

export interface MealType {
  id: string;
  name: string;
  price: number;
  available: boolean;
  description?: string;
}

export interface Curry {
  id: string;
  name: string;
  available: boolean;
  price?: number;
}

export interface Customization {
  id: string;
  name: string;
  price: number;
  available: boolean;
  type: 'protein' | 'curry' | 'extra';
}

export interface OrderItem {
  mealType: MealType;
  curries: Curry[];
  customizations: Customization[];
  subtotal: number;
}

export interface Order {
  id: string;
  orderNumber: string;
  customerId: string;
  customerName: string;
  merchantId: string;
  merchantName: string;
  items: OrderItem[];
  total: number;
  status: 'pending' | 'preparing' | 'ready' | 'completed';
  createdAt: string;
  completedAt?: string;
}

export interface Shop {
  id: string;
  name: string;
  location: string;
  phone: string;
  isOpen: boolean;
  closingTime?: string;
  orderLimit?: number;
  ordersReceived: number;
  acceptingOrders: boolean;
  mealTypes: MealType[];
  curries: Curry[];
  customizations: Customization[];
}