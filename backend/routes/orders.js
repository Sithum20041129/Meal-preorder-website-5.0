const express = require('express');
const { body, validationResult } = require('express-validator');
const Order = require('../models/Order');
const Shop = require('../models/Shop');
const { authenticate, authorize, checkMerchantApproval } = require('../middleware/auth');

const router = express.Router();

// Create new order
router.post('/', [
  authenticate,
  authorize('customer'),
  body('shopId').isMongoId().withMessage('Valid shop ID is required'),
  body('items').isArray({ min: 1 }).withMessage('At least one item is required'),
  body('items.*.mealType').notEmpty().withMessage('Meal type is required'),
  body('items.*.curries').isArray({ min: 1, max: 3 }).withMessage('1-3 curries required'),
  body('items.*.subtotal').isFloat({ min: 0 }).withMessage('Valid subtotal required'),
  body('total').isFloat({ min: 0 }).withMessage('Valid total required')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        message: 'Validation failed', 
        errors: errors.array() 
      });
    }

    const { shopId, items, total, notes } = req.body;

    // Verify shop exists and is accepting orders
    const shop = await Shop.findById(shopId).populate('merchantId', 'name');
    if (!shop) {
      return res.status(404).json({ message: 'Shop not found' });
    }

    if (!shop.isOpen) {
      return res.status(400).json({ message: 'Shop is currently closed' });
    }

    if (!shop.acceptingOrders) {
      return res.status(400).json({ message: 'Shop is not accepting orders at the moment' });
    }

    if (shop.orderLimit && shop.ordersReceived >= shop.orderLimit) {
      return res.status(400).json({ message: 'Shop has reached its daily order limit' });
    }

    // Create order
    const order = new Order({
      customerId: req.user._id,
      customerName: req.user.name,
      customerPhone: req.user.phone,
      merchantId: shop.merchantId._id,
      shopId: shop._id,
      merchantName: shop.name,
      items,
      total,
      notes
    });

    await order.save();

    // Update shop order count
    shop.ordersReceived += 1;
    shop.totalOrders += 1;
    await shop.save();

    // Populate order details for response
    await order.populate('customerId', 'name email phone');

    res.status(201).json({
      message: 'Order placed successfully',
      order
    });
  } catch (error) {
    console.error('Error creating order:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Get customer's orders
router.get('/my-orders', authenticate, authorize('customer'), async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 10;
    const skip = (page - 1) * limit;

    const orders = await Order.find({ customerId: req.user._id })
      .sort({ createdAt: -1 })
      .skip(skip)
      .limit(limit)
      .populate('shopId', 'name location');

    const total = await Order.countDocuments({ customerId: req.user._id });

    res.json({
      orders,
      pagination: {
        page,
        limit,
        total,
        pages: Math.ceil(total / limit)
      }
    });
  } catch (error) {
    console.error('Error fetching customer orders:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Get merchant's orders
router.get('/merchant/orders', authenticate, authorize('merchant'), checkMerchantApproval, async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 20;
    const skip = (page - 1) * limit;
    const status = req.query.status;

    let query = { merchantId: req.user._id };
    if (status && status !== 'all') {
      query.status = status;
    }

    const orders = await Order.find(query)
      .sort({ createdAt: -1 })
      .skip(skip)
      .limit(limit)
      .populate('customerId', 'name phone')
      .populate('shopId', 'name');

    const total = await Order.countDocuments(query);

    res.json({
      orders,
      pagination: {
        page,
        limit,
        total,
        pages: Math.ceil(total / limit)
      }
    });
  } catch (error) {
    console.error('Error fetching merchant orders:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Update order status (merchant only)
router.put('/:orderId/status', [
  authenticate,
  authorize('merchant'),
  checkMerchantApproval,
  body('status').isIn(['confirmed', 'preparing', 'ready', 'completed', 'cancelled']).withMessage('Invalid status'),
  body('estimatedPickupTime').optional().isISO8601().withMessage('Valid pickup time required'),
  body('cancellationReason').optional().isString()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        message: 'Validation failed', 
        errors: errors.array() 
      });
    }

    const { orderId } = req.params;
    const { status, estimatedPickupTime, cancellationReason } = req.body;

    const order = await Order.findOne({ 
      _id: orderId, 
      merchantId: req.user._id 
    });

    if (!order) {
      return res.status(404).json({ message: 'Order not found' });
    }

    // Validate status transition
    const validTransitions = {
      'pending': ['confirmed', 'cancelled'],
      'confirmed': ['preparing', 'cancelled'],
      'preparing': ['ready', 'cancelled'],
      'ready': ['completed'],
      'completed': [],
      'cancelled': []
    };

    if (!validTransitions[order.status].includes(status)) {
      return res.status(400).json({ 
        message: `Cannot change status from ${order.status} to ${status}` 
      });
    }

    order.status = status;
    
    if (estimatedPickupTime) {
      order.estimatedPickupTime = new Date(estimatedPickupTime);
    }
    
    if (status === 'cancelled' && cancellationReason) {
      order.cancellationReason = cancellationReason;
    }

    // Update shop revenue when order is completed
    if (status === 'completed') {
      const shop = await Shop.findById(order.shopId);
      if (shop) {
        shop.totalRevenue += order.total;
        await shop.save();
      }
    }

    await order.save();

    res.json({
      message: 'Order status updated successfully',
      order
    });
  } catch (error) {
    console.error('Error updating order status:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Get order by ID
router.get('/:orderId', authenticate, async (req, res) => {
  try {
    const { orderId } = req.params;
    
    let query = { _id: orderId };
    
    // Customers can only see their own orders
    if (req.user.role === 'customer') {
      query.customerId = req.user._id;
    }
    // Merchants can only see their shop's orders
    else if (req.user.role === 'merchant') {
      query.merchantId = req.user._id;
    }
    // Admins can see all orders

    const order = await Order.findOne(query)
      .populate('customerId', 'name email phone')
      .populate('merchantId', 'name email')
      .populate('shopId', 'name location phone');

    if (!order) {
      return res.status(404).json({ message: 'Order not found' });
    }

    res.json({ order });
  } catch (error) {
    console.error('Error fetching order:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

// Add rating and review (customer only, after completion)
router.put('/:orderId/review', [
  authenticate,
  authorize('customer'),
  body('rating').isInt({ min: 1, max: 5 }).withMessage('Rating must be between 1 and 5'),
  body('review').optional().isString().isLength({ max: 500 }).withMessage('Review too long')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        message: 'Validation failed', 
        errors: errors.array() 
      });
    }

    const { orderId } = req.params;
    const { rating, review } = req.body;

    const order = await Order.findOne({ 
      _id: orderId, 
      customerId: req.user._id,
      status: 'completed'
    });

    if (!order) {
      return res.status(404).json({ 
        message: 'Order not found or not completed' 
      });
    }

    if (order.rating) {
      return res.status(400).json({ 
        message: 'Order already reviewed' 
      });
    }

    order.rating = rating;
    if (review) order.review = review;
    await order.save();

    // Update shop rating
    const shop = await Shop.findById(order.shopId);
    if (shop) {
      const totalRating = (shop.rating * shop.reviewCount) + rating;
      shop.reviewCount += 1;
      shop.rating = totalRating / shop.reviewCount;
      await shop.save();
    }

    res.json({
      message: 'Review added successfully',
      order
    });
  } catch (error) {
    console.error('Error adding review:', error);
    res.status(500).json({ message: 'Server error' });
  }
});

module.exports = router;