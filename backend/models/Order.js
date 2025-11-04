const mongoose = require('mongoose');

const orderItemSchema = new mongoose.Schema({
  mealType: {
    id: String,
    name: String,
    price: Number
  },
  curries: [{
    id: String,
    name: String
  }],
  customizations: [{
    id: String,
    name: String,
    price: Number,
    type: String
  }],
  subtotal: {
    type: Number,
    required: true
  },
  specialInstructions: String
});

const orderSchema = new mongoose.Schema({
  orderNumber: {
    type: String,
    required: true,
    unique: true
  },
  customerId: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },
  customerName: {
    type: String,
    required: true
  },
  customerPhone: String,
  merchantId: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },
  shopId: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Shop',
    required: true
  },
  merchantName: {
    type: String,
    required: true
  },
  
  items: [orderItemSchema],
  
  total: {
    type: Number,
    required: true,
    min: 0
  },
  
  status: {
    type: String,
    enum: ['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'],
    default: 'pending'
  },
  
  paymentStatus: {
    type: String,
    enum: ['pending', 'paid', 'failed', 'refunded'],
    default: 'pending'
  },
  
  paymentMethod: {
    type: String,
    enum: ['cash', 'card', 'online'],
    default: 'cash'
  },
  
  // Timestamps for order lifecycle
  confirmedAt: Date,
  preparingAt: Date,
  readyAt: Date,
  completedAt: Date,
  cancelledAt: Date,
  
  // Estimated pickup time
  estimatedPickupTime: Date,
  
  // Customer notes
  notes: String,
  
  // Rating and review (after completion)
  rating: {
    type: Number,
    min: 1,
    max: 5
  },
  review: String,
  
  // Cancellation reason
  cancellationReason: String
}, {
  timestamps: true
});

// Generate order number
orderSchema.pre('save', async function(next) {
  if (!this.orderNumber) {
    const timestamp = Date.now().toString().slice(-6);
    const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
    this.orderNumber = `ORD${timestamp}${random}`;
  }
  next();
});

// Update status timestamps
orderSchema.pre('save', function(next) {
  if (this.isModified('status')) {
    const now = new Date();
    switch (this.status) {
      case 'confirmed':
        if (!this.confirmedAt) this.confirmedAt = now;
        break;
      case 'preparing':
        if (!this.preparingAt) this.preparingAt = now;
        break;
      case 'ready':
        if (!this.readyAt) this.readyAt = now;
        break;
      case 'completed':
        if (!this.completedAt) this.completedAt = now;
        break;
      case 'cancelled':
        if (!this.cancelledAt) this.cancelledAt = now;
        break;
    }
  }
  next();
});

module.exports = mongoose.model('Order', orderSchema);