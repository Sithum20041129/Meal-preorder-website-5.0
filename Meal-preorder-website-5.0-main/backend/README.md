# Meal Pre-Order Platform

A complete meal pre-order platform with PHP backend and React frontend, optimized for Hostinger hosting.

## 🚀 Features

- **Real User Authentication**: JWT-based auth with proper registration
- **Role-Based Access**: Customer, Merchant, and Admin roles
- **Shop Management**: Complete restaurant management system
- **Order Processing**: Full order lifecycle management
- **MySQL Database**: Optimized for shared hosting
- **Security**: Rate limiting, input validation, password hashing

## 📋 Prerequisites

- Hostinger shared hosting account
- PHP 7.4 or higher
- MySQL database
- Basic knowledge of FTP/cPanel

## 🛠️ Installation on Hostinger

### 1. Database Setup
1. Create MySQL database in Hostinger control panel
2. Note down: database name, username, password
3. Run the SQL schema from `database_schema.sql`

### 2. File Upload
1. Upload all files to your domain root via FTP or File Manager
2. Ensure file structure is maintained

### 3. Environment Configuration
1. Rename `.env.example` to `.env`
2. Update with your actual database credentials:
```env
DB_HOST=localhost
DB_NAME=your_database_name
DB_USERNAME=your_database_username
DB_PASSWORD=your_database_password
JWT_SECRET=your-super-secret-jwt-key
BASE_URL=https://yourdomain.com