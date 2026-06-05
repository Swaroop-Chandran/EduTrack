import React, { createContext, useContext, useState, useEffect } from 'react';
import {
  User,
  StudentProfile,
  TeacherProfile,
  Department,
  Course,
  Enrollment,
  Material,
  Assignment,
  Submission,
  Exam,
  Result,
  Attendance,
  Placement,
  PlacementApplication,
  Notification,
  Announcement
} from '../types';
import {
  initialUsers,
  initialStudentProfiles,
  initialTeacherProfiles,
  initialDepartments,
  initialCourses,
  initialEnrollments,
  initialMaterials,
  initialAssignments,
  initialSubmissions,
  initialExams,
  initialResults,
  initialAttendance,
  initialPlacements,
  initialPlacementApplications,
  initialNotifications,
  initialAnnouncements
} from '../db/seedData';

interface LmsContextType {
  users: User[];
  studentProfiles: StudentProfile[];
  teacherProfiles: TeacherProfile[];
  departments: Department[];
  courses: Course[];
  enrollments: Enrollment[];
  materials: Material[];
  assignments: Assignment[];
  submissions: Submission[];
  exams: Exam[];
  results: Result[];
  attendance: Attendance[];
  placements: Placement[];
  placementApplications: PlacementApplication[];
  notifications: Notification[];
  announcements: Announcement[];

  currentUser: User | null;
  login: (email: string, password: string) => { success: boolean; error?: string };
  logout: () => void;
  getStudentProfile: (userId: number) => StudentProfile | undefined;
  getTeacherProfile: (userId: number) => TeacherProfile | undefined;

  // Student methods
  submitAssignment: (assignmentId: number, textSubmission: string, fileName: string) => void;
  applyPlacement: (placementId: number) => { success: boolean; message: string };

  // Teacher methods
  createAssignment: (courseId: number, title: string, description: string, dueDate: string, maxMarks: number) => void;
  uploadMaterial: (courseId: number, title: string, description: string, fileName: string, fileType: string) => void;
  evaluateSubmission: (submissionId: number, marksObtained: number, feedback: string) => void;
  markAttendanceBatch: (courseId: number, date: string, records: { studentId: number; status: 'present' | 'absent' | 'late' }[]) => void;
  createExamSchedule: (courseId: number, title: string, examDate: string, startTime: string, durationMinutes: number, venue: string, type: 'internal' | 'external' | 'practical' | 'viva', maxMarks: number) => void;
  publishResult: (studentId: number, courseId: number, examId: number, internalMarks: number, externalMarks: number, grade: string, semester: number, status: 'pass' | 'fail') => void;

  // Admin methods
  addStudent: (studentData: Omit<User, 'id' | 'role' | 'avatar' | 'created_at'> & Omit<StudentProfile, 'id' | 'user_id' | 'cgpa'>) => void;
  editStudent: (userId: number, updatedUser: Partial<User>, updatedProfile: Partial<StudentProfile>) => void;
  deleteStudent: (userId: number) => void;
  addTeacher: (teacherData: Omit<User, 'id' | 'role' | 'avatar' | 'created_at'> & Omit<TeacherProfile, 'id' | 'user_id'>) => void;
  editTeacher: (userId: number, updatedUser: Partial<User>, updatedProfile: Partial<TeacherProfile>) => void;
  deleteTeacher: (userId: number) => void;
  addDepartment: (name: string, code: string, headId: number | null) => void;
  editDepartment: (id: number, name: string, code: string, headId: number | null) => void;
  addCourse: (title: string, code: string, deptId: number, teacherId: number, credits: number, semester: number, description: string) => void;
  editCourse: (id: number, title: string, code: string, deptId: number, teacherId: number, credits: number, semester: number, description: string, status: 'active' | 'inactive') => void;
  addEnrollment: (studentId: number, courseId: number) => { success: boolean; message: string };
  removeEnrollment: (id: number) => void;
  addPlacement: (placementData: Omit<Placement, 'id' | 'posted_by' | 'created_at' | 'status'>) => void;
  updatePlacementStatus: (placementId: number, status: 'open' | 'closed') => void;
  updatePlacementApplicationStatus: (appId: number, status: PlacementApplication['status']) => void;
  sendAnnouncement: (title: string, body: string, audience: Announcement['audience'], departmentId: number | null) => void;

