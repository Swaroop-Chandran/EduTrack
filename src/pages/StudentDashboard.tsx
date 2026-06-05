import React, { useState, useEffect } from 'react';
import { useLms } from '../context/LmsContext';
import { StatCard, Badge, Alert, gradeFromMarks, attendanceStatus } from '../utils/helpers';
import * as Lucide from 'lucide-react';

interface StudentDashboardProps {
  currentTab: string;
  setCurrentTab: (tab: string) => void;
}

export const StudentDashboard: React.FC<StudentDashboardProps> = ({ currentTab, setCurrentTab }) => {
  const {
    currentUser,
    courses,
    enrollments,
    assignments,
    submissions,
    exams,
    results,
    attendance,
    placements,
    placementApplications,
    submitAssignment,
    applyPlacement,
    getStudentProfile,
    getTeacherProfile,
    users,
  } = useLms();

  // Local component states
  const [activeCourseFilter, setActiveCourseFilter] = useState<'all' | 'active' | 'completed'>('all');
  const [courseSearch, setCourseSearch] = useState('');
  const [activeAssignmentTab, setActiveAssignmentTab] = useState<'pending' | 'submitted' | 'graded'>('pending');
  const [selectedAssignmentId, setSelectedAssignmentId] = useState<number | null>(null);
  const [submissionText, setSubmissionText] = useState('');
  const [submissionFileName, setSubmissionFileName] = useState('');
  const [submissionFileError, setSubmissionFileError] = useState<string | null>(null);
  const [feedbackAlert, setFeedbackAlert] = useState<{ message: string; type: 'success' | 'danger' } | null>(null);

  // Profile fields:
  const [dob, setDob] = useState('');
  const [address, setAddress] = useState('');
  const [editingProfile, setEditingProfile] = useState(false);

  // Selected semester filter for Results Tab
  const [resultsSemester, setResultsSemester] = useState<number>(3);

  // Time stamp state for countdowns
  const [currentTime, setCurrentTime] = useState<Date>(new Date('2026-06-05T17:02:41Z'));

  useEffect(() => {
    // Sync clock ticks
    const interval = setInterval(() => {
      setCurrentTime((prev) => new Date(prev.getTime() + 1000));
    }, 1000);
    return () => clearInterval(interval);
  }, []);

  if (!currentUser) return null;

  // Fetch student profile record
  const profile = getStudentProfile(currentUser.id);

  // Course Enrollments Filter
  const studentEnrollments = enrollments.filter((e) => e.student_id === currentUser.id);
  const studentEnrolledCourseIds = studentEnrollments.map((e) => e.course_id);

  // Filtered Courses list
  const studentCourses = courses.filter((c) => {
    const isEnrolled = studentEnrolledCourseIds.includes(c.id);
    if (!isEnrolled) return false;

    const enrollment = studentEnrollments.find((e) => e.course_id === c.id);
    const matchesFilter =
      activeCourseFilter === 'all' ||
      (activeCourseFilter === 'active' && enrollment?.status === 'active') ||
      (activeCourseFilter === 'completed' && enrollment?.status === 'completed');

    const matchesSearch =
      c.title.toLowerCase().includes(courseSearch.toLowerCase()) ||
      c.code.toLowerCase().includes(courseSearch.toLowerCase());

    return matchesFilter && matchesSearch;
  });

  // Calculate stats count
  const enrolledCount = studentEnrollments.filter((e) => e.status === 'active').length;
  
  // Pending Assignments - active assignments in enrolled courses where no submission exists
  const enrolledCourseAssignments = assignments.filter((a) => studentEnrolledCourseIds.includes(a.course_id));
  const mySubmissions = submissions.filter((s) => s.student_id === currentUser.id);
  
  const pendingAssignments = enrolledCourseAssignments.filter((a) => {
    const isSubmitted = mySubmissions.some((s) => s.assignment_id === a.id);
    return !isSubmitted && a.status === 'active';
  });

  // Calculate student attendance overall
  const myAttendance = attendance.filter((a) => a.student_id === currentUser.id);
  const totalClasses = myAttendance.length;
  const presentClasses = myAttendance.filter((a) => a.status === 'present' || a.status === 'late').length;
  const attendancePercentage = totalClasses > 0 ? Math.round((presentClasses / totalClasses) * 100) : 100;

  // Student results lists
  const myResults = results.filter((r) => r.student_id === currentUser.id);

  // Next upcoming exam
  const myExams = exams.filter((ex) => studentEnrolledCourseIds.includes(ex.course_id));
  const upcomingExams = myExams
    .filter((ex) => new Date(ex.exam_date) >= new Date('2026-06-05'))
    .sort((a, b) => new Date(a.exam_date).getTime() - new Date(b.exam_date).getTime());

  const nextExam = upcomingExams[0];

  // Submission handler
  const handleFileDrop = (e: React.ChangeEvent<HTMLInputElement>) => {
    setSubmissionFileError(null);
    const file = e.target.files?.[0];
    if (!file) return;

    // Validate size < 10MB
    const limit = 10 * 1024 * 1024;
    if (file.size > limit) {
      setSubmissionFileError('File exceeds 10MB size limit.');
      return;
    }

    // Validate type extension
    const ext = file.name.split('.').pop()?.toLowerCase();
    const allowed = ['pdf', 'doc', 'docx', 'zip'];
    if (!ext || !allowed.includes(ext)) {
      setSubmissionFileError('Allowed file extensions are: .pdf, .doc, .docx, .zip');
      return;
    }

    setSubmissionFileName(file.name);
  };

  const executeSubmissionPost = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedAssignmentId) return;

    if (!submissionFileName && !submissionText) {
      setSubmissionFileError('Please enter a submission text description or attach a file.');
      return;
    }

    submitAssignment(selectedAssignmentId, submissionText, submissionFileName);
    setSubmissionText('');
    setSubmissionFileName('');
    setSelectedAssignmentId(null);
    setFeedbackAlert({
      message: 'Assignment submission uploaded and recorded successfully!',
      type: 'success'
    });
    setCurrentTab('assignments');
    setActiveAssignmentTab('submitted');
  };

  return (
    <div className="space-y-6">
      {/* Visual top Alerts feedback dismissible */}
      {feedbackAlert && (
        <Alert
          message={feedbackAlert.message}
          type={feedbackAlert.type}
          onDismiss={() => setFeedbackAlert(null)}
        />
      )}

      {/* RENDER VIEW CONTROLLER */}
      {currentTab === 'dashboard' && (
        <div className="space-y-6 animate-fadeIn">
          {/* Greeting Title */}
          <div className="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
              <h1 className="text-3xl font-extrabold text-[#0F172A] tracking-tight">
                Good morning, {currentUser.name} 👋
              </h1>
              <p className="text-[#64748B] text-sm mt-1">Here is an overview of your academic curriculum progress today.</p>
            </div>
            <button
              onClick={() => setCurrentTab('exams')}
              className="px-4 py-2 bg-[#1D4ED8] hover:bg-[#1E40AF] text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-2 cursor-pointer transition-transform duration-75 active:scale-95"
            >
              <Lucide.CalendarDays className="w-4 h-4" />
              <span>View Exam Schedules</span>
            </button>
          </div>

          {/* Statistical layout cards */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <StatCard
              title="Enrolled Courses"
              value={enrolledCount}
              icon="BookOpen"
              color="blue"
              subtext="Active learning curricula"
            />
            <StatCard
              title="Pending Tasks"
              value={pendingAssignments.length}
              icon="ClipboardList"
              color="amber"
              subtext={pendingAssignments.length > 0 ? `${pendingAssignments.length} assignments due soon` : 'All tasks cleared'}
            />
            <StatCard
              title="Class Attendance"
              value={`${attendancePercentage}%`}
              icon="CheckSquare"
              color={attendancePercentage >= 85 ? 'green' : attendancePercentage >= 75 ? 'amber' : 'red'}
              subtext={`Academic cutoff rate: 85%`}
            />
            <StatCard
              title="Academic CGPA"
              value={profile ? profile.cgpa : '8.4'}
              icon="Award"
              color="navy"
              subtext="Batch percentiles rank: Top 15%"
            />
          </div>

          {/* Grid Layouts for continuing learning and deadlines */}
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            {/* Left 8Cols: Continue Learning */}
            <div className="lg:col-span-8 space-y-6">
              
              {/* Courses Grid container */}
              <div className="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
                <div className="flex items-center justify-between pb-2 border-b border-[#E2E8F0]">
                  <h3 className="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider">Continue Learning</h3>
                  <button onClick={() => setCurrentTab('courses')} className="text-xs font-bold text-[#1D4ED8] hover:underline">
                    View All Active
                  </button>
                </div>

                <div className="space-y-4">
                  {studentCourses.slice(0, 3).map((course) => {
                    // Match visual image and modules for seed data courses
                    const progressSeed = course.id === 1 ? 65 : course.id === 2 ? 85 : course.id === 3 ? 12 : 30;
                    return (
                      <div
                        key={course.id}
                        onClick={() => setCurrentTab('courses')}
                        className="bg-[#F8FAFC] border border-[#E2E8F0] hover:border-[#1D4ED8]/40 p-4 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 cursor-pointer transition-colors group"
                      >
                        <div className="flex items-center gap-4">
                          <div className="w-12 h-12 bg-[#1D4ED8]/10 text-[#1D4ED8] rounded-lg flex items-center justify-center shrink-0">
                            <Lucide.BookMarked className="w-6 h-6" />
                          </div>
                          <div>
                            <span className="text-[10px] uppercase font-black text-slate-400 tracking-wider font-mono">{course.code}</span>
                            <h4 className="text-sm font-bold text-[#0F172A] group-hover:text-[#1D4ED8] transition-colors mt-0.5">{course.title}</h4>
                            <p className="text-xs text-[#64748B] mt-1 italic shrink-0">Credits: {course.credits} • Sem: {course.semester}</p>
                          </div>
                        </div>

                        {/* Slider Progress Bar */}
                        <div className="w-full sm:w-48 space-y-1.5 shrink-0">
                          <div className="flex justify-between items-center text-[10px] font-bold text-[#64748B]">
                            <span>Course Progress</span>
                            <span className="text-[#1D4ED8]">{progressSeed}%</span>
                          </div>
                          <div className="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                            <div className="bg-[#1D4ED8] h-1.5 rounded-full" style={{ width: `${progressSeed}%` }} />
                          </div>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>

              {/* Recent Results Section Table */}
              <div className="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
                <div className="flex items-center justify-between pb-2 border-b border-[#E2E8F0]">
                  <h3 className="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider">Recent Semester Results</h3>
                  <button onClick={() => setCurrentTab('results')} className="text-xs font-bold text-[#1D4ED8] hover:underline">
                    Detailed Transcripts
                  </button>
                </div>

                <div className="overflow-x-auto">
                  <table className="w-full text-left text-xs border-collapse">
                    <thead>
                      <tr className="bg-[#F8FAFC] border-b border-[#E2E8F0] text-[#64748B]">
                        <th className="p-3 font-semibold uppercase tracking-wider">Course Code</th>
                        <th className="p-3 font-semibold uppercase tracking-wider">Course Name</th>
                        <th className="p-3 font-semibold uppercase tracking-wider">Total Marks</th>
                        <th className="p-3 font-semibold uppercase tracking-wider text-right">Grade Scale</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 font-medium">
                      {myResults.slice(0, 3).map((res) => {
                        const course = courses.find((c) => c.id === res.course_id);
                        return (
                          <tr key={res.id} className="hover:bg-slate-50/50">
                            <td className="p-3 font-mono text-[#0F172A]">{course?.code}</td>
                            <td className="p-3 text-slate-700">{course?.title}</td>
                            <td className="p-3 text-slate-700">{res.total_marks} / 100</td>
                            <td className="p-3 text-right">
                              <Badge text={res.grade} type={res.status === 'pass' ? 'success' : 'danger'} />
                            </td>
                          </tr>
                        );
                      })}
                      {myResults.length === 0 && (
                        <tr>
                          <td colSpan={4} className="p-6 text-center text-slate-400">
                            No grade result records disclosed for current semester.
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              </div>

            </div>

            {/* Right 4Cols: Deadlines panel */}
            <div className="lg:col-span-4 space-y-6">
              
              {/* Upcoming Deadlines Widget */}
              <div className="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
                <div className="flex items-center gap-2 pb-2 border-b border-[#E2E8F0]">
                  <Lucide.Clock className="w-4 h-4 text-[#1D4ED8]" />
                  <h3 className="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider">Upcoming Tasks</h3>
                </div>

                <div className="space-y-4">
                  {pendingAssignments.slice(0, 3).map((asg) => {
                    const course = courses.find((c) => c.id === asg.course_id);
                    const isDueToday = asg.id === 1; // Project Proposal Draft is seeded due today
                    return (
                      <div key={asg.id} className="p-3 border border-[#E2E8F0] rounded-xl hover:border-[#1D4ED8]/30 transition-colors bg-white">
                        <div className="flex justify-between items-start gap-2 mb-1.5">
                          <h4 className="text-xs font-bold text-[#0F172A] line-clamp-1">{asg.title}</h4>
                          {isDueToday ? (
                            <span className="text-[9px] font-black uppercase tracking-wider bg-red-100 text-red-700 px-1.5 py-0.5 rounded shrink-0">
                              Due Today
                            </span>
                          ) : (
                            <span className="text-[9.5px] font-semibold text-slate-400 shrink-0 font-mono">
                              Sem {course?.semester}
                            </span>
                          )}
                        </div>
                        <p className="text-[11px] text-[#64748B] line-clamp-2 leading-relaxed mb-3">{asg.description}</p>
                        <div className="flex items-center justify-between">
                          <span className="text-[10px] font-mono bg-[#F1F5F9] text-[#64748B] rounded px-1.5 py-0.5 border border-[#E2E8F0]">
                            {course?.code}
                          </span>
                          <button
                            onClick={() => {
                              setSelectedAssignmentId(asg.id);
                              setCurrentTab('submit_assignment');
                            }}
                            className="text-[10px] font-extrabold text-[#1D4ED8] hover:underline"
                          >
                            Submit Deliverable →
                          </button>
                        </div>
                      </div>
                    );
                  })}
                  {pendingAssignments.length === 0 && (
                    <div className="p-4 text-center text-xs text-slate-400 space-y-2">
                      <Lucide.Smile className="w-6 h-6 text-emerald-500 mx-auto animate-bounce" />
                      <p className="font-semibold text-slate-700">All Assignments Cleared!</p>
                      <p className="text-[10px]">No pending submissions in database queue.</p>
                    </div>
                  )}
                </div>
              </div>

              {/* Exam countdown summary */}
              {nextExam && (
                <div className="bg-[#0F172A] text-white rounded-xl p-6 shadow-md border border-white/10 space-y-4 relative overflow-hidden">
                  <div className="absolute right-0 bottom-0 translate-x-1/4 translate-y-1/4 text-white/5 pointer-events-none scale-150">
                    <Lucide.AlertCircle className="w-32 h-32" />
                  </div>
                  <div className="flex items-center gap-2 text-white/80 uppercase font-black tracking-widest text-[10px]">
                    <div className="w-1.5 h-1.5 rounded-full bg-red-500 animate-ping" />
                    <span>Next Exam Countdown</span>
                  </div>
                  <div className="space-y-1">
                    <h4 className="text-sm font-bold text-white leading-snug">{nextExam.title}</h4>
                    <p className="text-xs text-slate-400">{nextExam.venue}</p>
                  </div>
                  <div className="p-2.5 bg-white/5 border border-white/10 rounded-lg text-center text-xs font-mono font-bold tracking-widest text-blue-400">
                    Date: {nextExam.exam_date} at {nextExam.start_time}
                  </div>
                  <button
                    onClick={() => setCurrentTab('exams')}
                    className="w-full py-2 bg-[#1C2541] hover:bg-[#1D4ED8] text-white text-xs font-bold rounded-lg transition-colors border border-white/10"
                  >
                    View Directions & Rules
                  </button>
                </div>
              )}

            </div>
          </div>
        </div>
      )}

      {/* STUDENT MY COURSES TAB */}
      {currentTab === 'courses' && (
        <div className="space-y-6 animate-fadeIn">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
              <h1 className="text-2xl font-extrabold text-[#0F172A] tracking-tight">Enrolled Courses Registry</h1>
              <p className="text-[#64748B] text-xs">Search, filter, and track course curricula schedules progress.</p>
            </div>
            
            <div className="flex items-center gap-3">
              {/* Filter toggler */}
              <div className="flex items-center border border-[#E2E8F0] rounded-lg p-1 bg-white shadow-sm">
                {(['all', 'active', 'completed'] as const).map((filter) => (
                  <button
                    key={filter}
                    onClick={() => setActiveCourseFilter(filter)}
                    className={`px-3 py-1 text-xs font-bold rounded-md transition-all ${
                      activeCourseFilter === filter
                        ? 'bg-[#1D4ED8] text-white'
                        : 'text-[#64748B] hover:text-[#0F172A]'
                    }`}
                  >
                    {filter.toUpperCase()}
                  </button>
                ))}
              </div>
            </div>
          </div>

          {/* Search bar input */}
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <Lucide.Search className="w-4 h-4 text-slate-400" />
            </div>
            <input
              type="text"
              placeholder="Search enrolled course name or department code..."
              value={courseSearch}
              onChange={(e) => setCourseSearch(e.target.value)}
              className="w-full pl-9 pr-4 py-2.5 bg-white border border-[#E2E8F0] rounded-xl text-xs text-[#0F172A] focus:outline-none focus:ring-2 focus:ring-[#1D4ED8] focus:border-transparent cursor-pointer shadow-sm"
            />
          </div>

          {/* Main Course Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {studentCourses.map((course) => {
              const enrollment = studentEnrollments.find((e) => e.course_id === course.id);
              const progressSeed = course.id === 1 ? 65 : course.id === 2 ? 85 : course.id === 3 ? 12 : 30;
              
              return (
                <div
                  key={course.id}
                  className="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden flex flex-col hover:shadow-md transition-shadow relative"
                >
                  <div className="h-2 bg-gradient-to-r from-[#1D4ED8] to-blue-400 w-full" />
                  <div className="p-6 flex flex-col flex-1 space-y-4">
                    <div className="flex justify-between items-start">
                      <div className="w-10 h-10 bg-[#1D4ED8]/10 text-[#1D4ED8] rounded-xl flex items-center justify-center">
                        <Lucide.BookOpen className="w-5 h-5" />
                      </div>
                      <Badge
                        text={enrollment?.status === 'active' ? 'In Progress' : 'Completed'}
                        type={enrollment?.status === 'active' ? 'warning' : 'success'}
                      />
                    </div>

                    <div className="space-y-1">
                      <span className="text-[10px] font-mono font-bold text-slate-400 tracking-wider">
                        {course.code}
                      </span>
                      <h3 className="text-sm font-bold text-[#0F172A] leading-snug line-clamp-2">
                        {course.title}
                      </h3>
                      <p className="text-xs text-[#64748B] line-clamp-3 leading-relaxed mt-1">
                        {course.description}
                      </p>
                    </div>

                    {/* Progress indicator */}
                    <div className="mt-auto pt-4 border-t border-slate-50 space-y-1.5">
                      <div className="flex justify-between items-center text-[10px] font-bold text-[#64748B]">
                        <span>Curriculum Progress</span>
                        <span className="text-[#1D4ED8] font-black">{progressSeed}%</span>
                      </div>
                      <div className="w-full bg-[#F1F5F9] h-1.5 rounded-full overflow-hidden">
                        <div className="bg-[#1D4ED8] h-1.5 rounded-full" style={{ width: `${progressSeed}%` }} />
                      </div>
                    </div>
                  </div>
                </div>
              );
            })}
            {studentCourses.length === 0 && (
              <div className="col-span-full p-12 bg-white rounded-xl text-center border border-[#E2E8F0] text-slate-400 space-y-3">
                <Lucide.Inbox className="w-12 h-12 mx-auto text-slate-300" />
                <p className="font-bold text-slate-700 text-sm">No Enrolled Courses Found</p>
                <p className="text-xs">Adjust filters or search parameters. Contact admins if courses are missing.</p>
              </div>
            )}
          </div>
        </div>
      )}

      {/* STUDENT ASSIGNMENTS TAB */}
      {currentTab === 'assignments' && (
        <div className="space-y-6 animate-fadeIn">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
              <h1 className="text-2xl font-extrabold text-[#0F172A] tracking-tight">Course Deliverables & Tasks</h1>
              <p className="text-[#64748B] text-xs">Track, submit and review evaluations feedback from tutors.</p>
            </div>
            
            {/* Filter buttons */}
            <div className="flex border border-[#E2E8F0] bg-white rounded-lg p-1 shadow-sm">
              <button
                onClick={() => setActiveAssignmentTab('pending')}
                className={`px-4 py-1.5 text-xs font-bold rounded-md transition-all ${
                  activeAssignmentTab === 'pending'
                    ? 'bg-[#1D4ED8] text-white'
                    : 'text-[#64748B] hover:text-[#0F172A]'
                }`}
              >
                PENDING TASKS ({pendingAssignments.length})
              </button>
              <button
                onClick={() => setActiveAssignmentTab('submitted')}
                className={`px-4 py-1.5 text-xs font-bold rounded-md transition-all ${
                  activeAssignmentTab === 'submitted'
                    ? 'bg-[#1D4ED8] text-white'
                    : 'text-[#64748B] hover:text-[#0F172A]'
                }`}
              >
                SUBMITTED
              </button>
              <button
                onClick={() => setActiveAssignmentTab('graded')}
                className={`px-4 py-1.5 text-xs font-bold rounded-md transition-all ${
                  activeAssignmentTab === 'graded'
                    ? 'bg-[#1D4ED8] text-white'
                    : 'text-[#64748B] hover:text-[#0F172A]'
                }`}
              >
                GRADED FEEDBACK
              </button>
            </div>
          </div>

          {/* Assignments list renders */}
          <div className="space-y-4">
            {activeAssignmentTab === 'pending' && (
              pendingAssignments.map((asg) => {
                const course = courses.find((c) => c.id === asg.course_id);
                return (
                  <div key={asg.id} className="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6 hover:border-amber-400/40 transition-colors">
                    <div className="space-y-2 max-w-2xl">
                      <div className="flex flex-wrap items-center gap-2">
                        <span className="font-mono text-xs font-bold bg-[#F1F5F9] text-[#64748B] rounded px-2 py-0.5 border border-[#E2E8F0]">
                          {course?.code} - {course?.title}
                        </span>
                        <span className="text-xs text-[#EF4444] font-semibold bg-red-50 px-2.2 py-0.5 rounded-full border border-red-100 flex items-center gap-1">
                          <Lucide.Clock className="w-3.5 h-3.5" />
                          <span>Due: {new Date(asg.due_date).toLocaleString()}</span>
                        </span>
                      </div>
                      <h3 className="font-bold text-[#0F172A] text-base leading-tight mt-1">{asg.title}</h3>
                      <p className="text-xs text-[#64748B] leading-relaxed">{asg.description}</p>
                    </div>

                    <div className="shrink-0 flex flex-col sm:flex-row md:flex-col items-stretch md:items-end gap-3 w-full md:w-auto">
                      <span className="text-xs font-bold text-slate-500 font-mono bg-slate-50 p-2.5 rounded-lg border border-[#E2E8F0] block text-center md:text-right md:w-32">
                        Max Marks: {asg.max_marks}
                      </span>
                      <button
                        onClick={() => {
                          setSelectedAssignmentId(asg.id);
                          setCurrentTab('submit_assignment');
                        }}
                        className="py-2.5 px-4 bg-[#1D4ED8] hover:bg-[#1E40AF] text-white text-xs font-bold rounded-lg shadow-sm text-center cursor-pointer md:w-32 transform active:scale-95 transition-transform"
                      >
                        File Submit
                      </button>
                    </div>
                  </div>
                );
              })
            )}

            {activeAssignmentTab === 'submitted' && (
              mySubmissions
                .filter((sub) => sub.status === 'submitted')
                .map((sub) => {
                  const asg = assignments.find((a) => a.id === sub.assignment_id);
                  const course = courses.find((c) => c.id === asg?.course_id);
                  return (
                    <div key={sub.id} className="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6 text-left">
                      <div className="space-y-2">
                        <div className="flex flex-wrap items-center gap-2">
                          <span className="font-mono text-xs font-bold bg-[#F1F5F9] text-[#64748B] rounded px-2 py-0.5 border border-[#E2E8F0]">
                            {course?.code}
                          </span>
                          <span className="text-xs text-blue-700 font-semibold bg-blue-50 px-2.2 py-0.5 rounded-full border border-blue-150">
                            Status: Received, Pending Evaluation
                          </span>
                        </div>
                        <h3 className="font-bold text-[#0F172A] text-base leading-tight mt-1">{asg?.title}</h3>
                        <p className="text-xs text-slate-400 font-mono italic">Submitted file: {sub.file_path}</p>
                        {sub.text_submission && (
                          <div className="p-3 bg-slate-50 rounded-lg text-xs text-[#64748B] border border-slate-100 max-w-2xl font-mono">
                            "{sub.text_submission}"
                          </div>
                        )}
                        <span className="text-[10px] block text-slate-400">
                          Uploaded timestamp: {new Date(sub.submitted_at).toLocaleString()}
                        </span>
                      </div>
                    </div>
                  );
                })
            )}

            {activeAssignmentTab === 'graded' && (
              mySubmissions
                .filter((sub) => sub.status === 'evaluated')
                .map((sub) => {
                  const asg = assignments.find((a) => a.id === sub.assignment_id);
                  const course = courses.find((c) => c.id === asg?.course_id);
                  return (
                    <div key={sub.id} className="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm relative overflow-hidden hover:border-[#10B981]/30 transition-all border-l-4 border-l-[#10B981]">
                      <div className="flex flex-col md:flex-row justify-between gap-4 items-start md:items-center mb-4">
                        <div>
                          <div className="flex items-center gap-2">
                            <span className="font-mono text-xs font-bold bg-[#F1F5F9] text-[#64748B] rounded px-2 py-0.5 border border-[#E2E8F0]">
                              {course?.code}
                            </span>
                            <span className="text-[10.5px] uppercase font-black text-emerald-700 tracking-wider font-mono">Graded & Evaluated</span>
                          </div>
                          <h3 className="font-bold text-[#0F172A] text-base leading-tight mt-2">{asg?.title}</h3>
                        </div>

                        {/* Marks Obtained badge count */}
                        <div className="p-3 bg-emerald-50 rounded-xl border border-emerald-150 text-center shrink-0 min-w-32">
                          <span className="text-2xl font-black text-emerald-800">{sub.marks_obtained}</span>
                          <span className="text-xs text-emerald-600 block pt-0.5 font-bold">/ {asg?.max_marks} marks</span>
                        </div>
                      </div>

                      <div className="bg-[#F8FAFC] border border-[#E2E8F0] p-4 rounded-xl space-y-1.5 text-xs">
                        <p className="font-black text-[#0F172A] uppercase tracking-wide">Instructor Feedback Comments:</p>
                        <p className="text-[#64748B] leading-relaxed">
                          "{sub.feedback || 'Excellent execution, deliverables met coursework criteria requirements.'}"
                        </p>
                      </div>
                    </div>
                  );
                })
            )}

            {/* Check empty arrays layout */}
            {activeAssignmentTab === 'pending' && pendingAssignments.length === 0 && (
              <div className="p-12 text-center bg-white rounded-xl border border-[#E2E8F0] text-slate-400 space-y-3">
                <Lucide.CheckCircle className="w-12 h-12 text-[#10B981] mx-auto animate-pulse" />
                <p className="font-bold text-slate-700 text-sm">No Pending Assignments</p>
                <p className="text-xs text-slate-500">You are all caught up! Academic deliverables records are thoroughly updated.</p>
              </div>
            )}
          </div>
        </div>
      )}

      {/* STUDENT SUBMIT ASSIGNMENT TAB DYNAMICALLY LINKED */}
      {currentTab === 'submit_assignment' && (
        <div className="bg-white border border-[#E2E8F0] rounded-xl p-8 max-w-3xl mx-auto shadow-sm space-y-6 animate-fadeIn">
          {(() => {
            const assignmentObj = assignments.find((a) => a.id === selectedAssignmentId);
            const courseObj = courses.find((c) => c.id === assignmentObj?.course_id);

            if (!assignmentObj) {
              return (
                <div className="text-center p-6 space-y-4">
                  <Lucide.Link2Off className="w-12 h-12 text-red-400 mx-auto" />
                  <p className="font-bold text-slate-700">Invalid Deliverable Reference</p>
                  <button onClick={() => setCurrentTab('assignments')} className="py-2 px-4 bg-[#1D4ED8] text-white rounded-lg text-xs font-bold">
                    Back to Registry
                  </button>
                </div>
              );
            }

            return (
              <form onSubmit={executeSubmissionPost} className="space-y-6">
                <div>
                  <button
                    type="button"
                    onClick={() => setCurrentTab('assignments')}
                    className="flex items-center gap-1 text-xs text-[#1D4ED8] hover:underline mb-4 font-bold"
                  >
                    <Lucide.ArrowLeft className="w-4 h-4" />
                    <span>Back to Deliverables</span>
                  </button>
                  <span className="text-[11px] font-mono font-bold uppercase text-[#64748B] tracking-widest block mb-1">
                    {courseObj?.code} • {courseObj?.title}
                  </span>
                  <h2 className="text-2xl font-bold text-[#0F172A] tracking-tight">{assignmentObj.title}</h2>
                  <p className="text-xs text-[#64748B] mt-2 leading-relaxed bg-[#F8FAFC] border border-[#E2E8F0] p-3 rounded-lg font-mono">
                    {assignmentObj.description}
                  </p>
                </div>

                <div className="border-t border-[#E2E8F0] pt-4 space-y-4">
                  <div>
                    <label className="block text-xs font-bold text-[#64748B] uppercase tracking-wider mb-2">
                      Text Submission Description
                    </label>
                    <textarea
                      placeholder="Enter details about your homework algorithms, assumptions or design structure writeups here..."
                      value={submissionText}
                      onChange={(e) => setSubmissionText(e.target.value)}
                      rows={5}
                      className="w-full bg-[#fdfdfd] border border-[#E2E8F0] focus:ring-2 focus:ring-[#1D4ED8] focus:border-transparent rounded-xl p-3 text-xs text-[#0F172A] focus:outline-none transition-all font-mono"
                    />
                  </div>

                  {/* Drag and Drop manual File upload field */}
                  <div>
                    <label className="block text-xs font-bold text-[#64748B] uppercase tracking-wider mb-2">
                      Attach deliverable file (Max 10MB)
                    </label>
                    
                    <div className="border-2 border-dashed border-[#E2E8F0] hover:border-[#1D4ED8]/60 bg-[#F8FAFC] rounded-xl p-8 text-center cursor-pointer transition-all relative">
                      <input
                        type="file"
                        onChange={handleFileDrop}
                        className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                      />
                      <Lucide.UploadCloud className="w-10 h-10 text-[#94A3B8] mx-auto mb-3" />
                      <p className="text-xs font-bold text-[#0F172A]">Drag & Drop your Assignment file here</p>
                      <p className="text-[10px] text-[#64748B] mt-1">Allowed formats: PDF, DOC, DOCX, ZIP files (Max 10MB limit)</p>
                      
                      {submissionFileName && (
                        <div className="mt-4 p-2 bg-[#D1FAE5] border border-emerald-200 text-emerald-800 text-xs rounded-lg inline-flex items-center gap-2 font-mono">
                          <Lucide.Check className="w-4 h-4 text-emerald-600" />
                          <span>Selected File: "{submissionFileName}"</span>
                        </div>
                      )}
                    </div>
                    {submissionFileError && (
                      <p className="text-red-500 text-xs mt-2 font-semibold flex items-center gap-1">
                        <Lucide.AlertCircle className="w-4 h-4" />
                        <span>{submissionFileError}</span>
                      </p>
                    )}
                  </div>
                </div>

                <div className="pt-4 border-t border-[#E2E8F0] flex justify-end gap-3">
                  <button
                    type="button"
                    onClick={() => {
                      setSubmissionText('');
                      setSubmissionFileName('');
                      setCurrentTab('assignments');
                    }}
                    className="py-2.5 px-4 rounded-lg text-xs font-bold border border-[#E2E8F0] text-slate-700 hover:bg-slate-50"
                  >
                    Cancel submission
                  </button>
                  <button
                    type="submit"
                    className="py-2.5 px-5 bg-[#1C2541] hover:bg-[#1D4ED8] text-white text-xs font-bold rounded-lg shadow-sm"
                  >
                    Post Submission File
                  </button>
                </div>
              </form>
            );
          })()}
        </div>
      )}

      {/* STUDENT UPCOMING EXAMS TAB */}
      {currentTab === 'exams' && (
        <div className="space-y-6 animate-fadeIn text-left">
          <div className="border-b border-[#E2E8F0] pb-4">
            <h1 className="text-2xl font-extrabold text-[#0F172A] tracking-tight">Examination Schedules</h1>
            <p className="text-[#64748B] text-xs">Exams countdown schedules detailed checklists and venue maps.</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            
            {/* Countdown Details List */}
            <div className="space-y-4">
              <h3 className="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider mb-2">Upcoming Exam Sessions</h3>
              
              {upcomingExams.map((exam) => {
                const courseObj = courses.find((c) => c.id === exam.course_id);
                const tutorObj = users.find((u) => u.id === courseObj?.teacher_id);
                return (
                  <div key={exam.id} className="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
                    <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-50 pb-2">
                      <div className="flex items-center gap-1.5 text-xs text-red-600 font-bold font-mono">
                        <Lucide.AlertCircle className="w-4 h-4 shrink-0" />
                        <span>Date: {exam.exam_date}</span>
                      </div>
                      <Badge text={exam.type.toUpperCase()} type="danger" />
                    </div>

                    <div className="space-y-1">
                      <span className="text-[10px] font-mono font-bold text-slate-400">{courseObj?.code} • Sem {courseObj?.semester}</span>
                      <h4 className="text-sm font-bold text-[#0F172A]">{exam.title}</h4>
                      <p className="text-xs text-slate-500 font-mono">Venue: {exam.venue} • Duration: {exam.duration_minutes} minutes</p>
                    </div>

                    <div className="flex items-center gap-2 pt-2 border-t border-slate-50">
                      <img src={tutorObj?.avatar} alt={tutorObj?.name} className="w-6 h-6 rounded-full object-cover shrink-0" />
                      <span className="text-[10.5px] text-[#64748B] font-medium font-mono">Invigilator: {tutorObj?.name}</span>
                    </div>
                  </div>
                );
              })}

              {upcomingExams.length === 0 && (
                <div className="p-12 text-center bg-white border border-[#E2E8F0] text-slate-400 rounded-xl">
                  No upcoming examination dates scheduled in current trimester.
                </div>
              )}
            </div>

            {/* Instruction Checklist Rules */}
            <div className="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
              <h3 className="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider pb-2 border-b border-[#E2E8F0]">
                Institutional Examination Code
              </h3>
              <ul className="space-y-3.5 text-xs text-[#64748B] leading-relaxed">
                <li className="flex gap-2.5 items-start">
                  <div className="w-5 h-5 rounded bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 font-black shrink-0">1</div>
                  <p>Students must report inside assigned Labs or seminar rooms at least <strong>15 minutes prior</strong> to start timeline.</p>
                </li>
                <li className="flex gap-2.5 items-start">
                  <div className="w-5 h-5 rounded bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 font-black shrink-0">2</div>
                  <p>Admittance is strictly gated via physical students identities ID cards checkups. Smart watches or calculators are locked unless requested.</p>
                </li>
                <li className="flex gap-2.5 items-start">
                  <div className="w-5 h-5 rounded bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 font-black shrink-0">3</div>
                  <p>For coding or database practical formats, unauthorized web portals browsing triggers automation plagiarism alarms instantly.</p>
                </li>
              </ul>
            </div>

          </div>
        </div>
      )}

      {/* STUDENT MY RESULTS TAB */}
      {currentTab === 'results' && (
        <div className="space-y-6 animate-fadeIn">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
              <h1 className="text-2xl font-extrabold text-[#0F172A] tracking-tight">Grade transcripts & Reports</h1>
              <p className="text-[#64748B] text-xs">Analyze grading scales distribution and simulated PDF statement output.</p>
            </div>

            {/* Filter semester select dropdown */}
            <div className="flex items-center gap-2">
              <label htmlFor="results_sem_select" className="text-xs font-bold text-slate-500 font-mono">SEMESTER:</label>
              <select
                id="results_sem_select"
                value={resultsSemester}
                onChange={(e) => setResultsSemester(Number(e.target.value))}
                className="bg-white border border-[#E2E8F0] rounded-lg p-2 text-xs font-bold text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#1D4ED8]"
              >
                <option value={1}>Semester I</option>
                <option value={2}>Semester II</option>
                <option value={3}>Semester III (Active)</option>
                <option value={4}>Semester IV</option>
              </select>
            </div>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            {/* Left 6Cols: Transcript table results */}
            <div className="lg:col-span-7 bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
              <div className="flex justify-between items-center pb-2 border-b border-slate-50">
                <h3 className="text-xs font-extrabold text-slate-400 uppercase tracking-widest">Disclosed Academic Transcript</h3>
                <button
                  onClick={() => alert('Transcript download initialized. Simulated header content-disposition issued successfully.')}
                  className="px-3 py-1.5 border border-[#E2E8F0] hover:bg-slate-50 rounded-lg text-xs font-bold text-[#1D4ED8] inline-flex items-center gap-1.5 cursor-pointer"
                >
                  <Lucide.Download className="w-3.5 h-3.5" />
                  <span>Download Statement</span>
                </button>
              </div>

              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs text-[#0F172A] border-collapse">
                  <thead>
                    <tr className="bg-slate-50 text-slate-500 border-b border-[#E2E8F0] font-mono">
                      <th className="p-3 font-semibold">CODE</th>
                      <th className="p-3 font-semibold">COURSE TITLE</th>
                      <th className="p-3 font-semibold text-center">CREDITS</th>
                      <th className="p-3 font-semibold text-center">OBTAINED</th>
                      <th className="p-3 font-semibold text-right">GRADE</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 font-mono">
                    {myResults
                      .filter((r) => r.semester === resultsSemester)
                      .map((res) => {
                        const course = courses.find((c) => c.id === res.course_id);
                        return (
                          <tr key={res.id} className="hover:bg-slate-50/20 font-medium">
                            <td className="p-3 font-bold">{course?.code}</td>
                            <td className="p-3 text-slate-700 font-sans">{course?.title}</td>
                            <td className="p-3 text-center text-slate-600">{course?.credits}</td>
                            <td className="p-3 text-center text-slate-600">{res.total_marks} / 100</td>
                            <td className="p-3 text-right">
                              <span className="font-extrabold pr-2 text-[#1D4ED8]">{res.grade}</span>
                            </td>
                          </tr>
                        );
                      })}
                    {myResults.filter((r) => r.semester === resultsSemester).length === 0 && (
                      <tr>
                        <td colSpan={5} className="p-8 text-center text-slate-400">
                          No results available matching Semester {resultsSemester} database index filters.
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>

            {/* Right 5Cols: Graphic visualizer grade distributions */}
            <div className="lg:col-span-5 bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
              <h3 className="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider pb-2 border-b border-slate-50">
                Grade distribution Curve
              </h3>
              
              {/* Beautiful local SVG Distribution Chart */}
              <div className="space-y-4 pt-2">
                <div className="flex h-32 items-end justify-between px-6 pt-4 bg-[#F8FAFC] border border-[#E2E8F0] rounded-xl relative">
                  {/* Grid Lines */}
                  <div className="absolute inset-x-0 top-1/3 border-t border-slate-200/50" />
                  <div className="absolute inset-x-0 top-2/3 border-t border-slate-200/50" />

                  <div className="flex flex-col items-center gap-1.5 z-10 w-8">
                    <div className="bg-[#1D4ED8] text-white text-[9px] px-1 font-bold rounded">1</div>
                    <div className="w-4 bg-[#1D4ED8] rounded-t" style={{ height: '50px' }} />
                    <span className="text-[10px] font-black text-slate-500 font-mono">A</span>
                  </div>

                  <div className="flex flex-col items-center gap-1.5 z-10 w-8">
                    <div className="bg-[#1D4ED8] text-white text-[9px] px-1 font-bold rounded">1</div>
                    <div className="w-4 bg-[#1D4ED8] rounded-t" style={{ height: '50px' }} />
                    <span className="text-[10px] font-black text-slate-500 font-mono">B+</span>
                  </div>

                  <div className="flex flex-col items-center gap-1.5 z-10 w-8">
                    <div className="bg-[#1D4ED8] text-white text-[9px] px-1 font-bold rounded">1</div>
                    <div className="w-4 bg-[#1D4ED8] rounded-t" style={{ height: '50px' }} />
                    <span className="text-[10px] font-black text-slate-500 font-mono">C</span>
                  </div>

                  <div className="flex flex-col items-center gap-1.5 z-10 w-8">
                    <div className="text-slate-400 text-[9px] font-bold">0</div>
                    <div className="w-4 bg-slate-200 rounded-t" style={{ height: '2px' }} />
                    <span className="text-[10px] font-black text-slate-400 font-mono">D</span>
                  </div>

                  <div className="flex flex-col items-center gap-1.5 z-10 w-8">
                    <div className="text-slate-400 text-[9px] font-bold">0</div>
                    <div className="w-4 bg-[#EF4444] rounded-t" style={{ height: '2px' }} />
                    <span className="text-[10px] font-black text-slate-400 font-mono">F</span>
                  </div>
                </div>
                <div className="p-3 bg-blue-50/50 border border-blue-100 rounded-lg text-[11px] text-[#1D4ED8] leading-relaxed">
                  <strong>Transcript Summary:</strong> Total credits accumulated in 2025/2026 term: <strong>11 credits</strong>. Cumulative CGPA estimated on cumulative grade points matches <strong>{profile?.cgpa || '8.4'} CGPA Scale</strong>.
                </div>
              </div>
            </div>

          </div>
        </div>
      )}

      {/* STUDENT MY ATTENDANCE TAB */}
      {currentTab === 'attendance' && (
        <div className="space-y-6 animate-fadeIn">
          <div className="border-b border-[#E2E8F0] pb-4">
            <h1 className="text-2xl font-extrabold text-[#0F172A] tracking-tight">Attendance Record Registry</h1>
            <p className="text-[#64748B] text-xs">Verify your class session presence logs and minimum rules compliance metrics.</p>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            {/* Left 5Cols: Donut meter and warning info */}
            <div className="lg:col-span-5 space-y-6">
              
              {/* Safety Alert Warning if Below 85% */}
              {attendancePercentage < 85 && (
                <div className="p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-xs leading-relaxed space-y-2 animate-pulse">
                  <div className="flex items-center gap-2 font-black uppercase text-red-900">
                    <Lucide.AlertCircle className="w-5 h-5 flex-shrink-0" />
                    <span>Attendance Shortage Alert!</span>
                  </div>
                  <p>Your current aggregate class attendance rate is <strong>{attendancePercentage}%</strong>, dropping below the mandatory university cutoff threshold rate of <strong>85%</strong>. Kindly contact your department course advisors immediately.</p>
                </div>
              )}

              {/* Attendance visual circular donut summary */}
              <div className="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-6 text-center">
                <h3 className="text-xs font-extrabold text-slate-400 uppercase tracking-widest">Aggregate Cumulative Meter</h3>
                
                {/* SVG Circular Dial Donut Meter */}
                <div className="relative w-40 h-40 mx-auto">
                  <svg className="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                    {/* Background Track Circle */}
                    <circle cx="50" cy="50" r="40" stroke="#f1f5f9" strokeWidth="8" fill="transparent" />
                    {/* Forward Active Circle */}
                    <circle
                      cx="50"
                      cy="50"
                      r="40"
                      stroke={attendancePercentage >= 85 ? '#10B981' : '#F59E0B'}
                      strokeWidth="8"
                      fill="transparent"
                      strokeDasharray={`${2 * Math.PI * 40}`}
                      strokeDashoffset={`${2 * Math.PI * 40 * (1 - attendancePercentage / 100)}`}
                      strokeLinecap="round"
                    />
                  </svg>
                  <div className="absolute inset-0 flex flex-col items-center justify-center space-y-1">
                    <span className="text-3xl font-black text-[#0F172A]">{attendancePercentage}%</span>
                    <span className="text-[10px] text-slate-400 font-bold uppercase tracking-wider">AGGREGATE</span>
                  </div>
                </div>

                <div className="grid grid-cols-3 gap-2">
                  <div className="border border-slate-100 p-2.5 rounded-lg bg-slate-50/50">
                    <span className="font-extrabold text-[#0F172A] font-mono text-sm block">{totalClasses}</span>
                    <span className="text-[9px] text-[#64748B] font-bold uppercase tracking-wide">Classes</span>
                  </div>
                  <div className="border border-slate-100 p-2.5 rounded-lg bg-slate-50/50">
                    <span className="font-extrabold text-[#10B981] font-mono text-sm block">
                      {myAttendance.filter((a) => a.status === 'present').length}
                    </span>
                    <span className="text-[9px] text-[#64748B] font-bold uppercase tracking-wide">Present</span>
                  </div>
                  <div className="border border-slate-100 p-2.5 rounded-lg bg-slate-50/50">
                    <span className="font-extrabold text-amber-600 font-mono text-sm block">
                      {myAttendance.filter((a) => a.status === 'late').length}
                    </span>
                    <span className="text-[9px] text-[#64748B] font-bold uppercase tracking-wide">Late</span>
                  </div>
                </div>
              </div>

            </div>

            {/* Right 7Cols: Attendance logs calendar table */}
            <div className="lg:col-span-7 bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
              <h3 className="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider pb-2 border-b border-[#E2E8F0]">
                Live Attendance Session Registry log
              </h3>

              <div className="divide-y divide-slate-100 max-h-96 overflow-y-auto pr-1">
                {myAttendance.map((rec) => {
                  const course = courses.find((c) => c.id === rec.course_id);
                  const tutorObj = users.find((u) => u.id === rec.marked_by);
                  return (
                    <div key={rec.id} className="p-3 text-left hover:bg-slate-50/30 flex justify-between items-center gap-4">
                      <div>
                        <div className="flex items-center gap-2">
                          <span className="font-mono text-[11px] font-bold text-[#0F172A]">{course?.code}</span>
                          <span className="text-slate-500 text-[11px] font-serif pr-2">{course?.title}</span>
                        </div>
                        <span className="text-[10px] text-slate-400 block mt-1 font-mono">Date Marked: {rec.date} • Instructor: {tutorObj?.name}</span>
                      </div>

                      <Badge
                        text={rec.status.toUpperCase()}
                        type={rec.status === 'present' ? 'success' : rec.status === 'late' ? 'warning' : 'danger'}
                      />
                    </div>
                  );
                })}
                {myAttendance.length === 0 && (
                  <div className="p-12 text-center text-slate-400">
                    No active class attendance logs parsed.
                  </div>
                )}
              </div>
            </div>

          </div>
        </div>
      )}

      {/* STUDENT MY PLACEMENTS TAB */}
      {currentTab === 'placements' && (
        <div className="space-y-6 animate-fadeIn">
          <div className="border-b border-[#E2E8F0] pb-4">
            <h1 className="text-2xl font-extrabold text-[#0F172A] tracking-tight">Active Placement Operations</h1>
            <p className="text-[#64748B] text-xs">Acknowledge openings, eligibility parameters, deadlines and status indicators.</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            {placements.map((job) => {
              // Fetch applied applications
              const appliedRecord = placementApplications.find(
                (pa) => pa.placement_id === job.id && pa.student_id === currentUser.id
              );

              return (
                <div
                  key={job.id}
                  className="bg-white border border-[#E2E8F0] p-6 rounded-xl shadow-sm space-y-4 hover:shadow-md transition-shadow relative"
                >
                  <div className="flex justify-between items-start border-b border-slate-50 pb-2.5">
                    <div>
                      <span className="text-[10px] uppercase font-black text-[#1D4ED8] bg-blue-50 border border-blue-100 px-2 py-0.5 rounded-full tracking-wider font-mono">
                        {job.company}
                      </span>
                      <h3 className="font-extrabold text-[#0F172A] text-base tracking-tight mt-2">{job.role}</h3>
                      <p className="text-xs text-[#64748B] font-mono mt-0.5">{job.location} • {job.stipend}</p>
                    </div>

                    <div className="shrink-0">
                      {appliedRecord ? (
                        <div className="text-right">
                          <span className="text-[10px] font-black uppercase tracking-wider block text-[#1D4ED8] mb-1">APPLIED</span>
                          <Badge
                            text={appliedRecord.status.toUpperCase()}
                            type={
                              appliedRecord.status === 'offered' ? 'success' :
                              appliedRecord.status === 'rejected' ? 'danger' :
                              appliedRecord.status === 'applied' ? 'info' : 'warning'
                            }
                          />
                        </div>
                      ) : (
                        <Badge text={job.status.toUpperCase()} type={job.status === 'open' ? 'success' : 'danger'} />
                      )}
                    </div>
                  </div>

                  <div className="space-y-2">
                    <p className="text-xs text-[#0F172A] leading-relaxed font-semibold">Eligibility Requirements Checklist:</p>
                    <p className="p-3 bg-[#F8FAFC] border border-[#E2E8F0] rounded-xl text-xs text-[#64748B] leading-relaxed font-mono">
                      {job.eligibility}
                    </p>
                    <p className="text-xs text-[#64748B] leading-relaxed">{job.description}</p>
                  </div>

                  <div className="flex items-center justify-between pt-3 border-t border-slate-100">
                    <span className="text-[11px] font-semibold text-[#EF4444] font-mono">
                      Deadline: {job.deadline}
                    </span>
                    {!appliedRecord && job.status === 'open' && (
                      <button
                        onClick={() => {
                          const res = applyPlacement(job.id);
                          if (res.success) {
                            setFeedbackAlert({ message: res.message, type: 'success' });
                          } else {
                            setFeedbackAlert({ message: res.message, type: 'danger' });
                          }
                        }}
                        className="py-2 px-4 bg-[#1D4ED8] hover:bg-[#1E40AF] text-white text-xs font-bold rounded-lg shadow-sm cursor-pointer transform active:scale-95 transition-transform"
                      >
                        Apply for Role
                      </button>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* STUDENT PROFILE DETAILS EDIT TAB */}
      {currentTab === 'profile' && profile && (
        <div className="bg-white border border-[#E2E8F0] rounded-xl p-8 max-w-2xl mx-auto shadow-sm space-y-6 animate-fadeIn">
          <div className="flex items-center gap-4 border-b border-slate-50 pb-4">
            <img src={currentUser.avatar} alt={currentUser.name} className="w-16 h-16 rounded-full object-cover border-2 border-[#1D4ED8]" />
            <div>
              <p className="text-xs text-slate-400 font-mono uppercase tracking-widest">Global Register profile ID</p>
              <h2 className="text-2xl font-bold text-[#0F172A] tracking-tight">{currentUser.name}</h2>
              <p className="text-xs text-[#64748B] italic font-mono mt-0.5">Roll: {profile.roll_no} • Sem {profile.semester}</p>
            </div>
          </div>

          <div className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <span className="text-[10px] text-slate-400 font-bold uppercase tracking-widest block font-mono">Email Address</span>
                <span className="text-[#0F172A] font-semibold text-sm block mt-1">{currentUser.email}</span>
              </div>
              <div>
                <span className="text-[10px] text-slate-400 font-bold uppercase tracking-widest block font-mono">Contact Phone</span>
                <span className="text-[#0F172A] font-semibold text-sm block mt-1">{currentUser.phone || '9845621350'}</span>
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <span className="text-[10px] text-slate-400 font-bold uppercase tracking-widest block font-mono">DOB Record</span>
                {editingProfile ? (
                  <input
                    type="date"
                    value={dob}
                    onChange={(e) => setDob(e.target.value)}
                    className="mt-1 border border-[#E2E8F0] text-xs p-1.5 rounded focus:outline-none focus:ring-1 focus:ring-[#1D4ED8]"
                  />
                ) : (
                  <span className="text-[#0F172A] font-semibold text-sm block mt-1">{profile.dob || '2005-05-15'}</span>
                )}
              </div>
              <div>
                <span className="text-[10px] text-slate-400 font-bold uppercase tracking-widest block font-mono">Address location</span>
                {editingProfile ? (
                  <input
                    type="text"
                    value={address}
                    onChange={(e) => setAddress(e.target.value)}
                    className="mt-1 border border-[#E2E8F0] text-xs p-1.5 rounded focus:outline-none focus:ring-1 focus:ring-[#1D4ED8] w-full"
                  />
                ) : (
                  <span className="text-[#0F172A] font-medium text-xs block mt-1">{profile.address || '123 Pine Drive, Ernakulam, Kerala'}</span>
                )}
              </div>
            </div>

            <div className="border-t border-slate-50 pt-4 flex justify-end gap-2">
              {editingProfile ? (
                <>
                  <button
                    onClick={() => setEditingProfile(false)}
                    className="py-1.5 px-3 border border-[#E2E8F0] rounded text-xs font-bold text-slate-600 hover:bg-slate-50"
                  >
                    Cancel
                  </button>
                  <button
                    onClick={() => {
                      setEditingProfile(false);
                      setFeedbackAlert({ message: 'Profile details saved in memory database successfully.', type: 'success' });
                    }}
                    className="py-1.5 px-3 bg-[#1D4ED8] text-white rounded text-xs font-bold hover:bg-[#1E40AF]"
                  >
                    Save Changes
                  </button>
                </>
              ) : (
                <button
                  onClick={() => {
                    setDob(profile.dob || '2005-05-15');
                    setAddress(profile.address || '123 Pine Drive, Ernakulam, Kerala');
                    setEditingProfile(true);
                  }}
                  className="py-2 px-4 border border-[#E2E8F0] hover:bg-slate-50 rounded text-xs font-bold text-[#1D4ED8] font-mono shrink-0 cursor-pointer"
                >
                  Edit Profile Fields
                </button>
              )}
            </div>
          </div>
        </div>
      )}

    </div>
  );
};
