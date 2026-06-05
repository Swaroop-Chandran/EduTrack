import React, { useState } from 'react';
import { useLms } from '../context/LmsContext';
import { StatCard, Badge, Alert } from '../utils/helpers';
import * as Lucide from 'lucide-react';

interface AdminDashboardProps {
  currentTab: string;
  setCurrentTab: (tab: string) => void;
}

export const AdminDashboard: React.FC<AdminDashboardProps> = ({ currentTab, setCurrentTab }) => {
  const {
    users,
    studentProfiles,
    teacherProfiles,
    departments,
    courses,
    enrollments,
    placements,
    placementApplications,
    announcements,
    addStudent,
    editStudent,
    deleteStudent,
    addTeacher,
    editTeacher,
    deleteTeacher,
    addDepartment,
    editDepartment,
    addCourse,
    editCourse,
    addEnrollment,
    removeEnrollment,
    addPlacement,
    updatePlacementStatus,
    updatePlacementApplicationStatus,
    sendAnnouncement
  } = useLms();

  // Unified administrative local helper states
  const [feedbackAlert, setFeedbackAlert] = useState<{ message: string; type: 'success' | 'danger' } | null>(null);

  // Search thresholds
  const [studentSearch, setStudentSearch] = useState('');
  const [teacherSearch, setTeacherSearch] = useState('');
  const [selectedDeptFilter, setSelectedDeptFilter] = useState<string>('all');

  // Modal / Form state managers
  const [isAddStudentOpen, setIsAddStudentOpen] = useState(false);
  const [editingStudentId, setEditingStudentId] = useState<number | null>(null);

  const [isAddTeacherOpen, setIsAddTeacherOpen] = useState(false);
  const [editingTeacherId, setEditingTeacherId] = useState<number | null>(null);

  const [isAddDeptOpen, setIsAddDeptOpen] = useState(false);
  const [editingDeptId, setEditingDeptId] = useState<number | null>(null);

  const [isAddCourseOpen, setIsAddCourseOpen] = useState(false);
  const [isEnrollStudentOpen, setIsEnrollStudentOpen] = useState(false);

  // Student Input States
  const [stuName, setStuName] = useState('');
  const [stuEmail, setStuEmail] = useState('');
  const [stuPhone, setStuPhone] = useState('');
  const [stuRoll, setStuRoll] = useState('');
  const [stuDept, setStuDept] = useState(1);
  const [stuYear, setStuYear] = useState(2);
  const [stuSem, setStuSem] = useState(3);
  const [stuDob, setStuDob] = useState('2005-04-12');
  const [stuAddress, setStuAddress] = useState('456 Maple St, Ernakulam');

  // Teacher Input States
  const [teaName, setTeaName] = useState('');
  const [teaEmail, setTeaEmail] = useState('');
  const [teaPhone, setTeaPhone] = useState('');
  const [teaEmpId, setTeaEmpId] = useState('');
  const [teaDept, setTeaDept] = useState(1);
  const [teaDesig, setTeaDesig] = useState('Assistant Professor');
  const [teaQual, setTeaQual] = useState('M.Tech, Ph.D');

  // Department Input States
  const [deptName, setDeptName] = useState('');
  const [deptCode, setDeptCode] = useState('');
  const [deptHead, setDeptHead] = useState<number | null>(null);

  // Course Input States
  const [crsTitle, setCrsTitle] = useState('');
  const [crsCode, setCrsCode] = useState('');
  const [crsDept, setCrsDept] = useState(1);
  const [crsTeacher, setCrsTeacher] = useState(2);
  const [crsCredits, setCrsCredits] = useState(4);
  const [crsSem, setCrsSem] = useState(3);
  const [crsDesc, setCrsDesc] = useState('');

  // Course Enrollment Input States
  const [enrollStudentId, setEnrollStudentId] = useState<number>(2);
  const [enrollCourseId, setEnrollCourseId] = useState<number>(1);

  // Placement Opening Input States
  const [jobCompany, setJobCompany] = useState('');
  const [jobRole, setJobRole] = useState('');
  const [jobLoc, setJobLoc] = useState('Kochi, Kerala');
  const [jobEligibility, setJobEligibility] = useState('Aggregate CGPA >= 7.5, No active backlogs.');
  const [jobDesc, setJobDesc] = useState('We are hiring software engineering interns for full-time conversion tracks.');
  const [jobStipend, setJobStipend] = useState('₹45,000 / month');
  const [jobDeadline, setJobDeadline] = useState('2026-06-30');

  // Announcement Input States
  const [annTitle, setAnnTitle] = useState('');
  const [annBody, setAnnBody] = useState('');
  const [annAudience, setAnnAudience] = useState<'all' | 'students' | 'teachers' | 'department'>('all');
  const [annDeptId, setAnnDeptId] = useState<number | null>(null);

  // Settings Configuration states
  const [systemInstName, setSystemInstName] = useState('Rajagiri School of Engineering & Technology');
  const [systemAdminEmail, setSystemAdminEmail] = useState('admin@edutrack.com');

  // Gather stats info
  const countDepts = departments.length;
  const countCourses = courses.length;
  const countTeachers = users.filter((u) => u.role === 'teacher').length;
  const countStudents = users.filter((u) => u.role === 'student').length;

  const activeStudentsList = users.filter((u) => u.role === 'student');
  const activeTeachersList = users.filter((u) => u.role === 'teacher');

  // Filtering Students list
  const filteredStudents = activeStudentsList.filter((sUser) => {
    const sProfile = studentProfiles.find((p) => p.user_id === sUser.id);
    const matchesSearch =
      sUser.name.toLowerCase().includes(studentSearch.toLowerCase()) ||
      sUser.email.toLowerCase().includes(studentSearch.toLowerCase()) ||
      (sProfile && sProfile.roll_no.toLowerCase().includes(studentSearch.toLowerCase()));

    const matchesDept =
      selectedDeptFilter === 'all' ||
      (sProfile && sProfile.department_id === parseInt(selectedDeptFilter, 10));

    return matchesSearch && matchesDept;
  });

  // Filtering Teachers list
  const filteredTeachers = activeTeachersList.filter((tUser) => {
    const tProfile = teacherProfiles.find((p) => p.user_id === tUser.id);
    const matchesSearch =
      tUser.name.toLowerCase().includes(teacherSearch.toLowerCase()) ||
      tUser.email.toLowerCase().includes(teacherSearch.toLowerCase());

    const matchesDept =
      selectedDeptFilter === 'all' ||
      (tProfile && tProfile.department_id === parseInt(selectedDeptFilter, 10));

    return matchesSearch && matchesDept;
  });

  // Handle: Save Student
  const handleSaveStudent = (e: React.FormEvent) => {
    e.preventDefault();
    if (editingStudentId !== null) {
      editStudent(
        editingStudentId,
        { name: stuName, email: stuEmail, phone: stuPhone },
        { roll_no: stuRoll, department_id: Number(stuDept), year: Number(stuYear), semester: Number(stuSem), dob: stuDob, address: stuAddress }
      );
      setEditingStudentId(null);
      setFeedbackAlert({ message: 'Student information registry details saved successfully.', type: 'success' });
    } else {
      addStudent({
        name: stuName,
        email: stuEmail,
        phone: stuPhone,
        password: 'student123',
        status: 'active',
        roll_no: stuRoll,
        department_id: Number(stuDept),
        year: Number(stuYear),
        semester: Number(stuSem),
        dob: stuDob,
        address: stuAddress
      });
      setFeedbackAlert({ message: 'New student official profile initialized successfully.', type: 'success' });
    }
    // RESET FIELDS
    setStuName('');
    setStuEmail('');
    setStuPhone('');
    setStuRoll('');
    setIsAddStudentOpen(false);
  };

  // Handle: Save Teacher
  const handleSaveTeacher = (e: React.FormEvent) => {
    e.preventDefault();
    if (editingTeacherId !== null) {
      editTeacher(
        editingTeacherId,
        { name: teaName, email: teaEmail, phone: teaPhone },
        { employee_id: teaEmpId, department_id: Number(teaDept), designation: teaDesig, qualification: teaQual }
      );
      setEditingTeacherId(null);
      setFeedbackAlert({ message: 'Teacher credentials updated.', type: 'success' });
    } else {
      addTeacher({
        name: teaName,
        email: teaEmail,
        phone: teaPhone,
        password: 'teacher123',
        status: 'active',
        employee_id: teaEmpId,
        department_id: Number(teaDept),
        designation: teaDesig,
        qualification: teaQual
      });
      setFeedbackAlert({ message: 'New professor profile deployed successfully.', type: 'success' });
    }
    setTeaName('');
    setTeaEmail('');
    setTeaPhone('');
    setTeaEmpId('');
    setIsAddTeacherOpen(false);
  };

  // Handle: Save Department
  const handleSaveDept = (e: React.FormEvent) => {
    e.preventDefault();
    if (editingDeptId !== null) {
      editDepartment(editingDeptId, deptName, deptCode, deptHead);
      setEditingDeptId(null);
      setFeedbackAlert({ message: 'Department attributes updated.', type: 'success' });
    } else {
      addDepartment(deptName, deptCode, deptHead);
      setFeedbackAlert({ message: 'New academic department initialized.', type: 'success' });
    }
    setDeptName('');
    setDeptCode('');
    setIsAddDeptOpen(false);
  };

  // Handle: Save Course Create
  const handleSaveCourse = (e: React.FormEvent) => {
    e.preventDefault();
    addCourse(crsTitle, crsCode, crsDept, crsTeacher, crsCredits, crsSem, crsDesc);
    setIsAddCourseOpen(false);
    setCrsTitle('');
    setCrsCode('');
    setCrsDesc('');
    setFeedbackAlert({ message: 'Course curriculum deployed to active term catalogs.', type: 'success' });
  };

  // Handle: Enroll Student
  const handleEnrollStudentPost = (e: React.FormEvent) => {
    e.preventDefault();
    const res = addEnrollment(enrollStudentId, enrollCourseId);
    if (res.success) {
      setFeedbackAlert({ message: res.message, type: 'success' });
      setIsEnrollStudentOpen(false);
    } else {
      setFeedbackAlert({ message: res.message, type: 'danger' });
    }
  };

  // Handle: Broadcast Announcement
  const handlePostAnnouncement = (e: React.FormEvent) => {
    e.preventDefault();
    sendAnnouncement(annTitle, annBody, annAudience, annDeptId);
    setAnnTitle('');
    setAnnBody('');
    setFeedbackAlert({ message: 'Push announcements broadcast is online.', type: 'success' });
    setCurrentTab('dashboard');
  };

  // Handle: Publish Placement opening job
  const handlePostJobOpening = (e: React.FormEvent) => {
    e.preventDefault();
    addPlacement({
      company: jobCompany,
      role: jobRole,
      description: jobDesc,
      stipend: jobStipend,
      eligibility: jobEligibility,
      location: jobLoc,
      deadline: jobDeadline
    });
    setJobCompany('');
    setJobRole('');
    setFeedbackAlert({ message: 'New vacancy opening advertised to student terminals.', type: 'success' });
    setCurrentTab('placements');
  };

  return (
    <div className="space-y-6 text-left">
      {/* Alert indicators panel */}
      {feedbackAlert && (
        <Alert
          message={feedbackAlert.message}
          type={feedbackAlert.type}
          onDismiss={() => setFeedbackAlert(null)}
        />
      )}

      {/* RENDER VIEW CONTROLLERS FOR LMS ADMINS */}
      {currentTab === 'dashboard' && (
        <div className="space-y-6 animate-fadeIn">
          {/* Welcome Banner */}
          <div>
            <span className="text-xs uppercase font-black tracking-widest text-[#1D4ED8]">Administration Deck</span>
            <h1 className="text-3xl font-extrabold text-[#0F172A] tracking-tight mt-1">
              Welcome, Administrator Principal ⚙️
            </h1>
            <p className="text-[#64748B] text-xs">Verify enrollment statistics ratios, department boundaries, teachers and placers schedules.</p>
          </div>

          {/* Core admin KPIs cards summary */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <StatCard
              title="Departments Registered"
              value={countDepts}
              icon="Building2"
              color="blue"
              subtext="Independent learning sectors"
            />
            <StatCard
              title="Academic Courses"
              value={countCourses}
              icon="BookOpen"
              color="navy"
              subtext="Configured terms schedules"
            />
            <StatCard
              title="Faculty Instructors"
              value={countTeachers}
              icon="GraduationCap"
              color="green"
              subtext="Assigned classroom leaders"
            />
            <StatCard
              title="Enrolled Scholars"
              value={countStudents}
              icon="Users"
              color="amber"
              subtext="Active studying credentials"
            />
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            {/* Left 7Cols: Announcements lists overview */}
            <div className="lg:col-span-7 bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
              <div className="flex justify-between items-center pb-2 border-b border-slate-50">
                <h3 className="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider">Broadcasted Announcements</h3>
                <button onClick={() => setCurrentTab('announcements')} className="text-xs font-bold text-[#1D4ED8] hover:underline">
                  Configure push broadcasts
                </button>
              </div>

              <div className="space-y-4 max-h-96 overflow-y-auto pr-1">
                {announcements.map((ann) => (
                  <div key={ann.id} className="p-3 border rounded-xl bg-slate-50 border-slate-150 text-xs">
                    <div className="flex justify-between items-start mb-1 gap-2">
                      <h4 className="font-bold text-[#0F172A]">{ann.title}</h4>
                      <Badge text={ann.audience.toUpperCase()} type="info" />
                    </div>
                    <p className="text-[#64748B] leading-relaxed mt-1 font-mono">{ann.body}</p>
                    <span className="text-[9px] block text-slate-400 mt-2 font-mono">Published: {new Date(ann.created_at).toLocaleString()}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* Right 5Cols: Administrator Shortcuts deck */}
            <div className="lg:col-span-5 bg-[#0F172A] text-white rounded-xl p-6 shadow-md space-y-4">
              <h3 className="text-xs font-black uppercase tracking-widest text-[#1D4ED8]">Admin Operations Center</h3>
              <div className="space-y-3">
                <button
                  onClick={() => {
                    setIsAddStudentOpen(true);
                    setEditingStudentId(null);
                    setCurrentTab('students');
                  }}
                  className="w-full text-left py-2 px-3 border border-white/5 hover:border-slate-500 rounded-lg text-xs font-semibold flex items-center justify-between"
                >
                  <span>Initialize New Scholar Admission</span>
                  <Lucide.UserPlus className="w-4 h-4 text-emerald-400" />
                </button>

                <button
                  onClick={() => {
                    setIsAddTeacherOpen(true);
                    setEditingTeacherId(null);
                    setCurrentTab('teachers');
                  }}
                  className="w-full text-left py-2 px-3 border border-white/5 hover:border-slate-500 rounded-lg text-xs font-semibold flex items-center justify-between"
                >
                  <span>Appoint Faculty Professor</span>
                  <Lucide.PlusCircle className="w-4 h-4 text-blue-400" />
                </button>

                <button
                  onClick={() => {
                    setIsAddDeptOpen(true);
                    setEditingDeptId(null);
                    setCurrentTab('departments');
                  }}
                  className="w-full text-left py-2 px-3 border border-white/5 hover:border-slate-500 rounded-lg text-xs font-semibold flex items-center justify-between"
                >
                  <span>Configure Academic Sector</span>
                  <Lucide.Building className="w-4 h-4 text-indigo-400" />
                </button>
              </div>
            </div>

          </div>
        </div>
      )}

      {/* ADMIN STUDENTS REGISTRY MANAGEMENT TAB */}
      {currentTab === 'students' && (
        <div className="space-y-6 animate-fadeIn">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
              <h1 className="text-2xl font-extrabold text-[#0F172A]">Students admission Registry</h1>
              <p className="text-[#64748B] text-xs">Acknowledge registered profiles details, search roll, and configure credentials.</p>
            </div>

            <button
              onClick={() => {
                setEditingStudentId(null);
                setStuName('');
                setStuEmail('');
                setStuRoll('');
                setIsAddStudentOpen(true);
              }}
              className="py-2.5 px-4 bg-[#1D4ED8] hover:bg-[#1E40AF] text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-2 cursor-pointer transition-transform active:scale-95"
            >
              <Lucide.UserPlus className="w-4 h-4" />
              <span>Admit Scholar Profile</span>
            </button>
          </div>

          {/* Filtering students control selectors */}
          <div className="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div className="md:col-span-8 relative">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Lucide.Search className="w-4 h-4 text-slate-400" />
              </div>
              <input
                type="text"
                value={studentSearch}
                onChange={(e) => setStudentSearch(e.target.value)}
                placeholder="Search student scholar name, roll identifier, or email contact..."
                className="w-full pl-9 pr-4 py-2 bg-white border border-[#E2E8F0] focus:ring-1 focus:ring-[#1D4ED8] focus:border-transparent rounded-xl text-xs text-[#0F172A]"
              />
            </div>

            <div className="md:col-span-4 select-none">
              <select
                value={selectedDeptFilter}
                onChange={(e) => setSelectedDeptFilter(e.target.value)}
                className="w-full bg-white border border-[#E2E8F0] p-2 rounded-xl text-xs font-bold text-slate-600 focus:outline-none"
              >
                <option value="all">Display All sectors</option>
                {departments.map((d) => (
                  <option key={d.id} value={d.id}>{d.name} ({d.code})</option>
                ))}
              </select>
            </div>
          </div>

          {/* Student rows listings Table */}
          <div className="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs border-collapse">
                <thead>
                  <tr className="bg-[#F8FAFC] border-b border-[#E2E8F0] text-slate-500 font-mono uppercase text-[10px]">
                    <th className="p-4">NAME & ADDMISSION INDEX</th>
                    <th className="p-4">ROLL / INDIVIDUAL KEY</th>
                    <th className="p-4">DEPARTMENT</th>
                    <th className="p-4 text-center">CGPA</th>
                    <th className="p-4 text-right">CONTROLS</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 font-medium font-mono">
                  {filteredStudents.map((su) => {
                    const sp = studentProfiles.find((p) => p.user_id === su.id);
                    const dp = departments.find((d) => d.id === sp?.department_id);

                    return (
                      <tr key={su.id} className="hover:bg-slate-50/30">
                        <td className="p-4 flex items-center gap-3 font-sans">
                          <img src={su.avatar} alt="Avatar" className="w-8 h-8 rounded-full border border-slate-100 object-cover" />
                          <div>
                            <span className="font-extrabold text-[#0F172A] block">{su.name}</span>
                            <span className="text-[10px] text-slate-400 font-mono">{su.email}</span>
                          </div>
                        </td>
                        <td className="p-4">{sp?.roll_no || 'RSET-2025'}</td>
                        <td className="p-4 font-sans text-slate-700">{dp ? `${dp.name} (${dp.code})` : 'Unassigned'}</td>
                        <td className="p-4 text-center text-[#1D4ED8] font-black">{sp?.cgpa || '8.40'}</td>
                        <td className="p-4 text-right space-x-2 font-sans">
                          <button
                            onClick={() => {
                              if (!sp) return;
                              setEditingStudentId(su.id);
                              setStuName(su.name);
                              setStuEmail(su.email);
                              setStuPhone(su.phone || '9445612340');
                              setStuRoll(sp.roll_no);
                              setStuDept(sp.department_id);
                              setStuYear(sp.year);
                              setStuSem(sp.semester);
                              setStuDob(sp.dob);
                              setStuAddress(sp.address);
                              setIsAddStudentOpen(true);
                            }}
                            className="text-xs font-bold text-[#1D4ED8] hover:underline cursor-pointer"
                          >
                            Modify
                          </button>
                          <button
                            onClick={() => {
                              if (confirm(`Confirm physical deletion of student account "${su.name}" from repository files?`)) {
                                deleteStudent(su.id);
                                setFeedbackAlert({ message: 'Profile removed completely.', type: 'danger' });
                              }
                            }}
                            className="text-xs font-bold text-red-600 hover:underline cursor-pointer"
                          >
                            Remove
                          </button>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </div>

          {/* Scholars Add/Edit Modal Screen overlays */}
          {isAddStudentOpen && (
            <div className="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-fadeIn">
              <div className="bg-white border rounded-xl p-6 w-full max-w-lg shadow-xl relative max-h-[90vh] overflow-y-auto">
                <button onClick={() => setIsAddStudentOpen(false)} className="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
                  <Lucide.X className="w-5 h-5 animate-spin" />
                </button>
                <h3 className="text-lg font-extrabold text-[#0F172A] pb-2 border-b border-slate-50">
                  {editingStudentId ? 'Modify Student Scholar details' : 'Admit Student Scholar Profile'}
                </h3>

                <form onSubmit={handleSaveStudent} className="space-y-4 mt-4 text-xs font-bold">
                  <div>
                    <label className="block text-slate-500 mb-1">Scholar Full Name</label>
                    <input
                      type="text"
                      value={stuName}
                      onChange={(e) => setStuName(e.target.value)}
                      className="w-full border p-2 rounded focus:ring-1 focus:ring-blue-500"
                      required
                    />
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-slate-500 mb-1">Academic Email Profile</label>
                      <input
                        type="email"
                        value={stuEmail}
                        onChange={(e) => setStuEmail(e.target.value)}
                        className="w-full border p-2 rounded focus:ring-1"
                        required
                      />
                    </div>
                    <div>
                      <label className="block text-slate-500 mb-1">Mobile Phone contact</label>
                      <input
                        type="text"
                        value={stuPhone}
                        onChange={(e) => setStuPhone(e.target.value)}
                        className="w-full border p-2 rounded"
                        placeholder="9458621530"
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-slate-500 mb-1">Institutional Roll RollNo</label>
                      <input
                        type="text"
                        value={stuRoll}
                        onChange={(e) => setStuRoll(e.target.value)}
                        placeholder="RSET-CS-101"
                        className="w-full border p-2 rounded focus:ring-1"
                        required
                      />
                    </div>
                    <div>
                      <label className="block text-slate-500 mb-1">Select Department sector</label>
                      <select
                        value={stuDept}
                        onChange={(e) => setStuDept(Number(e.target.value))}
                        className="w-full border p-2 rounded bg-white text-slate-700"
                      >
                        {departments.map((d) => (
                          <option key={d.id} value={d.id}>{d.name}</option>
                        ))}
                      </select>
                    </div>
                  </div>

                  <div className="grid grid-cols-3 gap-2">
                    <div>
                      <label className="block text-slate-500 mb-1">Year</label>
                      <input
                        type="number"
                        value={stuYear}
                        onChange={(e) => setStuYear(Number(e.target.value))}
                        className="w-full border p-2 rounded"
                      />
                    </div>
                    <div>
                      <label className="block text-slate-500 mb-1">Semester</label>
                      <input
                        type="number"
                        value={stuSem}
                        onChange={(e) => setStuSem(Number(e.target.value))}
                        className="w-full border p-2 rounded"
                      />
                    </div>
                    <div>
                      <label className="block text-slate-500 mb-1">Birth DOB</label>
                      <input
                        type="date"
                        value={stuDob}
                        onChange={(e) => setStuDob(e.target.value)}
                        className="w-full border p-1.5 rounded font-mono"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block text-slate-500 mb-1">Full Permanent Address</label>
                    <input
                      type="text"
                      value={stuAddress}
                      onChange={(e) => setStuAddress(e.target.value)}
                      className="w-full border p-2 rounded"
                    />
                  </div>

                  <div className="pt-4 border-t border-slate-100 flex justify-end gap-2 text-xs">
                    <button
                      type="button"
                      onClick={() => setIsAddStudentOpen(false)}
                      className="px-4 py-2 border rounded hover:bg-slate-50"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      className="px-4 py-2 bg-[#1D4ED8] text-white rounded hover:bg-[#1E40AF]"
                    >
                      Save Scholar credentials
                    </button>
                  </div>
                </form>
              </div>
            </div>
          )}

        </div>
      )}

      {/* ADMIN TEACHERS REGISTRY MANAGEMENT TAB */}
      {currentTab === 'teachers' && (
        <div className="space-y-6 animate-fadeIn">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
              <h1 className="text-2xl font-extrabold text-[#0F172A]">Teachers instructing Faculty Registry</h1>
              <p className="text-[#64748B] text-xs">Appoint, evaluate, and adjust instructor information records.</p>
            </div>

            <button
              onClick={() => {
                setEditingTeacherId(null);
                setTeaName('');
                setTeaEmail('');
                setTeaEmpId('');
                setIsAddTeacherOpen(true);
              }}
              className="py-2.5 px-4 bg-[#1D4ED8] hover:bg-[#1E40AF] text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-2 cursor-pointer transition-transform active:scale-95"
            >
              <Lucide.PlusCircle className="w-4 h-4" />
              <span>Appoint Faculty Professor</span>
            </button>
          </div>

          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <Lucide.Search className="w-4 h-4 text-slate-400" />
            </div>
            <input
              type="text"
              value={teacherSearch}
              onChange={(e) => setTeacherSearch(e.target.value)}
              placeholder="Search faculty instructor name, qualifications, or department code..."
              className="w-full pl-9 pr-4 py-2.5 bg-white border border-[#E2E8F0] rounded-xl text-xs text-[#0F172A] focus:outline-none"
            />
          </div>

          {/* Teacher Rows Registry Table */}
          <div className="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs border-collapse">
                <thead>
                  <tr className="bg-[#F8FAFC] border-b border-[#E2E8F0] text-slate-500 font-mono uppercase text-[10px]">
                    <th className="p-4">PROFESSOR FULL NAME</th>
                    <th className="p-4">EMPLOYEE ID / BADGE</th>
                    <th className="p-4">DESIGNATION</th>
                    <th className="p-4">DEPT / CREDENTIALS</th>
                    <th className="p-4 text-right">CONTROLS</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 font-medium font-mono">
                  {filteredTeachers.map((tu) => {
                    const tp = teacherProfiles.find((p) => p.user_id === tu.id);
                    const dp = departments.find((d) => d.id === tp?.department_id);

                    return (
                      <tr key={tu.id} className="hover:bg-slate-50/30">
                        <td className="p-4 flex items-center gap-3 font-sans">
                          <img src={tu.avatar} alt="Teacher Avatar" className="w-8 h-8 rounded-full border border-slate-100 object-cover" />
                          <div>
                            <span className="font-extrabold text-[#0F172A] block">{tu.name}</span>
                            <span className="text-[10px] text-slate-400 font-mono">{tu.email}</span>
                          </div>
                        </td>
                        <td className="p-4 font-mono font-bold text-slate-700">{tp?.employee_id || 'EMP-1025'}</td>
                        <td className="p-4 text-slate-600">{tp?.designation || 'Lecturer'}</td>
                        <td className="p-4 font-sans text-slate-700">{dp ? `${dp.name} (${dp.code})` : 'Unassigned'}</td>
                        <td className="p-4 text-right space-x-2 font-sans">
                          <button
                            onClick={() => {
                              if (!tp) return;
                              setEditingTeacherId(tu.id);
                              setTeaName(tu.name);
                              setTeaEmail(tu.email);
                              setTeaPhone(tu.phone || '9482561560');
                              setTeaEmpId(tp.employee_id);
                              setTeaDept(tp.department_id);
                              setTeaDesig(tp.designation);
                              setTeaQual(tp.qualification);
                              setIsAddTeacherOpen(true);
                            }}
                            className="text-xs font-bold text-[#1D4ED8] hover:underline cursor-pointer"
                          >
                            Modify
                          </button>
                          <button
                            onClick={() => {
                              if (confirm(`Confirm physical deletion of instructor "${tu.name}" from global registry?`)) {
                                deleteTeacher(tu.id);
                              }
                            }}
                            className="text-xs font-bold text-red-600 hover:underline cursor-pointer mt-0.5"
                          >
                            Remove
                          </button>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </div>

          {/* Professor Add/Edit Modal overlays */}
          {isAddTeacherOpen && (
            <div className="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-fadeIn">
              <div className="bg-white border rounded-xl p-6 w-full max-w-lg shadow-xl relative">
                <button onClick={() => setIsAddTeacherOpen(false)} className="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
                  <Lucide.X className="w-5 h-5" />
                </button>
                <h3 className="text-lg font-extrabold text-[#0F172A] pb-2 border-b border-slate-50">
                  {editingTeacherId ? 'Modify Faculty details' : 'Admit Faculty Professor'}
                </h3>

                <form onSubmit={handleSaveTeacher} className="space-y-4 mt-4 text-xs font-bold">
                  <div>
                    <label className="block text-slate-500 mb-1">Professor Full Name</label>
                    <input
                      type="text"
                      value={teaName}
                      onChange={(e) => setTeaName(e.target.value)}
                      className="w-full border p-2 rounded focus:ring-1 focus:ring-blue-500"
                      required
                    />
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-slate-500 mb-1">Professional Email Profile</label>
                      <input
                        type="email"
                        value={teaEmail}
                        onChange={(e) => setTeaEmail(e.target.value)}
                        className="w-full border p-2 rounded"
                        required
                      />
                    </div>
                    <div>
                      <label className="block text-slate-500 mb-1">Contact Phone</label>
                      <input
                        type="text"
                        value={teaPhone}
                        onChange={(e) => setTeaPhone(e.target.value)}
                        className="w-full border p-2 rounded"
                        placeholder="9412586321"
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-slate-500 mb-1">Employee Badge ID</label>
                      <input
                        type="text"
                        value={teaEmpId}
                        onChange={(e) => setTeaEmpId(e.target.value)}
                        className="w-full border p-2 rounded focus:ring-1"
                        placeholder="EMP-102"
                        required
                      />
                    </div>
                    <div>
                      <label className="block text-slate-500 mb-1">Select Department sector</label>
                      <select
                        value={teaDept}
                        onChange={(e) => setTeaDept(Number(e.target.value))}
                        className="w-full border p-2 rounded bg-white text-slate-700"
                      >
                        {departments.map((d) => (
                          <option key={d.id} value={d.id}>{d.name}</option>
                        ))}
                      </select>
                    </div>
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-slate-500 mb-1">Designation Label</label>
                      <input
                        type="text"
                        value={teaDesig}
                        onChange={(e) => setTeaDesig(e.target.value)}
                        className="w-full border p-2 rounded"
                        required
                      />
                    </div>
                    <div>
                      <label className="block text-slate-500 mb-1">Qualifications list</label>
                      <input
                        type="text"
                        value={teaQual}
                        onChange={(e) => setTeaQual(e.target.value)}
                        className="w-full border p-2 rounded"
                        required
                      />
                    </div>
                  </div>

                  <div className="pt-4 border-t border-slate-100 flex justify-end gap-2 text-xs">
                    <button
                      type="button"
                      onClick={() => setIsAddTeacherOpen(false)}
                      className="px-4 py-2 border rounded hover:bg-slate-50"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      className="px-4 py-2 bg-[#1C2541] text-white rounded hover:bg-[#1D4ED8]"
                    >
                      Save Faculty Profile
                    </button>
                  </div>
                </form>
              </div>
            </div>
          )}

        </div>
      )}

      {/* ADMIN DEPARTMENTS TAB */}
      {currentTab === 'departments' && (
        <div className="space-y-6 animate-fadeIn">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
              <h1 className="text-2xl font-extrabold text-[#0F172A]">Institutional Departments Registry</h1>
              <p className="text-[#64748B] text-xs">Create major academic bodies, configure codes, and assign departmental heads.</p>
            </div>

            <button
              onClick={() => {
                setEditingDeptId(null);
                setDeptName('');
                setDeptCode('');
                setDeptHead(null);
                setIsAddDeptOpen(true);
              }}
              className="py-2.5 px-4 bg-[#1D4ED8] hover:bg-[#1E40AF] text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-2 cursor-pointer transition-transform active:scale-95"
            >
              <Lucide.PlusCircle className="w-4 h-4" />
              <span>Initialize Academic Sector</span>
            </button>
          </div>

          {/* Department cards grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {departments.map((dept) => {
              const headUser = users.find((u) => u.id === dept.head_id);
              return (
                <div key={dept.id} className="bg-white border border-[#E2E8F0] shadow-sm rounded-xl p-6 space-y-4 hover:shadow-md transition-shadow relative">
                  <div className="flex justify-between items-start">
                    <div className="w-10 h-10 bg-[#1D4ED8]/10 text-[#1D4ED8] rounded-xl flex items-center justify-center">
                      <Lucide.Building className="w-5 h-5 animate-pulse" />
                    </div>
                    <span className="font-mono text-xs font-black bg-[#F1F5F9] border border-slate-150 px-2.5 py-0.5 rounded-full text-slate-700">
                      {dept.code}
                    </span>
                  </div>

                  <div>
                    <h3 className="font-extrabold text-[#0F172A] text-base leading-tight">{dept.name}</h3>
                    {headUser ? (
                      <p className="text-xs text-slate-500 font-mono mt-1 flex items-center gap-1.5 pt-1">
                        <Lucide.UserSquare className="w-4 h-4 text-[#1D4ED8]" />
                        <span>Director Head: Profesor {headUser.name}</span>
                      </p>
                    ) : (
                      <p className="text-xs text-[#EF4444] font-mono mt-1">Lead Directorship Position Vacant</p>
                    )}
                  </div>

                  <div className="pt-3 border-t border-slate-50 flex justify-end gap-2 text-xs font-bold">
                    <button
                      onClick={() => {
                        setEditingDeptId(dept.id);
                        setDeptName(dept.name);
                        setDeptCode(dept.code);
                        setDeptHead(dept.head_id);
                        setIsAddDeptOpen(true);
                      }}
                      className="text-[#1D4ED8] hover:underline cursor-pointer"
                    >
                      Adjust details
                    </button>
                  </div>
                </div>
              );
            })}
          </div>

          {/* Department Add/Edit overlays modal */}
          {isAddDeptOpen && (
            <div className="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-fadeIn">
              <div className="bg-white border rounded-xl p-6 w-full max-w-sm shadow-xl relative">
                <button onClick={() => setIsAddDeptOpen(false)} className="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
                  <Lucide.X className="w-5 h-5" />
                </button>
                <h3 className="text-sm font-extrabold text-[#0F172A] pb-2 border-b border-slate-50 uppercase tracking-wider">
                  {editingDeptId ? 'Adjust Department sectors Attributes' : 'Create Academic Sector'}
                </h3>

                <form onSubmit={handleSaveDept} className="space-y-4 mt-4 text-xs font-bold">
                  <div>
                    <label className="block text-slate-500 mb-1">Department Sector name</label>
                    <input
                      type="text"
                      value={deptName}
                      onChange={(e) => setDeptName(e.target.value)}
                      className="w-full border p-2.5 rounded focus:ring-1"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-slate-500 mb-1">Department code label</label>
                    <input
                      type="text"
                      placeholder="e.g. CSE, MECH"
                      value={deptCode}
                      onChange={(e) => setDeptCode(e.target.value)}
                      className="w-full border p-2.5 rounded focus:ring-1"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-slate-500 mb-1">Assign Director Head</label>
                    <select
                      value={deptHead || ''}
                      onChange={(e) => setDeptHead(Number(e.target.value) || null)}
                      className="w-full border p-2 rounded bg-white text-slate-700"
                    >
                      <option value="">Leave director head vacant</option>
                      {activeTeachersList.map((t) => (
                        <option key={t.id} value={t.id}>{t.name}</option>
                      ))}
                    </select>
                  </div>

                  <div className="pt-4 border-t border-slate-100 flex justify-end gap-2 text-xs">
                    <button
                      type="button"
                      onClick={() => setIsAddDeptOpen(false)}
                      className="px-4 py-2 border rounded hover:bg-slate-50"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      className="px-4 py-2 bg-[#1C2541] hover:bg-[#1D4ED8] text-white rounded"
                    >
                      Save Department Section
                    </button>
                  </div>
                </form>
              </div>
            </div>
          )}

        </div>
      )}

      {/* ADMIN COURSES & ENROLLMENT MANAGEMENT TAB */}
      {currentTab === 'courses' && (
        <div className="space-y-6 animate-fadeIn">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
              <h1 className="text-2xl font-extrabold text-[#0F172A]">Academic Courses & Scholar Enrollments</h1>
              <p className="text-[#64748B] text-xs">Deploy semester programs, configure credits, and officially enroll registered student accounts.</p>
            </div>

            <div className="flex items-center gap-2 shrink-0">
              <button
                onClick={() => setIsEnrollStudentOpen(true)}
                className="px-4 py-2.5 border border-[#E2E8F0] bg-white hover:bg-slate-50 text-xs font-bold text-slate-700 rounded-lg shadow-sm flex items-center gap-1.5 cursor-pointer"
              >
                <Lucide.UserPlus className="w-4 h-4" />
                <span>Enroll Student In Course</span>
              </button>
              
              <button
                onClick={() => setIsAddCourseOpen(true)}
                className="px-4 py-2.5 bg-[#1D4ED8] hover:bg-[#1E40AF] text-[#ffffff] text-xs font-extrabold rounded-lg shadow-sm flex items-center gap-1.5 cursor-pointer"
              >
                <Lucide.PlusCircle className="w-4 h-4" />
                <span>Publish Term Program</span>
              </button>
            </div>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            {/* Left 6Cols: Course config catalog */}
            <div className="lg:col-span-6 bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
              <h3 className="text-xs font-extrabold text-slate-400 uppercase tracking-widest pb-1 border-b border-slate-50">
                Term Curriculum Catalog Config
              </h3>

              <div className="space-y-4 max-h-[500px] overflow-y-auto pr-1">
                {courses.map((crs) => {
                  const teacherUser = users.find((u) => u.id === crs.teacher_id);
                  const deptObj = departments.find((d) => d.id === crs.department_id);
                  
                  return (
                    <div key={crs.id} className="p-4 bg-slate-50 border border-slate-150 rounded-xl space-y-2">
                      <div className="flex justify-between items-start">
                        <span className="font-mono text-xs font-black bg-[#E2E8F0] px-2 py-0.5 rounded text-[#0F172A]">
                          {crs.code}
                        </span>
                        <Badge text={crs.status.toUpperCase()} type={crs.status === 'active' ? 'success' : 'danger'} />
                      </div>
                      <h4 className="font-bold text-xs text-[#0F172A]">{crs.title}</h4>
                      <p className="text-[11px] text-[#64748B] truncate">{crs.description}</p>
                      
                      <div className="pt-2 border-t border-slate-200/50 flex justify-between items-center text-[10px] font-mono">
                        <p className="text-slate-500 font-semibold">Tutor: Profesor {teacherUser?.name}</p>
                        <p className="text-indigo-600 font-black">Credits weight: {crs.credits}</p>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Right 6Cols: Active student class enrollments lists */}
            <div className="lg:col-span-6 bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
              <h3 className="text-xs font-extrabold text-slate-400 uppercase tracking-widest pb-1 border-b border-slate-50">
                Registered Student Course Enrollments
              </h3>

              <div className="divide-y divide-slate-100 max-h-[500px] overflow-y-auto pr-1">
                {enrollments.map((enr) => {
                  const scholarUser = users.find((u) => u.id === enr.student_id);
                  const crsObj = courses.find((c) => c.id === enr.course_id);

                  return (
                    <div key={enr.id} className="py-3 flex items-center justify-between gap-4">
                      <div>
                        <div className="flex items-center gap-1.5 text-xs text-[#0F172A] font-bold">
                          <span className="font-mono bg-[#F1F5F9] text-[#64748B] rounded px-1.5 py-0.5">{crsObj?.code}</span>
                          <span className="font-sans font-semibold text-[11.5px] pr-2">{scholarUser?.name}</span>
                        </div>
                        <span className="text-[9px] block text-slate-400 mt-1 font-mono">Timestamp enrolled: {new Date(enr.enrolled_at).toLocaleDateString()}</span>
                      </div>

                      <button
                        onClick={() => {
                          if (confirm(`Confirm de-registration and immediate removal of "${scholarUser?.name}" from course code "${crsObj?.code}" catalog?`)) {
                            removeEnrollment(enr.id);
                            setFeedbackAlert({ message: 'Enrollment registry removed.', type: 'danger' });
                          }
                        }}
                        className="py-1 px-2.5 border border-[#EF4444]/30 text-red-650 rounded hover:bg-red-50 text-[10px] font-bold cursor-pointer"
                      >
                        Un-enroll
                      </button>
                    </div>
                  );
                })}
              </div>
            </div>

          </div>

          {/* Create courses overlay modal */}
          {isAddCourseOpen && (
            <div className="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-fadeIn">
              <div className="bg-white border rounded-xl p-6 w-full max-w-md shadow-xl relative max-h-[85vh] overflow-y-auto">
                <button onClick={() => setIsAddCourseOpen(false)} className="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
                  <Lucide.X className="w-5 h-5" />
                </button>
                <h3 className="text-sm font-extrabold text-[#0F172A] pb-2 border-b border-slate-50 uppercase tracking-wider">
                  Deploy Term Program curriculum
                </h3>

                <form onSubmit={handleSaveCourse} className="space-y-4 mt-4 text-xs font-bold">
                  <div>
                    <label className="block text-slate-500 mb-1">Course Title Name</label>
                    <input
                      type="text"
                      value={crsTitle}
                      onChange={(e) => setCrsTitle(e.target.value)}
                      className="w-full border p-2.5 rounded focus:ring-1"
                      required
                    />
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-slate-500 mb-1">Code label identifier</label>
                      <input
                        type="text"
                        placeholder="e.g. CS-301, ME-302"
                        value={crsCode}
                        onChange={(e) => setCrsCode(e.target.value)}
                        className="w-full border p-2.5 rounded focus:ring-1"
                        required
                      />
                    </div>
                    <div>
                      <label className="block text-slate-500 mb-1">Credits Weightage</label>
                      <input
                        type="number"
                        value={crsCredits}
                        onChange={(e) => setCrsCredits(Number(e.target.value))}
                        className="w-full border p-2 rounded"
                        min={1}
                        required
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-slate-500 mb-1">Assign Faculty Instructor</label>
                      <select
                        value={crsTeacher}
                        onChange={(e) => setCrsTeacher(Number(e.target.value))}
                        className="w-full border p-2 rounded bg-white text-slate-700"
                      >
                        {activeTeachersList.map((tea) => (
                          <option key={tea.id} value={tea.id}>{tea.name}</option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="block text-slate-500 mb-1">Select Department sector</label>
                      <select
                        value={crsDept}
                        onChange={(e) => setCrsDept(Number(e.target.value))}
                        className="w-full border p-2 rounded bg-white text-slate-700"
                      >
                        {departments.map((d) => (
                          <option key={d.id} value={d.id}>{d.name}</option>
                        ))}
                      </select>
                    </div>
                  </div>

                  <div>
                    <label className="block text-slate-500 mb-1">Trimester Syllabus Description</label>
                    <textarea
                      value={crsDesc}
                      onChange={(e) => setCrsDesc(e.target.value)}
                      rows={4}
                      className="w-full border p-2 rounded font-mono"
                      required
                    />
                  </div>

                  <div className="pt-4 border-t border-slate-100 flex justify-end gap-2 text-xs">
                    <button
                      type="button"
                      onClick={() => setIsAddCourseOpen(false)}
                      className="px-4 py-2 border rounded hover:bg-slate-50"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      className="px-4 py-2 bg-[#1C2541] hover:bg-[#1D4ED8] text-white rounded"
                    >
                      Deploy program
                    </button>
                  </div>
                </form>
              </div>
            </div>
          )}

          {/* Enroll Students overlay modal */}
          {isEnrollStudentOpen && (
            <div className="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
              <div className="bg-white border rounded-xl p-6 w-full max-w-sm shadow-xl relative">
                <button onClick={() => setIsEnrollStudentOpen(false)} className="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
                  <Lucide.X className="w-5 h-5" />
                </button>
                <h3 className="text-sm font-extrabold text-[#0F172A] pb-2 border-b border-slate-50 uppercase tracking-wider">
                  Enroll Student Account
                </h3>

                <form onSubmit={handleEnrollStudentPost} className="space-y-4 mt-4 text-xs font-bold">
                  <div>
                    <label className="block text-slate-500 mb-1">Target Student Profile</label>
                    <select
                      value={enrollStudentId}
                      onChange={(e) => setEnrollStudentId(Number(e.target.value))}
                      className="w-full border p-2.5 rounded bg-white text-slate-700"
                    >
                      {activeStudentsList.map((stu) => (
                        <option key={stu.id} value={stu.id}>{stu.name} ({stu.email})</option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="block text-slate-500 mb-1">Academic Program Course</label>
                    <select
                      value={enrollCourseId}
                      onChange={(e) => setEnrollCourseId(Number(e.target.value))}
                      className="w-full border p-2.5 rounded bg-white text-slate-700 font-sans"
                    >
                      {courses.map((crs) => (
                        <option key={crs.id} value={crs.id}>{crs.code} - {crs.title}</option>
                      ))}
                    </select>
                  </div>

                  <div className="pt-4 border-t border-slate-100 flex justify-end gap-2 text-xs">
                    <button
                      type="button"
                      onClick={() => setIsEnrollStudentOpen(false)}
                      className="px-4 py-2 border rounded hover:bg-slate-50"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      className="px-4 py-2 bg-[#1C2541] hover:bg-[#1D4ED8] text-white rounded"
                    >
                      Process Enrollment
                    </button>
                  </div>
                </form>
              </div>
            </div>
          )}

        </div>
      )}

      {/* ADMIN PLACEMENTS CELL DIVISION TAB */}
      {currentTab === 'placements' && (
        <div className="space-y-6 animate-fadeIn">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
              <h1 className="text-2xl font-extrabold text-[#0F172A]">Placement Cell Vacancies & applications screening</h1>
              <p className="text-[#64748B] text-xs">Manage professional opportunities, update applications selection board parameters.</p>
            </div>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            {/* Left 5Cols: Post job openings input */}
            <div className="lg:col-span-4 bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
              <h3 className="text-xs font-extrabold text-slate-400 uppercase tracking-widest pb-1 border-b border-slate-50">
                Post Professional opening
              </h3>

              <form onSubmit={handlePostJobOpening} className="space-y-3 text-xs font-bold">
                <div>
                  <label className="block text-slate-500 mb-1">Company Corporate Brand name</label>
                  <input
                    type="text"
                    placeholder="e.g. Google India Private Ltd, Infosys"
                    value={jobCompany}
                    onChange={(e) => setJobCompany(e.target.value)}
                    className="w-full border p-2 rounded text-xs focus:ring-1"
                    required
                  />
                </div>

                <div>
                  <label className="block text-slate-500 mb-1">Vacancy Job Role Title</label>
                  <input
                    type="text"
                    placeholder="e.g. SDE Intern, Frontend Developer"
                    value={jobRole}
                    onChange={(e) => setJobRole(e.target.value)}
                    className="w-full border p-2 rounded text-xs"
                    required
                  />
                </div>

                <div className="grid grid-cols-2 gap-2">
                  <div>
                    <label className="block text-slate-500 mb-1">Location Block</label>
                    <input
                      type="text"
                      value={jobLoc}
                      onChange={(e) => setJobLoc(e.target.value)}
                      className="w-full border p-2 rounded text-xs"
                    />
                  </div>
                  <div>
                    <label className="block text-slate-500 mb-1">Stipend Stipulation</label>
                    <input
                      type="text"
                      value={jobStipend}
                      onChange={(e) => setJobStipend(e.target.value)}
                      className="w-full border p-2 rounded text-xs"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-slate-500 mb-1">Criteria Eligibility Rules</label>
                  <input
                    type="text"
                    value={jobEligibility}
                    onChange={(e) => setJobEligibility(e.target.value)}
                    className="w-full border p-2 rounded text-xs"
                  />
                </div>

                <div>
                  <label className="block text-slate-500 mb-1">Apply Date Deadline</label>
                  <input
                    type="date"
                    value={jobDeadline}
                    onChange={(e) => setJobDeadline(e.target.value)}
                    className="w-full border p-1.5 rounded font-mono"
                    required
                  />
                </div>

                <button
                  type="submit"
                  className="w-full py-2 bg-[#1C2541] hover:bg-[#1D4ED8] text-white text-xs font-extrabold rounded shadow-sm"
                >
                  Publish Openings
                </button>
              </form>
            </div>

            {/* Right 8Cols: Screening Student Applications Board */}
            <div className="lg:col-span-8 bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
              <h3 className="text-xs font-extrabold text-slate-400 uppercase tracking-widest pb-1 border-b border-slate-50">
                Academic screening list (Placement applications)
              </h3>

              <div className="divide-y divide-slate-100 max-h-[500px] overflow-y-auto pr-1">
                {placementApplications.map((app) => {
                  const job = placements.find((p) => p.id === app.placement_id);
                  const applicantUser = users.find((u) => u.id === app.student_id);

                  return (
                    <div key={app.id} className="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                      <div className="space-y-1.5 text-xs text-left">
                        <div className="flex items-center gap-2">
                          <span className="font-extrabold text-[#0F172A] bg-slate-100 p-1 rounded font-mono uppercase text-[9.5px] border">
                            {job?.company}
                          </span>
                          <span className="font-semibold text-slate-500">{job?.role}</span>
                        </div>
                        <p className="font-semibold font-sans text-slate-700">Applicant: {applicantUser?.name} ({applicantUser?.email})</p>
                        <span className="text-[9.5px] block text-slate-400 font-mono">Date applied: {new Date(app.applied_at).toLocaleString()}</span>
                      </div>

                      {/* Dropdown status toggler */}
                      <div className="flex items-center gap-2 shrink-0">
                        <select
                          value={app.status}
                          onChange={(e) => {
                            updatePlacementApplicationStatus(app.id, e.target.value as any);
                          }}
                          className={`border rounded-lg p-2 text-xs font-bold leading-tight ${
                            app.status === 'offered' ? 'bg-[#DCFCE7] text-[#15803D] border-[#BBF7D0]' :
                            app.status === 'rejected' ? 'bg-[#FEE2E2] text-[#B91C1C] border-[#FCA5A5]' :
                            'bg-blue-50 text-blue-700 border-blue-150'
                          }`}
                        >
                          <option value="applied">Applied (Initial Screening)</option>
                          <option value="shortlisted">Shortlisted for Board</option>
                          <option value="offered">Offered Role</option>
                          <option value="rejected">Rejected / Closed</option>
                        </select>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>

          </div>
        </div>
      )}

      {/* ADMIN ANNOUNCEMENTS Broadcaster */}
      {currentTab === 'announcements' && (
        <div className="space-y-6 animate-fadeIn">
          <div className="border-b border-[#E2E8F0] pb-4">
            <h1 className="text-2xl font-extrabold text-[#0F172A]">Broadcast System configuration Panels</h1>
            <p className="text-[#64748B] text-xs font-medium">Orchestrate university updates, deploy target announcements.</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            
            {/* Left Post Broadcast form */}
            <form onSubmit={handlePostAnnouncement} className="bg-white border rounded-xl p-6 shadow-sm space-y-4 text-xs font-bold">
              <h3 className="text-xs uppercase tracking-widest text-[#1D4ED8]" >Push Announcement broadcaster</h3>
              <div>
                <label className="block text-slate-500 mb-1">Headline title name</label>
                <input
                  type="text"
                  placeholder="e.g. End Semester Practical schedules disclosure"
                  value={annTitle}
                  onChange={(e) => setAnnTitle(e.target.value)}
                  className="w-full border p-2.5 rounded focus:ring-1"
                  required
                />
              </div>

              <div>
                <label className="block text-slate-500 mb-1">Audience target list</label>
                <select
                  value={annAudience}
                  onChange={(e) => setAnnAudience(e.target.value as any)}
                  className="w-full bg-white border p-2 rounded text-slate-700 font-sans"
                >
                  <option value="all">Display Broadcast to All accounts</option>
                  <option value="students">Display targeted to Scholar students only</option>
                  <option value="teachers">Display targeted to Faculty Tutors only</option>
                </select>
              </div>

              <div>
                <label className="block text-slate-500 mb-1">Broadcasting writeup comments</label>
                <textarea
                  value={annBody}
                  onChange={(e) => setAnnBody(e.target.value)}
                  rows={4}
                  className="w-full border p-2 rounded font-mono focus:outline-none"
                  required
                />
              </div>

              <button
                type="submit"
                className="w-full py-2.5 bg-[#1E3A8A] hover:bg-[#1D4ED8] text-white rounded text-xs font-semibold"
              >
                Trigger broadcast Push update
              </button>
            </form>

            {/* Right Display listing */}
            <div className="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
              <h3 className="text-xs font-extrabold text-slate-400 uppercase tracking-widest">Active Announcements Catalog</h3>

              <div className="space-y-3.5 max-h-[500px] overflow-y-auto pr-1">
                {announcements.map((an) => (
                  <div key={an.id} className="p-3 bg-slate-50 border rounded-lg font-mono text-[11px] leading-relaxed">
                    <div className="flex justify-between items-start gap-2 h-max mb-1">
                      <p className="font-extrabold text-[#0F172A] font-sans">{an.title}</p>
                      <Badge text={an.audience.toUpperCase()} type="warning" />
                    </div>
                    <p className="text-slate-500">"{an.body}"</p>
                  </div>
                ))}
              </div>
            </div>

          </div>
        </div>
      )}

      {/* ADMIN INSTUTIONAL ANALYTICS GRAPH */}
      {currentTab === 'analytics' && (
        <div className="space-y-6 animate-fadeIn">
          <div className="border-b border-[#E2E8F0] pb-4">
            <h1 className="text-2xl font-extrabold text-[#0F172A]">Campus Enrollment Ratio & Placement Success</h1>
            <p className="text-[#64748B] text-xs">Verify total student distributions ratios parameters.</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            
            {/* SVG Enrollment distributions */}
            <div className="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
              <h3 className="text-xs font-extrabold text-slate-400 uppercase tracking-widest pb-1 border-b">
                Scholars Enrolled by Department code
              </h3>

              <div className="pt-2">
                <div className="h-44 bg-[#F8FAFC] border border-slate-150 rounded-xl flex items-end justify-between p-6 relative">
                  <div className="flex flex-col items-center gap-1.5 z-10 w-16">
                    <span className="text-[10px] font-mono font-bold text-[#1D4ED8]">4 students</span>
                    <div className="w-8 bg-[#1D4ED8] rounded-t" style={{ height: '110px' }} />
                    <span className="text-[10px] font-black font-mono text-slate-400">CS</span>
                  </div>

                  <div className="flex flex-col items-center gap-1.5 z-10 w-16">
                    <span className="text-[10px] font-mono font-bold text-slate-400">0 students</span>
                    <div className="w-8 bg-slate-200 rounded-t" style={{ height: '4px' }} />
                    <span className="text-[10px] font-black font-mono text-slate-400">MATH</span>
                  </div>

                  <div className="flex flex-col items-center gap-1.5 z-10 w-16">
                    <span className="text-[10px] font-mono font-bold text-slate-400">0 students</span>
                    <div className="w-8 bg-slate-200 rounded-t" style={{ height: '4px' }} />
                    <span className="text-[10px] font-black font-mono text-slate-400">MECH</span>
                  </div>
                </div>
              </div>
            </div>

            {/* SVG placement selections metrics */}
            <div className="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
              <h3 className="text-xs font-extrabold text-slate-400 uppercase tracking-widest pb-1 border-b">
                Placement success ratios
              </h3>
              
              <div className="pt-2">
                <div className="h-44 bg-[#F8FAFC] border border-slate-150 rounded-xl flex items-end justify-between p-6 relative">
                  <div className="flex flex-col items-center gap-1.5 z-10 w-16">
                    <span className="text-[10px] font-mono font-bold text-emerald-605">1 Offered</span>
                    <div className="w-8 bg-[#10B981] rounded-t" style={{ height: '60px' }} />
                    <span className="text-[10px] font-black font-mono text-slate-400">Google SDE</span>
                  </div>

                  <div className="flex flex-col items-center gap-1.5 z-10 w-16">
                    <span className="text-[10px] font-mono font-bold text-[#1D4ED8]">1 Shortlisted</span>
                    <div className="w-8 bg-[#1D4ED8] rounded-t" style={{ height: '60px' }} />
                    <span className="text-[10px] font-black font-mono text-slate-400">Infosys QA</span>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      )}

      {/* ADMIN SETTINGS & DB TRANSFERS TAB */}
      {currentTab === 'settings' && (
        <div className="bg-white border border-[#E2E8F0] rounded-xl p-8 max-w-2xl mx-auto shadow-sm space-y-8 animate-fadeIn">
          <div>
            <h2 className="text-xl font-extrabold text-[#0F172A] tracking-tight">System Settings Configuration</h2>
            <p className="text-xs text-[#64748B] mt-1 font-serif">Simulate database administration backup protocols, configure institution banners.</p>
          </div>

          <form onSubmit={(e) => { e.preventDefault(); setFeedbackAlert({ message: 'Settings attributes saved in memory catalogs successfully.', type: 'success' }); }} className="space-y-4 text-xs font-bold text-slate-600">
            <div>
              <label className="block text-slate-450 mb-1">Institutional Entity Name</label>
              <input
                type="text"
                value={systemInstName}
                onChange={(e) => setSystemInstName(e.target.value)}
                className="w-full border p-2.5 rounded text-xs text-[#0F172A]"
                required
              />
            </div>

            <div>
              <label className="block text-slate-450 mb-1">Global Principal Admin Contact Email</label>
              <input
                type="email"
                value={systemAdminEmail}
                onChange={(e) => setSystemAdminEmail(e.target.value)}
                className="w-full border p-2.5 rounded text-xs text-[#0F172A]"
                required
              />
            </div>

            <div className="p-4 bg-slate-50 border rounded-xl space-y-3">
              <span className="text-[10px] font-black uppercase text-[#1D4ED8] tracking-widest font-mono">Institutional Database Admin Backup</span>
              <p className="text-[#64748B] leading-relaxed text-[11px] font-mono font-semibold">Generate a simulated institutional backup SQL dump matching PDO prepared schemas of pure MySQL structures instantly:</p>
              
              <button
                type="button"
                onClick={() => alert('Simulated SQL backups exported successfully: "edutrack_backup_20260605.sql" compiled inside downloads buffer.')}
                className="py-2 px-4 border bg-white hover:bg-slate-50 rounded text-xs font-black text-[#1D4ED8]"
              >
                Compile Offline SQL Backup Dump
              </button>
            </div>

            <div className="pt-4 border-t border-slate-100 flex justify-end">
              <button
                type="submit"
                className="py-2.5 px-6 bg-[#0F172A] hover:bg-[#1D4ED8] text-white rounded text-xs font-extrabold"
              >
                Save Settings
              </button>
            </div>
          </form>
        </div>
      )}

    </div>
  );
};
