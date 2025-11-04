const mongoose = require('mongoose');
const { seedDatabase } = require('../utils/seedData');
require('dotenv').config();

const runSeed = async () => {
  try {
    // Connect to MongoDB
    await mongoose.connect(process.env.MONGODB_URI || 'mongodb://localhost:27017/meal-preorder', {
      useNewUrlParser: true,
      useUnifiedTopology: true,
    });

    console.log('📦 Connected to MongoDB');

    // Run seed
    await seedDatabase();

    // Close connection
    await mongoose.connection.close();
    console.log('🔌 Database connection closed');
    
    process.exit(0);
  } catch (error) {
    console.error('❌ Seed failed:', error);
    process.exit(1);
  }
};

runSeed();