# Meal Pre-Order Backend API

A comprehensive Node.js/Express backend for the meal pre-order platform with MongoDB integration.

## 🚀 Features

- **Authentication & Authorization**: JWT-based auth with role-based access control
- **User Management**: Customer, Merchant, and Admin roles with approval system
- **Shop Management**: Complete restaurant/shop management system
- **Order Processing**: Full order lifecycle from placement to completion
- **Real-time Updates**: Order status tracking and notifications
- **Security**: Helmet, rate limiting, input validation, and password hashing
- **Database**: MongoDB with Mongoose ODM

## 📋 Prerequisites

- Node.js (v14 or higher)
- MongoDB (local or cloud instance)
- npm or yarn

## 🛠️ Installation

1. **Clone and setup**:
```bash
cd backend
npm install
```

2. **Environment Configuration**:
```bash
cp .env.example .env
```

Edit `.env` with your configuration:
```env
MONGODB_URI=mongodb://localhost:27017/meal-preorder
JWT_SECRET=your-super-secret-jwt-key-here
PORT=3000
NODE_ENV=development
FRONTEND_URL=http://localhost:5173
```

3. **Database Setup**:
```bash
# Start MongoDB (if running locally)
mongod

# Seed the database with demo data
npm run seed
```

4. **Start the server**:
```bash
# Development mode
npm run dev

# Production mode
npm start
```

## 📚 API Documentation

### Authentication Endpoints

#### POST `/api/auth/register`
Register a new user (customer or merchant)

**Request Body**:
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "role": "customer", // or "merchant"
  // Merchant-specific fields (required if role is "merchant")
  "shopName": "John's Restaurant",
  "location": "123 Main St",
  "phone": "+1234567890"
}
```

#### POST `/api/auth/login`
Login user

**Request Body**:
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response**:
```json
{
  "message": "Login successful",
  "token": "jwt-token-here",
  "user": {
    "id": "user-id",
    "name": "John Doe",
    "email": "john@example.com",
    "role": "customer"
  }
}
```

### Shop Endpoints

#### GET `/api/shops`
Get all active shops (public)

#### GET `/api/shops/merchant/my-shop`
Get merchant's shop (requires merchant auth)

#### PUT `/api/shops/merchant/settings`
Update shop settings (requires merchant auth)

**Request Body**:
```json
{
  "isOpen": true,
  "acceptingOrders": true,
  "orderLimit": 50,
  "closingTime": "21:00"
}
```

### Order Endpoints

#### POST `/api/orders`
Create new order (requires customer auth)

**Request Body**:
```json
{
  "shopId": "shop-id",
  "items": [{
    "mealType": {
      "id": "meal-id",
      "name": "Chicken Rice",
      "price": 350
    },
    "curries": [
      {"id": "curry-id", "name": "Dhal Curry"}
    ],
    "customizations": [
      {"id": "custom-id", "name": "Extra Chicken", "price": 100, "type": "protein"}
    ],
    "subtotal": 450
  }],
  "total": 450,
  "notes": "Extra spicy please"
}
```

#### GET `/api/orders/my-orders`
Get customer's orders (requires customer auth)

#### GET `/api/orders/merchant/orders`
Get merchant's orders (requires merchant auth)

#### PUT `/api/orders/:orderId/status`
Update order status (requires merchant auth)

**Request Body**:
```json
{
  "status": "preparing", // confirmed, preparing, ready, completed, cancelled
  "estimatedPickupTime": "2024-01-15T14:30:00Z"
}
```

### Admin Endpoints

#### GET `/api/admin/merchants/pending`
Get pending merchant approvals (requires admin auth)

#### PUT `/api/admin/merchants/:merchantId/approval`
Approve or reject merchant (requires admin auth)

**Request Body**:
```json
{
  "approved": true, // or false
  "rejectionReason": "Optional reason if rejected"
}
```

#### POST `/api/admin/admins`
Create new admin account (requires admin auth)

**Request Body**:
```json
{
  "name": "New Admin",
  "email": "admin@example.com",
  "password": "password123"
}
```

## 🔐 Authentication

All protected routes require a JWT token in the Authorization header:

```
Authorization: Bearer your-jwt-token-here
```

## 👥 User Roles

1. **Customer**: Can browse shops, place orders, view order history
2. **Merchant**: Can manage shop, menu, and orders (requires admin approval)
3. **Admin**: Can approve merchants, manage platform, create other admins

## 🗄️ Database Models

### User Model
- Basic user information
- Role-based fields
- Password hashing
- Approval system for merchants

### Shop Model
- Restaurant/shop details
- Menu management (meals, curries, customizations)
- Operational settings
- Business hours and statistics

### Order Model
- Complete order information
- Order lifecycle tracking
- Customer and merchant details
- Payment and review system

## 🛡️ Security Features

- **Password Hashing**: bcrypt with salt rounds
- **JWT Authentication**: Secure token-based auth
- **Rate Limiting**: Prevents API abuse
- **Input Validation**: express-validator for all inputs
- **CORS**: Configured for frontend integration
- **Helmet**: Security headers

## 🚀 Deployment

### Environment Variables for Production:
```env
NODE_ENV=production
MONGODB_URI=mongodb+srv://user:pass@cluster.mongodb.net/meal-preorder
JWT_SECRET=your-production-secret-key
FRONTEND_URL=https://your-frontend-domain.com
```

### PM2 Deployment:
```bash
npm install -g pm2
pm2 start server.js --name "meal-preorder-api"
pm2 startup
pm2 save
```

## 📊 Demo Data

The seed script creates:
- **Admin**: `admin@mealorder.com` / `admin123`
- **Merchant**: `merchant@demo.com` / `demo123`
- **Customer**: `customer@demo.com` / `demo123`
- Sample shop with menu items
- Demo orders for testing

## 🧪 Testing

```bash
npm test
```

## 📝 API Response Format

**Success Response**:
```json
{
  "message": "Success message",
  "data": { /* response data */ }
}
```

**Error Response**:
```json
{
  "message": "Error message",
  "errors": [ /* validation errors if any */ ]
}
```

## 🔄 Order Status Flow

1. **pending** → **confirmed** → **preparing** → **ready** → **completed**
2. Any status can transition to **cancelled**

## 📱 Frontend Integration

This backend is designed to work with the React frontend. Make sure to:

1. Set the correct `FRONTEND_URL` in your `.env`
2. Use the provided JWT token for authenticated requests
3. Handle the standardized API response format

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

MIT License - see LICENSE file for details