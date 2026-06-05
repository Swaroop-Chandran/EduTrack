import React, { useState } from 'react';
import { useLms } from '../context/LmsContext';
import * as Lucide from 'lucide-react';

interface SidebarProps {
  currentTab: string;
  setCurrentTab: (tab: string) => void;
  isSidebarExpanded: boolean;
  setIsSidebarExpanded: (expanded: boolean) => void;
}

export const Sidebar: React.FC<SidebarProps> = ({
  currentTab,
  setCurrentTab,
  isSidebarExpanded,
  setIsSidebarExpanded,
}) => {
  const { currentUser, logout } = useLms();

  if (!currentUser) return null;

  // Let's list the navigation structures based on role
  const getNavItems = () => {
    switch (currentUser.role) {
      case 'student':
        return [
          { id: 'dashboard', name: 'Dashboard', icon: Lucide.LayoutDashboard },
          { id: 'courses', name: 'My Courses', icon: Lucide.BookOpen },
          { id: 'assignments', name: 'Assignments', icon: Lucide.ClipboardList },
          { id: 'exams', name: 'Upcoming Exams', icon: Lucide.CalendarClock },
          { id: 'results', name: 'My Results', icon: Lucide.FileBarChart2 },
          { id: 'attendance', name: 'Attendance Record', icon: Lucide.CheckSquare },
          { id: 'placements', name: 'Placement Cell', icon: Lucide.Briefcase },
          { id: 'profile', name: 'My Profile', icon: Lucide.UserSquare2 }
        ];
      case 'teacher':
        return [
          { id: 'dashboard', name: 'Dashboard', icon: Lucide.LayoutDashboard },
          { id: 'courses', name: 'My Courses', icon: Lucide.BookOpen },
          { id: 'assignments', name: 'Assignments', icon: Lucide.ClipboardList },
          { id: 'attendance', name: 'Take Attendance', icon: Lucide.CheckSquare },
          { id: 'exams', name: 'Exams & Results', icon: Lucide.CalendarClock },
          { id: 'analytics', name: 'Course Analytics', icon: Lucide.LineChart },
          { id: 'profile', name: 'Teacher Profile', icon: Lucide.UserSquare2 }
        ];
      case 'admin':
        return [
          { id: 'dashboard', name: 'LMS Insights', icon: Lucide.LayoutDashboard },
          { id: 'students', name: 'Students Registry', icon: Lucide.Users2 },
          { id: 'teachers', name: 'Teachers Registry', icon: Lucide.GraduationCap },
          { id: 'departments', name: 'Departments', icon: Lucide.Building2 },
          { id: 'courses', name: 'Courses Config', icon: Lucide.BookOpen },
          { id: 'placements', name: 'Placement Cell', icon: Lucide.Briefcase },
          { id: 'announcements', name: 'Announcements', icon: Lucide.Megaphone },
          { id: 'analytics', name: 'Campus Analytics', icon: Lucide.Activity }
        ];
      default:
        return [];
    }
  };

  const navItems = getNavItems();

  const getRoleBadgeColor = () => {
    switch (currentUser.role) {
      case 'student': return 'bg-blue-500/20 text-blue-400';
      case 'teacher': return 'bg-emerald-500/20 text-emerald-400';
      case 'admin': return 'bg-amber-500/20 text-amber-500';
      default: return 'bg-gray-500/20 text-gray-400';
    }
  };

  return (
    <aside
      className={`fixed left-0 top-0 h-screen bg-[#0F172A] text-white shadow-xl flex flex-col z-50 transition-all duration-300 ${
        isSidebarExpanded ? 'w-[240px]' : 'w-[68px] sm:w-[68px]'
      }`}
    >
      {/* Header Wordmark branding */}
      <div className="p-6 flex items-center justify-between border-b border-white/10">
        <div className="flex items-center gap-3 overflow-hidden">
          <div className="w-8 h-8 rounded-lg bg-[#1D4ED8] flex items-center justify-center text-white shrink-0 font-black">
            E
          </div>
          {isSidebarExpanded && (
            <div className="flex flex-col">
              <span className="font-bold text-sm tracking-tight text-white leading-tight">EduTrack LMS</span>
              <span className="text-[10px] text-gray-400 uppercase tracking-widest font-semibold mt-0.5">Enterprise</span>
            </div>
          )}
        </div>
        {isSidebarExpanded && (
          <button
            onClick={() => setIsSidebarExpanded(false)}
            className="hidden sm:block text-gray-400 hover:text-white p-1 rounded-md hover:bg-white/5"
          >
            <Lucide.ChevronLeft className="w-4 h-4" />
          </button>
        )}
        {!isSidebarExpanded && (
          <button
            onClick={() => setIsSidebarExpanded(true)}
            className="hidden sm:block text-gray-400 hover:text-white p-1 mt-0.5 rounded-md mx-auto hover:bg-white/5"
          >
            <Lucide.ChevronRight className="w-4 h-4" />
          </button>
        )}
      </div>

      {/* Nav Link Lists container */}
      <nav className="flex-1 py-6 space-y-1 block h-fit overflow-y-auto px-3">
        {navItems.map((item) => {
          const Icon = item.icon;
          const isActive = currentTab === item.id;

          return (
            <button
              key={item.id}
              onClick={() => setCurrentTab(item.id)}
              className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 relative ${
                isActive
                  ? 'bg-[#1D4ED8] text-white font-semibold'
                  : 'text-[#94A3B8] hover:text-white hover:bg-white/5'
              }`}
            >
              <Icon className="w-5 h-5 flex-shrink-0" />
              {isSidebarExpanded && (
                <span className="truncate tracking-wide animate-fadeIn">{item.name}</span>
              )}
              {isActive && !isSidebarExpanded && (
                <div className="absolute right-0 top-1/2 -translate-y-1/2 w-1.5 h-8 bg-white rounded-l-md" />
              )}
            </button>
          );
        })}
      </nav>

      {/* Footer Profile badge & logouts */}
      <div className="p-4 border-t border-white/10 space-y-4 shrink-0 bg-black/10">
        <div className="flex items-center gap-3 overflow-hidden">
          <img
            src={currentUser.avatar}
            alt={currentUser.name}
            className="w-10 h-10 rounded-full border border-white/10 object-cover shrink-0 bg-white/20"
          />
          {isSidebarExpanded && (
            <div className="flex flex-col min-w-0 flex-1">
              <span className="font-semibold text-xs text-white truncate leading-tight">
                {currentUser.name}
              </span>
              <span className={`text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-full mt-1 w-max ${getRoleBadgeColor()}`}>
                {currentUser.role}
              </span>
            </div>
          )}
        </div>

        {/* Action controls */}
        {isSidebarExpanded ? (
          <button
            onClick={logout}
            className="w-full flex items-center justify-center gap-2 px-3 py-2 border border-white/10 hover:border-red-500/40 text-[#94A3B8] hover:text-red-500 rounded-lg text-xs font-semibold bg-white/5 hover:bg-red-500/5 transition-all cursor-pointer"
          >
            <Lucide.LogOut className="w-4 h-4" />
            <span>Sign Out Session</span>
          </button>
        ) : (
          <button
            onClick={logout}
            title="Sign Out Session"
            className="w-full flex items-center justify-center py-2 text-[#94A3B8] hover:text-red-500 rounded-lg bg-white/5 hover:bg-red-500/5 transition-all cursor-pointer"
          >
            <Lucide.LogOut className="w-4 h-4" />
          </button>
        )}
      </div>
    </aside>
  );
};
