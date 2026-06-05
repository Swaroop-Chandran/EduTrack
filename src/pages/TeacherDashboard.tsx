import React, { useState } from 'react';
import { useLms } from '../context/LmsContext';
import { StatCard, Badge, Alert, gradeFromMarks } from '../utils/helpers';
import * as Lucide from 'lucide-react';

interface TeacherDashboardProps {
  currentTab: string;
  setCurrentTab: (tab: string) => void;
}

export const TeacherDashboard: React.FC<TeacherDashboardProps> = ({ currentTab, setCurrentTab }) => {
  const {
    currentUser,
    courses,
    enrollments,
    assignments,
    submissions,
    exams,
    results,
    attendance,
    users,
    studentProfiles,
    createAssignment,
    uploadMaterial,
    evaluateSubmission,
    markAttendanceBatch,
    createExamSchedule,
    publishResult,
    getTeacherProfile
  } = useLms();

  // Local helper states
  const [feedbackAlert, setFeedbackAlert] = useState<{ message: string; type: 'success' | 'danger' } | null>(null);

  // Sub-selection states
  const [selectedCourseId, setSelectedCourseId] = useState<number>(1);
  const [selectedAssignmentId, setSelectedAssignmentId] = useState<number | null>(null);
  const [selectedSubmissionId, setSelectedSubmissionId] = useState<number | null>(null);
  const [evalMarks, setEvalMarks] = useState<number>(45);
  const [evalFeedback, setEvalFeedback] = useState('');

  // Post forms configurations:
  // 1. New Material State
  const [materialTitle, setMaterialTitle] = useState('');
  const [materialDesc, setMaterialDesc] = useState('');
  const [materialFileName, setMaterialFileName] = useState('');
  
  // 2. New Assignment State
  const [asgTitle, setAsgTitle] = useState('');
  const [asgDesc, setAsgDesc] = useState('');
  const [asgDueDate, setAsgDueDate] = useState('2026-06-15T23:59');
  const [asgMaxMarks, setAsgMaxMarks] = useState<number>(50);

  // 3. Roll-Call Attendance State
  const [attendanceDate, setAttendanceDate] = useState('2026-05-15');
  const [attendanceRecords, setAttendanceRecords] = useState<{ [stuUser_id: number]: 'present' | 'absent' | 'late' }>({});

  // 4. Create Exam State
  const [examTitle, setExamTitle] = useState('');
  const [examDate, setExamDate] = useState('25-06-21');
  const [examStartTime, setExamStartTime] = useState('09:30 AM');
  const [examDuration, setExamDuration] = useState<number>(120);
  const [examVenue, setExamVenue] = useState('Seminar Lab Alpha');
  const [examType, setExamType] = useState<'internal' | 'external' | 'practical' | 'viva'>('internal');
  const [examMaxMarks, setExamMaxMarks] = useState<number>(100);

  // 5. Publish Result State
  const [resExamId, setResExamId] = useState<number>(1);
  const [resStudentId, setResStudentId] = useState<number>(2); // Arjun
  const [resIntMarks, setResIntMarks] = useState<number>(25);
  const [resExtMarks, setResExtMarks] = useState<number>(65);

  if (!currentUser) return null;

  // Active Teacher profile fields
  const profile = getTeacherProfile(currentUser.id);

  // Filter courses taught by active teacher
  const teacherCourses = courses.filter((c) => c.teacher_id === currentUser.id && c.status === 'active');
  const teacherCourseIds = teacherCourses.map((c) => c.id);

  // Stats Counters estimate
  const taughtCount = teacherCourses.length;
  const teacherAssignments = assignments.filter((a) => teacherCourseIds.includes(a.course_id));
  const teacherSubmissions = submissions.filter((s) => teacherAssignments.map(a => a.id).includes(s.assignment_id));
  const pendingEvaluationCount = teacherSubmissions.filter((s) => s.status === 'submitted').length;

  // Handles: Attendance Mark Preparation
  const handleLoadClassRollCall = (cId: number) => {
    setSelectedCourseId(cId);
    // Find enrolled students in selected course ID
    const enrolledStudentIds = enrollments
      .filter((e) => e.course_id === cId && e.status === 'active')
      .map((e) => e.student_id);

    const initialMap: typeof attendanceRecords = {};
    enrolledStudentIds.forEach((id) => {
      initialMap[id] = 'present';
    });
    setAttendanceRecords(initialMap);
  };

  const handlePostClassRollCall = (e: React.FormEvent) => {
    e.preventDefault();
    const recordsArray = Object.keys(attendanceRecords).map((idStr) => {
      const studentId = parseInt(idStr, 10);
      return {
        studentId,
        status: attendanceRecords[studentId]
      };
    });

    if (recordsArray.length === 0) {
      setFeedbackAlert({ message: 'No enrolled student records found to mark.', type: 'danger' });
      return;
    }

    markAttendanceBatch(selectedCourseId, attendanceDate, recordsArray);
    setFeedbackAlert({ message: 'Batch class session attendance updated in database successfully.', type: 'success' });
    setCurrentTab('dashboard');
  };

  // Handles: Material Publish form
  const handlePostMaterial = (e: React.FormEvent) => {
    e.preventDefault();
    if (!materialTitle) {
      setFeedbackAlert({ message: 'Material title field is mandatory.', type: 'danger' });
      return;
    }

    uploadMaterial(selectedCourseId, materialTitle, materialDesc, materialFileName || 'lecture_slides.pdf', 'pdf');
    setMaterialTitle('');
    setMaterialDesc('');
    setMaterialFileName('');
    setFeedbackAlert({ message: 'Lectures course resource posted successfully!', type: 'success' });
    setCurrentTab('courses');
  };

  // Handles: New Assignment Publish form
  const handlePostAssignment = (e: React.FormEvent) => {
    e.preventDefault();
    if (!asgTitle || !asgDesc) {
      setFeedbackAlert({ message: 'Title and Description details are required.', type: 'danger' });
      return;
    }

    createAssignment(selectedCourseId, asgTitle, asgDesc, asgDueDate, Number(asgMaxMarks));
    setAsgTitle('');
    setAsgDesc('');
    setFeedbackAlert({ message: 'Course assignment issued and student warnings triggered successfully!', type: 'success' });
    setCurrentTab('assignments');
  };

  // Handles: Evaluates student homework
  const handlePostEvaluation = (e: React.FormEvent) => {
    e.preventDefault();
    if (selectedSubmissionId === null) return;

    evaluateSubmission(selectedSubmissionId, Number(evalMarks), evalFeedback);
    setSelectedSubmissionId(null);
    setEvalFeedback('');
    setFeedbackAlert({ message: 'Marks assessment evaluation successfully recorded.', type: 'success' });
  };

  // Handles: Examination scheduler
  const handlePostExam = (e: React.FormEvent) => {
    e.preventDefault();
    if (!examTitle || !examVenue) {
      setFeedbackAlert({ message: 'Exam Title and Venue are necessary fields.', type: 'danger' });
      return;
    }

    createExamSchedule(selectedCourseId, examTitle, examDate, examStartTime, Number(examDuration), examVenue, examType, Number(examMaxMarks));
    setExamTitle('');
    setExamVenue('');
    setFeedbackAlert({ message: 'Examination schedule published officially.', type: 'success' });
    setCurrentTab('exams');
  };

  // Handles: Publish Result Form
  const handlePublishTermResult = (e: React.FormEvent) => {
    e.preventDefault();
    
    const max = 100;
    const computedTotal = Number(resIntMarks) + Number(resExtMarks);
    const computedGrade = gradeFromMarks(computedTotal, max);
    const calculatedStatus: 'pass' | 'fail' = computedTotal >= 50 ? 'pass' : 'fail';

    publishResult(
      resStudentId,
      selectedCourseId,
      resExamId,
      resIntMarks,
      resExtMarks,
      computedGrade,
      3, // Defaulting to sem 3
      calculatedStatus
    );
    setFeedbackAlert({ message: 'Student examination results published onto grade book.', type: 'success' });
    setCurrentTab('exams');
  };

  return (
    <div className="space-y-6 text-left">
      {/* Dynamic Alerts feedback panel */}
      {feedbackAlert && (
        <Alert
          message={feedbackAlert.message}
          type={feedbackAlert.type}
          onDismiss={() => setFeedbackAlert(null)}
        />
      )}

      {/* RENDER VIEW CONTROLLER FOR TEACHERS */}
      {currentTab === 'dashboard' && (
        <div className="space-y-6 animate-fadeIn">
          {/* Dashboard Header Banner greeting */}
          <div>
            <span className="text-xs uppercase font-black tracking-widest text-[#1D4ED8]">Academic Portal</span>
            <h1 className="text-3xl font-extrabold text-[#0F172A] tracking-tight mt-1">
              Welcome back, Profesor {currentUser.name} 📚
            </h1>
            <p className="text-[#64748B] text-xs">Analyze student engagement metrics, evaluate scripts, and record checklists.</p>
          </div>

          {/* Statistical KPI summary layout cards */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <StatCard
              title="Assigned Courses"
              value={taughtCount}
              icon="BookOpen"
              color="navy"
              subtext="Instructing curricula term"
            />
            <StatCard
              title="Active Assignments"
              value={teacherAssignments.length}
              icon="ClipboardList"
              color="blue"
              subtext="Weekly active assessments"
            />
            <StatCard
              title="Pending Gradings"
              value={pendingEvaluationCount}
              icon="Award"
              color="amber"
              subtext={`${pendingEvaluationCount} homework scripts to mark`}
            />
            <StatCard
              title="Campus Engagement"
              value="93%"
              icon="Activity"
              color="green"
              subtext="Average student class turnout"
            />
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            {/* Left 8Cols: Submissions list queue needing marks */}
            <div className="lg:col-span-8 bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
              <div className="flex items-center justify-between pb-2 border-b border-slate-50">
                <h3 className="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider">Submissions Grading Queue</h3>
                {pendingEvaluationCount > 0 && (
                  <span className="text-xs font-bold text-amber-600 bg-amber-50 border border-amber-100 px-2 py-0.5 rounded-full">
                    {pendingEvaluationCount} scripts waiting
                  </span>
                )}
              </div>

              {/* Submission rows for evaluations */}
              <div className="divide-y divide-slate-100 max-h-96 overflow-y-auto pr-1 space-y-2">
                {teacherSubmissions
                  .filter((s) => s.status === 'submitted')
                  .map((sub) => {
                    const asg = assignments.find((a) => a.id === sub.assignment_id);
                    const course = courses.find((c) => c.id === asg?.course_id);
                    const studentObj = users.find((u) => u.id === sub.student_id);

                    return (
                      <div key={sub.id} className="p-4 bg-slate-50 border border-slate-100 hover:border-blue-400/30 rounded-xl flex items-start justify-between gap-4 transition-colors">
                        <div className="space-y-1.5 min-w-0 flex-1">
                          <div className="flex items-center gap-2">
                            <span className="font-mono text-[10px] font-bold bg-[#E2E8F0] tracking-wide px-1.5 py-0.5 rounded text-[#0F172A]">
                              {course?.code}
                            </span>
                            <span className="text-[11px] font-bold text-slate-700">{studentObj?.name}</span>
                          </div>
                          <h4 className="text-xs font-bold text-[#0F172A] leading-snug truncate">{asg?.title}</h4>
                          <p className="text-[11px] text-[#64748B] font-mono italic">File: "{sub.file_path}"</p>
                          {sub.text_submission && (
                            <p className="text-[10px] bg-white border border-slate-100 p-2 rounded text-slate-500 font-mono line-clamp-1">"{sub.text_submission}"</p>
                          )}
                        </div>

                        <button
                          onClick={() => {
                            setSelectedSubmissionId(sub.id);
                            // Autofill max marks as default template
                            setEvalMarks(asg?.max_marks ? Math.round(asg.max_marks * 0.9) : 40);
                            setEvalFeedback('Well developed structure, codes compilation is solid and requirements were met fully.');
                          }}
                          className="py-1 px-3 bg-[#1D4ED8] hover:bg-[#1E40AF] text-white font-bold text-xs rounded-lg shrink-0"
                        >
                          Grade Script
                        </button>
                      </div>
                    );
                  })}
                
                {teacherSubmissions.filter((s) => s.status === 'submitted').length === 0 && (
                  <div className="text-center p-12 text-xs text-slate-400 space-y-2 bg-slate-50/50 border border-dashed rounded-xl">
                    <Lucide.Smile className="w-8 h-8 text-emerald-500 mx-auto animate-bounce" />
                    <p className="font-bold text-slate-700">Excellent! Registry Cleared</p>
                    <p>No active homework deliverables waiting evaluation records.</p>
                  </div>
                )}
              </div>
            </div>

            {/* Right 4Cols: Action widgets links */}
            <div className="lg:col-span-4 space-y-6">
              
              {/* Quick links shortcut menu */}
              <div className="bg-[#0F172A] text-white rounded-xl p-6 shadow-md space-y-4">
                <h3 className="text-xs font-black uppercase tracking-widest text-[#1D4ED8]">LMS Quick-deck</h3>
                <div className="space-y-2">
                  <button
                    onClick={() => {
                      if (teacherCourses.length > 0) handleLoadClassRollCall(teacherCourses[0].id);
                      setCurrentTab('attendance');
                    }}
                    className="w-full text-left py-2 px-3 border border-white/5 hover:border-slate-500 rounded-lg text-xs font-medium flex items-center justify-between"
                  >
                    <span>Record attendance roll-call</span>
                    <Lucide.CheckSquare className="w-4 h-4 text-emerald-400" />
                  </button>
                  <button
                    onClick={() => {
                      if (teacherCourses.length > 0) setSelectedCourseId(teacherCourses[0].id);
                      setCurrentTab('create_assignment');
                    }}
                    className="w-full text-left py-2 px-3 border border-white/5 hover:border-slate-500 rounded-lg text-xs font-medium flex items-center justify-between"
                  >
                    <span>Issue new assignment</span>
                    <Lucide.PlusCircle className="w-4 h-4 text-blue-400" />
                  </button>
                  <button
                    onClick={() => {
                      setSelectedCourseId(teacherCourses[0]?.id || 1);
                      setCurrentTab('upload_material');
                    }}
                    className="w-full text-left py-2 px-3 border border-white/5 hover:border-slate-500 rounded-lg text-xs font-medium flex items-center justify-between"
                  >
                    <span>Upload lecture resources</span>
                    <Lucide.UploadCloud className="w-4 h-4 text-indigo-400" />
                  </button>
                </div>
              </div>

            </div>
          </div>

          {/* Submissions Assessment Overlay Modal if selected */}
          {selectedSubmissionId !== null && (
            <div className="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-fadeIn">
              <div className="bg-white border border-[#E2E8F0] rounded-xl p-6 w-full max-w-lg shadow-xl space-y-4 relative">
                <button
                  onClick={() => setSelectedSubmissionId(null)}
                  className="absolute top-4 right-4 text-slate-400 hover:text-slate-600 cursor-pointer"
                >
                  <Lucide.X className="w-5 h-5" />
                </button>
                <div className="flex items-center gap-2 pb-2 border-b border-slate-50">
                  <Lucide.Award className="w-5 h-5 text-[#1D4ED8]" />
                  <h3 className="text-base font-extrabold text-[#0F172A] tracking-tight">Evaluate Script Marks</h3>
                </div>

                <form onSubmit={handlePostEvaluation} className="space-y-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase font-mono mb-1.5">Score Points Obtained</label>
                    <input
                      type="number"
                      value={evalMarks}
                      onChange={(e) => setEvalMarks(Number(e.target.value))}
                      className="w-full border border-slate-200 p-2 rounded focus:ring-1 focus:ring-blue-500"
                      min={0}
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase font-mono mb-1.5">Instructor Tutors Review comments</label>
                    <textarea
                      value={evalFeedback}
                      onChange={(e) => setEvalFeedback(e.target.value)}
                      rows={4}
                      className="w-full border border-slate-200 p-2 rounded focus:ring-1 focus:ring-blue-500"
                      required
                    />
                  </div>

                  <div className="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button
                      type="button"
                      onClick={() => setSelectedSubmissionId(null)}
                      className="px-4 py-2 border rounded text-xs font-medium hover:bg-slate-50"
                    >
                      Close view
                    </button>
                    <button
                      type="submit"
                      className="px-4 py-2 bg-[#10B981] text-white rounded text-xs font-bold hover:bg-[#059669]"
                    >
                      Publish assessment Evaluation
                    </button>
                  </div>
                </form>
              </div>
            </div>
          )}

        </div>
      )}

      {/* TEACHER CURRICULUM COURSES TAB */}
      {currentTab === 'courses' && (
        <div className="space-y-6 animate-fadeIn">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
              <h1 className="text-2xl font-extrabold text-[#0F172A]">Instructing Course Curricula</h1>
              <p className="text-[#64748B] text-xs">Manage educational materials, upload presentations slides, or check syllabus modules.</p>
            </div>
            
            <button
              onClick={() => {
                if (teacherCourses.length > 0) setSelectedCourseId(teacherCourses[0].id);
                setCurrentTab('upload_material');
              }}
              className="py-2 px-4 bg-[#1D4ED8] hover:bg-[#1E40AF] text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-2 cursor-pointer"
            >
              <Lucide.UploadCloud className="w-4 h-4" />
              <span>Share Lecture Document</span>
            </button>
          </div>

          {/* Teacher Course Rows list */}
          <div className="space-y-6">
            {teacherCourses.map((c) => {
              // Find active enrollments
              const activeCount = enrollments.filter((e) => e.course_id === c.id && e.status === 'active').length;
              return (
                <div key={c.id} className="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
                  <div className="flex flex-col sm:flex-row justify-between sm:items-center gap-2 border-b border-slate-100 pb-3">
                    <div>
                      <span className="text-[10px] uppercase font-black text-slate-400 font-mono tracking-wider">{c.code}</span>
                      <h3 className="font-extrabold text-[#0F172A] text-lg tracking-tight mt-0.5">{c.title}</h3>
                      <p className="text-xs text-slate-500 font-medium italic mt-0.5">Credits weightage: {c.credits} points • Classroom Year: Term III</p>
                    </div>

                    <div className="flex items-center gap-1 bg-[#F1F5F9] p-2 rounded-lg border border-slate-100 font-medium">
                      <Lucide.Users className="w-4 h-4 text-[#1D4ED8]" />
                      <span className="text-xs font-bold text-slate-700">{activeCount} Registered Students</span>
                    </div>
                  </div>

                  <p className="text-xs text-[#64748B] leading-relaxed max-w-4xl">{c.description}</p>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* TEACHER UPLOAD MATERIAL FORM VIEW */}
      {currentTab === 'upload_material' && (
        <div className="bg-white border border-[#E2E8F0] p-8 max-w-xl mx-auto rounded-xl shadow-sm space-y-6 animate-fadeIn">
          <div>
            <h2 className="text-xl font-extrabold text-[#0F172A] tracking-tight">Upload Lecture Document</h2>
            <p className="text-xs text-[#64748B] mt-1">Disclose lecture sheets slideshows and codes writeups directly to course catalog.</p>
          </div>

          <form onSubmit={handlePostMaterial} className="space-y-4">
            <div>
              <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Target Class Curriculum</label>
              <select
                value={selectedCourseId}
                onChange={(e) => setSelectedCourseId(Number(e.target.value))}
                className="w-full bg-[#fdfdfd] border border-[#E2E8F0] p-2.5 rounded text-xs font-bold text-[#0F172A]"
              >
                {teacherCourses.map((c) => (
                  <option key={c.id} value={c.id}>{c.code} - {c.title}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Lectures Topic Name</label>
              <input
                type="text"
                placeholder="e.g. Unit III: Normalization and ER Schemes paradigms"
                value={materialTitle}
                onChange={(e) => setMaterialTitle(e.target.value)}
                className="w-full border border-slate-200 p-2.5 rounded text-xs focus:ring-1 focus:ring-blue-500 outline-none"
                required
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Description syllabus mapping</label>
              <textarea
                placeholder="Detailed explanations regarding homework mapping chapter checklist..."
                value={materialDesc}
                onChange={(e) => setMaterialDesc(e.target.value)}
                rows={4}
                className="w-full border border-slate-200 p-2.5 rounded text-xs focus:ring-1 focus:ring-blue-500 outline-none font-mono"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Attach file name mock identifier</label>
              <input
                type="text"
                placeholder="normalization_lecture_guide_pdf"
                value={materialFileName}
                onChange={(e) => setMaterialFileName(e.target.value)}
                className="w-full border border-slate-200 p-2 rounded text-xs font-mono"
              />
            </div>

            <div className="pt-4 border-t border-slate-100 flex justify-end gap-2 text-xs font-bold">
              <button
                type="button"
                onClick={() => setCurrentTab('courses')}
                className="px-4 py-2 border rounded text-slate-600 hover:bg-slate-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                className="px-4 py-2 bg-[#1C2541] hover:bg-[#1D4ED8] text-white rounded"
              >
                Publish Resources
              </button>
            </div>
          </form>
        </div>
      )}

      {/* TEACHER ASSIGNMENTS LIST VIEW */}
      {currentTab === 'assignments' && (
        <div className="space-y-6 animate-fadeIn">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
              <h1 className="text-2xl font-extrabold text-[#0F172A]">Issued coursework Assignments</h1>
              <p className="text-[#64748B] text-xs">Create new task deadlines, customize maximum marks, check script uploads.</p>
            </div>

            <button
              onClick={() => {
                if (teacherCourses.length > 0) setSelectedCourseId(teacherCourses[0].id);
                setCurrentTab('create_assignment');
              }}
              className="py-2 px-4 bg-[#1D4ED8] hover:bg-[#1E40AF] text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-2 cursor-pointer"
            >
              <Lucide.PlusCircle className="w-4 h-4" />
              <span>Issue New Task</span>
            </button>
          </div>

          {/* Table display listings */}
          <div className="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs text-[#0F172A] border-collapse">
                <thead>
                  <tr className="bg-slate-50 text-slate-500 border-b border-[#E2E8F0] font-mono uppercase">
                    <th className="p-4">CODE</th>
                    <th className="p-4">ASSIGNMENT TITLE</th>
                    <th className="p-4">DUE DATE DEADLINE</th>
                    <th className="p-4 text-center">SUBMISSIONS</th>
                    <th className="p-4 text-center">MAX MARKS</th>
                    <th className="p-4 text-right">STATUS</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 font-mono font-medium">
                  {teacherAssignments.map((asg) => {
                    const course = courses.find((c) => c.id === asg.course_id);
                    // submissions count
                    const subCount = submissions.filter((s) => s.assignment_id === asg.id).length;
                    return (
                      <tr key={asg.id} className="hover:bg-slate-50/30">
                        <td className="p-4 font-bold">{course?.code}</td>
                        <td className="p-4 font-sans text-slate-800 font-semibold">{asg.title}</td>
                        <td className="p-4">{new Date(asg.due_date).toLocaleString()}</td>
                        <td className="p-4 text-center text-[#1D4ED8] font-bold">{subCount} scripts uploaded</td>
                        <td className="p-4 text-center">{asg.max_marks} pts</td>
                        <td className="p-4 text-right">
                          <Badge text={asg.status.toUpperCase()} type={asg.status === 'active' ? 'success' : 'danger'} />
                        </td>
                      </tr>
                    );
                  })}
                  {teacherAssignments.length === 0 && (
                    <tr>
                      <td colSpan={6} className="p-8 text-center text-slate-400">
                        No customized coursework tasks posted for active terms yet.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* TEACHER CREATE ASSIGNMENT FORM */}
      {currentTab === 'create_assignment' && (
        <div className="bg-white border border-[#E2E8F0] p-8 max-w-xl mx-auto rounded-xl shadow-sm space-y-6 animate-fadeIn">
          <div>
            <h2 className="text-xl font-extrabold text-[#0F172A] tracking-tight">Issue New Task</h2>
            <p className="text-xs text-[#64748B] mt-1">Specify target dates, evaluation weights and instructional codes details.</p>
          </div>

          <form onSubmit={handlePostAssignment} className="space-y-4">
            <div>
              <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Target Curriculum</label>
              <select
                value={selectedCourseId}
                onChange={(e) => setSelectedCourseId(Number(e.target.value))}
                className="w-full bg-[#fdfdfd] border border-[#E2E8F0] p-2.5 rounded text-xs font-bold text-[#0F172A]"
              >
                {teacherCourses.map((c) => (
                  <option key={c.id} value={c.id}>{c.code} - {c.title}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Assignment Title Name</label>
              <input
                type="text"
                placeholder="e.g. Lab Exercise-IV: Index structures design query tests"
                value={asgTitle}
                onChange={(e) => setAsgTitle(e.target.value)}
                className="w-full border border-slate-200 p-2.5 rounded text-xs"
                required
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Requirements descriptions</label>
              <textarea
                placeholder="Write algorithmic assumptions detailed guide map structures..."
                value={asgDesc}
                onChange={(e) => setAsgDesc(e.target.value)}
                rows={5}
                className="w-full border border-slate-200 p-2.5 rounded text-xs font-mono"
                required
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Maximum Mark Points</label>
                <input
                  type="number"
                  value={asgMaxMarks}
                  onChange={(e) => setAsgMaxMarks(Number(e.target.value))}
                  className="w-full border border-slate-200 p-2 rounded text-xs"
                  min={1}
                  required
                />
              </div>
              <div>
                <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Due Deadline Date</label>
                <input
                  type="datetime-local"
                  value={asgDueDate}
                  onChange={(e) => setAsgDueDate(e.target.value)}
                  className="w-full border border-slate-200 p-2 rounded text-xs font-mono"
                  required
                />
              </div>
            </div>

            <div className="pt-4 border-t border-slate-100 flex justify-end gap-2 text-xs font-bold">
              <button
                type="button"
                onClick={() => setCurrentTab('assignments')}
                className="px-4 py-2 border rounded text-slate-600 hover:bg-slate-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                className="px-4 py-2 bg-[#1C2541] hover:bg-[#1D4ED8] text-white rounded"
              >
                Publish Assignments
              </button>
            </div>
          </form>
        </div>
      )}

      {/* TEACHER TAKE ATTENDANCE BATCH FORM */}
      {currentTab === 'attendance' && (
        <div className="space-y-6 animate-fadeIn">
          <div className="border-b border-[#E2E8F0] pb-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
              <h1 className="text-2xl font-extrabold text-[#0F172A]">Mark Class Attendance</h1>
              <p className="text-[#64748B] text-xs">Choose target curriculum, select session date and toggle present/absent states.</p>
            </div>

            {/* Course Taught selector trigger */}
            <div className="flex items-center gap-2">
              <label htmlFor="teacher_att_course_select" className="text-xs font-bold text-slate-500 font-mono">CURRICULUM:</label>
              <select
                id="teacher_att_course_select"
                value={selectedCourseId}
                onChange={(e) => handleLoadClassRollCall(Number(e.target.value))}
                className="bg-white border border-[#E2E8F0] p-2 rounded text-xs font-bold text-[#0F172A]"
              >
                {teacherCourses.map((c) => (
                  <option key={c.id} value={c.id}>{c.code} - {c.title}</option>
                ))}
              </select>
            </div>
          </div>

          <form onSubmit={handlePostClassRollCall} className="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-6">
            <div className="flex items-center gap-4 max-w-sm">
              <div className="w-full">
                <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Session Date Record</label>
                <input
                  type="date"
                  value={attendanceDate}
                  onChange={(e) => setAttendanceDate(e.target.value)}
                  className="w-full border border-[#E2E8F0] p-2 rounded text-xs font-bold focus:outline-none"
                  required
                />
              </div>
            </div>

            {/* Class Roll list checkbox toggler */}
            <div className="border-t border-slate-50 pt-4 space-y-3">
              <h3 className="text-xs font-extrabold text-[#0F172A] uppercase tracking-wider mb-2">Class Turnout Roll Call</h3>

              <div className="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                {Object.keys(attendanceRecords).map((idStr) => {
                  const studentId = parseInt(idStr, 10);
                  const sUser = users.find((u) => u.id === studentId);
                  const currentStatus = attendanceRecords[studentId];

                  return (
                    <div key={studentId} className="py-3 flex items-center justify-between gap-4">
                      <div className="flex items-center gap-3">
                        <img src={sUser?.avatar} alt={sUser?.name} className="w-8 h-8 rounded-full object-cover bg-gray-100 border text-xs" />
                        <div>
                          <span className="text-xs font-bold text-[#0F172A] block">{sUser?.name}</span>
                          <span className="text-[10px] text-slate-400 font-mono block">Eメール: {sUser?.email}</span>
                        </div>
                      </div>

                      {/* Tri-state status buttons present, absent, late */}
                      <div className="flex items-center border border-slate-200 rounded-lg overflow-hidden p-0.5 bg-slate-50 font-bold text-[10px]">
                        <button
                          type="button"
                          onClick={() => setAttendanceRecords((prev) => ({ ...prev, [studentId]: 'present' }))}
                          className={`px-3 py-1.5 rounded-md ${
                            currentStatus === 'present' ? 'bg-[#10B981] text-white' : 'text-[#64748B] hover:text-[#0F172A]'
                          }`}
                        >
                          PRESENT
                        </button>
                        <button
                          type="button"
                          onClick={() => setAttendanceRecords((prev) => ({ ...prev, [studentId]: 'absent' }))}
                          className={`px-3 py-1.5 rounded-md ${
                            currentStatus === 'absent' ? 'bg-[#EF4444] text-white' : 'text-[#64748B] hover:text-[#0F172A]'
                          }`}
                        >
                          ABSENT
                        </button>
                        <button
                          type="button"
                          onClick={() => setAttendanceRecords((prev) => ({ ...prev, [studentId]: 'late' }))}
                          className={`px-3 py-1.5 rounded-md ${
                            currentStatus === 'late' ? 'bg-[#F59E0B] text-white' : 'text-[#64748B] hover:text-[#0F172A]'
                          }`}
                        >
                          LATE
                        </button>
                      </div>
                    </div>
                  );
                })}
                {Object.keys(attendanceRecords).length === 0 && (
                  <div className="p-8 text-center text-slate-400 text-xs">
                    No registered students found enrolled inside selected course curriculum.
                  </div>
                )}
              </div>
            </div>

            <div className="border-t border-slate-150 pt-4 flex justify-end">
              <button
                type="submit"
                className="py-2.5 px-6 bg-[#166534] hover:bg-[#10B981] text-white text-xs font-bold rounded-lg shadow-sm"
              >
                Submit Class Roll Call
              </button>
            </div>
          </form>
        </div>
      )}

      {/* TEACHER EXAM SCHEDULES & RESULTS TAB */}
      {currentTab === 'exams' && (
        <div className="space-y-6 animate-fadeIn">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
              <h1 className="text-2xl font-extrabold text-[#0F172A]">Exams Scheduling & Grades</h1>
              <p className="text-[#64748B] text-xs">Orchestrate internal written examinations maps, modify grade distributions.</p>
            </div>

            <div className="flex flex-wrap items-center gap-2">
              <button
                onClick={() => {
                  setSelectedCourseId(teacherCourses[0]?.id || 1);
                  setCurrentTab('create_exam');
                }}
                className="px-4 py-2 border border-[#E2E8F0] hover:bg-slate-50 text-xs font-bold text-slate-700 rounded-lg flex items-center gap-1.5"
              >
                <Lucide.PlusCircle className="w-4 h-4" />
                <span>Schedule written test</span>
              </button>
              <button
                onClick={() => {
                  setSelectedCourseId(teacherCourses[0]?.id || 1);
                  setCurrentTab('publish_result');
                }}
                className="px-4 py-2 bg-[#1D4ED8] hover:bg-[#1E40AF] text-[#ffffff] text-xs font-extrabold rounded-lg flex items-center gap-1.5 shrink-0"
              >
                <Lucide.Award className="w-4 h-4" />
                <span>Publish Exam Result</span>
              </button>
            </div>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            {/* Left 6Cols: Exam Schedules */}
            <div className="lg:col-span-6 bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
              <h3 className="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider pb-2 border-b border-slate-50">
                Scheduled Examination Sheets
              </h3>

              <div className="space-y-3 divide-y divide-slate-100 max-h-96 overflow-y-auto pr-1">
                {exams.filter((ex) => teacherCourseIds.includes(ex.course_id)).map((ex) => {
                  const course = courses.find((c) => c.id === ex.course_id);
                  return (
                    <div key={ex.id} className="pt-3 text-left">
                      <div className="flex justify-between items-start gap-2 mb-1">
                        <div>
                          <span className="text-[9.5px] font-mono font-bold text-slate-400">{course?.code}</span>
                          <h4 className="text-xs font-bold text-[#0F172A] mt-0.5">{ex.title}</h4>
                        </div>
                        <Badge text={ex.type.toUpperCase()} type="danger" />
                      </div>
                      <p className="text-[10px] text-slate-500 font-mono">Date: {ex.exam_date} @ {ex.start_time} • Max: {ex.max_marks} pts • Venue: {ex.venue}</p>
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Right 6Cols: Disclosed result stats */}
            <div className="lg:col-span-6 bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
              <h3 className="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider pb-2 border-b border-slate-50">
                Disclosed Student Grade Books
              </h3>

              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs border-collapse">
                  <thead>
                    <tr className="bg-slate-50 text-slate-500 border-b border-slate-100 font-mono uppercase">
                      <th className="p-2.5">STUDENT</th>
                      <th className="p-2.5">EXAM</th>
                      <th className="p-2.5 text-center">OBTAINED</th>
                      <th className="p-2.5 text-right font-semibold">GRADE</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 font-mono font-medium">
                    {results.filter((r) => teacherCourseIds.includes(r.course_id)).map((res) => {
                      const sUser = users.find((u) => u.id === res.student_id);
                      const exObj = exams.find((e) => e.id === res.exam_id);
                      return (
                        <tr key={res.id} className="hover:bg-slate-50/20">
                          <td className="p-2.5 font-sans font-bold">{sUser?.name || 'Student'}</td>
                          <td className="p-2.5 text-[11px] truncate">{exObj?.title || 'Written test'}</td>
                          <td className="p-2.5 text-center">{res.total_marks}/100</td>
                          <td className="p-2.5 text-right">
                            <Badge text={res.grade} type={res.status === 'pass' ? 'success' : 'danger'} />
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </div>
      )}

      {/* TEACHER CREATE EXAM SCHEDULE FORM */}
      {currentTab === 'create_exam' && (
        <div className="bg-white border border-[#E2E8F0] p-8 max-w-xl mx-auto rounded-xl shadow-sm space-y-6 animate-fadeIn">
          <div>
            <h2 className="text-xl font-extrabold text-[#0F172A] tracking-tight">Schedule Examination Card</h2>
            <p className="text-xs text-[#64748B] mt-1">Deploy invigilator instructions, classroom venue codes and timing constraints.</p>
          </div>

          <form onSubmit={handlePostExam} className="space-y-4">
            <div>
              <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Target Course Selection</label>
              <select
                value={selectedCourseId}
                onChange={(e) => setSelectedCourseId(Number(e.target.value))}
                className="w-full bg-[#fdfdfd] border border-[#E2E8F0] p-2.5 rounded text-xs font-bold text-[#0F172A]"
              >
                {teacherCourses.map((c) => (
                  <option key={c.id} value={c.id}>{c.code} - {c.title}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Exam Written Title</label>
              <input
                type="text"
                placeholder="e.g. End Semester Practical Exam Schema-A"
                value={examTitle}
                onChange={(e) => setExamTitle(e.target.value)}
                className="w-full border border-slate-200 p-2.5 rounded text-xs"
                required
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Exam Format Type</label>
                <select
                  value={examType}
                  onChange={(e) => setExamType(e.target.value as any)}
                  className="w-full bg-white border border-slate-200 p-2 rounded text-xs font-bold text-slate-700"
                >
                  <option value="internal">Internal Assessment</option>
                  <option value="external">University End Sem</option>
                  <option value="practical">Lab Practical</option>
                  <option value="viva">Viva-Voce Board</option>
                </select>
              </div>
              <div>
                <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Exam Date Stamp</label>
                <input
                  type="date"
                  value={examDate}
                  onChange={(e) => setExamDate(e.target.value)}
                  className="w-full border border-slate-200 p-2 rounded text-xs font-mono"
                  required
                />
              </div>
            </div>

            <div className="grid grid-cols-3 gap-4">
              <div>
                <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Start Hour</label>
                <input
                  type="text"
                  placeholder="09:30 AM"
                  value={examStartTime}
                  onChange={(e) => setExamStartTime(e.target.value)}
                  className="w-full border border-slate-200 p-2 rounded text-xs font-mono"
                  required
                />
              </div>
              <div>
                <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Minutes Duration</label>
                <input
                  type="number"
                  value={examDuration}
                  onChange={(e) => setExamDuration(Number(e.target.value))}
                  className="w-full border border-slate-200 p-2 rounded text-xs"
                  required
                />
              </div>
              <div>
                <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Max Score Scale</label>
                <input
                  type="number"
                  value={examMaxMarks}
                  onChange={(e) => setExamMaxMarks(Number(e.target.value))}
                  className="w-full border border-slate-200 p-2 rounded text-xs"
                  required
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Classroom Venue Block</label>
              <input
                type="text"
                value={examVenue}
                onChange={(e) => setExamVenue(e.target.value)}
                className="w-full border border-slate-200 p-2.5 rounded text-xs"
                required
              />
            </div>

            <div className="pt-4 border-t border-slate-100 flex justify-end gap-2 text-xs font-bold">
              <button
                type="button"
                onClick={() => setCurrentTab('exams')}
                className="px-4 py-2 border rounded text-slate-600 hover:bg-slate-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                className="px-4 py-2 bg-[#1C2541] hover:bg-[#1D4ED8] text-white rounded"
              >
                Publish Exam Schedule
              </button>
            </div>
          </form>
        </div>
      )}

      {/* TEACHER PUBLISH STUDENT RESULTS FORM */}
      {currentTab === 'publish_result' && (
        <div className="bg-white border border-[#E2E8F0] p-8 max-w-xl mx-auto rounded-xl shadow-sm space-y-6 animate-fadeIn">
          <div>
            <h2 className="text-xl font-extrabold text-[#0F172A] tracking-tight font-sans">Publish Examination Result</h2>
            <p className="text-xs text-[#64748B] mt-1">Commit student coursework exam grades indices with automated percentiles recalculation.</p>
          </div>

          <form onSubmit={handlePublishTermResult} className="space-y-4">
            <div>
              <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Select Course Curriculae</label>
              <select
                value={selectedCourseId}
                onChange={(e) => setSelectedCourseId(Number(e.target.value))}
                className="w-full bg-[#fdfdfd] border border-[#E2E8F0] p-2.5 rounded text-xs font-bold text-[#0F172A]"
              >
                {teacherCourses.map((c) => (
                  <option key={c.id} value={c.id}>{c.code} - {c.title}</option>
                ))}
              </select>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Target Written Exam</label>
                <select
                  value={resExamId}
                  onChange={(e) => setResExamId(Number(e.target.value))}
                  className="w-full bg-white border border-slate-200 p-2 rounded text-xs font-bold text-slate-700 font-sans"
                >
                  {exams.filter(ex=>ex.course_id === selectedCourseId).map((ex) => (
                    <option key={ex.id} value={ex.id}>{ex.title}</option>
                  ))}
                  {exams.filter(ex=>ex.course_id === selectedCourseId).length === 0 && (
                    <option value={1}>Written Exam Demo</option>
                  )}
                </select>
              </div>
              
              <div>
                <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Target Student</label>
                <select
                  value={resStudentId}
                  onChange={(e) => setResStudentId(Number(e.target.value))}
                  className="w-full bg-white border border-slate-200 p-2 rounded text-xs font-bold text-slate-700 font-sans"
                >
                  {users.filter(u=>u.role === 'student' && u.status === 'active').map((u) => (
                    <option key={u.id} value={u.id}>{u.name}</option>
                  ))}
                </select>
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-bold text-slate-500 font-mono mb-1">Internal Assessment score (Max 30)</label>
                <input
                  type="number"
                  value={resIntMarks}
                  onChange={(e) => setResIntMarks(Number(e.target.value))}
                  className="w-full border border-slate-200 p-2 rounded text-xs"
                  max={30}
                  min={0}
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-500 font-mono mb-1">University Written exam (Max 70)</label>
                <input
                  type="number"
                  value={resExtMarks}
                  onChange={(e) => setResExtMarks(Number(e.target.value))}
                  className="w-full border border-slate-200 p-2 rounded text-xs"
                  max={70}
                  min={0}
                  required
                />
              </div>
            </div>

            <div className="pt-4 border-t border-slate-100 flex justify-end gap-2 text-xs font-bold">
              <button
                type="button"
                onClick={() => setCurrentTab('exams')}
                className="px-4 py-2 border rounded text-slate-600 hover:bg-slate-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                className="px-4 py-2 bg-[#1E3A8A] hover:bg-[#1D4ED8] text-white rounded"
              >
                Publish Marks Grades
              </button>
            </div>
          </form>
        </div>
      )}

      {/* TEACHER COURSE ANALYTICS VIEW */}
      {currentTab === 'analytics' && (
        <div className="space-y-6 animate-fadeIn">
          <div className="border-b border-[#E2E8F0] pb-4">
            <h1 className="text-2xl font-extrabold text-[#0F172A]">Class Performance Insights</h1>
            <p className="text-[#64748B] text-xs">Verify students grading curves average milestones indexes.</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            
            {/* SVG Class Performance Trends widget */}
            <div className="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
              <h3 className="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider pb-2 border-b border-slate-50">
                Grade Turnout Distributions
              </h3>

              {/* Dist curves SVG lines visualization */}
              <div className="pt-2">
                <div className="h-44 bg-slate-50 border border-slate-150 rounded-xl relative flex items-end justify-between p-6">
                  <div className="absolute inset-y-0 left-10 border-r border-slate-200/50" />
                  <div className="absolute inset-x-0 bottom-10 border-t border-slate-200/50" />

                  <div className="flex flex-col items-center gap-1.5 z-10 w-12">
                    <span className="text-[10px] font-mono text-[#1D4ED8] font-bold">85-100%</span>
                    <div className="w-6 bg-[#1D4ED8] rounded-t" style={{ height: '110px' }} />
                    <span className="text-[10px] font-black font-mono text-slate-400">Class A</span>
                  </div>

                  <div className="flex flex-col items-center gap-1.5 z-10 w-12">
                    <span className="text-[10px] font-mono text-[#10B981] font-bold">70-84%</span>
                    <div className="w-6 bg-[#10B981] rounded-t" style={{ height: '70px' }} />
                    <span className="text-[10px] font-black font-mono text-slate-400">Class B</span>
                  </div>

                  <div className="flex flex-col items-center gap-1.5 z-10 w-12">
                    <span className="text-[10px] font-mono text-amber-500 font-bold">50-69%</span>
                    <div className="w-6 bg-[#F59E0B] rounded-t" style={{ height: '30px' }} />
                    <span className="text-[10px] font-black font-mono text-slate-400">Class C</span>
                  </div>

                  <div className="flex flex-col items-center gap-1.5 z-10 w-12">
                    <span className="text-[10px] font-mono text-red-500 font-bold">&lt; 50%</span>
                    <div className="w-6 bg-[#EF4444] rounded-t" style={{ height: '10px' }} />
                    <span className="text-[10px] font-black font-mono text-slate-400">Failures</span>
                  </div>
                </div>
              </div>
            </div>

            {/* Assessment Checklist rules */}
            <div className="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
              <h3 className="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider pb-2 border-b border-[#E2E8F0]">
                Institutional Grading Key
              </h3>
              <ul className="space-y-4 text-xs text-[#64748B] leading-relaxed">
                <li className="flex gap-2.5 items-start">
                  <span className="text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded font-black font-mono">A Scale</span>
                  <p>Represents score marks &gt;= 90%. Yields GPA weighting score points matching <strong>10.0 points</strong>.</p>
                </li>
                <li className="flex gap-2.5 items-start">
                  <span className="text-indigo-700 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded font-black font-mono">B Scale</span>
                  <p>Represents score marks between 70%-89%. Yields GPAs matching <strong>8.0-9.0 points</strong>.</p>
                </li>
                <li className="flex gap-2.5 items-start">
                  <span className="text-red-700 bg-red-50 border border-red-100 px-2 py-0.5 rounded font-black font-mono">F Scale</span>
                  <p>Represents score marks below 50%. Yields critical failure, necessitating re-examinations papers.</p>
                </li>
              </ul>
            </div>

          </div>
        </div>
      )}

      {/* TEACHER PROFILE DETAILS VIEW */}
      {currentTab === 'profile' && profile && (
        <div className="bg-white border border-[#E2E8F0] rounded-xl p-8 max-w-2xl mx-auto shadow-sm space-y-6 animate-fadeIn">
          <div className="flex items-center gap-4 border-b border-slate-50 pb-4">
            <img src={currentUser.avatar} alt={currentUser.name} className="w-16 h-16 rounded-full object-cover border-2 border-[#10B981]" />
            <div>
              <p className="text-xs text-slate-400 font-mono uppercase tracking-widest">Tutor Registry Directory ID</p>
              <h2 className="text-2xl font-bold text-[#0F172A] tracking-tight">{currentUser.name}</h2>
              <p className="text-xs text-[#64748B] italic font-mono mt-0.5">EmpID: {profile.employee_id} • Designation: {profile.designation}</p>
            </div>
          </div>

          <div className="space-y-4 text-sm">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <span className="text-[10px] text-slate-400 font-bold uppercase tracking-widest block font-mono">Professional Email</span>
                <span className="text-[#0F172A] font-semibold text-sm block mt-1">{currentUser.email}</span>
              </div>
              <div>
                <span className="text-[10px] text-slate-400 font-bold uppercase tracking-widest block font-mono">Emergency Phone contact</span>
                <span className="text-[#0F172A] font-semibold text-sm block mt-1">{currentUser.phone || '9412586326'}</span>
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <span className="text-[10px] text-slate-400 font-bold uppercase tracking-widest block font-mono">Qualifications academic</span>
                <span className="text-[#0F172A] font-semibold text-sm block mt-1">{profile.qualification || 'M.Tech in Computational Science, Ph.D'}</span>
              </div>
              <div>
                <span className="text-[10px] text-slate-400 font-bold uppercase tracking-widest block font-mono">Primary Department mapping</span>
                <span className="text-[#0F172A] font-semibold text-sm block mt-1">Computer Science and Engineering</span>
              </div>
            </div>
          </div>
        </div>
      )}

    </div>
  );
};
