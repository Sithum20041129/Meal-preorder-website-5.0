# Meal Pre-Order Platform - PHP Backend

A complete PHP backend API for the meal pre-order platform with MySQL database integration.

## 🚀 Features

- **Multi-role Authentication** (Customer, Merchant, Admin)
- **JWT Token-based Security**
- **Complete Order Management System**
- **Shop Management with Menu Control**
- **Admin Panel for Merchant Approvals**
- **RESTful API Design**
- **MySQL Database with Proper Relations**

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx with mod_rewrite enabled
- Composer (for JWT library)

## 🛠️ Installation

### 1. Clone/Download Files
Place all backend-php files in your web server directory.

### 2. Install Dependencies
```bash
cd backend-php
composer install
```

### 3. Database Setup
```bash
# Import the database schema
mysql -u root -p < database/schema.sql
```

### 4. Configure Database
Edit `config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'meal_preorder';
private $username = 'your_username';
private $password = 'your_password';
```

### 5. Web Server Configuration
Ensure your web server has:
- **mod_rewrite enabled** (for Apache)
- **URL rewriting support** (for Nginx)
- **PHP extensions**: PDO, PDO_MySQL, JSON

## 📚 API Endpoints

### Authentication (`/api/auth/`)
- `POST /register` - User registration
- `POST /login` - User login with JWT token
- `GET /me` - Get current user profile

### Admin Management (`/api/admin/`)
- `GET /merchants/pending` - Get pending merchant approvals
- `PUT /merchants/{id}/approval` - Approve/reject merchant
- `POST /admins` - Create new admin account
- `GET /admins` - Get all admin accounts
- `DELETE /admins/{id}` - Delete admin account
- `GET /stats` - Platform statistics

### Shop Management (`/api/shops/`)
- `GET /` - Browse all active shops (public)
- `GET /merchant/my-shop` - Get merchant's shop details
- `PUT /merchant/settings` - Update shop operational settings
- `PUT /merchant/meal-types/{id}` - Update menu items

### Order Processing (`/api/orders/`)
- `POST /` - Place new order
- `GET /my-orders` - Customer order history
- `GET /merchant/orders` - Merchant order management
- `GET /{id}` - Get specific order details
- `PUT /{id}/status` - Update order status

## 🔐 Authentication

All protected endpoints require JWT token in Authorization header:
```
Authorization: Bearer YOUR_JWT_TOKEN
```

### Demo Accounts
The database comes with pre-configured accounts:

**Admin Account:**
- Email: `admin@mealorder.com`
- Password: `admin123`

**Merchant Account:**
- Email: `merchant@demo.com`
- Password: `demo123`

**Customer Account:**
- Email: `customer@demo.com`
- Password: `demo123`

## 🗄️ Database Schema

### Core Tables
- **users** - Multi-role user accounts
- **shops** - Restaurant information and settings
- **meal_types** - Available meal options
- **curries** - Available curry selections
- **customizations** - Extra options and add-ons

### Order Management
- **orders** - Main order information
- **order_items** - Individual items in orders
- **order_item_curries** - Selected curries per item
- **order_item_customizations** - Selected extras per item

## 🔧 Configuration

### JWT Settings
Edit `utils/jwt.php` to change:
- Secret key (IMPORTANT: Change in production!)
- Token expiration time
- Issuer and audience

### CORS Settings
Modify headers in API files to restrict origins:
```php
header("Access-Control-Allow-Origin: https://yourdomain.com");
```

## 🚀 Deployment

### Production Checklist
1. **Change JWT secret key** in `utils/jwt.php`
2. **Update database credentials** in `config/database.php`
3. **Configure CORS** for your frontend domain
4. **Enable HTTPS** for secure token transmission
5. **Set proper file permissions** (644 for files, 755 for directories)
6. **Configure error logging** in PHP

### Security Features
- ✅ Password hashing with bcrypt
- ✅ JWT token authentication
- ✅ SQL injection prevention with prepared statements
- ✅ Input validation and sanitization
- ✅ Role-based access control
- ✅ CORS protection

## 🔄 Frontend Integration

To connect your React frontend:

1. **Update API base URL** to your PHP server
2. **Add JWT token handling** for authenticated requests
3. **Handle API responses** and error codes
4. **Replace localStorage** with actual API calls

Example API call:
```javascript
const response = await fetch('https://yourapi.com/api/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ email, password })
});
```

## 📞 Support

For issues or questions:
1. Check the API response messages for detailed error information
2. Verify database connection and table structure
3. Ensure proper file permissions and web server configuration
4. Check PHP error logs for detailed debugging information

## 🎯 Next Steps

1. **Test all endpoints** with your frontend
2. **Configure production environment** with proper security
3. **Set up backup system** for database
4. **Monitor API performance** and optimize as needed
5. **Implement additional features** as required