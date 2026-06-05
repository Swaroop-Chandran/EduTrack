import React, { useState, useEffect } from 'react';
import { LmsProvider, useLms } from './context/LmsContext';
import { Login } from './components/Login';
import { Sidebar } from './components/Sidebar';
import { Header } from './components/Header';
import { StudentDashboard } from './pages/StudentDashboard';
import { TeacherDashboard } from './pages/TeacherDashboard';
import { AdminDashboard } from './pages/AdminDashboard';

const AppContent: React.FC = () => {
  const { currentUser } = useLms();
  const [currentTab, setCurrentTab] = useState('dashboard');
  const [isSidebarExpanded, setIsSidebarExpanded] = useState(true);

  // Auto handle sidebar expansion on mobile views
  useEffect(() => {
    const handleResize = () => {
      if (window.innerWidth < 1024) {
        setIsSidebarExpanded(false);
      } else {
        setIsSidebarExpanded(true);
      }
    };
    window.addEventListener('resize', handleResize);
    handleResize(); // trigger initial layout sizing
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  // Reset tab index on active login role switch
  useEffect(() => {
    setCurrentTab('dashboard');
  }, [currentUser?.role]);

  if (!currentUser) {
    return <Login />;
  }

  // Map view titles for header display
  const getPageTitle = () => {
    const titlesMap: { [key: string]: string } = {
      dashboard: currentUser.role === 'admin' ? 'Campus LMS Dashboard Insights' : 'My Academic Terminal Dashboard',
      courses: currentUser.role === 'student' ? 'My Enrolled Curricula Courses' : 'Instructing Program schedules',
      assignments: currentUser.role === 'student' ? 'Assignments & Homework tasks' : 'Issued homework deliverables',
      submit_assignment: 'Upload Assignment deliverables',
      exams: currentUser.role === 'student' ? 'Upcoming Exam Schedules' : 'Campus Exam and results controller',
      results: 'Term Results transcripts details',
      attendance: currentUser.role === 'student' ? 'Live turnout records' : 'Mark active class roll-call',
      placements: 'Strategic Placement Cell recruitment',
      profile: 'My Institutional Profile details',
      students: 'Students Admissions and Roll configuration',
      teachers: 'Faculty Professor and Instructor schedules',
      departments: 'Academic bodies & sectors settings',
      announcements: 'Push updates broadcaster',
      analytics: 'Cumulative Campus metrics & score charts',
      settings: 'Institutional Settings & SQL Backups'
    };
    return titlesMap[currentTab] || 'EduTrack LMS Navigation Portal';
  };

  const renderActiveDashboard = () => {
    switch (currentUser.role) {
      case 'student':
        return <StudentDashboard currentTab={currentTab} setCurrentTab={setCurrentTab} />;
      case 'teacher':
        return <TeacherDashboard currentTab={currentTab} setCurrentTab={setCurrentTab} />;
      case 'admin':
        return <AdminDashboard currentTab={currentTab} setCurrentTab={setCurrentTab} />;
      default:
        return (
          <div className="flex items-center justify-center h-full p-12 text-center select-none text-slate-400">
            Unknown user authentication index logs.
          </div>
        );
    }
  };

  return (
    <div className="min-h-screen bg-[#F8FAFC] text-[#0F172A] font-sans flex relative overflow-hidden">
      {/* Dynamic Left Sidebar Deck */}
      <Sidebar
        currentTab={currentTab}
        setCurrentTab={setCurrentTab}
        isSidebarExpanded={isSidebarExpanded}
        setIsSidebarExpanded={setIsSidebarExpanded}
      />

      {/* Main viewport Container */}
      <div
        className="flex-1 flex flex-col min-h-screen transition-all duration-300 w-full"
        style={{
          paddingLeft: isSidebarExpanded ? '240px' : '68px',
          paddingTop: '56px' // h-14 offset for fixed top navbar Header
        }}
      >
        {/* Dynamic Nav Header Bar */}
        <Header
          pageTitle={getPageTitle()}
          isSidebarExpanded={isSidebarExpanded}
          setIsSidebarExpanded={setIsSidebarExpanded}
          setCurrentTab={setCurrentTab}
        />

        {/* Dynamic viewport layout component */}
        <main className="flex-1 p-6 lg:p-8 overflow-y-auto block select-text">
          <div className="max-w-7xl mx-auto">
            {renderActiveDashboard()}
          </div>
        </main>
      </div>
    </div>
  );
};

export default function App() {
  return (
    <LmsProvider>
      <AppContent />
    </LmsProvider>
  );
}
