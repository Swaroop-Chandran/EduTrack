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

export const initialDepartments: Department[] = [
  { id: 1, name: 'Computer Science & Engineering', code: 'CSE', head_id: 4, created_at: '2025-01-10T08:00:00Z' },
  { id: 2, name: 'Electronics & Communication', code: 'ECE', head_id: 5, created_at: '2025-01-11T09:00:00Z' },
  { id: 3, name: 'Business Administration', code: 'BBA', head_id: 6, created_at: '2025-01-12T10:00:00Z' }
];

export const initialUsers: User[] = [
  // Admins
  { id: 1, name: 'Admin System', email: 'admin@edutrack.com', password: 'admin123', role: 'admin', avatar: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCWrUpkmRTZkavn-Tl_knNiDPodz3ihKXmPPTJXcMGHQxsOo0TjmRSyhEjJ3fWr5x7c_nGYjjiDMWWUNmSn7yuO501818ng7VwndsdVw-y2SVI0YxNmYMnhUO0khxV-O-SHqhwzsrnmXfsLny4NXEoz7nTCqUrI7CSvuPGA40FJnpjQBZchFqh5ge__ltfNEj4dm8Nvcg8Rk8Qn7xmSHK6ymV0AsQlQ90wlstdJgdRkss21QiPKHS9q6AL1_yAgnL3uRes4U9GisSo', phone: '1-800-EDUTRACK', status: 'active', created_at: '2025-01-01T00:00:00Z' },
  
  // Students
  // Student 2 (Arjun - default student@edutrack.com)
  { id: 2, name: 'Arjun Nair', email: 'student@edutrack.com', password: 'student123', role: 'student', avatar: 'https://lh3.googleusercontent.com/aida-public/AB6AXuC5jEI0GABAomDUDILrgk4_06EC_EEa2BZdrObbHOz96biUe2P7GZHtqG0YwjvhC3n4azP8TT2PDBVTfSbfTNGhVeemMBau-T62SB3NlWG118e6Pf-K6LwnUCy56CEBoPF99vybdLWC4Z_psEixAaaIO7ZgXmpo4su9Hsxo8hzSxs0pQ1iBI3sOOC3FbX8Zk10AROGN0ziblZJZuTfvr8eQcf7vFxxPnGpCU2YHZhE2xCdLADEip3ncg_545OTX6tTevAIPRx2Qhdc', phone: '9845621350', status: 'active', created_at: '2025-02-01T00:00:00Z' },
  // Student 3 (Markus Chen - active in recent activity list)
  { id: 3, name: 'Markus Chen', email: 'markus@edutrack.com', password: 'student123', role: 'student', avatar: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCtU0FpGK4Huha_kZtsBj5dAXnz28HQ4mDR4FACj6fOldjXDQ52sfxjp4_ontKBPxfbmt4ZQsM1jOEQ5S6UYzBvtV2wZc7mSF4qML9xtTl7Dl8vKNJc_D5aVujvMHsoEj2ofFCaFyR-V7ipWlNZDhB1k2gNo3Gnae8bxyBch7J98z-wJnz9h6KGRPzs-eIRlT1NyTeJ9maGdHCPbmQnIjqBxv5JjzwO6ocUk_Ywr9W9LD8cIDCkevEUKi41sAUT4hj_FF-Vej2uagk', phone: '9845621351', status: 'active', created_at: '2025-02-02T00:00:00Z' },
  { id: 7, name: 'Priya Sharma', email: 'priya@edutrack.com', password: 'student123', role: 'student', avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150', phone: '9845621352', status: 'active', created_at: '2025-02-03T00:00:00Z' },
  { id: 8, name: 'Aditya Rao', email: 'aditya@edutrack.com', password: 'student123', role: 'student', avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150', phone: '9845621353', status: 'active', created_at: '2025-02-04T00:00:00Z' },
  { id: 9, name: 'Ananya Iyer', email: 'ananya@edutrack.com', password: 'student123', role: 'student', avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150', phone: '9845621354', status: 'active', created_at: '2025-02-05T00:00:00Z' },
  { id: 10, name: 'Rahul Mehta', email: 'rahul@edutrack.com', password: 'student123', role: 'student', avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150', phone: '9845621355', status: 'active', created_at: '2025-02-06T00:00:00Z' },
  { id: 11, name: 'Sneha Patel', email: 'sneha@edutrack.com', password: 'student123', role: 'student', avatar: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150', phone: '9845621356', status: 'active', created_at: '2025-02-07T00:00:00Z' },
  { id: 12, name: 'Vikram Singh', email: 'vikram@edutrack.com', password: 'student123', role: 'student', avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150', phone: '9845621357', status: 'active', created_at: '2025-02-08T00:00:00Z' },
  { id: 13, name: 'Kavya Reddy', email: 'kavya@edutrack.com', password: 'student123', role: 'student', avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150', phone: '9845621358', status: 'active', created_at: '2025-02-09T00:00:00Z' },
  { id: 14, name: 'Rohan Gupta', email: 'rohan@edutrack.com', password: 'student123', role: 'student', avatar: 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150', phone: '9845621359', status: 'active', created_at: '2025-02-10T00:00:00Z' },
  { id: 15, name: 'Deepa Krishnan', email: 'deepa@edutrack.com', password: 'student123', role: 'student', avatar: 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=150', phone: '9845621360', status: 'active', created_at: '2025-02-11T00:00:00Z' },
  { id: 16, name: 'Siddharth Sen', email: 'siddharth@edutrack.com', password: 'student123', role: 'student', avatar: 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=150', phone: '9845621361', status: 'active', created_at: '2025-02-12T00:00:00Z' },
  { id: 17, name: 'Meera Nair', email: 'meera@edutrack.com', password: 'student123', role: 'student', avatar: 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=150', phone: '9845621362', status: 'active', created_at: '2025-02-13T00:00:00Z' },
  { id: 18, name: 'Varun Joshi', email: 'varun@edutrack.com', password: 'student123', role: 'student', avatar: 'https://images.unsplash.com/photo-1488161628813-04466f872be2?w=150', phone: '9845621363', status: 'active', created_at: '2025-02-14T00:00:00Z' },
  { id: 19, name: 'Neha Deshmukh', email: 'nehadeshmukh@edutrack.com', password: 'student123', role: 'student', avatar: 'https://images.unsplash.com/photo-1513956589380-bad6acb9b9d4?w=150', phone: '9845621364', status: 'inactive', created_at: '2025-02-15T00:00:00Z' },

  // Teachers
  // Teacher 4 (Dr. Sarah Jenkins - default teacher@edutrack.com)
  { id: 4, name: 'Dr. Sarah Jenkins', email: 'teacher@edutrack.com', password: 'teacher123', role: 'teacher', avatar: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAr42sH1uGcJdzKjkv0qkcgpGNFnyhvQIr7TVofOmt5VlVia4qZx3AKrPH7_8iML-cLWbkDxO68H7yekLTjJVMURJ0ndYWXhoHznxFDZUuhx5AfL6ovmYSNXTrXT7UbSmbkqsYf5Paa-tyN56UhnC8yy4vX3ZYRTIglgeYNchm1RthT4sKYdM7h1SX5zieShHGVmHqHRqFEvmB-hLnO41AcOwA5esB-x-XDoFwMuyHHEGLpZz-cYskWMEkWVC2DzkKlg1U8qIflBz8', phone: '9447331551', status: 'active', created_at: '2025-01-05T00:00:00Z' },
  { id: 5, name: 'Dr. Jonathan Davis', email: 'jonathan@edutrack.com', password: 'teacher123', role: 'teacher', avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150', phone: '9447331552', status: 'active', created_at: '2025-01-06T00:00:00Z' },
  { id: 6, name: 'Sarah Wong, Esq.', email: 'sarahwong@edutrack.com', password: 'teacher123', role: 'teacher', avatar: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150', phone: '9447331553', status: 'active', created_at: '2025-01-07T00:00:00Z' },
  { id: 20, name: 'Prof. Alan Lee', email: 'alanlee@edutrack.com', password: 'teacher123', role: 'teacher', avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150', phone: '9447331554', status: 'active', created_at: '2025-01-08T00:00:00Z' },
  { id: 21, name: 'Dr. Emily Watson', email: 'emily@edutrack.com', password: 'teacher123', role: 'teacher', avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150', phone: '9447331555', status: 'active', created_at: '2025-01-09T00:00:00Z' }
];

export const initialStudentProfiles: StudentProfile[] = [
  { id: 1, user_id: 2, roll_no: 'CS-2025-001', department_id: 1, year: 2, semester: 4, cgpa: 8.40, dob: '2005-05-15', address: '123 Pine Drive, Ernakulam, Kerala' },
  { id: 2, user_id: 3, roll_no: 'CS-2025-002', department_id: 1, year: 2, semester: 4, cgpa: 7.95, dob: '2005-02-18', address: '45 Lakeview Apt, Bangalore' },
  { id: 3, user_id: 7, roll_no: 'CS-2025-003', department_id: 1, year: 2, semester: 4, cgpa: 9.10, dob: '2005-09-02', address: 'Flat 3B, Sunshine Court, Kochi' },
  { id: 4, user_id: 8, roll_no: 'EC-2025-012', department_id: 2, year: 2, semester: 4, cgpa: 8.12, dob: '2005-12-25', address: 'Rosewood Villa, Trivandrum' },
  { id: 5, user_id: 9, roll_no: 'BA-2025-054', department_id: 3, year: 2, semester: 4, cgpa: 8.85, dob: '2005-07-04', address: 'Park Avenue St 12, Calicut' },
  { id: 6, user_id: 10, roll_no: 'CS-2025-004', department_id: 1, year: 3, semester: 6, cgpa: 7.20, dob: '2004-04-12', address: 'Green Glen Layout, Bangalore' },
  { id: 7, user_id: 11, roll_no: 'CS-2025-005', department_id: 1, year: 3, semester: 6, cgpa: 8.64, dob: '2004-10-30', address: 'Whitefield Manor, Bangalore' },
  { id: 8, user_id: 12, roll_no: 'EC-2025-013', department_id: 2, year: 3, semester: 6, cgpa: 6.80, dob: '2004-03-22', address: 'Navy Colony, Kochi' },
  { id: 9, user_id: 13, roll_no: 'BA-2025-055', department_id: 3, year: 3, semester: 6, cgpa: 8.35, dob: '2004-08-11', address: 'Vyttila Heights, Kochi' },
  { id: 10, user_id: 14, roll_no: 'CS-2025-006', department_id: 1, year: 4, semester: 8, cgpa: 9.42, dob: '2003-11-05', address: 'DLF New Town, Kochi' },
  { id: 11, user_id: 15, roll_no: 'EC-2025-014', department_id: 2, year: 4, semester: 8, cgpa: 7.50, dob: '2003-01-20', address: 'High Street Road, Thrissur' },
  { id: 12, user_id: 16, roll_no: 'BA-2025-056', department_id: 3, year: 4, semester: 8, cgpa: 8.10, dob: '2003-05-18', address: 'Marine Drive Gateway, Kochi' },
  { id: 13, user_id: 17, roll_no: 'CS-2025-007', department_id: 1, year: 1, semester: 2, cgpa: 8.50, dob: '2006-08-09', address: 'Prestige Apartments, Kakkanad' },
  { id: 14, user_id: 18, roll_no: 'EC-2025-015', department_id: 2, year: 1, semester: 2, cgpa: 7.15, dob: '2006-10-15', address: 'Lakeside Villas, Alappuzha' },
  { id: 15, user_id: 19, roll_no: 'BA-2025-057', department_id: 3, year: 1, semester: 2, cgpa: 0.00, dob: '2006-12-01', address: 'St Gg Colony, Kottayam' }
];

export const initialTeacherProfiles: TeacherProfile[] = [
  { id: 1, user_id: 4, employee_id: 'EMP-CSE-001', department_id: 1, designation: 'Professor & Head', qualification: 'Ph.D. in Computer Science, IIT Bombay' },
  { id: 2, user_id: 5, employee_id: 'EMP-ECE-002', department_id: 2, designation: 'Associate Professor', qualification: 'Ph.D. in Signal Processing, IISc Bangalore' },
  { id: 3, user_id: 6, employee_id: 'EMP-BBA-003', department_id: 3, designation: 'Assistant Professor', qualification: 'MBA & PhD, Wharton School' },
  { id: 4, user_id: 20, employee_id: 'EMP-CSE-004', department_id: 1, designation: 'Assistant Professor', qualification: 'M.Tech, BITS Pilani' },
  { id: 5, user_id: 21, employee_id: 'EMP-ECE-005', department_id: 2, designation: 'Professor', qualification: 'Ph.D., Stanford University' }
];

export const initialCourses: Course[] = [
  // CSE Courses
  { id: 1, title: 'Cloud Computing Architecture', code: 'CS401', department_id: 1, teacher_id: 4, credits: 4, semester: 4, description: 'Design, configuration and deployment of decentralized containerized systems over public and private clouds, including AWS, GCP and Kubernetes orchestrations.', status: 'active', created_at: '2025-01-15T00:00:00Z' },
  { id: 2, title: 'Advanced Data Structures', code: 'CS402', department_id: 1, teacher_id: 20, credits: 4, semester: 4, description: 'Heaps, Red-Black Trees, B-Trees, Segment Trees, Advanced Graph Algorithms, network flows, and time-space complexity management.', status: 'active', created_at: '2025-01-16T00:00:00Z' },
  { id: 3, title: 'Introduction to Database Systems', code: 'CS301', department_id: 1, teacher_id: 4, credits: 3, semester: 4, description: 'Relational algebra, SQL language, indexing, locking schemes, transactions isolation rules and database normalization workflows.', status: 'active', created_at: '2025-01-17T00:00:00Z' },
  
  // ECE Courses
  { id: 4, title: 'Digital Signal Processing', code: 'EC401', department_id: 2, teacher_id: 5, credits: 4, semester: 4, description: 'Discrete Fourier Transforms, Discrete-time systems stability, Infinite Impulse Response filters, and spectral density calculations.', status: 'active', created_at: '2025-01-18T00:00:00Z' },
  { id: 5, title: 'Microprocessors & Embeds', code: 'EC402', department_id: 2, teacher_id: 21, credits: 3, semester: 4, description: 'Instruction sets for x86 and ARM controllers, DMA transfers, interrupts priorities, multiplexing, and serial bus interfacing protocols.', status: 'active', created_at: '2025-01-19T00:00:00Z' },

  // BBA Courses
  { id: 6, title: 'Organizational Behavior', code: 'MGT305', department_id: 3, teacher_id: 6, credits: 3, semester: 4, description: 'Individual motivators, team dynamics, conflicts mitigation mechanisms, organizational systems structure or leadership influence techniques.', status: 'active', created_at: '2025-01-20T00:00:00Z' },
  { id: 7, title: 'Business Analytics Fundamentals', code: 'BA302', department_id: 3, teacher_id: 6, credits: 4, semester: 4, description: 'Statistical forecasting engine, multiple-variable linear regressions, hypothesis validations, and dashboards delivery layouts.', status: 'active', created_at: '2025-01-21T00:00:00Z' },
  { id: 8, title: 'Strategic Management', code: 'MGT401', department_id: 3, teacher_id: 6, credits: 3, semester: 4, description: 'Firms competitiveness frameworks, SWOT audits, horizontal and vertical integrations, and Blue Ocean strategy implementation schemes.', status: 'active', created_at: '2025-01-22T00:00:00Z' }
];

export const initialEnrollments: Enrollment[] = [
  // Arjun (User 2) is enrolled in 6 courses (CS401, CS402, CS301, MGT305, BA302, MGT401)
  { id: 1, student_id: 2, course_id: 1, enrolled_at: '2025-02-10T08:00:00Z', status: 'active' },
  { id: 2, student_id: 2, course_id: 2, enrolled_at: '2025-02-10T08:10:00Z', status: 'active' },
  { id: 3, student_id: 2, course_id: 3, enrolled_at: '2025-02-10T08:20:00Z', status: 'active' },
  { id: 4, student_id: 2, course_id: 6, enrolled_at: '2025-02-10T08:30:00Z', status: 'active' },
  { id: 5, student_id: 2, course_id: 7, enrolled_at: '2025-02-10T08:40:00Z', status: 'active' },
  { id: 6, student_id: 2, course_id: 8, enrolled_at: '2025-02-10T08:50:00Z', status: 'active' },

  // Markus Chen (User 3) enrolled in CS401, CS402, CS301
  { id: 7, student_id: 3, course_id: 1, enrolled_at: '2025-02-10T09:00:00Z', status: 'active' },
  { id: 8, student_id: 3, course_id: 2, enrolled_at: '2025-02-10T09:10:00Z', status: 'active' },
  { id: 9, student_id: 3, course_id: 3, enrolled_at: '2025-02-10T09:20:00Z', status: 'active' },

  // Priya Sharma (User 7) CS401, CS402, CS301
  { id: 10, student_id: 7, course_id: 1, enrolled_at: '2025-02-10T09:30:00Z', status: 'active' },
  { id: 11, student_id: 7, course_id: 2, enrolled_at: '2025-02-10T09:40:00Z', status: 'active' },
  
  // Aditya Rao (User 8) ECE Courses
  { id: 12, student_id: 8, course_id: 4, enrolled_at: '2025-02-10T09:50:00Z', status: 'active' },
  { id: 13, student_id: 8, course_id: 5, enrolled_at: '2025-02-10T10:00:00Z', status: 'active' },

  // Ananya Iyer (User 9) BBA courses
  { id: 14, student_id: 9, course_id: 6, enrolled_at: '2025-02-10T10:10:00Z', status: 'active' },
  { id: 15, student_id: 9, course_id: 7, enrolled_at: '2025-02-10T10:20:00Z', status: 'active' },
  { id: 16, student_id: 9, course_id: 8, enrolled_at: '2025-02-10T10:30:00Z', status: 'active' }
];

export const initialMaterials: Material[] = [
  { id: 1, course_id: 1, teacher_id: 4, title: 'Introduction to Cloud Architectures', description: 'Comprehensive lecture notes describing IAAS, PAAS, SAAS and microservices routing diagrams.', file_path: 'material_cloud_1.pdf', file_type: 'pdf', uploaded_at: '2025-03-01T10:00:00Z' },
  { id: 2, course_id: 1, teacher_id: 4, title: 'Docker Containers Orchestration Reference', description: 'Guide sheet for setting up Dockerfiles, environment files and virtual ports configurations.', file_path: 'docker_guide.docx', file_type: 'docx', uploaded_at: '2025-03-08T11:00:00Z' },
  { id: 3, course_id: 2, teacher_id: 20, title: 'Graph Traversal Systems BFS & DFS', description: 'Detailed slide deck detailing search queues optimizations and tree backtracking matrices.', file_path: 'graphs_traversal.pdf', file_type: 'pdf', uploaded_at: '2025-03-04T14:30:00Z' },
  { id: 4, course_id: 6, teacher_id: 6, title: 'Maslow Hierarchy of Motivators in Enterprise', description: 'Review paper exploring self-actualization impacts on developers efficiency rates.', file_path: 'maslow_org.pdf', file_type: 'pdf', uploaded_at: '2025-03-06T09:15:00Z' }
];

export const initialAssignments: Assignment[] = [
  // Assignments for Course 1
  { id: 1, course_id: 1, teacher_id: 4, title: 'Project Proposal Draft', description: 'Submit initial system requirement document, listing server sizes, network subnetting arrays, and storage persistence policies.', due_date: '2026-06-05T23:59:00Z', max_marks: 100, status: 'active', created_at: '2026-05-25T08:00:00Z' },
  { id: 2, course_id: 1, teacher_id: 4, title: 'Docker Compose Local Clusters Setup', description: 'Design a docker-compose file executing an Express application, an Nginx router and a MongoDB persistence endpoint with isolated ports.', due_date: '2026-06-12T23:59:00Z', max_marks: 100, status: 'active', created_at: '2026-05-28T10:00:00Z' },
  
  // Course 2 (Advanced Data Structures)
  { id: 3, course_id: 2, teacher_id: 20, title: 'Weekly Quiz #4: Graph Traversal', description: 'Solve Graph Algorithmic challenges implementing Dijkstra shortest path and segment tree subdivisions.', due_date: '2026-06-08T17:00:00Z', max_marks: 50, status: 'active', created_at: '2026-06-01T09:00:00Z' },
  { id: 4, course_id: 2, teacher_id: 20, title: 'Red-Black Tree Self-Balancing Algorithms', description: 'Develop recursive nodes rotation code verifying standard alignment coloring constraints.', due_date: '2026-05-20T23:59:00Z', max_marks: 100, status: 'closed', created_at: '2026-05-01T09:00:00Z' },

  // Course 6 (Organizational Behavior)
  { id: 5, course_id: 6, teacher_id: 6, title: 'Leadership Case Audit Analysis', description: 'Write a comparative essay reviewing structural crises leadership behaviors of Netflix vs Blockbuster.', due_date: '2026-05-29T23:59:00Z', max_marks: 100, status: 'closed', created_at: '2026-05-15T14:00:00Z' }
];

export const initialSubmissions: Submission[] = [
  // Submission - Red-Black Tree (Assignment 4) by Arjun
  { id: 1, assignment_id: 4, student_id: 2, file_path: 'rbt_arjun.zip', text_submission: 'Implemented Left-Leaning Red-Black self-balancing logic with auxiliary node pointers.', submitted_at: '2026-05-18T16:45:00Z', marks_obtained: 95, feedback: 'Beautiful implementation. Elegant helper abstractions and comprehensive specs.', status: 'evaluated' },
  // Submission - Red-Black Tree by Markus Chen
  { id: 2, assignment_id: 4, student_id: 3, file_path: 'rbt_markus.zip', text_submission: 'Implemented standard RBT rotations with custom visual tree layout nodes rendering.', submitted_at: '2026-05-19T18:20:00Z', marks_obtained: 88, feedback: 'Excellent UI renderer. Slight logical memory lock on double left rotations.', status: 'evaluated' },

  // Submission - Leadership case audit (Assignment 5) by Arjun
  { id: 3, assignment_id: 5, student_id: 2, file_path: 'leadership_arjun.pdf', text_submission: 'Analyzed cultural shifts at Netflix supporting risk-taking, comparing it to Blockbusters rigid models.', submitted_at: '2026-05-28T12:00:00Z', marks_obtained: 92, feedback: 'Articulate analysis of cultural feedback loops. Satisfies all criteria.', status: 'evaluated' },

  // Pending Submissions to evaluate - Assignment 1 (Project Proposal)
  // Markus Chen submitted, not yet evaluated
  { id: 4, assignment_id: 1, student_id: 3, file_path: 'proposal_markus.pdf', text_submission: 'My submission for cloud computing clusters design.', submitted_at: '2026-06-04T15:30:00Z', marks_obtained: null, feedback: null, status: 'submitted' },
  // Priya Sharma submitted, not yet evaluated
  { id: 5, assignment_id: 1, student_id: 7, file_path: 'proposal_priya.pdf', text_submission: 'Architecture drawings for Multi-zone container environments.', submitted_at: '2026-06-05T09:12:00Z', marks_obtained: null, feedback: null, status: 'submitted' }
];

export const initialExams: Exam[] = [
  { id: 1, course_id: 1, title: 'Cloud Containers Midterm Examination', exam_date: '2026-06-06', start_time: '10:00', duration_minutes: 120, venue: 'Lab 4B, Systems Annex', type: 'internal', max_marks: 100, created_by: 1, created_at: '2026-05-20T00:00:00Z' },
  { id: 2, course_id: 2, title: 'Graph Optimizations Practical Exam', exam_date: '2026-06-15', start_time: '14:00', duration_minutes: 180, venue: 'CSE Complex Comp Room', type: 'practical', max_marks: 100, created_by: 1, created_at: '2026-05-22T00:00:00Z' },
  { id: 3, course_id: 6, title: 'Organizational Behavior Theory Final Endsem', exam_date: '2026-06-25', start_time: '09:30', duration_minutes: 180, venue: 'Seminar Hall 1', type: 'external', max_marks: 100, created_by: 1, created_at: '2026-05-24T00:00:00Z' }
];

export const initialResults: Result[] = [
  // Prior semester results to populate CGPA stats
  { id: 1, student_id: 2, course_id: 1, exam_id: 1, internal_marks: 25.00, external_marks: 65.00, total_marks: 90.00, grade: 'A', semester: 3, status: 'pass', published_at: '2025-12-20T10:00:00Z' },
  { id: 2, student_id: 2, course_id: 6, exam_id: 3, internal_marks: 23.00, external_marks: 62.00, total_marks: 85.00, grade: 'B+', semester: 3, status: 'pass', published_at: '2025-12-20T10:00:00Z' },
  { id: 3, student_id: 2, course_id: 7, exam_id: 3, internal_marks: 18.00, external_marks: 52.00, total_marks: 70.00, grade: 'C', semester: 3, status: 'pass', published_at: '2025-12-20T10:00:00Z' },
  
  // Markus Chen results
  { id: 4, student_id: 3, course_id: 1, exam_id: 1, internal_marks: 21.00, external_marks: 59.00, total_marks: 80.00, grade: 'B', semester: 3, status: 'pass', published_at: '2025-12-20T10:00:00Z' },
  { id: 5, student_id: 3, course_id: 6, exam_id: 3, internal_marks: 19.00, external_marks: 53.00, total_marks: 72.00, grade: 'F', semester: 3, status: 'fail', published_at: '2025-12-20T10:00:00Z' }
];

// Generate attendance records over last 30 days for students
export const generateAttendance = (): Attendance[] => {
  const records: Attendance[] = [];
  const students = [2, 3, 7, 8, 9];
  const courses = [1, 2, 3, 4, 5, 6, 7, 8];
  let id = 1;

  // Let's create static records for Arjun (User 2)
  // Overall attendance should be ~87%
  // We simulate 30 calendar days (excluding weekends)
  const today = new Date('2026-06-05');
  for (let i = 30; i >= 0; i--) {
    const d = new Date(today);
    d.setDate(today.getDate() - i);
    const day = d.getDay();
    if (day === 0 || day === 6) continue; // Skip weekends

    const dateString = d.toISOString().split('T')[0];

    // Student 2 Arjun Nair
    // Course 1 (Cloud - 4 credits - meets Mon/Wed/Fri)
    if (day === 1 || day === 3 || day === 5) {
      // 87% attendance means occasionally absent/late
      const statusSeed = (id % 15 === 0) ? 'absent' : (id % 20 === 0) ? 'late' : 'present';
      records.push({
        id: id++,
        student_id: 2,
        course_id: 1,
        date: dateString,
        status: statusSeed,
        marked_by: 4,
        created_at: `${dateString}T09:15:00Z`
      });

      // Course 2 (Advanced Data - Mon/Wed/Fri)
      const statusSeed2 = (id % 12 === 0) ? 'absent' : (id % 23 === 0) ? 'late' : 'present';
      records.push({
        id: id++,
        student_id: 2,
        course_id: 2,
        date: dateString,
        status: statusSeed2,
        marked_by: 20,
        created_at: `${dateString}T11:15:00Z`
      });
    }

    // Course 6 (Org Behavior - Tue/Thu)
    if (day === 2 || day === 4) {
      const statusSeed3 = (id % 18 === 0) ? 'absent' : 'present';
      records.push({
        id: id++,
        student_id: 2,
        course_id: 6,
        date: dateString,
        status: statusSeed3,
        marked_by: 6,
        created_at: `${dateString}T14:15:00Z`
      });
    }

    // Add some random records for other students to generate database size
    for (const stud of [3, 7]) {
      if (day === 1 || day === 3 || day === 5) {
        records.push({
          id: id++,
          student_id: stud,
          course_id: 1,
          date: dateString,
          status: 'present',
          marked_by: 4,
          created_at: `${dateString}T09:12:00Z`
        });
      }
    }
  }

  return records;
};

export const initialAttendance: Attendance[] = generateAttendance();

export const initialPlacements: Placement[] = [
  { id: 1, company: 'Google Cloud Platform', role: 'Solutions Engineer Intern', type: 'internship', location: 'Singapore (Hybrid)', stipend: '$3,500 / month', eligibility: 'CGPA > 8.00, CSE/ECE only, Proficiency in Golang or Python, understanding of Docker nodes architecture.', description: 'Help enterprise partners design scalable Kubernetes blueprints and assist cloud migration pipelines. Works under Chief Platform architect mentorship.', deadline: '2026-06-30', posted_by: 1, status: 'open', created_at: '2026-05-15T09:00:00Z' },
  { id: 2, company: 'Deloitte Corporate Analytics', role: 'Business Consultant Graduate', type: 'full-time', location: 'Mumbai Office', stipend: 'INR 12.5 LPA', eligibility: 'CGPA > 7.50, Open to all departments (BBA preferred), solid spreadsheet modeling skills and SQL basics knowledge.', description: 'Support financial audit pipelines by parsing clients enterprise structural data and constructing visual analytical dashboards with insights summary.', deadline: '2026-06-20', posted_by: 1, status: 'open', created_at: '2026-05-18T10:30:00Z' },
  { id: 3, company: 'Intel Semiconductor labs', role: 'Embedded Firmware Associate', type: 'full-time', location: 'Bangalore Complex', stipend: 'INR 18 LPA', eligibility: 'CGPA > 8.50, ECE/CSE only, Strong C/C++ memory registers modeling and real-time operations schedulers protocols knowledge.', description: 'Write optimized host controller serial interfaces, evaluate DMA buffers speed constraints and deploy real-time microprocessors firmware kernels.', deadline: '2026-06-15', posted_by: 1, status: 'open', created_at: '2026-05-20T11:00:00Z' },
  { id: 4, company: 'Stripe Global Payments', role: 'Platform Integrations Engineer', type: 'full-time', location: 'Remote (APAC)', stipend: '$55,000 / year', eligibility: 'CGPA > 7.00, Open to all, proficient in API designs, Node.js or Ruby, with empathetic communication skills.', description: 'Unblock global e-commerce merchants integrating Strype payment flows. Debug custom OAuth integrations, webhooks delivery, and secure TLS tunnels.', deadline: '2026-05-30', posted_by: 1, status: 'closed', created_at: '2026-05-01T08:00:00Z' }
];

export const initialPlacementApplications: PlacementApplication[] = [
  // Arjun applied to Google Cloud and Deloitte Corporate
  { id: 1, placement_id: 1, student_id: 2, applied_at: '2026-05-16T11:20:00Z', status: 'shortlisted' },
  { id: 2, placement_id: 2, student_id: 2, applied_at: '2026-05-19T09:30:00Z', status: 'applied' },
  
  // Markus Chen applied to Google and Intel
  { id: 3, placement_id: 1, student_id: 3, applied_at: '2026-05-17T14:40:00Z', status: 'interview' },
  { id: 4, placement_id: 3, student_id: 3, applied_at: '2026-05-21T10:15:00Z', status: 'rejected' }
];

export const initialNotifications: Notification[] = [
  { id: 1, user_id: 2, title: 'Submission Evaluated', message: 'Dr. Sarah Jenkins graded your Red-Black Tree Practical node alignment algorithm. Score: 95/100.', type: 'success', is_read: 0, created_at: '2026-06-05T15:20:00Z' },
  { id: 2, user_id: 2, title: 'Google Interview Scheduled', message: 'You have been shortlisted for Singapore GCP Solutions Intern post. Please reserve your slot for June 12th.', type: 'info', is_read: 0, created_at: '2026-06-04T11:00:00Z' },
  { id: 3, user_id: 2, title: 'Upcoming Exam Alert', message: 'Cloud Containers Midterm Examination is scheduled for tomorrow at 10:00 in Lab 4B Annex.', type: 'warning', is_read: 0, created_at: '2026-06-05T09:00:00Z' }
];

export const initialAnnouncements: Announcement[] = [
  { id: 1, title: 'Summer Semesters Elective Registrations Open', body: 'Students can now select secondary elective courses for upcoming summer term via administrative dashboards. The registration window remains open till June 15th.', audience: 'students', department_id: null, created_by: 1, scheduled_at: '2026-06-01T08:00:00Z', created_at: '2026-06-01T08:00:00Z' },
  { id: 2, title: 'Platform Security System Upgrade Schedulers', body: 'The EduTrack Learning Portal servers in the Data Center will undergo software security hardening patches tonight from 02:00 to 04:00 AM UTC. Portals might lag.', audience: 'all', department_id: null, created_by: 1, scheduled_at: '2026-06-04T12:00:00Z', created_at: '2026-06-04T12:00:00Z' },
  { id: 3, title: 'Grants Submissions Deadlines for Research Labs', body: 'Faculty members are reminded to send academic research lab grants proposal briefs along with equipment inventory checklist by the end of next week.', audience: 'teachers', department_id: null, created_by: 1, scheduled_at: '2026-06-02T10:00:00Z', created_at: '2026-06-02T10:00:00Z' }
];
