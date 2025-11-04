const User = require('../models/User');
const Shop = require('../models/Shop');
const Order = require('../models/Order');

const seedDatabase = async () => {
  try {
    // Clear existing data
    await User.deleteMany({});
    await Shop.deleteMany({});
    await Order.deleteMany({});

    console.log('🗑️  Cleared existing data');

    // Create admin user
    const admin = new User({
      name: 'Platform Admin',
      email: 'admin@mealorder.com',
      password: 'admin123',
      role: 'admin',
      approved: true
    });
    await admin.save();

    // Create demo customer
    const customer = new User({
      name: 'Demo Customer',
      email: 'customer@demo.com',
      password: 'demo123',
      role: 'customer',
      approved: true
    });
    await customer.save();

    // Create demo merchant
    const merchant = new User({
      name: 'Demo Restaurant Owner',
      email: 'merchant@demo.com',
      password: 'demo123',
      role: 'merchant',
      approved: true,
      shopName: 'Spice Garden Restaurant',
      location: 'Main Street, Downtown',
      phone: '+1 234-567-8900'
    });
    await merchant.save();

    // Create demo shop
    const shop = new Shop({
      merchantId: merchant._id,
      name: 'Spice Garden Restaurant',
      location: 'Main Street, Downtown',
      phone: '+1 234-567-8900',
      description: 'Authentic Sri Lankan cuisine with fresh ingredients',
      isOpen: true,
      acceptingOrders: true,
      orderLimit: 25,
      ordersReceived: 5,
      closingTime: '21:00',
      mealTypes: [
        {
          name: 'Vegetarian Rice',
          price: 250,
          available: true,
          description: 'Fresh vegetables with aromatic basmati rice'
        },
        {
          name: 'Chicken Rice',
          price: 350,
          available: true,
          description: 'Tender chicken pieces with spiced rice'
        },
        {
          name: 'Fish Rice',
          price: 400,
          available: true,
          description: 'Fresh fish curry with fragrant rice'
        },
        {
          name: 'Egg Rice',
          price: 300,
          available: true,
          description: 'Scrambled eggs with seasoned rice'
        }
      ],
      curries: [
        { name: 'Dhal Curry', available: true, spiceLevel: 'mild' },
        { name: 'Vegetable Curry', available: true, spiceLevel: 'medium' },
        { name: 'Potato Curry', available: true, spiceLevel: 'medium' },
        { name: 'Chicken Curry', available: true, spiceLevel: 'hot' },
        { name: 'Fish Curry', available: true, spiceLevel: 'hot' },
        { name: 'Coconut Curry', available: false, spiceLevel: 'mild' }
      ],
      customizations: [
        { name: 'Extra Chicken Piece', price: 100, available: true, type: 'protein' },
        { name: 'Extra Fish Piece', price: 150, available: true, type: 'protein' },
        { name: 'Extra Curry Portion', price: 50, available: true, type: 'curry' },
        { name: 'Extra Rice', price: 30, available: true, type: 'extra' },
        { name: 'Spicy Level +1', price: 0, available: true, type: 'extra' }
      ],
      totalOrders: 15,
      totalRevenue: 6750,
      rating: 4.5,
      reviewCount: 8
    });
    await shop.save();

    // Create demo order
    const order = new Order({
      customerId: customer._id,
      customerName: customer.name,
      customerPhone: '+1 555-0123',
      merchantId: merchant._id,
      shopId: shop._id,
      merchantName: shop.name,
      items: [{
        mealType: {
          id: shop.mealTypes[1]._id.toString(),
          name: 'Chicken Rice',
          price: 350
        },
        curries: [
          { id: shop.curries[0]._id.toString(), name: 'Dhal Curry' },
          { id: shop.curries[3]._id.toString(), name: 'Chicken Curry' },
          { id: shop.curries[1]._id.toString(), name: 'Vegetable Curry' }
        ],
        customizations: [{
          id: shop.customizations[0]._id.toString(),
          name: 'Extra Chicken Piece',
          price: 100,
          type: 'protein'
        }],
        subtotal: 450
      }],
      total: 450,
      status: 'preparing',
      confirmedAt: new Date(Date.now() - 20 * 60 * 1000), // 20 minutes ago
      preparingAt: new Date(Date.now() - 10 * 60 * 1000), // 10 minutes ago
      estimatedPickupTime: new Date(Date.now() + 10 * 60 * 1000) // 10 minutes from now
    });
    await order.save();

    console.log('✅ Database seeded successfully!');
    console.log('👑 Admin: admin@mealorder.com / admin123');
    console.log('🏪 Merchant: merchant@demo.com / demo123');
    console.log('🛒 Customer: customer@demo.com / demo123');

  } catch (error) {
    console.error('❌ Error seeding database:', error);
  }
};

module.exports = { seedDatabase };