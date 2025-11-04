const express = require('express');
const { body, validationResult } = require('express-validator');
const User = require('../models/User');
const { authenticate, authorize } = require('../middleware/auth');

const router = express.Router();

// Get user profile
router.get('/profile', authenticate, async (req, res) => {
  res.json({ user: req.user.toJSON() });
});

// Update user profile
router.put('/profile', [
  authenticate,
  body('name').optional().trim().isLength({ min: 2 }).withMessage('Name must be at least 2 characters'),
  body('phone').optional().isMobilePhone().withMessage('Please provide a valid phone number'),
  // Merchant-specific fields
  body('shopName').optional().trim().isLength({ min: 2 }).withMessage('Shop name must be at least 2 characters'),
  body('location').optional().trim().isLength({ min: 5 }).withMessage('Location must be at least 5 characters')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        message: 'Validation failed', 
        errors: errors.array() 
      });
    }

    const allowedUpdates = ['name', 'phone'];
    if (req.user.role === 'merchant') {
      allowedUpdates.push('shopName', 'location');
    }

    const updates = {};
    allowedUpdates.forEach(field => {
      if (req.body[field] !== undefined) {
        updates[field] = req.body[field];
      }
    });

    const user = await User.findByIdAndUpdate(
      req.user._id,
      updates,
      { new: true, runValidators: true }
    );

    res.json({
      message: 'Profile updated successfully',
      user: user.toJSON()
    });
  } catch (error) {
    console.error('Error updating profile:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Get all users (admin only)
router.get('/', authenticate, authorize('admin'), async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 20;
    const skip = (page - 1) * limit;
    const role = req.query.role;

    let query = { isActive: true };
    if (role) {
      query.role = role;
    }

    const users = await User.find(query)
      .select('-password')
      .sort({ createdAt: -1 })
      .skip(skip)
      .limit(limit);

    const total = await User.countDocuments(query);

    res.json({
      users,
      pagination: {
        page,
        limit,
        total,
        pages: Math.ceil(total / limit)
      }
    });
  } catch (error) {
    console.error('Error fetching users:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Deactivate user (admin only)
router.put('/:userId/deactivate', authenticate, authorize('admin'), async (req, res) => {
  try {
    const { userId } = req.params;

    if (userId === req.user._id.toString()) {
      return res.status(400).json({ message: 'Cannot deactivate your own account' });
    }

    const user = await User.findById(userId);
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    user.isActive = false;
    await user.save();

    res.json({
      message: 'User deactivated successfully',
      user: user.toJSON()
    });
  } catch (error) {
    console.error('Error deactivating user:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Reactivate user (admin only)
router.put('/:userId/activate', authenticate, authorize('admin'), async (req, res) => {
  try {
    const { userId } = req.params;

    const user = await User.findById(userId);
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    user.isActive = true;
    await user.save();

    res.json({
      message: 'User activated successfully',
      user: user.toJSON()
    });
  } catch (error) {
    console.error('Error activating user:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

module.exports = router;