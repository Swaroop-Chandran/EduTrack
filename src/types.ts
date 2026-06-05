export interface User {
  id: number;
  name: string;
  email: string;
  password?: string; // Optional for security, but we keep it inside our mock DB
  role: 'student' | 'teacher' | 'admin';
  avatar: string;
  phone?: string;
  status: 'active' | 'inactive';
  created_at: string;
}

export interface StudentProfile {
  id: number;
  user_id: number;
  roll_no: string;
  department_id: number;
  year: number;
  semester: number;
  cgpa: number;
  dob?: string;
  address?: string;
}

export interface TeacherProfile {
  id: number;
  user_id: number;
  employee_id: string;
  department_id: number;
  designation: string;
  qualification: string;
}

export interface Department {
  id: number;
  name: string;
  code: string;
  head_id?: number | null; // teacher user_id
  created_at: string;
}

export interface Course {
  id: number;
  title: string;
  code: string;
  department_id: number;
  teacher_id: number; // teacher user_id
  credits: number;
  semester: number;
  description: string;
  status: 'active' | 'inactive';
  created_at: string;
}

export interface Enrollment {
  id: number;
  student_id: number; // student user_id
  course_id: number;
  enrolled_at: string;
  status: 'active' | 'completed' | 'dropped';
}

export interface Material {
  id: number;
  course_id: number;
  teacher_id: number; // teacher user_id
  title: string;
  description: string;
  file_path: string;
  file_type: string;
  uploaded_at: string;
}

export interface Assignment {
  id: number;
  course_id: number;
  teacher_id: number; // teacher user_id
  title: string;
  description: string;
  due_date: string;
  max_marks: number;
  status: 'active' | 'closed';
  created_at: string;
}

export interface Submission {
  id: number;
  assignment_id: number;
  student_id: number; // student user_id
  file_path?: string;
  text_submission?: string;
  submitted_at: string;
  marks_obtained?: number | null;
  feedback?: string | null;
  status: 'submitted' | 'evaluated';
}

export interface Exam {
  id: number;
  course_id: number;
  title: string;
  exam_date: string;
  start_time: string;
  duration_minutes: number;
  venue: string;
  type: 'internal' | 'external' | 'practical' | 'viva';
  max_marks: number;
  created_by: number; // admin user_id
  created_at: string;
}

export interface Result {
  id: number;
  student_id: number; // student user_id
  course_id: number;
  exam_id: number;
  internal_marks: number;
  external_marks: number;
  total_marks: number;
  grade: string;
  semester: number;
  status: 'pass' | 'fail';
  published_at: string;
}

export interface Attendance {
  id: number;
  student_id: number; // student user_id
  course_id: number;
  date: string; // YYYY-MM-DD
  status: 'present' | 'absent' | 'late';
  marked_by: number; // teacher user_id
  created_at: string;
}

export interface Placement {
  id: number;
  company: string;
  role: string;
  type: 'internship' | 'full-time' | 'part-time' | 'contract';
  location: string;
  stipend: string;
  eligibility: string;
  description: string;
  deadline: string; // YYYY-MM-DD
  posted_by: number; // admin user_id
  status: 'open' | 'closed';
  created_at: string;
}

export interface PlacementApplication {
  id: number;
  placement_id: number;
  student_id: number; // student user_id
  applied_at: string;
  status: 'applied' | 'shortlisted' | 'interview' | 'offered' | 'rejected';
}

export interface Notification {
  id: number;
  user_id: number;
  title: string;
  message: string;
  type: 'info' | 'warning' | 'success' | 'danger';
  is_read: number; // 0 or 1
  created_at: string;
}

export interface Announcement {
  id: number;
  title: string;
  body: string;
  audience: 'all' | 'students' | 'teachers' | 'department';
  department_id?: number | null;
  created_by: number; // user_id (usually admin or teacher)
  scheduled_at: string;
  created_at: string;
}