  // Notification helpers
  markNotificationRead: (id: number) => void;
  clearNotifications: () => void;
}

const LmsContext = createContext<LmsContextType | undefined>(undefined);

export const LmsProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [users, setUsers] = useState<User[]>([]);
  const [studentProfiles, setStudentProfiles] = useState<StudentProfile[]>([]);
  const [teacherProfiles, setTeacherProfiles] = useState<TeacherProfile[]>([]);
  const [departments, setDepartments] = useState<Department[]>([]);
  const [courses, setCourses] = useState<Course[]>([]);
  const [enrollments, setEnrollments] = useState<Enrollment[]>([]);
  const [materials, setMaterials] = useState<Material[]>([]);
  const [assignments, setAssignments] = useState<Assignment[]>([]);
  const [submissions, setSubmissions] = useState<Submission[]>([]);
  const [exams, setExams] = useState<Exam[]>([]);
  const [results, setResults] = useState<Result[]>([]);
  const [attendance, setAttendance] = useState<Attendance[]>([]);
  const [placements, setPlacements] = useState<Placement[]>([]);
  const [placementApplications, setPlacementApplications] = useState<PlacementApplication[]>([]);
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [announcements, setAnnouncements] = useState<Announcement[]>([]);

  const [currentUser, setCurrentUser] = useState<User | null>(null);

  // Initial loading from localStorage or Seed Data
  useEffect(() => {
    const loadTable = <T,>(key: string, initialData: T[]): T[] => {
      const stored = localStorage.getItem(`edutrack_${key}`);
      if (stored) {
        try {
          return JSON.parse(stored);
        } catch (e) {
          console.error(`Error loading table ${key}`, e);
        }
      }
      localStorage.setItem(`edutrack_${key}`, JSON.stringify(initialData));
      return initialData;
    };

    setUsers(loadTable('users', initialUsers));
    setStudentProfiles(loadTable('student_profiles', initialStudentProfiles));
    setTeacherProfiles(loadTable('teacher_profiles', initialTeacherProfiles));
    setDepartments(loadTable('departments', initialDepartments));
    setCourses(loadTable('courses', initialCourses));
    setEnrollments(loadTable('enrollments', initialEnrollments));
    setMaterials(loadTable('materials', initialMaterials));
    setAssignments(loadTable('assignments', initialAssignments));
    setSubmissions(loadTable('submissions', initialSubmissions));
    setExams(loadTable('exams', initialExams));
    setResults(loadTable('results', initialResults));
    setAttendance(loadTable('attendance', initialAttendance));
    setPlacements(loadTable('placements', initialPlacements));
    setPlacementApplications(loadTable('placement_applications', initialPlacementApplications));
    setNotifications(loadTable('notifications', initialNotifications));
    setAnnouncements(loadTable('announcements', initialAnnouncements));

    // Load session info if saved
    const savedUserId = localStorage.getItem('edutrack_session_user_id');
    if (savedUserId) {
      const allUsers = loadTable('users', initialUsers);
      const matched = allUsers.find((u) => u.id === parseInt(savedUserId, 10));
      if (matched && matched.status === 'active') {
        setCurrentUser(matched);
      } else {
        localStorage.removeItem('edutrack_session_user_id');
      }
    }
  }, []);

  // Sync state helpers to localStorage on database update
  const syncTable = <T,>(key: string, data: T[]) => {
    localStorage.setItem(`edutrack_${key}`, JSON.stringify(data));
  };

  const getStudentProfile = (userId: number) => {
    return studentProfiles.find((sp) => sp.user_id === userId);
  };

  const getTeacherProfile = (userId: number) => {
    return teacherProfiles.find((tp) => tp.user_id === userId);
  };

  // Auth Operations
  const login = (email: string, password: string) => {
    const user = users.find((u) => u.email.toLowerCase().trim() === email.toLowerCase().trim());
    if (!user) {
      return { success: false, error: 'User account does not exist' };
    }
    if (user.password !== password) {
      return { success: false, error: 'Incorrect email or password' };
    }
    if (user.status === 'inactive') {
      return { success: false, error: 'This user account has been deactivated' };
    }
    
    setCurrentUser(user);
    localStorage.setItem('edutrack_session_user_id', user.id.toString());
    return { success: true };
  };

  const logout = () => {
    setCurrentUser(null);
    localStorage.removeItem('edutrack_session_user_id');
  };

  // Student Operations
  const submitAssignment = (assignmentId: number, textSubmission: string, fileName: string) => {
    if (!currentUser) return;
    const newSubmission: Submission = {
      id: Math.max(0, ...submissions.map((s) => s.id)) + 1,
      assignment_id: assignmentId,
      student_id: currentUser.id,
      file_path: fileName || 'assignment_upload.pdf',
      text_submission: textSubmission,
      submitted_at: new Date().toISOString(),
      marks_obtained: null,
      feedback: null,
      status: 'submitted'
    };

    // Prevent duplicate submissions by filtering out existing
    const filtered = submissions.filter(
      (s) => !(s.assignment_id === assignmentId && s.student_id === currentUser.id)
    );

    const updated = [...filtered, newSubmission];
    setSubmissions(updated);
    syncTable('submissions', updated);

    // Notify teacher
    const assignObj = assignments.find((a) => a.id === assignmentId);
    if (assignObj) {
      const teacherNotif: Notification = {
        id: Math.max(0, ...notifications.map((n) => n.id)) + 1,
        user_id: assignObj.teacher_id,
        title: 'Assignment Submitted',
        message: `${currentUser.name} submitted assignment: "${assignObj.title}"`,
        type: 'info',
        is_read: 0,
        created_at: new Date().toISOString()
      };
      const updatedNotifs = [teacherNotif, ...notifications];
      setNotifications(updatedNotifs);
      syncTable('notifications', updatedNotifs);
    }
  };

  const applyPlacement = (placementId: number) => {
    if (!currentUser) return { success: false, message: 'Not logged in' };
    
    // Check if duplicate
    const existing = placementApplications.find(
      (pa) => pa.placement_id === placementId && pa.student_id === currentUser.id
    );
    if (existing) {
      return { success: false, message: 'You have already applied for this placement' };
    }

    const newApp: PlacementApplication = {
      id: Math.max(0, ...placementApplications.map((pa) => pa.id)) + 1,
      placement_id: placementId,
      student_id: currentUser.id,
      applied_at: new Date().toISOString(),
      status: 'applied'
    };

    const updated = [...placementApplications, newApp];
    setPlacementApplications(updated);
    syncTable('placement_applications', updated);

    return { success: true, message: 'Application submitted successfully!' };
  };

  // Teacher Operations
  const createAssignment = (courseId: number, title: string, description: string, dueDate: string, maxMarks: number) => {
    if (!currentUser) return;
    const newAssign: Assignment = {
      id: Math.max(0, ...assignments.map((a) => a.id)) + 1,
      course_id: courseId,
      teacher_id: currentUser.id,
      title,
      description,
      due_date: dueDate,
      max_marks: maxMarks,
      status: 'active',
      created_at: new Date().toISOString()
    };

    const updated = [...assignments, newAssign];
    setAssignments(updated);
    syncTable('assignments', updated);

    // Create notifications for enrolled students
    const enrolledStudents = enrollments
      .filter((e) => e.course_id === courseId && e.status === 'active')
      .map((e) => e.student_id);

    const cour = courses.find((c) => c.id === courseId);
    const newNotifications = [...notifications];
    let nId = Math.max(0, ...notifications.map((n) => n.id)) + 1;

    enrolledStudents.forEach((studentId) => {
      newNotifications.unshift({
        id: nId++,
        user_id: studentId,
        title: 'New Assignment Added',
        message: `New assignment: "${title}" has been details for ${cour?.title || 'course'}`,
        type: 'warning',
        is_read: 0,
        created_at: new Date().toISOString()
      });
    });

    setNotifications(newNotifications);
    syncTable('notifications', newNotifications);
  };

  const uploadMaterial = (courseId: number, title: string, description: string, fileName: string, fileType: string) => {
    if (!currentUser) return;
    const newMaterial: Material = {
      id: Math.max(0, ...materials.map((m) => m.id)) + 1,
      course_id: courseId,
      teacher_id: currentUser.id,
      title,
      description,
      file_path: fileName || 'lecture.pdf',
      file_type: fileType || 'pdf',
      uploaded_at: new Date().toISOString()
    };

    const updated = [...materials, newMaterial];
    setMaterials(updated);
    syncTable('materials', updated);
  };

  const evaluateSubmission = (submissionId: number, marksObtained: number, feedback: string) => {
    const updatedSubmissions = submissions.map((sub) => {
      if (sub.id === submissionId) {
        return {
          ...sub,
          marks_obtained: marksObtained,
          feedback,
          status: 'evaluated' as const
        };
      }
      return sub;
    });

    setSubmissions(updatedSubmissions);
    syncTable('submissions', updatedSubmissions);

    // Notify student
    const evaluatedSub = submissions.find((s) => s.id === submissionId);
    if (evaluatedSub) {
      const assignmentObj = assignments.find((a) => a.id === evaluatedSub.assignment_id);
      const studentNotif: Notification = {
        id: Math.max(0, ...notifications.map((n) => n.id)) + 1,
        user_id: evaluatedSub.student_id,
        title: 'Assignment Evaluated',
        message: `Your assignment: "${assignmentObj?.title || 'Assignment'}" has been graded: ${marksObtained}/${assignmentObj?.max_marks || 100}`,
        type: 'success',
        is_read: 0,
        created_at: new Date().toISOString()
      };
      
      const updatedNotifications = [studentNotif, ...notifications];
      setNotifications(updatedNotifications);
      syncTable('notifications', updatedNotifications);
    }
  };

  const markAttendanceBatch = (courseId: number, date: string, records: { studentId: number; status: 'present' | 'absent' | 'late' }[]) => {
    if (!currentUser) return;
    
    // Filter out historical attendance records for same student/course/date
    const studentIdsToMark = records.map((r) => r.studentId);
    const filteredAttendance = attendance.filter(
      (a) => !(a.course_id === courseId && a.date === date && studentIdsToMark.includes(a.student_id))
    );

    let nextId = Math.max(0, ...attendance.map((a) => a.id)) + 1;
    const newRecords: Attendance[] = records.map((r) => ({
      id: nextId++,
      student_id: r.studentId,
      course_id: courseId,
      date,
      status: r.status,
      marked_by: currentUser.id,
      created_at: new Date().toISOString()
    }));

    const updated = [...filteredAttendance, ...newRecords];
    setAttendance(updated);
    syncTable('attendance', updated);
  };

  const createExamSchedule = (courseId: number, title: string, examDate: string, startTime: string, durationMinutes: number, venue: string, type: 'internal' | 'external' | 'practical' | 'viva', maxMarks: number) => {
    if (!currentUser) return;
    const newExam: Exam = {
      id: Math.max(0, ...exams.map((e) => e.id)) + 1,
      course_id: courseId,
      title,
      exam_date: examDate,
      start_time: startTime,
      duration_minutes: durationMinutes,
      venue,
      type,
      max_marks: maxMarks,
      created_by: currentUser.id,
      created_at: new Date().toISOString()
    };

    const updated = [...exams, newExam];
    setExams(updated);
    syncTable('exams', updated);

    // Notify course enrolled students
    const enrolledStudents = enrollments
      .filter((e) => e.course_id === courseId && e.status === 'active')
      .map((e) => e.student_id);

    const cour = courses.find((c) => c.id === courseId);
    const newNotifications = [...notifications];
    let nId = Math.max(0, ...notifications.map((n) => n.id)) + 1;

    enrolledStudents.forEach((studentId) => {
      newNotifications.unshift({
        id: nId++,
        user_id: studentId,
        title: 'New Exam Scheduled',
        message: `An exam has been scheduled for ${cour?.title || 'course'}: "${title}" on ${examDate}`,
        type: 'danger',
        is_read: 0,
        created_at: new Date().toISOString()
      });
    });

    setNotifications(newNotifications);
    syncTable('notifications', newNotifications);
  };

  const publishResult = (studentId: number, courseId: number, examId: number, internalMarks: number, externalMarks: number, grade: string, semester: number, status: 'pass' | 'fail') => {
    const totalMarks = Number(internalMarks) + Number(externalMarks);
    
    // Check duplication
    const filtered = results.filter(
      (r) => !(r.student_id === studentId && r.course_id === courseId && r.exam_id === examId)
    );

    const newResult: Result = {
      id: Math.max(0, ...results.map((r) => r.id)) + 1,
      student_id: studentId,
      course_id: courseId,
      exam_id: examId,
      internal_marks: Number(internalMarks),
      external_marks: Number(externalMarks),
      total_marks: totalMarks,
      grade,
      semester,
      status,
      published_at: new Date().toISOString()
    };

    const updated = [...filtered, newResult];
    setResults(updated);
    syncTable('results', updated);

    // Re-calculate Student's Cumulative GPAs (Average GPA)
    // We mock recalculation out of total marks in all results of student
    const studentResults = updated.filter((r) => r.student_id === studentId);
    if (studentResults.length > 0) {
      let cgpaSum = 0;
      studentResults.forEach((r) => {
        // Simple grade letter mappings to GPAs (Outstanding values):
        // A=10, B+=9, B=8, C=7, D=6, F=0
        const g = r.grade.toUpperCase();
        if (g.startsWith('A')) cgpaSum += 9.5;
        else if (g === 'B+') cgpaSum += 8.5;
        else if (g === 'B') cgpaSum += 7.5;
        else if (g === 'C') cgpaSum += 6.5;
        else if (g === 'D') cgpaSum += 5.5;
        else cgpaSum += 0;
      });
      const finalCgpa = Number((cgpaSum / studentResults.length).toFixed(2));
      
      const updatedProfiles = studentProfiles.map((p) => {
        if (p.user_id === studentId) {
          return { ...p, cgpa: finalCgpa };
        }
        return p;
      });
      setStudentProfiles(updatedProfiles);
      syncTable('student_profiles', updatedProfiles);
    }

    // Send Notification to Student
    const cour = courses.find((c) => c.id === courseId);
    const resultNotif: Notification = {
      id: Math.max(0, ...notifications.map((n) => n.id)) + 1,
      user_id: studentId,
      title: 'Exam Result Published',
      message: `Your results for ${cour?.title || 'Course'} have been published. Grade: "${grade}"`,
      type: 'success',
      is_read: 0,
      created_at: new Date().toISOString()
    };
    const updatedNotifs = [resultNotif, ...notifications];
    setNotifications(updatedNotifs);
    syncTable('notifications', updatedNotifs);
  };

  // Admin Student Operations
  const addStudent = (studentData: Omit<User, 'id' | 'role' | 'avatar' | 'created_at'> & Omit<StudentProfile, 'id' | 'user_id' | 'cgpa'>) => {
    const nextUserId = Math.max(0, ...users.map((u) => u.id)) + 1;
    const nextProfileId = Math.max(0, ...studentProfiles.map((sp) => sp.id)) + 1;

    // Use a high-quality default dynamic avatar placeholder if not provided
    const newStudentUser: User = {
      id: nextUserId,
      name: studentData.name,
      email: studentData.email,
      password: studentData.password || 'student123',
      role: 'student',
      avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150',
      phone: studentData.phone,
      status: studentData.status || 'active',
      created_at: new Date().toISOString()
    };

    const newStudentProfile: StudentProfile = {
      id: nextProfileId,
      user_id: nextUserId,
      roll_no: studentData.roll_no,
      department_id: Number(studentData.department_id),
      year: Number(studentData.year),
      semester: Number(studentData.semester),
      cgpa: 0.00,
      dob: studentData.dob,
      address: studentData.address
    };

    const updatedUsers = [...users, newStudentUser];
    const updatedProfiles = [...studentProfiles, newStudentProfile];

    setUsers(updatedUsers);
    setStudentProfiles(updatedProfiles);

    syncTable('users', updatedUsers);
    syncTable('student_profiles', updatedProfiles);

    // Send dynamic Welcome Notification to new Student
    const welcomeNotif: Notification = {
      id: Math.max(0, ...notifications.map((n) => n.id)) + 1,
      user_id: nextUserId,
      title: 'Welcome to EduTrack LMS!',
      message: 'Hi there! Your official student account is created. Start tracking your attendance, courses curriculum schedules, and placements alerts in real-time.',
      type: 'success',
      is_read: 0,
      created_at: new Date().toISOString()
    };
    setNotifications([welcomeNotif, ...notifications]);
    syncTable('notifications', [welcomeNotif, ...notifications]);
  };

  const editStudent = (userId: number, updatedUser: Partial<User>, updatedProfile: Partial<StudentProfile>) => {
    const nextUsers = users.map((u) => {
      if (u.id === userId) {
        return { ...u, ...updatedUser };
      }
      return u;
    });

    const nextProfiles = studentProfiles.map((p) => {
      if (p.user_id === userId) {
        return { ...p, ...updatedProfile };
      }
      return p;
    });

    setUsers(nextUsers);
    setStudentProfiles(nextProfiles);

    syncTable('users', nextUsers);
    syncTable('student_profiles', nextProfiles);
  };

  const deleteStudent = (userId: number) => {
    // Delete by setting user.status = 'inactive' OR completely remove
    // User requested edit and delete capabilities, to keep database consistency let's filter out
    const updatedUsers = users.filter((u) => u.id !== userId);
    const updatedProfiles = studentProfiles.filter((p) => p.user_id !== userId);
    // Remove enrollments associated
    const updatedEnrollments = enrollments.filter((e) => e.student_id !== userId);

    setUsers(updatedUsers);
    setStudentProfiles(updatedProfiles);
    setEnrollments(updatedEnrollments);

    syncTable('users', updatedUsers);
    syncTable('student_profiles', updatedProfiles);
    syncTable('enrollments', updatedEnrollments);
  };

  // Admin Teacher Operations
  const addTeacher = (teacherData: Omit<User, 'id' | 'role' | 'avatar' | 'created_at'> & Omit<TeacherProfile, 'id' | 'user_id'>) => {
    const nextUserId = Math.max(0, ...users.map((u) => u.id)) + 1;
    const nextProfileId = Math.max(0, ...teacherProfiles.map((tp) => tp.id)) + 1;

    const newTeacherUser: User = {
      id: nextUserId,
      name: teacherData.name,
      email: teacherData.email,
      password: teacherData.password || 'teacher123',
      role: 'teacher',
      avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150',
      phone: teacherData.phone,
      status: teacherData.status || 'active',
      created_at: new Date().toISOString()
    };

    const newTeacherProfile: TeacherProfile = {
      id: nextProfileId,
      user_id: nextUserId,
      employee_id: teacherData.employee_id,
      department_id: Number(teacherData.department_id),
      designation: teacherData.designation,
      qualification: teacherData.qualification
    };

    const updatedUsers = [...users, newTeacherUser];
    const updatedProfiles = [...teacherProfiles, newTeacherProfile];

    setUsers(updatedUsers);
    setTeacherProfiles(updatedProfiles);

    syncTable('users', updatedUsers);
    syncTable('teacher_profiles', updatedProfiles);
  };

  const editTeacher = (userId: number, updatedUser: Partial<User>, updatedProfile: Partial<TeacherProfile>) => {
    const nextUsers = users.map((u) => {
      if (u.id === userId) {
        return { ...u, ...updatedUser };
      }
      return u;
    });

    const nextProfiles = teacherProfiles.map((p) => {
      if (p.user_id === userId) {
        return { ...p, ...updatedProfile };
      }
      return p;
    });

    setUsers(nextUsers);
    setTeacherProfiles(nextProfiles);

    syncTable('users', nextUsers);
    syncTable('teacher_profiles', nextProfiles);
  };

  const deleteTeacher = (userId: number) => {
    const updatedUsers = users.filter((u) => u.id !== userId);
    const updatedProfiles = teacherProfiles.filter((p) => p.user_id !== userId);
    setUsers(updatedUsers);
    setTeacherProfiles(updatedProfiles);
    syncTable('users', updatedUsers);
    syncTable('teacher_profiles', updatedProfiles);
  };

  // Departments
  const addDepartment = (name: string, code: string, headId: number | null) => {
    const newDept: Department = {
      id: Math.max(0, ...departments.map((d) => d.id)) + 1,
      name,
      code: code.toUpperCase(),
      head_id: headId,
      created_at: new Date().toISOString()
    };
    const updated = [...departments, newDept];
    setDepartments(updated);
    syncTable('departments', updated);
  };

  const editDepartment = (id: number, name: string, code: string, headId: number | null) => {
    const updated = departments.map((d) => {
      if (d.id === id) {
        return { ...d, name, code: code.toUpperCase(), head_id: headId };
      }
      return d;
    });
    setDepartments(updated);
    syncTable('departments', updated);
  };

  // Admin Courses & Enrollments
  const addCourse = (title: string, code: string, deptId: number, teacherId: number, credits: number, semester: number, description: string) => {
    const newCourse: Course = {
      id: Math.max(0, ...courses.map((c) => c.id)) + 1,
      title,
      code: code.toUpperCase(),
      department_id: Number(deptId),
      teacher_id: Number(teacherId),
      credits: Number(credits),
      semester: Number(semester),
      description,
      status: 'active',
      created_at: new Date().toISOString()
    };
    const updated = [...courses, newCourse];
    setCourses(updated);
    syncTable('courses', updated);
  };

  const editCourse = (id: number, title: string, code: string, deptId: number, teacherId: number, credits: number, semester: number, description: string, status: 'active' | 'inactive') => {
    const updated = courses.map((c) => {
      if (c.id === id) {
        return {
          ...c,
          title,
          code: code.toUpperCase(),
          department_id: Number(deptId),
          teacher_id: Number(teacherId),
          credits: Number(credits),
          semester: Number(semester),
          description,
          status
        };
      }
      return c;
    });
    setCourses(updated);
    syncTable('courses', updated);
  };

  const addEnrollment = (studentId: number, courseId: number) => {
    // Check duplication
    const duplicate = enrollments.find((e) => e.student_id === studentId && e.course_id === courseId);
    if (duplicate) {
      return { success: false, message: 'Student is already enrolled in this course' };
    }

    const newEnroll: Enrollment = {
      id: Math.max(0, ...enrollments.map((e) => e.id)) + 1,
      student_id: Number(studentId),
      course_id: Number(courseId),
      enrolled_at: new Date().toISOString(),
      status: 'active'
    };

    const updated = [...enrollments, newEnroll];
    setEnrollments(updated);
    syncTable('enrollments', updated);

    // Notify student about enrollment
    const cour = courses.find((c) => c.id === courseId);
    const enrollNotif: Notification = {
      id: Math.max(0, ...notifications.map((n) => n.id)) + 1,
      user_id: studentId,
      title: 'Enrolled in New Course',
      message: `You have been officially enrolled in course: "${cour?.title || 'Academic Course'}" (${cour?.code || 'CS'})`,
      type: 'success',
      is_read: 0,
      created_at: new Date().toISOString()
    };
    const updatedNotifs = [enrollNotif, ...notifications];
    setNotifications(updatedNotifs);
    syncTable('notifications', updatedNotifs);

    return { success: true, message: 'Enrolled successfully!' };
  };

  const removeEnrollment = (id: number) => {
    const updated = enrollments.filter((e) => e.id !== id);
    setEnrollments(updated);
    syncTable('enrollments', updated);
  };

  // Placements admin
  const addPlacement = (placementData: Omit<Placement, 'id' | 'posted_by' | 'created_at' | 'status'>) => {
    if (!currentUser) return;
    const newPlace: Placement = {
      ...placementData,
      id: Math.max(0, ...placements.map((p) => p.id)) + 1,
      posted_by: currentUser.id,
      status: 'open',
      created_at: new Date().toISOString()
    };
    const updated = [...placements, newPlace];
    setPlacements(updated);
    syncTable('placements', updated);

    // Notify all students
    const studentUsers = users.filter((u) => u.role === 'student' && u.status === 'active');
    const newNotifications = [...notifications];
    let nId = Math.max(0, ...notifications.map((n) => n.id)) + 1;

    studentUsers.forEach((student) => {
      newNotifications.unshift({
        id: nId++,
        user_id: student.id,
        title: 'New Placement Opportunity',
        message: `New opening posted at ${placementData.company}: "${placementData.role}". Deadline to apply: ${placementData.deadline}`,
        type: 'info',
        is_read: 0,
        created_at: new Date().toISOString()
      });
    });

    setNotifications(newNotifications);
    syncTable('notifications', newNotifications);
  };

  const updatePlacementStatus = (placementId: number, status: 'open' | 'closed') => {
    const updated = placements.map((p) => {
      if (p.id === placementId) {
        return { ...p, status };
      }
      return p;
    });
    setPlacements(updated);
    syncTable('placements', updated);
  };

  const updatePlacementApplicationStatus = (appId: number, status: PlacementApplication['status']) => {
    const updated = placementApplications.map((ap) => {
      if (ap.id === appId) {
        return { ...ap, status };
      }
      return ap;
    });
    setPlacementApplications(updated);
    syncTable('placement_applications', updated);

    // Notify student about application update
    const application = placementApplications.find((a) => a.id === appId);
    if (application) {
      const placement = placements.find((p) => p.id === application.placement_id);
      const studentNotif: Notification = {
        id: Math.max(0, ...notifications.map((n) => n.id)) + 1,
        user_id: application.student_id,
        title: 'Placement Status Updated',
        message: `Your application for "${placement?.role}" at ${placement?.company} status has been updated to "${status.toUpperCase()}"`,
        type: status === 'offered' ? 'success' : status === 'rejected' ? 'danger' : 'warning',
        is_read: 0,
        created_at: new Date().toISOString()
      };
      
      const updatedNotifs = [studentNotif, ...notifications];
      setNotifications(updatedNotifs);
      syncTable('notifications', updatedNotifs);
    }
  };

  // Announcements admin or teacher
  const sendAnnouncement = (title: string, body: string, audience: Announcement['audience'], department_id: number | null) => {
    if (!currentUser) return;
    const newAnn: Announcement = {
      id: Math.max(0, ...announcements.map((a) => a.id)) + 1,
      title,
      body,
      audience,
      department_id: department_id ? Number(department_id) : null,
      created_by: currentUser.id,
      scheduled_at: new Date().toISOString(),
      created_at: new Date().toISOString()
    };

    const updated = [...announcements, newAnn];
    setAnnouncements(updated);
    syncTable('announcements', updated);

    // Generate notification for targeted audience list
    let targetUsers: User[] = [];
    if (audience === 'all') {
      targetUsers = users.filter((u) => u.status === 'active');
    } else if (audience === 'students') {
      targetUsers = users.filter((u) => u.role === 'student' && u.status === 'active');
    } else if (audience === 'teachers') {
      targetUsers = users.filter((u) => u.role === 'teacher' && u.status === 'active');
    } else if (audience === 'department' && department_id) {
      const targetStudentIds = studentProfiles
        .filter((sp) => sp.department_id === Number(department_id))
        .map((p) => p.user_id);
      const targetTeacherIds = teacherProfiles
        .filter((tp) => tp.department_id === Number(department_id))
        .map((p) => p.user_id);
      
      targetUsers = users.filter(
        (u) => u.status === 'active' && (targetStudentIds.includes(u.id) || targetTeacherIds.includes(u.id))
      );
    }

    const newNotifications = [...notifications];
    let nId = Math.max(0, ...notifications.map((n) => n.id)) + 1;

    targetUsers.forEach((targetUser) => {
      newNotifications.unshift({
        id: nId++,
        user_id: targetUser.id,
        title: 'New Announcement Posted',
        message: `Announcement: "${title}" - ${body.substring(0, 80)}...`,
        type: 'info',
        is_read: 0,
        created_at: new Date().toISOString()
      });
    });

    setNotifications(newNotifications);
    syncTable('notifications', newNotifications);
  };

  // Notification methods
  const markNotificationRead = (id: number) => {
    const updated = notifications.map((n) => {
      if (n.id === id) {
        return { ...n, is_read: 1 };
      }
      return n;
    });
    setNotifications(updated);
    syncTable('notifications', updated);
  };

  const clearNotifications = () => {
    if (!currentUser) return;
    const updated = notifications.filter((n) => n.user_id !== currentUser.id);
    setNotifications(updated);
    syncTable('notifications', updated);
  };

  return (
    <LmsContext.Provider
      value={{
        users,
        studentProfiles,
        teacherProfiles,
        departments,
        courses,
        enrollments,
        materials,
        assignments,
        submissions,
        exams,
        results,
        attendance,
        placements,
        placementApplications,
        notifications,
        announcements,

        currentUser,
        login,
        logout,
        getStudentProfile,
        getTeacherProfile,

        // Student
        submitAssignment,
        applyPlacement,

        // Teacher
        createAssignment,
        uploadMaterial,
        evaluateSubmission,
        markAttendanceBatch,
        createExamSchedule,
        publishResult,

        // Admin
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
        sendAnnouncement,

        // Notifications helper
        markNotificationRead,
        clearNotifications
      }}
    >
      {children}
    </LmsContext.Provider>
  );
};

export const useLms = () => {
  const context = useContext(LmsContext);
  if (context === undefined) {
    throw new Error('useLms must be used inside an LmsProvider');
  }
  return context;
};
