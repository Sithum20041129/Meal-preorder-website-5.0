const express = require('express');
const { body, validationResult } = require('express-validator');
const User = require('../models/User');
const Shop = require('../models/Shop');
const Order = require('../models/Order');
const { authenticate, authorize } = require('../middleware/auth');

const router = express.Router();

// All routes require admin authentication
router.use(authenticate);
router.use(authorize('admin'));

// Get pending merchant approvals
router.get('/merchants/pending', async (req, res) => {
  try {
    const pendingMerchants = await User.find({
      role: 'merchant',
      approved: false,
      isActive: true
    }).select('-password').sort({ createdAt: -1 });

    res.json({ merchants: pendingMerchants });
  } catch (error) {
    console.error('Error fetching pending merchants:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Approve or reject merchant
router.put('/merchants/:merchantId/approval', [
  body('approved').isBoolean().withMessage('Approved must be a boolean'),
  body('rejectionReason').optional().isString()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        message: 'Validation failed', 
        errors: errors.array() 
      });
    }

    const { merchantId } = req.params;
    const { approved, rejectionReason } = req.body;

    const merchant = await User.findById(merchantId);
    if (!merchant || merchant.role !== 'merchant') {
      return res.status(404).json({ message: 'Merchant not found' });
    }

    if (approved) {
      // Approve merchant
      merchant.approved = true;
      await merchant.save();

      // Create shop for approved merchant
      const shop = new Shop({
        merchantId: merchant._id,
        name: merchant.shopName,
        location: merchant.location,
        phone: merchant.phone,
        mealTypes: [
          { name: 'Vegetarian Rice', price: 250, available: true },
          { name: 'Chicken Rice', price: 350, available: true },
          { name: 'Fish Rice', price: 400, available: true },
          { name: 'Egg Rice', price: 300, available: true }
        ],
        curries: [
          { name: 'Dhal Curry', available: true },
          { name: 'Vegetable Curry', available: true },
          { name: 'Potato Curry', available: true },
          { name: 'Chicken Curry', available: true },
          { name: 'Fish Curry', available: true }
        ],
        customizations: [
          { name: 'Extra Chicken Piece', price: 100, available: true, type: 'protein' },
          { name: 'Extra Fish Piece', price: 150, available: true, type: 'protein' },
          { name: 'Extra Curry', price: 50, available: true, type: 'curry' },
          { name: 'Extra Rice', price: 30, available: true, type: 'extra' }
        ]
      });
      await shop.save();

      res.json({ 
        message: 'Merchant approved successfully',
        merchant: merchant.toJSON(),
        shop 
      });
    } else {
      // Reject merchant
      merchant.isActive = false;
      if (rejectionReason) {
        merchant.rejectionReason = rejectionReason;
      }
      await merchant.save();

      res.json({ 
        message: 'Merchant rejected',
        merchant: merchant.toJSON()
      });
    }
  } catch (error) {
    console.error('Error processing merchant approval:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Get all admins
router.get('/admins', async (req, res) => {
  try {
    const admins = await User.find({
      role: 'admin',
      isActive: true
    }).select('-password').sort({ createdAt: -1 });

    res.json({ admins });
  } catch (error) {
    console.error('Error fetching admins:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Create new admin
router.post('/admins', [
  body('name').trim().isLength({ min: 2 }).withMessage('Name must be at least 2 characters'),
  body('email').isEmail().normalizeEmail().withMessage('Please provide a valid email'),
  body('password').isLength({ min: 6 }).withMessage('Password must be at least 6 characters')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        message: 'Validation failed', 
        errors: errors.array() 
      });
    }

    const { name, email, password } = req.body;

    // Check if email already exists
    const existingUser = await User.findOne({ email });
    if (existingUser) {
      return res.status(400).json({ message: 'Email already exists' });
    }

    // Create admin user
    const admin = new User({
      name,
      email,
      password,
      role: 'admin',
      approved: true
    });

    await admin.save();

    res.status(201).json({
      message: 'Admin created successfully',
      admin: admin.toJSON()
    });
  } catch (error) {
    console.error('Error creating admin:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Delete admin
router.delete('/admins/:adminId', async (req, res) => {
  try {
    const { adminId } = req.params;

    // Check if trying to delete self
    if (adminId === req.user._id.toString()) {
      return res.status(400).json({ message: 'Cannot delete your own account' });
    }

    // Check if this is the last admin
    const adminCount = await User.countDocuments({ role: 'admin', isActive: true });
    if (adminCount <= 1) {
      return res.status(400).json({ message: 'Cannot delete the last admin account' });
    }

    const admin = await User.findById(adminId);
    if (!admin || admin.role !== 'admin') {
      return res.status(404).json({ message: 'Admin not found' });
    }

    // Soft delete
    admin.isActive = false;
    await admin.save();

    res.json({ message: 'Admin deleted successfully' });
  } catch (error) {
    console.error('Error deleting admin:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Get platform statistics
router.get('/stats', async (req, res) => {
  try {
    const [
      totalUsers,
      totalMerchants,
      approvedMerchants,
      pendingMerchants,
      totalOrders,
      todayOrders,
      totalRevenue
    ] = await Promise.all([
      User.countDocuments({ isActive: true }),
      User.countDocuments({ role: 'merchant', isActive: true }),
      User.countDocuments({ role: 'merchant', approved: true, isActive: true }),
      User.countDocuments({ role: 'merchant', approved: false, isActive: true }),
      Order.countDocuments(),
      Order.countDocuments({
        createdAt: {
          $gte: new Date(new Date().setHours(0, 0, 0, 0))
        }
      }),
      Order.aggregate([
        { $match: { status: 'completed' } },
        { $group: { _id: null, total: { $sum: '$total' } } }
      ])
    ]);

    res.json({
      users: {
        total: totalUsers,
        merchants: totalMerchants,
        approved: approvedMerchants,
        pending: pendingMerchants
      },
      orders: {
        total: totalOrders,
        today: todayOrders
      },
      revenue: {
        total: totalRevenue[0]?.total || 0
      }
    });
  } catch (error) {
    console.error('Error fetching platform stats:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

module.exports = router;