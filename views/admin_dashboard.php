<?php
// views/admin_dashboard.php
// Admin Dashboard view matching AdminDashboard.tsx exactly

if (!isset($currentUser) || $currentUser['role'] !== 'admin') {
    return;
}

$db = Database::connect();

// 1. Fetch KPI metrics count
$deptCount = $db->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$courseCount = $db->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$teacherCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
$studentCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();

// Fetch departments for dropdowns & lists
$deptStmt = $db->query("
    SELECT d.*, u.name as head_name 
    FROM departments d 
    LEFT JOIN users u ON d.head_id = u.id 
    ORDER BY d.name ASC
");
$departmentsList = $deptStmt->fetchAll();

// Fetch courses
$coursesStmt = $db->query("
    SELECT c.*, d.name as department_name, d.code as department_code, u.name as teacher_name 
    FROM courses c 
    JOIN departments d ON c.department_id = d.id 
    JOIN users u ON c.teacher_id = u.id 
    ORDER BY c.code ASC
");
$allCourses = $coursesStmt->fetchAll();

// Fetch teachers
$teachersStmt = $db->query("
    SELECT u.*, tp.employee_id, tp.designation, tp.qualification, tp.department_id, d.name as department_name, d.code as department_code 
    FROM users u 
    JOIN teacher_profiles tp ON u.id = tp.user_id 
    LEFT JOIN departments d ON tp.department_id = d.id 
    ORDER BY u.name ASC
");
$allTeachers = $teachersStmt->fetchAll();

// Fetch students
$studentsStmt = $db->query("
    SELECT u.*, sp.roll_no, sp.cgpa, sp.department_id, sp.year, sp.semester, sp.dob, sp.address, d.name as department_name, d.code as department_code 
    FROM users u 
    JOIN student_profiles sp ON u.id = sp.user_id 
    LEFT JOIN departments d ON sp.department_id = d.id 
    ORDER BY u.name ASC
");
$allStudents = $studentsStmt->fetchAll();
?>

<?php if ($currentTab === 'dashboard'): ?>
    <!-- ADMIN HOME TAB -->
    <div class="space-y-6 animate-fadeIn text-left">
        <!-- Header -->
        <div class="border-b border-[#E2E8F0] pb-4">
            <h1 class="text-2xl font-bold text-[#0F172A] tracking-tight">
                Admin Dashboard
            </h1>
            <p class="text-[#64748B] text-xs mt-1">Overview of institutional settings, faculty registries, and campus records</p>
        </div>

        <!-- KPI metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php 
            echo renderStatCard("Departments Registered", $deptCount, "building-2", "blue", "Independent learning sectors");
            echo renderStatCard("Academic Courses", $courseCount, "book-open", "navy", "Configured terms schedules");
            echo renderStatCard("Faculty Instructors", $teacherCount, "graduation-cap", "green", "Assigned classroom leaders");
            echo renderStatCard("Enrolled Scholars", $studentCount, "users", "amber", "Active studying credentials");
            ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left 7Cols: Broadcasted Announcements -->
            <div class="lg:col-span-7 bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
                <div class="flex justify-between items-center pb-2 border-b border-slate-50">
                    <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider">Broadcasted Announcements</h3>
                    <a href="?tab=announcements" class="text-xs font-bold text-[#2563EB] hover:underline">Configure push broadcasts</a>
                </div>
                
                <div class="space-y-4 max-h-96 overflow-y-auto pr-1">
                    <?php 
                    $annStmt = $db->query("SELECT * FROM announcements ORDER BY created_at DESC");
                    $announcements = $annStmt->fetchAll();
                    foreach ($announcements as $ann): 
                    ?>
                        <div class="p-3 border rounded-xl bg-slate-50 border-slate-150 text-xs">
                            <div class="flex justify-between items-start mb-1 gap-2">
                                <h4 class="font-bold text-[#0F172A]"><?php echo htmlspecialchars($ann['title']); ?></h4>
                                <?php echo renderBadge($ann['audience'], 'info'); ?>
                            </div>
                            <p class="text-[#64748B] leading-relaxed mt-1 font-mono"><?php echo htmlspecialchars($ann['body']); ?></p>
                            <span class="text-[9px] block text-slate-400 mt-2 font-mono">Published: <?php echo date('Y-m-d H:i:s', strtotime($ann['created_at'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right 5Cols: Administrator Shortcuts deck -->
            <div class="lg:col-span-5 bg-[#0F172A] text-white rounded-xl p-6 shadow-md space-y-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-[#2563EB]">Admin Operations Center</h3>
                <div class="space-y-3">
                    <a href="?tab=students&open_add=1" class="w-full text-left py-2 px-3 border border-white/5 hover:border-slate-500 rounded-lg text-xs font-semibold flex items-center justify-between block">
                        <span>Initialize New Scholar Admission</span>
                        <i data-lucide="user-plus" class="w-4 h-4 text-emerald-400"></i>
                    </a>
                    <a href="?tab=teachers&open_add=1" class="w-full text-left py-2 px-3 border border-white/5 hover:border-slate-500 rounded-lg text-xs font-semibold flex items-center justify-between block">
                        <span>Appoint Faculty Professor</span>
                        <i data-lucide="plus-circle" class="w-4 h-4 text-blue-400"></i>
                    </a>
                    <a href="?tab=departments&open_add=1" class="w-full text-left py-2 px-3 border border-white/5 hover:border-slate-500 rounded-lg text-xs font-semibold flex items-center justify-between block">
                        <span>Configure Academic Sector</span>
                        <i data-lucide="building" class="w-4 h-4 text-indigo-400"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($currentTab === 'students'): ?>
    <!-- ADMIN STUDENTS Registry -->
    <div class="space-y-6 animate-fadeIn text-left">
        <?php 
        $studentSearch = $_GET['search'] ?? '';
        $selectedDeptFilter = $_GET['dept_filter'] ?? 'all';

        // Filter student list
        $filteredStudents = array_filter($allStudents, function($stu) use ($studentSearch, $selectedDeptFilter) {
            $matchesSearch = empty($studentSearch) 
                || str_contains(strtolower($stu['name']), strtolower($studentSearch))
                || str_contains(strtolower($stu['email']), strtolower($studentSearch))
                || str_contains(strtolower($stu['roll_no']), strtolower($studentSearch));

            $matchesDept = ($selectedDeptFilter === 'all') 
                || ((int)$stu['department_id'] === (int)$selectedDeptFilter);

            return $matchesSearch && $matchesDept;
        });

        $openAdd = isset($_GET['open_add']) ? 'true' : 'false';
        ?>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
                <h1 class="text-2xl font-extrabold text-[#0F172A]">Students admission Registry</h1>
                <p class="text-[#64748B] text-xs">Acknowledge registered profiles details, search roll, and configure credentials.</p>
            </div>

            <button onclick="openAddStudentModal();" class="py-2.5 px-4 bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-2 cursor-pointer transition-transform active:scale-95">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Admit Scholar Profile</span>
            </button>
        </div>

        <!-- Filter bar -->
        <form method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <input type="hidden" name="tab" value="students" />
            <div class="md:col-span-8 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
                </div>
                <input
                    type="text"
                    name="search"
                    value="<?php echo htmlspecialchars($studentSearch); ?>"
                    placeholder="Search student scholar name, roll identifier, or email contact..."
                    class="w-full pl-9 pr-4 py-2 bg-white border border-[#E2E8F0] focus:ring-1 focus:ring-[#2563EB] focus:border-transparent rounded-xl text-xs text-[#0F172A]"
                    onchange="this.form.submit();"
                />
            </div>

            <div class="md:col-span-4">
                <select
                    name="dept_filter"
                    class="w-full bg-white border border-[#E2E8F0] p-2 rounded-xl text-xs font-bold text-slate-600 focus:outline-none"
                    onchange="this.form.submit();"
                >
                    <option value="all">Display All sectors</option>
                    <?php foreach ($departmentsList as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo (string)$selectedDeptFilter === (string)$d['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['name']); ?> (<?php echo htmlspecialchars($d['code']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <!-- Students list table -->
        <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#F8FAFC] border-b border-[#E2E8F0] text-slate-500 font-mono uppercase text-[10px]">
                            <th class="p-4">NAME & ADMISSION INDEX</th>
                            <th class="p-4">ROLL / INDIVIDUAL KEY</th>
                            <th class="p-4">DEPARTMENT</th>
                            <th class="p-4 text-center">CGPA</th>
                            <th class="p-4 text-right">CONTROLS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium font-mono">
                        <?php foreach ($filteredStudents as $su): ?>
                            <tr class="hover:bg-slate-50/30">
                                <td class="p-4 flex items-center gap-3 font-sans">
                                    <img src="<?php echo htmlspecialchars($su['avatar']); ?>" alt="Avatar" class="w-8 h-8 rounded-full border border-slate-100 object-cover" />
                                    <div>
                                        <span class="font-extrabold text-[#0F172A] block"><?php echo htmlspecialchars($su['name']); ?></span>
                                        <span class="text-[10px] text-slate-400 font-mono"><?php echo htmlspecialchars($su['email']); ?></span>
                                    </div>
                                </td>
                                <td class="p-4"><?php echo htmlspecialchars($su['roll_no']); ?></td>
                                <td class="p-4 font-sans text-slate-700"><?php echo htmlspecialchars($su['department_name'] ?? 'Unassigned'); ?></td>
                                <td class="p-4 text-center text-[#2563EB] font-black"><?php echo number_format($su['cgpa'], 2); ?></td>
                                <td class="p-4 text-right space-x-2 font-sans">
                                    <button onclick="openEditStudentModal(<?php echo $su['id']; ?>, '<?php echo htmlspecialchars(addslashes($su['name'])); ?>', '<?php echo htmlspecialchars(addslashes($su['email'])); ?>', '<?php echo htmlspecialchars($su['phone']); ?>', '<?php echo htmlspecialchars($su['roll_no']); ?>', <?php echo $su['department_id']; ?>, <?php echo $su['year']; ?>, <?php echo $su['semester']; ?>, '<?php echo $su['dob']; ?>', '<?php echo htmlspecialchars(addslashes($su['address'])); ?>');" class="text-xs font-bold text-[#2563EB] hover:underline cursor-pointer">Modify</button>
                                    <a href="actions.php?action=delete_student&user_id=<?php echo $su['id']; ?>" onclick="return confirm('Confirm physical deletion of student account \'<?php echo htmlspecialchars(addslashes($su['name'])); ?>\' from repository files?')" class="text-xs font-bold text-red-600 hover:underline cursor-pointer">Remove</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Student Add/Edit Modal -->
        <div id="student_modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 animate-modalBackdrop hidden">
            <div class="bg-white border border-[#E2E8F0] rounded-xl p-6 w-full max-w-lg shadow-lg relative max-h-[90vh] overflow-y-auto animate-modalContent">
                <button onclick="closeStudentModal();" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
                <h3 id="student_modal_title" class="text-lg font-extrabold text-[#0F172A] pb-2 border-b border-slate-50">Admit Student Scholar Profile</h3>

                <form id="student_form" action="actions.php?action=add_student" method="POST" class="space-y-4 mt-4 text-xs font-bold">
                    <input type="hidden" name="user_id" id="stu_user_id" value="" />
                    
                    <div>
                        <label class="block text-slate-500 mb-1">Scholar Full Name</label>
                        <input type="text" name="name" id="stu_name" class="w-full border p-2 rounded focus:ring-1 focus:ring-blue-500 text-xs" required />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-500 mb-1">Academic Email Profile</label>
                            <input type="email" name="email" id="stu_email" class="w-full border p-2 rounded focus:ring-1 text-xs" required />
                        </div>
                        <div>
                            <label class="block text-slate-500 mb-1">Mobile Phone contact</label>
                            <input type="text" name="phone" id="stu_phone" class="w-full border p-2 rounded text-xs" placeholder="9458621530" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-500 mb-1">Institutional Roll Number</label>
                            <input type="text" name="roll_no" id="stu_roll" placeholder="RSET-CS-101" class="w-full border p-2 rounded focus:ring-1 text-xs" required />
                        </div>
                        <div>
                            <label class="block text-slate-500 mb-1">Select Department sector</label>
                            <select name="department_id" id="stu_dept" class="w-full border p-2 rounded bg-white text-slate-700 text-xs">
                                <?php foreach ($departmentsList as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-slate-500 mb-1">Year</label>
                            <input type="number" name="year" id="stu_year" value="1" class="w-full border p-2 rounded text-xs" />
                        </div>
                        <div>
                            <label class="block text-slate-500 mb-1">Semester</label>
                            <input type="number" name="semester" id="stu_sem" value="1" class="w-full border p-2 rounded text-xs" />
                        </div>
                        <div>
                            <label class="block text-slate-500 mb-1">Birth DOB</label>
                            <input type="date" name="dob" id="stu_dob" class="w-full border p-1.5 rounded font-mono text-xs" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-500 mb-1">Full Permanent Address</label>
                        <input type="text" name="address" id="stu_address" class="w-full border p-2 rounded text-xs" />
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end gap-2 text-xs">
                        <button type="button" onclick="closeStudentModal();" class="px-4 py-2 border rounded hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#2563EB] text-white rounded hover:bg-[#1D4ED8]">Save Scholar Credentials</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openAddStudentModal() {
                document.getElementById('student_form').action = "actions.php?action=add_student";
                document.getElementById('stu_user_id').value = "";
                document.getElementById('stu_name').value = "";
                document.getElementById('stu_email').value = "";
                document.getElementById('stu_phone').value = "";
                document.getElementById('stu_roll').value = "";
                document.getElementById('stu_year').value = "1";
                document.getElementById('stu_sem').value = "1";
                document.getElementById('stu_dob').value = "";
                document.getElementById('stu_address').value = "";
                document.getElementById('student_modal_title').innerText = "Admit Student Scholar Profile";
                document.getElementById('student_modal').classList.remove('hidden');
            }
            function openEditStudentModal(id, name, email, phone, roll, deptId, year, sem, dob, address) {
                document.getElementById('student_form').action = "actions.php?action=edit_student";
                document.getElementById('stu_user_id').value = id;
                document.getElementById('stu_name').value = name;
                document.getElementById('stu_email').value = email;
                document.getElementById('stu_phone').value = phone;
                document.getElementById('stu_roll').value = roll;
                document.getElementById('stu_dept').value = deptId;
                document.getElementById('stu_year').value = year;
                document.getElementById('stu_sem').value = sem;
                document.getElementById('stu_dob').value = dob;
                document.getElementById('stu_address').value = address;
                document.getElementById('student_modal_title').innerText = "Modify Student Scholar details";
                document.getElementById('student_modal').classList.remove('hidden');
            }
            function closeStudentModal() {
                document.getElementById('student_modal').classList.add('hidden');
            }
            
            // Auto open if redirected
            if (<?php echo $openAdd; ?>) {
                openAddStudentModal();
            }
        </script>
    </div>

<?php elseif ($currentTab === 'teachers'): ?>
    <!-- ADMIN TEACHERS Registry -->
    <div class="space-y-6 animate-fadeIn text-left">
        <?php 
        $teacherSearch = $_GET['search'] ?? '';

        // Filter teacher list
        $filteredTeachers = array_filter($allTeachers, function($t) use ($teacherSearch) {
            return empty($teacherSearch) 
                || str_contains(strtolower($t['name']), strtolower($teacherSearch))
                || str_contains(strtolower($t['email']), strtolower($teacherSearch))
                || str_contains(strtolower($t['employee_id']), strtolower($teacherSearch));
        });

        $openAdd = isset($_GET['open_add']) ? 'true' : 'false';
        ?>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
                <h1 class="text-2xl font-extrabold text-[#0F172A]">Teachers instructing Faculty Registry</h1>
                <p class="text-[#64748B] text-xs">Appoint, evaluate, and adjust instructor information records.</p>
            </div>

            <button onclick="openAddTeacherModal();" class="py-2.5 px-4 bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-2 cursor-pointer transition-transform active:scale-95">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Appoint Faculty Professor</span>
            </button>
        </div>

        <form method="GET" class="relative">
            <input type="hidden" name="tab" value="teachers" />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
            </div>
            <input
                type="text"
                name="search"
                value="<?php echo htmlspecialchars($teacherSearch); ?>"
                placeholder="Search faculty instructor name, qualifications, or department code..."
                class="w-full pl-9 pr-4 py-2.5 bg-white border border-[#E2E8F0] rounded-xl text-xs text-[#0F172A] focus:outline-none"
                onchange="this.form.submit();"
            />
        </form>

        <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#F8FAFC] border-b border-[#E2E8F0] text-slate-500 font-mono uppercase text-[10px]">
                            <th class="p-4">PROFESSOR FULL NAME</th>
                            <th class="p-4">EMPLOYEE ID / BADGE</th>
                            <th class="p-4">DESIGNATION</th>
                            <th class="p-4">DEPT / CREDENTIALS</th>
                            <th class="p-4 text-right">CONTROLS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium font-mono">
                        <?php foreach ($filteredTeachers as $tu): ?>
                            <tr class="hover:bg-slate-50/30">
                                <td class="p-4 flex items-center gap-3 font-sans">
                                    <img src="<?php echo htmlspecialchars($tu['avatar']); ?>" alt="Avatar" class="w-8 h-8 rounded-full border border-slate-100 object-cover" />
                                    <div>
                                        <span class="font-extrabold text-[#0F172A] block"><?php echo htmlspecialchars($tu['name']); ?></span>
                                        <span class="text-[10px] text-slate-400 font-mono"><?php echo htmlspecialchars($tu['email']); ?></span>
                                    </div>
                                </td>
                                <td class="p-4"><?php echo htmlspecialchars($tu['employee_id']); ?></td>
                                <td class="p-4 text-slate-600"><?php echo htmlspecialchars($tu['designation']); ?></td>
                                <td class="p-4 font-sans text-slate-700"><?php echo htmlspecialchars($tu['department_name'] ?? 'Unassigned'); ?></td>
                                <td class="p-4 text-right space-x-2 font-sans">
                                    <button onclick="openEditTeacherModal(<?php echo $tu['id']; ?>, '<?php echo htmlspecialchars(addslashes($tu['name'])); ?>', '<?php echo htmlspecialchars(addslashes($tu['email'])); ?>', '<?php echo htmlspecialchars($tu['phone']); ?>', '<?php echo htmlspecialchars($tu['employee_id']); ?>', <?php echo $tu['department_id']; ?>, '<?php echo htmlspecialchars(addslashes($tu['designation'])); ?>', '<?php echo htmlspecialchars(addslashes($tu['qualification'])); ?>');" class="text-xs font-bold text-[#2563EB] hover:underline cursor-pointer">Modify</button>
                                    <a href="actions.php?action=delete_teacher&user_id=<?php echo $tu['id']; ?>" onclick="return confirm('Confirm physical deletion of instructor \'<?php echo htmlspecialchars(addslashes($tu['name'])); ?>\' from global registry?')" class="text-xs font-bold text-red-600 hover:underline cursor-pointer">Remove</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Teacher Add/Edit Modal -->
        <div id="teacher_modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 animate-modalBackdrop hidden">
            <div class="bg-white border border-[#E2E8F0] rounded-xl p-6 w-full max-w-lg shadow-lg relative max-h-[90vh] overflow-y-auto animate-modalContent">
                <button onclick="closeTeacherModal();" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
                <h3 id="teacher_modal_title" class="text-lg font-extrabold text-[#0F172A] pb-2 border-b border-slate-50">Appoint Faculty Professor</h3>

                <form id="teacher_form" action="actions.php?action=add_teacher" method="POST" class="space-y-4 mt-4 text-xs font-bold">
                    <input type="hidden" name="user_id" id="tea_user_id" value="" />
                    
                    <div>
                        <label class="block text-slate-500 mb-1">Professor Full Name</label>
                        <input type="text" name="name" id="tea_name" class="w-full border p-2 rounded focus:ring-1 focus:ring-blue-500 text-xs" required />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-500 mb-1">Academic Email</label>
                            <input type="email" name="email" id="tea_email" class="w-full border p-2 rounded text-xs" required />
                        </div>
                        <div>
                            <label class="block text-slate-500 mb-1">Mobile Contact Phone</label>
                            <input type="text" name="phone" id="tea_phone" class="w-full border p-2 rounded text-xs" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-500 mb-1">Employee ID / Code</label>
                            <input type="text" name="employee_id" id="tea_empid" class="w-full border p-2 rounded text-xs" required />
                        </div>
                        <div>
                            <label class="block text-slate-500 mb-1">Select Department</label>
                            <select name="department_id" id="tea_dept" class="w-full border p-2 rounded bg-white text-slate-700 text-xs">
                                <?php foreach ($departmentsList as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-500 mb-1">Designation</label>
                            <input type="text" name="designation" id="tea_desig" class="w-full border p-2 rounded text-xs" required />
                        </div>
                        <div>
                            <label class="block text-slate-500 mb-1">Qualifications</label>
                            <input type="text" name="qualification" id="tea_qual" class="w-full border p-2 rounded text-xs" required />
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end gap-2 text-xs">
                        <button type="button" onclick="closeTeacherModal();" class="px-4 py-2 border rounded hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#2563EB] text-white rounded hover:bg-[#1D4ED8]">Save Faculty Profile</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openAddTeacherModal() {
                document.getElementById('teacher_form').action = "actions.php?action=add_teacher";
                document.getElementById('tea_user_id').value = "";
                document.getElementById('tea_name').value = "";
                document.getElementById('tea_email').value = "";
                document.getElementById('tea_phone').value = "";
                document.getElementById('tea_empid').value = "";
                document.getElementById('tea_desig').value = "Assistant Professor";
                document.getElementById('tea_qual').value = "M.Tech, Ph.D";
                document.getElementById('teacher_modal_title').innerText = "Appoint Faculty Professor";
                document.getElementById('teacher_modal').classList.remove('hidden');
            }
            function openEditTeacherModal(id, name, email, phone, empId, deptId, designation, qualification) {
                document.getElementById('teacher_form').action = "actions.php?action=edit_teacher";
                document.getElementById('tea_user_id').value = id;
                document.getElementById('tea_name').value = name;
                document.getElementById('tea_email').value = email;
                document.getElementById('tea_phone').value = phone;
                document.getElementById('tea_empid').value = empId;
                document.getElementById('tea_dept').value = deptId;
                document.getElementById('tea_desig').value = designation;
                document.getElementById('tea_qual').value = qualification;
                document.getElementById('teacher_modal_title').innerText = "Modify Faculty details";
                document.getElementById('teacher_modal').classList.remove('hidden');
            }
            function closeTeacherModal() {
                document.getElementById('teacher_modal').classList.add('hidden');
            }
            
            // Auto open if redirected
            if (<?php echo $openAdd; ?>) {
                openAddTeacherModal();
            }
        </script>
    </div>

<?php elseif ($currentTab === 'departments'): ?>
    <!-- ADMIN DEPARTMENTS TAB -->
    <div class="space-y-6 animate-fadeIn text-left">
        <?php 
        $openAdd = isset($_GET['open_add']) ? 'true' : 'false';
        ?>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
                <h1 class="text-2xl font-extrabold text-[#0F172A]">Campus Academic Sectors</h1>
                <p class="text-[#64748B] text-xs">Configure departments, define unique codes, appoint heads.</p>
            </div>

            <button onclick="openAddDeptModal();" class="py-2.5 px-4 bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-2 cursor-pointer transition-transform active:scale-95">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Configure Academic Sector</span>
            </button>
        </div>

        <!-- Departments grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($departmentsList as $d): ?>
                <div class="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow relative">
                    <div class="space-y-3">
                        <span class="font-mono text-xs font-bold bg-slate-100 text-slate-500 rounded px-2 py-0.5 border"><?php echo htmlspecialchars($d['code']); ?></span>
                        <h3 class="font-extrabold text-[#0F172A] text-sm mt-1.5 leading-snug"><?php echo htmlspecialchars($d['name']); ?></h3>
                        <p class="text-xs text-[#64748B]"><strong>Head of Dept:</strong> <?php echo htmlspecialchars($d['head_name'] ?: 'None'); ?></p>
                    </div>
                    <div class="border-t pt-3 mt-4 flex justify-end gap-2">
                        <button onclick="openEditDeptModal(<?php echo $d['id']; ?>, '<?php echo htmlspecialchars(addslashes($d['name'])); ?>', '<?php echo htmlspecialchars(addslashes($d['code'])); ?>', <?php echo $d['head_id'] ?: 'null'; ?>);" class="text-xs font-bold text-[#2563EB] hover:underline cursor-pointer">Modify</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Department Modal -->
        <div id="dept_modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 animate-modalBackdrop hidden">
            <div class="bg-white border border-[#E2E8F0] rounded-xl p-6 w-full max-w-md shadow-lg relative animate-modalContent">
                <button onclick="closeDeptModal();" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
                <h3 id="dept_modal_title" class="text-lg font-extrabold text-[#0F172A] pb-2 border-b border-slate-50">Configure Academic Sector</h3>

                <form id="dept_form" action="actions.php?action=add_department" method="POST" class="space-y-4 mt-4 text-xs font-bold">
                    <input type="hidden" name="id" id="dept_id" value="" />
                    
                    <div>
                        <label class="block text-slate-500 mb-1">Department Name</label>
                        <input type="text" name="name" id="dept_name" class="w-full border p-2.5 rounded text-xs focus:ring-1" required />
                    </div>

                    <div>
                        <label class="block text-slate-500 mb-1">Unique Code identifier</label>
                        <input type="text" name="code" id="dept_code" class="w-full border p-2.5 rounded text-xs uppercase" required />
                    </div>

                    <div>
                        <label class="block text-slate-500 mb-1">Appoint Department Head</label>
                        <select name="head_id" id="dept_head" class="w-full border p-2 rounded bg-white text-slate-700 text-xs">
                            <option value="">Select Department Head (Teacher)</option>
                            <?php foreach ($allTeachers as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?> (<?php echo htmlspecialchars($t['employee_id']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end gap-2 text-xs">
                        <button type="button" onclick="closeDeptModal();" class="px-4 py-2 border rounded hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#2563EB] text-white rounded hover:bg-[#1D4ED8]">Save Department</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openAddDeptModal() {
                document.getElementById('dept_form').action = "actions.php?action=add_department";
                document.getElementById('dept_id').value = "";
                document.getElementById('dept_name').value = "";
                document.getElementById('dept_code').value = "";
                document.getElementById('dept_head').value = "";
                document.getElementById('dept_modal_title').innerText = "Configure Academic Sector";
                document.getElementById('dept_modal').classList.remove('hidden');
            }
            function openEditDeptModal(id, name, code, headId) {
                document.getElementById('dept_form').action = "actions.php?action=edit_department";
                document.getElementById('dept_id').value = id;
                document.getElementById('dept_name').value = name;
                document.getElementById('dept_code').value = code;
                document.getElementById('dept_head').value = headId !== null ? headId : "";
                document.getElementById('dept_modal_title').innerText = "Modify Department attributes";
                document.getElementById('dept_modal').classList.remove('hidden');
            }
            function closeDeptModal() {
                document.getElementById('dept_modal').classList.add('hidden');
            }
            
            // Auto open
            if (<?php echo $openAdd; ?>) {
                openAddDeptModal();
            }
        </script>
    </div>

<?php elseif ($currentTab === 'courses'): ?>
    <!-- ADMIN COURSES CONFIG -->
    <div class="space-y-6 animate-fadeIn text-left">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
                <h1 class="text-2xl font-extrabold text-[#0F172A]">Course Catalogs Registry</h1>
                <p class="text-[#64748B] text-xs">Configure syllabus indexes, credit weightings, assign faculty leaders.</p>
            </div>

            <div class="flex items-center gap-2">
                <button onclick="openEnrollStudentModal();" class="py-2.5 px-4 bg-[#0F172A] hover:bg-[#1E293B] text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-2 cursor-pointer transition-transform active:scale-95">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    <span>Enroll Student Scholar</span>
                </button>
                <button onclick="openAddCourseModal();" class="py-2.5 px-4 bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-2 cursor-pointer transition-transform active:scale-95">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    <span>Create Course catalog</span>
                </button>
            </div>
        </div>

        <!-- Course list -->
        <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 border-b border-[#E2E8F0] font-mono uppercase">
                            <th class="p-4">CODE</th>
                            <th class="p-4">COURSE TITLE</th>
                            <th class="p-4">DEPARTMENT</th>
                            <th class="p-4">FACULTY ASSIGNED</th>
                            <th class="p-4 text-center">CREDITS</th>
                            <th class="p-4 text-center">SEM</th>
                            <th class="p-4 text-right">STATUS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono font-medium">
                        <?php foreach ($allCourses as $c): ?>
                            <tr class="hover:bg-slate-50/30">
                                <td class="p-4 font-bold"><?php echo htmlspecialchars($c['code']); ?></td>
                                <td class="p-4 font-sans text-slate-800 font-semibold flex items-center justify-between">
                                    <span><?php echo htmlspecialchars($c['title']); ?></span>
                                    <button onclick="openEditCourseModal(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars(addslashes($c['title'])); ?>', '<?php echo htmlspecialchars(addslashes($c['code'])); ?>', <?php echo $c['department_id']; ?>, <?php echo $c['teacher_id']; ?>, <?php echo $c['credits']; ?>, <?php echo $c['semester']; ?>, '<?php echo htmlspecialchars(addslashes($c['description'])); ?>', '<?php echo $c['status']; ?>');" class="text-[10px] text-[#2563EB] hover:underline font-bold font-sans">Modify</button>
                                </td>
                                <td class="p-4 font-sans"><?php echo htmlspecialchars($c['department_name']); ?></td>
                                <td class="p-4 font-sans"><?php echo htmlspecialchars($c['teacher_name']); ?></td>
                                <td class="p-4 text-center"><?php echo $c['credits']; ?></td>
                                <td class="p-4 text-center"><?php echo $c['semester']; ?></td>
                                <td class="p-4 text-right">
                                    <?php echo renderBadge($c['status'], $c['status'] === 'active' ? 'success' : 'danger'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Enrolled Scholars Table -->
        <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden mt-6">
            <div class="p-4 border-b">
                <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider">Scholar active classroom enrollments</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 border-b border-[#E2E8F0] font-mono uppercase">
                            <th class="p-4">SCHOLAR</th>
                            <th class="p-4">CLASS / SYLLABUS</th>
                            <th class="p-4">ENROLLMENT TIMESTAMP</th>
                            <th class="p-4 text-right">STATUS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono font-medium">
                        <?php 
                        $enrStmt = $db->query("
                            SELECT e.*, u.name as student_name, u.email as student_email, c.code as course_code, c.title as course_title 
                            FROM enrollments e 
                            JOIN users u ON e.student_id = u.id 
                            JOIN courses c ON e.course_id = c.id 
                            ORDER BY e.enrolled_at DESC
                        ");
                        $allEnrollments = $enrStmt->fetchAll();
                        foreach ($allEnrollments as $en): 
                        ?>
                            <tr class="hover:bg-slate-50/30">
                                <td class="p-4 font-sans">
                                    <span class="font-bold text-[#0F172A] block"><?php echo htmlspecialchars($en['student_name']); ?></span>
                                    <span class="text-[10px] text-slate-400 font-mono"><?php echo htmlspecialchars($en['student_email']); ?></span>
                                </td>
                                <td class="p-4">
                                    <span class="font-bold block"><?php echo htmlspecialchars($en['course_code']); ?></span>
                                    <span class="text-[10px] text-slate-400 font-sans"><?php echo htmlspecialchars($en['course_title']); ?></span>
                                </td>
                                <td class="p-4"><?php echo date('Y-m-d H:i', strtotime($en['enrolled_at'])); ?></td>
                                <td class="p-4 text-right space-x-2 font-sans">
                                    <?php echo renderBadge($en['status'], 'success'); ?>
                                    <a href="actions.php?action=remove_enrollment&id=<?php echo $en['id']; ?>" onclick="return confirm('Drop scholar enrollment registry from course?')" class="text-xs text-red-600 hover:underline">Drop</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Course Add/Edit Modal -->
        <div id="course_modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 animate-modalBackdrop hidden">
            <div class="bg-white border border-[#E2E8F0] rounded-xl p-6 w-full max-w-lg shadow-lg relative animate-modalContent">
                <button onclick="closeCourseModal();" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
                <h3 id="course_modal_title" class="text-lg font-extrabold text-[#0F172A] pb-2 border-b border-slate-50">Create Course catalog</h3>

                <form id="course_form" action="actions.php?action=add_course" method="POST" class="space-y-4 mt-4 text-xs font-bold">
                    <input type="hidden" name="id" id="crs_id" value="" />
                    
                    <div>
                        <label class="block text-slate-500 mb-1">Course Title</label>
                        <input type="text" name="title" id="crs_title" class="w-full border p-2.5 rounded text-xs" required />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-500 mb-1">Course Code</label>
                            <input type="text" name="code" id="crs_code" placeholder="CS401" class="w-full border p-2.5 rounded text-xs uppercase" required />
                        </div>
                        <div>
                            <label class="block text-slate-500 mb-1">Academic Department</label>
                            <select name="department_id" id="crs_dept" class="w-full border p-2 rounded bg-white text-slate-700 text-xs">
                                <?php foreach ($departmentsList as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-slate-500 mb-1">Select Instructor</label>
                            <select name="teacher_id" id="crs_teacher" class="w-full border p-2 rounded bg-white text-slate-700 text-xs">
                                <?php foreach ($allTeachers as $t): ?>
                                    <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-500 mb-1">Credits weight</label>
                            <input type="number" name="credits" id="crs_credits" value="3" min="1" class="w-full border p-2 rounded text-xs" required />
                        </div>
                        <div>
                            <label class="block text-slate-500 mb-1">Class Semester</label>
                            <input type="number" name="semester" id="crs_sem" value="4" min="1" class="w-full border p-2 rounded text-xs" required />
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-500 mb-1">Catalog Description</label>
                        <textarea name="description" id="crs_desc" rows="4" class="w-full border p-2.5 rounded text-xs font-mono" required></textarea>
                    </div>

                    <div id="crs_status_block" class="hidden">
                        <label class="block text-slate-500 mb-1">Course Catalog status</label>
                        <select name="status" id="crs_status" class="w-full border p-2 rounded bg-white text-slate-700 text-xs">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end gap-2 text-xs">
                        <button type="button" onclick="closeCourseModal();" class="px-4 py-2 border rounded hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#2563EB] text-white rounded hover:bg-[#1D4ED8]">Save Course catalog</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Enroll Student Modal -->
        <div id="enroll_modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 animate-modalBackdrop hidden">
            <div class="bg-white border border-[#E2E8F0] rounded-xl p-6 w-full max-w-md shadow-lg relative animate-modalContent">
                <button onclick="closeEnrollStudentModal();" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
                <h3 class="text-lg font-extrabold text-[#0F172A] pb-2 border-b border-slate-50">Enroll Student Scholar</h3>

                <form action="actions.php?action=add_enrollment" method="POST" class="space-y-4 mt-4 text-xs font-bold">
                    <div>
                        <label class="block text-slate-500 mb-1">Select Student Scholar</label>
                        <select name="student_id" class="w-full border p-2.5 rounded bg-white text-slate-700 text-xs">
                            <?php foreach ($allStudents as $st): ?>
                                <option value="<?php echo $st['id']; ?>"><?php echo htmlspecialchars($st['name']); ?> (<?php echo htmlspecialchars($st['roll_no']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-slate-500 mb-1">Select course catalog</label>
                        <select name="course_id" class="w-full border p-2.5 rounded bg-white text-slate-700 text-xs">
                            <?php foreach ($allCourses as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['code']); ?> - <?php echo htmlspecialchars($c['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end gap-2 text-xs">
                        <button type="button" onclick="closeEnrollStudentModal();" class="px-4 py-2 border rounded hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#0F172A] text-white rounded hover:bg-[#1E293B]">Confirm Enrollment</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openAddCourseModal() {
                document.getElementById('course_form').action = "actions.php?action=add_course";
                document.getElementById('crs_id').value = "";
                document.getElementById('crs_title').value = "";
                document.getElementById('crs_code').value = "";
                document.getElementById('crs_credits').value = "3";
                document.getElementById('crs_sem').value = "4";
                document.getElementById('crs_desc').value = "";
                document.getElementById('crs_status_block').classList.add('hidden');
                document.getElementById('course_modal_title').innerText = "Create Course catalog";
                document.getElementById('course_modal').classList.remove('hidden');
            }
            function openEditCourseModal(id, title, code, deptId, teacherId, credits, sem, desc, status) {
                document.getElementById('course_form').action = "actions.php?action=edit_course";
                document.getElementById('crs_id').value = id;
                document.getElementById('crs_title').value = title;
                document.getElementById('crs_code').value = code;
                document.getElementById('crs_dept').value = deptId;
                document.getElementById('crs_teacher').value = teacherId;
                document.getElementById('crs_credits').value = credits;
                document.getElementById('crs_sem').value = sem;
                document.getElementById('crs_desc').value = desc;
                document.getElementById('crs_status').value = status;
                document.getElementById('crs_status_block').classList.remove('hidden');
                document.getElementById('course_modal_title').innerText = "Modify Course Catalog details";
                document.getElementById('course_modal').classList.remove('hidden');
            }
            function closeCourseModal() {
                document.getElementById('course_modal').classList.add('hidden');
            }
            function openEnrollStudentModal() {
                document.getElementById('enroll_modal').classList.remove('hidden');
            }
            function closeEnrollStudentModal() {
                document.getElementById('enroll_modal').classList.add('hidden');
            }
        </script>
    </div>

<?php elseif ($currentTab === 'placements'): ?>
    <!-- ADMIN PLACEMENTS CELL -->
    <div class="space-y-6 animate-fadeIn text-left">
        <div class="border-b border-[#E2E8F0] pb-4">
            <h1 class="text-2xl font-extrabold text-[#0F172A]">Strategic Placements Cell</h1>
            <p class="text-[#64748B] text-xs">Advertise open jobs/internships, modify details, check student application processes.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left 6Cols: Openings & Applications pipeline -->
            <div class="lg:col-span-7 space-y-6">
                <!-- Placements list -->
                <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
                    <div class="p-4 border-b">
                        <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider">Placement vacancies Registry</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 border-b border-[#E2E8F0] font-mono uppercase">
                                    <th class="p-4">COMPANY & ROLE</th>
                                    <th class="p-4">DEADLINE</th>
                                    <th class="p-4 text-right">STATUS</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-mono font-medium">
                                <?php 
                                $plStmt = $db->query("SELECT * FROM placements ORDER BY deadline ASC");
                                $placements = $plStmt->fetchAll();
                                foreach ($placements as $pl): 
                                ?>
                                    <tr class="hover:bg-slate-50/30">
                                        <td class="p-4 font-sans">
                                            <span class="font-extrabold text-[#0F172A] block"><?php echo htmlspecialchars($pl['company']); ?></span>
                                            <span class="text-[10px] text-slate-400 font-mono"><?php echo htmlspecialchars($pl['role']); ?></span>
                                        </td>
                                        <td class="p-4"><?php echo $pl['deadline']; ?></td>
                                        <td class="p-4 text-right space-x-2 font-sans">
                                            <?php echo renderBadge($pl['status'], $pl['status'] === 'open' ? 'success' : 'danger'); ?>
                                            <?php if ($pl['status'] === 'open'): ?>
                                                <a href="actions.php?action=update_placement_status&placement_id=<?php echo $pl['id']; ?>&status=closed" class="text-xs text-red-600 hover:underline">Close</a>
                                            <?php else: ?>
                                                <a href="actions.php?action=update_placement_status&placement_id=<?php echo $pl['id']; ?>&status=open" class="text-xs text-emerald-600 hover:underline">Reopen</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Applications pipeline -->
                <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
                    <div class="p-4 border-b">
                        <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider">Placement applications pipeline</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 border-b border-[#E2E8F0] font-mono uppercase">
                                    <th class="p-4">SCHOLAR</th>
                                    <th class="p-4">ROLE / COMPANY</th>
                                    <th class="p-4 text-right">PIPELINE STATUS</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-mono font-medium">
                                <?php 
                                $appStmt = $db->query("
                                    SELECT pa.*, u.name as student_name, u.email as student_email, p.role as job_role, p.company as job_company 
                                    FROM placement_applications pa 
                                    JOIN users u ON pa.student_id = u.id 
                                    JOIN placements p ON pa.placement_id = p.id 
                                    ORDER BY pa.applied_at DESC
                                ");
                                $applications = $appStmt->fetchAll();
                                foreach ($applications as $ap): 
                                ?>
                                    <tr class="hover:bg-slate-50/30">
                                        <td class="p-4 font-sans">
                                            <span class="font-bold text-[#0F172A] block"><?php echo htmlspecialchars($ap['student_name']); ?></span>
                                            <span class="text-[10px] text-slate-400 font-mono"><?php echo htmlspecialchars($ap['student_email']); ?></span>
                                        </td>
                                        <td class="p-4 font-sans">
                                            <span class="font-bold block"><?php echo htmlspecialchars($ap['job_role']); ?></span>
                                            <span class="text-[10px] text-slate-400 font-mono"><?php echo htmlspecialchars($ap['job_company']); ?></span>
                                        </td>
                                        <td class="p-4 text-right font-sans">
                                            <select
                                                onchange="window.location.href='actions.php?action=update_placement_app_status&app_id=<?php echo $ap['id']; ?>&status=' + this.value;"
                                                class="bg-white border p-1 rounded text-xs font-bold text-slate-700 focus:outline-none"
                                            >
                                                <?php foreach (['applied', 'shortlisted', 'interview', 'offered', 'rejected'] as $st): ?>
                                                    <option value="<?php echo $st; ?>" <?php echo $ap['status'] === $st ? 'selected' : ''; ?>><?php echo strtoupper($st); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right 6Cols: Create placement opening form -->
            <div class="lg:col-span-5 bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
                <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider border-b pb-2">Publish Job opening</h3>
                <form action="actions.php?action=add_placement" method="POST" class="space-y-4 text-xs font-bold">
                    <div>
                        <label class="block text-slate-500 mb-1">Company Name</label>
                        <input type="text" name="company" placeholder="Google, Microsoft..." class="w-full border p-2.5 rounded focus:ring-1 focus:ring-blue-500 outline-none" required />
                    </div>
                    <div>
                        <label class="block text-slate-500 mb-1">Role Title</label>
                        <input type="text" name="role" placeholder="Software Engineer Intern..." class="w-full border p-2.5 rounded focus:ring-1 focus:ring-blue-500 outline-none" required />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-500 mb-1">Job Type</label>
                            <select name="type" class="w-full border p-2 rounded bg-white text-slate-700 text-xs">
                                <option value="internship">Internship</option>
                                <option value="full-time">Full-Time</option>
                                <option value="part-time">Part-Time</option>
                                <option value="contract">Contract</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-500 mb-1">Location</label>
                            <input type="text" name="location" value="Bangalore (Hybrid)" class="w-full border p-2 rounded" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-500 mb-1">Stipend / Package</label>
                            <input type="text" name="stipend" placeholder="₹45,000 / month" class="w-full border p-2 rounded" />
                        </div>
                        <div>
                            <label class="block text-slate-500 mb-1">Deadline Date</label>
                            <input type="date" name="deadline" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" class="w-full border p-2 rounded font-mono" required />
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-500 mb-1">Eligibility Criteria</label>
                        <input type="text" name="eligibility" value="CGPA > 8.00, CSE/ECE only." class="w-full border p-2.5 rounded focus:ring-1" required />
                    </div>
                    <div>
                        <label class="block text-slate-500 mb-1">Job Description</label>
                        <textarea name="description" rows="4" class="w-full border p-2.5 rounded font-mono" placeholder="Help platform engineers..." required></textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-[#0F172A] hover:bg-[#2563EB] text-white rounded font-bold transition-colors">
                        Broadcast Job Opening
                    </button>
                </form>
            </div>
        </div>
    </div>

<?php elseif ($currentTab === 'announcements'): ?>
    <!-- ADMIN BROADCASTER -->
    <div class="bg-white border border-[#E2E8F0] p-8 max-w-xl mx-auto rounded-xl shadow-sm space-y-6 animate-fadeIn text-left">
        <div>
            <h2 class="text-xl font-extrabold text-[#0F172A] tracking-tight">Push updates broadcaster</h2>
            <p class="text-xs text-[#64748B] mt-1">Publish warnings, eletives notifications, or server patching schedules warnings.</p>
        </div>

        <form action="actions.php?action=send_announcement" method="POST" class="space-y-4 text-xs font-bold">
            <div>
                <label class="block text-slate-500 mb-1">Announcement Title</label>
                <input
                    type="text"
                    name="title"
                    placeholder="e.g. Server Software hardening security patches schedules"
                    class="w-full border border-slate-200 p-2.5 rounded text-xs focus:ring-1 focus:ring-blue-500 outline-none"
                    required
                />
            </div>

            <div>
                <label class="block text-slate-500 mb-1">Broadcaster Body Content</label>
                <textarea
                    name="body"
                    placeholder="Write detailed announcements messages here..."
                    rows="6"
                    class="w-full border border-slate-200 p-2.5 rounded text-xs focus:ring-1 focus:ring-blue-500 outline-none font-mono"
                    required
                ></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-500 mb-1">Target Audience</label>
                    <select name="audience" id="ann_aud" class="w-full border p-2.5 rounded bg-white text-slate-700 text-xs" onchange="toggleAnnDept(this.value);">
                        <option value="all">ALL</option>
                        <option value="students">STUDENTS ONLY</option>
                        <option value="teachers">TEACHERS ONLY</option>
                        <option value="department">DEPARTMENT SPECIFIC</option>
                    </select>
                </div>
                <div id="ann_dept_block" class="hidden">
                    <label class="block text-slate-500 mb-1">Target Department</label>
                    <select name="department_id" class="w-full border p-2.5 rounded bg-white text-slate-700 text-xs">
                        <?php foreach ($departmentsList as $d): ?>
                            <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end gap-2 text-xs font-bold">
                <a href="?tab=dashboard" class="px-4 py-2 border rounded text-slate-600 hover:bg-slate-50 text-center">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-[#0F172A] hover:bg-[#2563EB] text-white rounded">
                    Publish Broadcaster
                </button>
            </div>
        </form>
        <script>
            function toggleAnnDept(val) {
                const block = document.getElementById('ann_dept_block');
                if (val === 'department') {
                    block.classList.remove('hidden');
                } else {
                    block.classList.add('hidden');
                }
            }
        </script>
    </div>

<?php elseif ($currentTab === 'analytics'): ?>
    <!-- ADMIN CAMPUS ANALYTICS -->
    <div class="space-y-6 animate-fadeIn text-left">
        <div class="border-b border-[#E2E8F0] pb-4">
            <h1 class="text-2xl font-extrabold text-[#0F172A]">Campus Analytics Dashboard</h1>
            <p class="text-[#64748B] text-xs">Acknowledge average grade rankings, placement percentages, and enrollment turnouts.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white border border-[#E2E8F0] p-6 rounded-xl shadow-sm space-y-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-[#2563EB]">Scholars enrollment by sectors</h3>
                <div class="space-y-2 text-xs">
                    <div>
                        <div class="flex justify-between font-bold mb-1">
                            <span>Computer Science (CSE)</span>
                            <span>45%</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: 45%;"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between font-bold mb-1">
                            <span>Electronics (ECE)</span>
                            <span>35%</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-emerald-500 h-2 rounded-full" style="width: 35%;"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between font-bold mb-1">
                            <span>Business (BBA)</span>
                            <span>20%</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-amber-500 h-2 rounded-full" style="style: 20%; width: 20%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-[#E2E8F0] p-6 rounded-xl shadow-sm space-y-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-[#2563EB]">Placement recruitment success ratios</h3>
                <div class="flex items-end justify-around h-32 pt-4 px-2 font-mono font-bold text-[10px] text-slate-400 text-center">
                    <div class="w-8">
                        <div class="bg-[#2563EB] h-24 rounded-t"></div>
                        <span class="block mt-1">Applied</span>
                    </div>
                    <div class="w-8">
                        <div class="bg-indigo-500 h-16 rounded-t"></div>
                        <span class="block mt-1">Shortlist</span>
                    </div>
                    <div class="w-8">
                        <div class="bg-amber-500 h-10 rounded-t"></div>
                        <span class="block mt-1">Intervw</span>
                    </div>
                    <div class="w-8">
                        <div class="bg-emerald-500 h-6 rounded-t"></div>
                        <span class="block mt-1">Offered</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($currentTab === 'settings'): ?>
    <!-- ADMIN SETTINGS TAB -->
    <div class="bg-white border border-[#E2E8F0] p-8 max-w-xl mx-auto rounded-xl shadow-sm space-y-6 animate-fadeIn text-left">
        <div>
            <h2 class="text-xl font-extrabold text-[#0F172A] tracking-tight">LMS Institutional Settings</h2>
            <p class="text-xs text-[#64748B] mt-1">Configure global variables settings defaults or download full system backups.</p>
        </div>

        <form action="#" method="POST" class="space-y-4 text-xs font-bold" onsubmit="alert('Backup settings saved successfully (Mock)!'); return false;">
            <div>
                <label class="block text-slate-500 mb-1">Institutional Brand Name</label>
                <input type="text" value="Rajagiri School of Engineering & Technology" class="w-full border p-2.5 rounded focus:ring-1" required />
            </div>

            <div>
                <label class="block text-slate-500 mb-1">Global System Admin Email</label>
                <input type="email" value="admin@edutrack.com" class="w-full border p-2.5 rounded focus:ring-1" required />
            </div>

            <div class="pt-4 border-t border-slate-100 flex flex-col gap-3">
                <a href="db_seed.php" class="w-full text-center py-2.5 border border-amber-500 hover:bg-amber-50 text-amber-700 rounded font-bold">
                    ⚠️ Restore Database Seeds Default Reset
                </a>
                <button type="submit" class="py-2.5 bg-[#2563EB] hover:bg-[#1D4ED8] text-white rounded font-bold">
                    Save Institutional settings
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>
