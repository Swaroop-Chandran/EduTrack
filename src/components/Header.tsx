import React, { useState, useRef, useEffect } from 'react';
import { useLms } from '../context/LmsContext';
import * as Lucide from 'lucide-react';

interface HeaderProps {
  pageTitle: string;
  isSidebarExpanded: boolean;
  setIsSidebarExpanded: (expanded: boolean) => void;
  setCurrentTab: (tab: string) => void;
}

export const Header: React.FC<HeaderProps> = ({
  pageTitle,
  isSidebarExpanded,
  setIsSidebarExpanded,
  setCurrentTab,
}) => {
  const { currentUser, logout, notifications, markNotificationRead, clearNotifications } = useLms();
  const [notificationsOpen, setNotificationsOpen] = useState(false);
  const [profileOpen, setProfileOpen] = useState(false);

  const notifRef = useRef<HTMLDivElement>(null);
  const profileRef = useRef<HTMLDivElement>(null);

  // Close dropdowns on clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (notifRef.current && !notifRef.current.contains(event.target as Node)) {
        setNotificationsOpen(false);
      }
      if (profileRef.current && !profileRef.current.contains(event.target as Node)) {
        setProfileOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, []);

  if (!currentUser) return null;

  // Track unread count
  const myNotifications = notifications.filter((n) => n.user_id === currentUser.id);
  const unreadCount = myNotifications.filter((n) => n.is_read === 0).length;

  const getNotifClass = (type: string) => {
    switch (type) {
      case 'success': return 'bg-emerald-50 text-emerald-600';
      case 'warning': return 'bg-amber-50 text-amber-600';
      case 'danger': return 'bg-red-50 text-red-600';
      default: return 'bg-blue-50 text-blue-600';
    }
  };

  const getNotifIcon = (type: string) => {
    switch (type) {
      case 'success': return Lucide.CheckCircle2;
      case 'warning': return Lucide.AlertTriangle;
      case 'danger': return Lucide.AlertCircle;
      default: return Lucide.Info;
    }
  };

  return (
    <header className="fixed top-0 right-0 h-14 bg-white border-b border-[#E2E8F0] flex items-center justify-between px-6 z-40 transition-all duration-300 left-0 md:left-auto"
      style={{ width: '100%', paddingLeft: isSidebarExpanded ? '256px' : '84px' }}
    >
      {/* Search and collapse toggle controls list */}
      <div className="flex items-center gap-4">
        <button
          onClick={() => setIsSidebarExpanded(!isSidebarExpanded)}
          className="p-1.5 rounded-lg text-[#64748B] hover:text-[#0F172A] hover:bg-gray-100 md:hidden"
        >
          <Lucide.Menu className="w-5 h-5" />
        </button>
        <div className="hidden sm:flex items-center gap-2">
          <Lucide.GraduationCap className="w-5 h-5 text-[#1D4ED8]" />
          <h2 className="text-sm font-bold text-[#0F172A] tracking-tight">{pageTitle}</h2>
        </div>
      </div>

      {/* Top right side buttons menu profiles and notifications */}
      <div className="flex items-center gap-3">
        
        {/* Dynamic global Clock Display */}
        <div className="hidden lg:flex items-center gap-1.5 px-3 py-1 bg-[#F1F5F9] rounded-full text-xs text-[#64748B] font-semibold border border-[#E2E8F0]">
          <Lucide.Clock className="w-3.5 h-3.5 text-[#1D4ED8]" />
          <span>UTC 2026-06-05</span>
        </div>

        {/* Notifications Icon and dropdown trigger */}
        <div className="relative" ref={notifRef}>
          <button
            onClick={() => setNotificationsOpen(!notificationsOpen)}
            className="w-10 h-10 rounded-full flex items-center justify-center text-[#64748B] hover:bg-[#F1F5F9] transition-all relative border border-transparent hover:border-[#E2E8F0] cursor-pointer"
          >
            <Lucide.Bell className="w-5 h-5" />
            {unreadCount > 0 && (
              <span className="absolute top-1.5 right-1.5 w-4 h-4 bg-[#EF4444] text-white text-[9px] font-black rounded-full flex items-center justify-center border-2 border-white">
                {unreadCount}
              </span>
            )}
          </button>

          {/* Notifications Dropdown Panel */}
          {notificationsOpen && (
            <div className="absolute right-0 mt-2 w-80 bg-white border border-[#E2E8F0] rounded-xl shadow-xl z-50 overflow-hidden animate-fadeIn select-none">
              <div className="px-4 py-3 bg-[#F8FAFC] border-b border-[#E2E8F0] flex items-center justify-between">
                <span className="font-bold text-xs text-[#0F172A] uppercase tracking-wider">Notifications Center</span>
                {myNotifications.length > 0 && (
                  <button
                    onClick={clearNotifications}
                    className="text-[10px] text-[#1D4ED8] font-bold hover:underline"
                  >
                    Clear All
                  </button>
                )}
              </div>

              {/* Notification rows content */}
              <div className="divide-y divide-[#E2E8F0] max-h-72 overflow-y-auto">
                {myNotifications.length === 0 ? (
                  <div className="p-6 text-center text-xs text-[#64748B] space-y-2">
                    <Lucide.Inbox className="w-8 h-8 text-gray-300 mx-auto" />
                    <p className="font-semibold">All caught up!</p>
                    <p className="text-[10px]">No unread alerts in database queue.</p>
                  </div>
                ) : (
                  myNotifications.map((notif) => {
                    const IconComp = getNotifIcon(notif.type);
                    return (
                      <div
                        key={notif.id}
                        onClick={() => {
                          markNotificationRead(notif.id);
                        }}
                        className={`p-3 text-left transition-colors cursor-pointer hover:bg-gray-50 flex gap-3 items-start relative ${
                          notif.is_read === 0 ? 'bg-blue-50/20' : ''
                        }`}
                      >
                        <div className={`p-1.5 rounded-lg shrink-0 ${getNotifClass(notif.type)}`}>
                          <IconComp className="w-4 h-4" />
                        </div>
                        <div className="flex-1 min-w-0 space-y-1">
                          <p className="text-xs font-semibold text-[#0F172A] truncate">
                            {notif.title}
                          </p>
                          <p className="text-[11px] text-[#64748B] leading-relaxed">
                            {notif.message}
                          </p>
                          <span className="text-[9px] text-[#94A3B8] block">
                            {new Date(notif.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                          </span>
                        </div>
                        {notif.is_read === 0 && (
                          <span className="w-2 h-2 rounded-full bg-[#1D4ED8] mt-2 shrink-0" />
                        )}
                      </div>
                    );
                  })
                )}
              </div>
            </div>
          )}
        </div>

        {/* Profile Avatar and Dropdown trigger */}
        <div className="relative" ref={profileRef}>
          <button
            onClick={() => setProfileOpen(!profileOpen)}
            className="flex items-center gap-2 hover:bg-[#F1F5F9] p-1 pr-3 rounded-full border border-transparent hover:border-[#E2E8F0] transition-colors cursor-pointer shrink-0"
          >
            <img
              src={currentUser.avatar}
              alt={currentUser.name}
              className="w-8 h-8 rounded-full border border-white object-cover bg-gray-100"
            />
            <span className="hidden md:block font-semibold text-xs text-[#0F172A]">
              Account
            </span>
            <Lucide.ChevronDown className="w-3.5 h-3.5 text-[#64748B] hidden md:block" />
          </button>

          {/* Profile Dropdown Panel */}
          {profileOpen && (
            <div className="absolute right-0 mt-2 w-56 bg-white border border-[#E2E8F0] rounded-xl shadow-xl z-50 overflow-hidden animate-fadeIn py-2">
              <div className="px-4 py-3 border-b border-[#E2E8F0]">
                <p className="font-bold text-xs text-[#0F172A] truncate">{currentUser.name}</p>
                <p className="text-[10px] text-[#64748B] truncate mt-0.5">{currentUser.email}</p>
              </div>

              <div className="py-1">
                <button
                  onClick={() => {
                    setCurrentTab('profile');
                    setProfileOpen(false);
                  }}
                  className="w-full text-left px-4 py-2 text-xs font-medium text-[#0F172A] hover:bg-gray-50 flex items-center gap-2"
                >
                  <Lucide.User className="w-4 h-4 text-[#64748B]" />
                  <span>My Profile Details</span>
                </button>
                
                {currentUser.role === 'admin' && (
                  <button
                    onClick={() => {
                      setCurrentTab('settings');
                      setProfileOpen(false);
                    }}
                    className="w-full text-left px-4 py-2 text-xs font-medium text-[#0F172A] hover:bg-gray-50 flex items-center gap-2"
                  >
                    <Lucide.Sliders className="w-4 h-4 text-[#64748B]" />
                    <span>LMS Admin Settings</span>
                  </button>
                )}
              </div>

              <div className="border-t border-[#E2E8F0] pt-1">
                <button
                  onClick={() => {
                    setProfileOpen(false);
                    logout();
                  }}
                  className="w-full text-left px-4 py-2.5 text-xs font-semibold text-[#EF4444] hover:bg-red-50 flex items-center gap-2"
                >
                  <Lucide.LogOut className="w-4 h-4" />
                  <span>Logout Session</span>
                </button>
              </div>
            </div>
          )}
        </div>

      </div>
    </header>
  );
};
