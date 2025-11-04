import React, { useState } from 'react';
import { Mail, Lock, Loader2 } from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';

interface LoginFormProps {
  onSwitchToRegister: () => void;
}

export const LoginForm: React.FC<LoginFormProps> = ({ onSwitchToRegister }) => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const { login, loading } = useAuth();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    
    const success = await login(email, password);
    if (!success) {
      setError('Invalid credentials or pending merchant approval');
    }
  };

  return (
    <div className="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
      <h2 className="text-2xl font-bold text-center mb-6 text-gray-900">Sign In</h2>
      
      {error && (
        <div className="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
          {error}
        </div>
      )}
      
      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="mb-4 p-3 bg-green-50 rounded-md border border-green-200">
          <h4 className="text-sm font-medium text-green-800 mb-2">🚀 Quick Demo Access</h4>
          <p className="text-xs text-green-700 mb-2">Click any role below to auto-fill login credentials:</p>
          <div className="grid grid-cols-1 gap-2">
            <button
              type="button"
              onClick={() => {
                setEmail('admin@mealorder.com');
                setPassword('admin123');
              }}
              className="text-left p-2 bg-white rounded border hover:bg-green-50 transition-colors"
            >
              <div className="flex items-center">
                <span className="text-lg mr-2">👑</span>
                <div>
                  <div className="text-sm font-medium text-gray-900">Platform Admin</div>
                  <div className="text-xs text-gray-600">Manage merchant approvals & platform overview</div>
                </div>
              </div>
            </button>
            
            <button
              type="button"
              onClick={() => {
                setEmail('merchant@demo.com');
                setPassword('demo123');
              }}
              className="text-left p-2 bg-white rounded border hover:bg-green-50 transition-colors"
            >
              <div className="flex items-center">
                <span className="text-lg mr-2">🏪</span>
                <div>
                  <div className="text-sm font-medium text-gray-900">Restaurant Owner</div>
                  <div className="text-xs text-gray-600">Manage menu, orders & shop settings</div>
                </div>
              </div>
            </button>
            
            <button
              type="button"
              onClick={() => {
                setEmail('customer@demo.com');
                setPassword('demo123');
              }}
              className="text-left p-2 bg-white rounded border hover:bg-green-50 transition-colors"
            >
              <div className="flex items-center">
                <span className="text-lg mr-2">🛒</span>
                <div>
                  <div className="text-sm font-medium text-gray-900">Customer</div>
                  <div className="text-xs text-gray-600">Browse restaurants & place orders</div>
                </div>
              </div>
            </button>
          </div>
        </div>
        
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Email Address
          </label>
          <div className="relative">
            <Mail className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
              placeholder="your@email.com"
              required
            />
          </div>
        </div>
        
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Password
          </label>
          <div className="relative">
            <Lock className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
              placeholder="••••••••"
              required
            />
          </div>
        </div>
        
        <button
          type="submit"
          disabled={loading}
          className="w-full flex items-center justify-center px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {loading ? (
            <Loader2 className="h-4 w-4 animate-spin" />
          ) : (
            'Sign In'
          )}
        </button>
      </form>
      
      <div className="mt-6 text-center">
        <p className="text-sm text-gray-600">
          Don't have an account?{' '}
          <button
            onClick={onSwitchToRegister}
            className="text-orange-500 hover:text-orange-600 font-medium"
          >
            Sign up
          </button>
        </p>
      </div>
    </div>
  );
};