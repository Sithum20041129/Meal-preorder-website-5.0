const mongoose = require('mongoose');

const mealTypeSchema = new mongoose.Schema({
  name: {
    type: String,
    required: true
  },
  price: {
    type: Number,
    required: true,
    min: 0
  },
  description: String,
  available: {
    type: Boolean,
    default: true
  },
  image: String
});

const currySchema = new mongoose.Schema({
  name: {
    type: String,
    required: true
  },
  available: {
    type: Boolean,
    default: true
  },
  spiceLevel: {
    type: String,
    enum: ['mild', 'medium', 'hot', 'extra-hot'],
    default: 'medium'
  }
});

const customizationSchema = new mongoose.Schema({
  name: {
    type: String,
    required: true
  },
  price: {
    type: Number,
    required: true,
    min: 0
  },
  type: {
    type: String,
    enum: ['protein', 'curry', 'extra'],
    required: true
  },
  available: {
    type: Boolean,
    default: true
  }
});

const shopSchema = new mongoose.Schema({
  merchantId: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true,
    unique: true
  },
  name: {
    type: String,
    required: true,
    trim: true
  },
  location: {
    type: String,
    required: true
  },
  phone: {
    type: String,
    required: true
  },
  description: String,
  image: String,
  
  // Operational settings
  isOpen: {
    type: Boolean,
    default: false
  },
  acceptingOrders: {
    type: Boolean,
    default: true
  },
  closingTime: String,
  orderLimit: {
    type: Number,
    default: 50
  },
  ordersReceived: {
    type: Number,
    default: 0
  },
  
  // Menu items
  mealTypes: [mealTypeSchema],
  curries: [currySchema],
  customizations: [customizationSchema],
  
  // Business hours
  businessHours: {
    monday: { open: String, close: String, closed: Boolean },
    tuesday: { open: String, close: String, closed: Boolean },
    wednesday: { open: String, close: String, closed: Boolean },
    thursday: { open: String, close: String, closed: Boolean },
    friday: { open: String, close: String, closed: Boolean },
    saturday: { open: String, close: String, closed: Boolean },
    sunday: { open: String, close: String, closed: Boolean }
  },
  
  // Statistics
  totalOrders: {
    type: Number,
    default: 0
  },
  totalRevenue: {
    type: Number,
    default: 0
  },
  rating: {
    type: Number,
    default: 0,
    min: 0,
    max: 5
  },
  reviewCount: {
    type: Number,
    default: 0
  }
}, {
  timestamps: true
});

// Reset daily order count at midnight
shopSchema.methods.resetDailyOrders = function() {
  this.ordersReceived = 0;
  return this.save();
};

module.exports = mongoose.model('Shop', shopSchema);