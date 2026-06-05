import React, { useState } from 'react';
import { useLms } from '../context/LmsContext';
import * as Lucide from 'lucide-react';

export const Login: React.FC = () => {
  const { login } = useLms();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [demoHintOpen, setDemoHintOpen] = useState(true);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    if (!email || !password) {
      setError('Please fill in both email and password fields.');
      return;
    }

    const res = login(email, password);
    if (!res.success) {
      setError(res.error || 'Invalid credentials');
    }
  };

  const handleQuickLogin = (roleEmail: string, pass: string) => {
    setEmail(roleEmail);
    setPassword(pass);
    setError(null);
  };

  return (
    <div className="min-h-screen font-sans bg-[#F8FAFC] flex flex-col md:flex-row w-full">
      {/* Left Side: Branding & Prop (55% width) */}
      <div className="bg-[#0F172A] flex-shrink-0 w-full md:w-[55%] min-h-[400px] md:min-h-screen p-8 md:p-16 flex flex-col justify-between relative overflow-hidden">
        {/* Decorative Grid Panel */}
        <div 
          className="absolute inset-0 opacity-10 pointer-events-none" 
          style={{ backgroundImage: 'radial-gradient(circle at 100% 0%, #1D4ED8 0%, transparent 50%)' }}
        />

        {/* Logo Area */}
        <div className="z-10 flex items-center gap-3">
          <div className="w-10 h-10 bg-[#1D4ED8] rounded-xl flex items-center justify-center text-white shadow-md">
            <Lucide.GraduationCap className="w-6 h-6" />
          </div>
          <span className="text-xl font-bold text-white tracking-tight">EduTrack LMS</span>
        </div>

        {/* Value Proposal Header */}
        <div className="z-10 mt-16 md:mt-0 max-w-lg space-y-6">
          <h1 className="text-4xl md:text-5xl font-extrabold text-white leading-tight tracking-tight">
            Empower Learning.<br />Track Progress.
          </h1>
          <p className="text-[#94A3B8] text-base leading-relaxed">
            A comprehensive, high-fidelity Learning Management System orchestrating students schedules, assignments evaluations, results disclosures and placements.
          </p>
          <ul className="space-y-4 pt-4">
            <li className="flex items-start gap-3">
              <div className="flex-shrink-0 w-6 h-6 rounded-full bg-[#1D4ED8]/20 flex items-center justify-center mt-0.5">
                <Lucide.Check className="w-4 h-4 text-[#1D4ED8]" />
              </div>
              <p className="text-white font-medium text-sm">Advanced curriculum management & attendance tracking.</p>
            </li>
            <li className="flex items-start gap-3">
              <div className="flex-shrink-0 w-6 h-6 rounded-full bg-[#1D4ED8]/20 flex items-center justify-center mt-0.5">
                <Lucide.Check className="w-4 h-4 text-[#1D4ED8]" />
              </div>
              <p className="text-white font-medium text-sm">Real-time grades distribution analytics and performance insights.</p>
            </li>
            <li className="flex items-start gap-3">
              <div className="flex-shrink-0 w-6 h-6 rounded-full bg-[#1D4ED8]/20 flex items-center justify-center mt-0.5">
                <Lucide.Check className="w-4 h-4 text-[#1D4ED8]" />
              </div>
              <p className="text-white font-medium text-sm">Seamless placements applications, calendars and notifications.</p>
            </li>
          </ul>
        </div>

        {/* Outer Legal credits */}
        <div className="z-10 mt-16 md:mt-0 flex flex-wrap gap-6 text-xs text-[#64748B]">
          <span className="hover:text-white transition-colors cursor-pointer">Privacy Policies</span>
          <span className="hover:text-white transition-colors cursor-pointer">Terms of Service</span>
          <span className="ml-auto">© 2026 EduTrack Inc. All rights reserved.</span>
        </div>
      </div>

      {/* Right Side: Form Shell (45% width) */}
      <div className="w-full md:w-[45%] bg-white flex flex-col justify-center items-center p-8 md:p-16 min-h-[600px] md:min-h-screen">
        <div className="w-full max-w-md space-y-8 animate-fadeIn">
          
          {/* Header Greetings */}
          <div>
            <h2 className="text-3xl font-bold text-[#0F172A] tracking-tight">Welcome back</h2>
            <p className="text-[#64748B] mt-2 text-sm">Please sign in to access your administrative, teacher or student dashboards portal.</p>
          </div>

          {/* Feedback Errors dismissible */}
          {error && (
            <div className="p-4 bg-red-50 border border-red-100 rounded-lg text-red-600 text-sm flex items-start gap-3 relative animate-shake">
              <Lucide.AlertCircle className="w-5 h-5 flex-shrink-0 mt-0.5" />
              <div className="flex-1 font-medium">{error}</div>
              <button onClick={() => setError(null)} className="text-red-400 hover:text-red-600">
                <Lucide.X className="w-4 h-4" />
              </button>
            </div>
          )}

          {/* Interactive Form */}
          <form onSubmit={handleSubmit} className="space-y-6">
            <div>
              <label htmlFor="email_addr" className="block text-xs font-bold text-[#64748B] uppercase tracking-wider mb-2">
                Email Address
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Lucide.Mail className="w-5 h-5 text-[#94A3B8]" />
                </div>
                <input
                  id="email_addr"
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="Enter your email address"
                  className="w-full pl-10 pr-4 py-2.5 bg-[#F8FAFC] border border-[#E2E8F0] rounded-lg text-sm text-[#0F172A] placeholder-[#94A3B8] focus:outline-none focus:ring-2 focus:ring-[#1D4ED8] focus:border-transparent transition-all"
                  required
                />
              </div>
            </div>

            <div>
              <label htmlFor="password_input" className="block text-xs font-bold text-[#64748B] uppercase tracking-wider mb-2">
                Password
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Lucide.Lock className="w-5 h-5 text-[#94A3B8]" />
                </div>
                <input
                  id="password_input"
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="Password"
                  className="w-full pl-10 pr-10 py-2.5 bg-[#F8FAFC] border border-[#E2E8F0] rounded-lg text-sm text-[#0F172A] placeholder-[#94A3B8] focus:outline-none focus:ring-2 focus:ring-[#1D4ED8] focus:border-transparent transition-all"
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-[#0F172A]"
                >
                  {showPassword ? (
                    <Lucide.EyeOff className="w-5 h-5 text-[#94A3B8]" />
                  ) : (
                    <Lucide.Eye className="w-5 h-5 text-[#94A3B8]" />
                  )}
                </button>
              </div>
            </div>

            {/* Remember & forgot bar */}
            <div className="flex items-center justify-between text-xs pt-1">
              <label className="flex items-center gap-2 text-[#64748B] cursor-pointer">
                <input
                  type="checkbox"
                  className="h-4 w-4 rounded border-[#E2E8F0] text-[#1D4ED8] focus:ring-[#1D4ED8] bg-[#F8FAFC]"
                />
                <span>Remember this computer</span>
              </label>
              <span className="text-[#1D4ED8] font-bold hover:underline cursor-pointer">Forgot passcode?</span>
            </div>

            {/* Blue primary button action */}
            <button
              type="submit"
              className="w-full py-3 px-4 bg-[#1D4ED8] hover:bg-[#1E40AF] text-white rounded-lg text-sm font-semibold tracking-wide transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1D4ED8] shadow-sm transform hover:-translate-y-px active:translate-y-px active:shadow-none"
            >
              Sign In to Dashboard
            </button>
          </form>

          {/* Seed demo credential indicators box (highly functional drawer) */}
          {demoHintOpen && (
            <div className="p-4 bg-[#F1F5F9] border border-[#E2E8F0] rounded-lg space-y-3 relative">
              <button
                onClick={() => setDemoHintOpen(false)}
                className="absolute top-2 right-2 text-gray-400 hover:text-gray-600"
              >
                <Lucide.X className="w-4 h-4" />
              </button>
              <div className="flex items-center gap-2 text-xs font-black text-[#0F172A] tracking-wide uppercase">
                <Lucide.Key className="w-4 h-4 text-[#1D4ED8]" />
                <span>Demo Sandbox Credentials</span>
              </div>
              <p className="text-xs text-[#64748B]">Click any role button below to instantly populate credentials form:</p>
              <div className="grid grid-cols-3 gap-2 pt-1">
                <button
                  type="button"
                  onClick={() => handleQuickLogin('student@edutrack.com', 'student123')}
                  className="py-2 px-1 text-center font-semibold text-xs border border-[#E2E8F0] hover:border-[#1D4ED8] rounded-md transition-colors bg-white hover:bg-blue-50 text-[#1D4ED8] shadow-sm text-ellipsis overflow-hidden whitespace-nowrap"
                >
                  Student Arjun
                </button>
                <button
                  type="button"
                  onClick={() => handleQuickLogin('teacher@edutrack.com', 'teacher123')}
                  className="py-2 px-1 text-center font-semibold text-xs border border-[#E2E8F0] hover:border-[#1D4ED8] rounded-md transition-colors bg-white hover:bg-blue-50 text-emerald-700 shadow-sm text-ellipsis overflow-hidden whitespace-nowrap"
                >
                  Teacher Jenkins
                </button>
                <button
                  type="button"
                  onClick={() => handleQuickLogin('admin@edutrack.com', 'admin123')}
                  className="py-2 px-1 text-center font-semibold text-xs border border-[#E2E8F0] hover:border-[#1D4ED8] rounded-md transition-colors bg-white hover:bg-blue-50 text-amber-700 shadow-sm text-ellipsis overflow-hidden whitespace-nowrap"
                >
                  Admin System
                </button>
              </div>
            </div>
          )}

          {/* Legal / assistance block */}
          <div className="text-center pt-2">
            <p className="text-[#64748B] text-xs">
              Don't have an institutional login? <strong className="text-[#1D4ED8] hover:underline cursor-pointer">Request department admission</strong>
            </p>
          </div>

        </div>
      </div>
    </div>
  );
};
