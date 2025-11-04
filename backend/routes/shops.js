const express = require('express');
const { body, validationResult } = require('express-validator');
const Shop = require('../models/Shop');
const { authenticate, authorize, checkMerchantApproval } = require('../middleware/auth');

const router = express.Router();

// Get all active shops (public)
router.get('/', async (req, res) => {
  try {
    const shops = await Shop.find({})
      .populate('merchantId', 'name email')
      .sort({ createdAt: -1 });

    res.json({ shops });
  } catch (error) {
    console.error('Error fetching shops:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Get shop by ID (public)
router.get('/:shopId', async (req, res) => {
  try {
    const shop = await Shop.findById(req.params.shopId)
      .populate('merchantId', 'name email');

    if (!shop) {
      return res.status(404).json({ message: 'Shop not found' });
    }

    res.json({ shop });
  } catch (error) {
    console.error('Error fetching shop:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Get merchant's shop
router.get('/merchant/my-shop', authenticate, authorize('merchant'), checkMerchantApproval, async (req, res) => {
  try {
    const shop = await Shop.findOne({ merchantId: req.user._id });

    if (!shop) {
      return res.status(404).json({ message: 'Shop not found' });
    }

    res.json({ shop });
  } catch (error) {
    console.error('Error fetching merchant shop:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Update shop settings
router.put('/merchant/settings', [
  authenticate,
  authorize('merchant'),
  checkMerchantApproval,
  body('isOpen').optional().isBoolean(),
  body('acceptingOrders').optional().isBoolean(),
  body('orderLimit').optional().isInt({ min: 0 }),
  body('closingTime').optional().isString()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        message: 'Validation failed', 
        errors: errors.array() 
      });
    }

    const shop = await Shop.findOne({ merchantId: req.user._id });
    if (!shop) {
      return res.status(404).json({ message: 'Shop not found' });
    }

    const allowedUpdates = ['isOpen', 'acceptingOrders', 'orderLimit', 'closingTime'];
    const updates = {};

    allowedUpdates.forEach(field => {
      if (req.body[field] !== undefined) {
        updates[field] = req.body[field];
      }
    });

    Object.assign(shop, updates);
    await shop.save();

    res.json({ 
      message: 'Shop settings updated successfully',
      shop 
    });
  } catch (error) {
    console.error('Error updating shop settings:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Update meal type
router.put('/merchant/meal-types/:mealId', [
  authenticate,
  authorize('merchant'),
  checkMerchantApproval,
  body('name').optional().trim().isLength({ min: 1 }),
  body('price').optional().isFloat({ min: 0 }),
  body('available').optional().isBoolean(),
  body('description').optional().isString()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        message: 'Validation failed', 
        errors: errors.array() 
      });
    }

    const shop = await Shop.findOne({ merchantId: req.user._id });
    if (!shop) {
      return res.status(404).json({ message: 'Shop not found' });
    }

    const mealType = shop.mealTypes.id(req.params.mealId);
    if (!mealType) {
      return res.status(404).json({ message: 'Meal type not found' });
    }

    const allowedUpdates = ['name', 'price', 'available', 'description'];
    allowedUpdates.forEach(field => {
      if (req.body[field] !== undefined) {
        mealType[field] = req.body[field];
      }
    });

    await shop.save();

    res.json({ 
      message: 'Meal type updated successfully',
      mealType 
    });
  } catch (error) {
    console.error('Error updating meal type:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Update curry availability
router.put('/merchant/curries/:curryId', [
  authenticate,
  authorize('merchant'),
  checkMerchantApproval,
  body('name').optional().trim().isLength({ min: 1 }),
  body('available').optional().isBoolean()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        message: 'Validation failed', 
        errors: errors.array() 
      });
    }

    const shop = await Shop.findOne({ merchantId: req.user._id });
    if (!shop) {
      return res.status(404).json({ message: 'Shop not found' });
    }

    const curry = shop.curries.id(req.params.curryId);
    if (!curry) {
      return res.status(404).json({ message: 'Curry not found' });
    }

    const allowedUpdates = ['name', 'available'];
    allowedUpdates.forEach(field => {
      if (req.body[field] !== undefined) {
        curry[field] = req.body[field];
      }
    });

    await shop.save();

    res.json({ 
      message: 'Curry updated successfully',
      curry 
    });
  } catch (error) {
    console.error('Error updating curry:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Update customization
router.put('/merchant/customizations/:customId', [
  authenticate,
  authorize('merchant'),
  checkMerchantApproval,
  body('name').optional().trim().isLength({ min: 1 }),
  body('price').optional().isFloat({ min: 0 }),
  body('available').optional().isBoolean(),
  body('type').optional().isIn(['protein', 'curry', 'extra'])
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        message: 'Validation failed', 
        errors: errors.array() 
      });
    }

    const shop = await Shop.findOne({ merchantId: req.user._id });
    if (!shop) {
      return res.status(404).json({ message: 'Shop not found' });
    }

    const customization = shop.customizations.id(req.params.customId);
    if (!customization) {
      return res.status(404).json({ message: 'Customization not found' });
    }

    const allowedUpdates = ['name', 'price', 'available', 'type'];
    allowedUpdates.forEach(field => {
      if (req.body[field] !== undefined) {
        customization[field] = req.body[field];
      }
    });

    await shop.save();

    res.json({ 
      message: 'Customization updated successfully',
      customization 
    });
  } catch (error) {
    console.error('Error updating customization:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Reset daily order count (can be called by cron job)
router.post('/reset-daily-orders', authenticate, authorize('admin'), async (req, res) => {
  try {
    await Shop.updateMany({}, { ordersReceived: 0 });
    res.json({ message: 'Daily order counts reset successfully' });
  } catch (error) {
    console.error('Error resetting daily orders:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

module.exports = router;