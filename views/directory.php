<?php
// views/directory.php
// Shared Campus Profiles Directory view with Role-Based Access Controls

if (!isset($currentUser)) {
    return;
}

$db = Database::connect();

$role = $currentUser['role'];

// 1. Role-based restrictions block
if ($role === 'student') {
    echo '
    <div class="bg-white border border-[#E2E8F0] p-8 max-w-xl mx-auto rounded-xl shadow-sm text-center space-y-4 animate-fadeIn">
        <div class="w-12 h-12 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto">
            <i data-lucide="shield-x" class="w-6 h-6"></i>
        </div>
        <h2 class="text-lg font-extrabold text-[#0F172A]">Directory Access Blocked</h2>
        <p class="text-xs text-slate-500 leading-relaxed">
            The global Campus Profiles Directory is restricted to Admin, HOD, and Faculty personnel only.
        </p>
    </div>';
    return;
}

// 2. Determine HOD status
$hodDept = null;
if ($role === 'teacher') {
    $hodStmt = $db->prepare("SELECT * FROM departments WHERE head_id = :uid LIMIT 1");
    $hodStmt->execute([':uid' => $currentUser['id']]);
    $hodDept = $hodStmt->fetch();
}

$isHod = ($hodDept !== false && $hodDept !== null);

// 3. Fetch departments based on role
if ($role === 'admin') {
    $deptsQuery = $db->query("SELECT * FROM departments ORDER BY name ASC");
    $depts = $deptsQuery->fetchAll();
} else {
    // HOD is locked to their department. Regular teachers are also locked to their own department.
    if ($isHod) {
        $depts = [$hodDept];
    } else {
        $tDeptStmt = $db->prepare("
            SELECT d.* 
            FROM departments d
            JOIN teacher_profiles tp ON d.id = tp.department_id
            WHERE tp.user_id = :uid
            LIMIT 1
        ");
        $tDeptStmt->execute([':uid' => $currentUser['id']]);
        $teacherDept = $tDeptStmt->fetch();
        $depts = $teacherDept ? [$teacherDept] : [];
    }
}

// Active department selection
$activeDeptId = 0;
if (!empty($depts)) {
    $activeDeptId = (int)($_GET['dept_id'] ?? $depts[0]['id']);
    // Verify selection is allowed
    $allowedDeptIds = array_map(function($d) { return (int)$d['id']; }, $depts);
    if (!in_array($activeDeptId, $allowedDeptIds)) {
        $activeDeptId = $allowedDeptIds[0];
    }
}

// Filter selections
$selectedYear = isset($_GET['year']) ? ($_GET['year'] === 'all' ? 'all' : (int)$_GET['year']) : 'all';
$selectedSem = isset($_GET['sem']) ? ($_GET['sem'] === 'all' ? 'all' : (int)$_GET['sem']) : 'all';
$selectedSection = trim($_GET['section'] ?? 'all');
$searchQuery = trim($_GET['search'] ?? '');

// 4. Fetch teachers for the department
$teachers = [];
$showTeachersSection = ($role === 'admin' || $isHod);

if ($showTeachersSection && $activeDeptId > 0) {
    $teacherStmt = $db->prepare("
        SELECT u.*, tp.employee_id, tp.designation, tp.qualification, tp.dob, tp.gender, tp.religion, tp.caste, tp.nationality, tp.blood_group, tp.aadhaar_number, tp.address, tp.experience, tp.personal_email
        FROM users u
        JOIN teacher_profiles tp ON u.id = tp.user_id
        WHERE u.status = 'active' AND tp.department_id = :dept_id
        ORDER BY u.name ASC
    ");
    $teacherStmt->execute([':dept_id' => $activeDeptId]);
    $teachers = $teacherStmt->fetchAll();

    // Group teachers by Designation hierarchy
    if (!empty($searchQuery)) {
        $teachers = array_filter($teachers, function($t) use ($searchQuery) {
            return str_contains(strtolower($t['name']), strtolower($searchQuery))
                || str_contains(strtolower($t['email']), strtolower($searchQuery))
                || str_contains(strtolower($t['employee_id']), strtolower($searchQuery))
                || str_contains(strtolower($t['designation']), strtolower($searchQuery));
        });
    }
}

// 5. Fetch students
$students = [];
if ($activeDeptId > 0) {
    $studentQueryStr = "
        SELECT u.*, sp.roll_no, sp.cgpa, sp.year, sp.semester, sp.dob, sp.gender, sp.religion, sp.caste, sp.nationality, sp.blood_group, sp.aadhaar_number, sp.address, sp.admission_no, sp.programme, sp.section, sp.personal_email
        FROM users u
        JOIN student_profiles sp ON u.id = sp.user_id
        WHERE u.status = 'active' AND sp.is_archived = 0 AND sp.department_id = :dept_id
    ";

    $params = [':dept_id' => $activeDeptId];

    // If teacher (not HOD), restrict to assigned classes
    if ($role === 'teacher' && !$isHod) {
        $studentQueryStr .= " AND u.id IN (
            SELECT DISTINCT student_id FROM enrollments e 
            JOIN courses c ON e.course_id = c.id 
            WHERE c.teacher_id = :teacher_id
        )";
        $params[':teacher_id'] = $currentUser['id'];
    }

    // Apply Year Filter
    if ($selectedYear !== 'all') {
        $studentQueryStr .= " AND sp.year = :year";
        $params[':year'] = $selectedYear;
    }

    // Apply Semester Filter
    if ($selectedSem !== 'all') {
        $studentQueryStr .= " AND sp.semester = :sem";
        $params[':sem'] = $selectedSem;
    }

    // Apply Section Filter
    if ($selectedSection !== 'all') {
        $studentQueryStr .= " AND sp.section = :sec";
        $params[':sec'] = $selectedSection;
    }

    $studentQueryStr .= " ORDER BY u.name ASC";
    $studentStmt = $db->prepare($studentQueryStr);
    $studentStmt->execute($params);
    $students = $studentStmt->fetchAll();

    // Apply Search
    if (!empty($searchQuery)) {
        $students = array_filter($students, function($s) use ($searchQuery) {
            return str_contains(strtolower($s['name']), strtolower($searchQuery))
                || str_contains(strtolower($s['email']), strtolower($searchQuery))
                || str_contains(strtolower($s['admission_no']), strtolower($searchQuery))
                || str_contains(strtolower($s['roll_no']), strtolower($searchQuery));
        });
    }
}
?>

<div class="space-y-6 animate-fadeIn text-left">
    <!-- Header -->
    <div class="border-b border-[#E2E8F0] pb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[#0F172A] tracking-tight">Campus Directory</h1>
            <p class="text-[#64748B] text-xs mt-1">
                <?php if ($isHod): ?>
                    HOD View: Browsing department <strong><?php echo htmlspecialchars($hodDept['name']); ?></strong>.
                <?php elseif ($role === 'teacher'): ?>
                    Faculty View: Showing assigned courses students.
                <?php else: ?>
                    Administrator View: Complete system database access.
                <?php endif; ?>
            </p>
        </div>
        
        <!-- Search bar -->
        <form method="GET" class="relative max-w-sm w-full">
            <input type="hidden" name="tab" value="directory" />
            <input type="hidden" name="dept_id" value="<?php echo $activeDeptId; ?>" />
            <input type="hidden" name="year" value="<?php echo $selectedYear; ?>" />
            <input type="hidden" name="sem" value="<?php echo $selectedSem; ?>" />
            <input type="hidden" name="section" value="<?php echo $selectedSection; ?>" />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
            </div>
            <input
                type="text"
                name="search"
                value="<?php echo htmlspecialchars($searchQuery); ?>"
                placeholder="Search name, code, roll..."
                class="w-full pl-9 pr-4 py-2.5 bg-white border border-[#E2E8F0] focus:ring-1 focus:ring-[#2563EB] focus:border-transparent rounded-xl text-xs text-[#0F172A]"
            />
        </form>
    </div>

    <!-- Department Selector Tabs -->
    <?php if ($role === 'admin'): ?>
        <div class="flex border-b border-[#E2E8F0] overflow-x-auto pb-px gap-1">
            <?php foreach ($depts as $d): 
                $isActive = ($activeDeptId === (int)$d['id']);
                $tabClass = $isActive 
                    ? 'border-[#2563EB] text-[#2563EB] font-bold' 
                    : 'border-transparent text-[#64748B] hover:text-[#0F172A] hover:border-slate-300';
            ?>
                <a href="?tab=directory&dept_id=<?php echo $d['id']; ?>&year=<?php echo $selectedYear; ?>&sem=<?php echo $selectedSem; ?>&section=<?php echo $selectedSection; ?>&search=<?php echo urlencode($searchQuery); ?>" class="py-2.5 px-4 text-xs border-b-2 font-semibold whitespace-nowrap transition-colors <?php echo $tabClass; ?>">
                    <?php echo htmlspecialchars($d['name']); ?> (<?php echo htmlspecialchars($d['code']); ?>)
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Hierarchy Filters Card -->
    <div class="bg-white border border-[#E2E8F0] rounded-xl p-5 shadow-sm space-y-4">
        <div class="flex items-center gap-2 pb-1 border-b border-slate-100">
            <i data-lucide="filter" class="w-4 h-4 text-[#2563EB]"></i>
            <h4 class="text-xs uppercase tracking-wider font-extrabold text-[#0F172A]">Hierarchy Filters</h4>
        </div>
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end text-xs">
            <input type="hidden" name="tab" value="directory" />
            <input type="hidden" name="dept_id" value="<?php echo $activeDeptId; ?>" />
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" />
            
            <div>
                <label class="block text-slate-500 font-semibold mb-1">Academic Year</label>
                <select name="year" class="w-full bg-white border p-2 rounded focus:ring-1 focus:ring-blue-500 font-semibold text-slate-700">
                    <option value="all" <?php echo $selectedYear === 'all' ? 'selected' : ''; ?>>All Years</option>
                    <option value="1" <?php echo $selectedYear === 1 ? 'selected' : ''; ?>>First Year</option>
                    <option value="2" <?php echo $selectedYear === 2 ? 'selected' : ''; ?>>Second Year</option>
                    <option value="3" <?php echo $selectedYear === 3 ? 'selected' : ''; ?>>Third Year</option>
                    <option value="4" <?php echo $selectedYear === 4 ? 'selected' : ''; ?>>Fourth Year</option>
                </select>
            </div>

            <div>
                <label class="block text-slate-500 font-semibold mb-1">Semester</label>
                <select name="sem" class="w-full bg-white border p-2 rounded focus:ring-1 focus:ring-blue-500 font-semibold text-slate-700">
                    <option value="all" <?php echo $selectedSem === 'all' ? 'selected' : ''; ?>>All Semesters</option>
                    <?php for ($s = 1; $s <= 8; $s++): ?>
                        <option value="<?php echo $s; ?>" <?php echo $selectedSem === $s ? 'selected' : ''; ?>>Semester <?php echo $s; ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div>
                <label class="block text-slate-500 font-semibold mb-1">Section/Batch</label>
                <select name="section" class="w-full bg-white border p-2 rounded focus:ring-1 focus:ring-blue-500 font-semibold text-slate-700">
                    <option value="all" <?php echo $selectedSection === 'all' ? 'selected' : ''; ?>>All Sections</option>
                    <option value="A" <?php echo $selectedSection === 'A' ? 'selected' : ''; ?>>Section A</option>
                    <option value="B" <?php echo $selectedSection === 'B' ? 'selected' : ''; ?>>Section B</option>
                    <option value="C" <?php echo $selectedSection === 'C' ? 'selected' : ''; ?>>Section C</option>
                </select>
            </div>

            <div>
                <button type="submit" class="w-full py-2.5 px-4 bg-slate-800 hover:bg-slate-900 text-white rounded font-bold transition-colors cursor-pointer">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- 1. Faculty Section -->
    <?php if ($showTeachersSection): ?>
        <div class="space-y-4 pt-2">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-1.5">
                <i data-lucide="graduation-cap" class="w-5 h-5 text-[#2563EB]"></i>
                <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider">Faculty & Instructors</h3>
                <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full font-mono"><?php echo count($teachers); ?></span>
            </div>

            <?php if (empty($teachers)): ?>
                <p class="text-xs text-slate-400 py-2 font-medium italic">No faculty profiles found matching filters.</p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($teachers as $t): ?>
                        <div class="bg-white border border-[#E2E8F0] rounded-xl p-5 shadow-sm space-y-4 hover:shadow-md transition-shadow relative">
                            <div class="flex items-center gap-3">
                                <img src="<?php echo htmlspecialchars($t['avatar']); ?>" alt="Avatar" class="w-12 h-12 rounded-full border object-cover bg-slate-50" />
                                <div>
                                    <span class="font-extrabold text-sm text-[#0F172A] block"><?php echo htmlspecialchars($t['name']); ?></span>
                                    <span class="text-xs text-slate-500 font-medium font-mono"><?php echo htmlspecialchars($t['designation']); ?></span>
                                </div>
                            </div>

                            <div class="text-xs space-y-2 border-t pt-3">
                                <div class="grid grid-cols-2 gap-2 font-mono">
                                    <div>
                                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Employee ID</span>
                                        <span class="text-[#0F172A] font-bold"><?php echo htmlspecialchars($t['employee_id']); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Qualifications</span>
                                        <span class="text-[#0F172A] font-bold truncate block" title="<?php echo htmlspecialchars($t['qualification']); ?>"><?php echo htmlspecialchars($t['qualification']); ?></span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Official Email</span>
                                        <a href="mailto:<?php echo htmlspecialchars($t['email']); ?>" class="text-[#2563EB] hover:underline text-[10px] font-mono break-all"><?php echo htmlspecialchars($t['email']); ?></a>
                                    </div>
                                    <div>
                                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Personal Email</span>
                                        <span class="text-slate-600 text-[10px] font-mono break-all"><?php echo htmlspecialchars($t['personal_email'] ?: 'Not declared'); ?></span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2 font-mono">
                                    <div>
                                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Contact Mobile</span>
                                        <span class="text-[#0F172A]"><?php echo htmlspecialchars($t['phone'] ?: 'Unspecified'); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Blood Group</span>
                                        <span class="text-[#0F172A]"><?php echo htmlspecialchars($t['blood_group'] ?: '-'); ?></span>
                                    </div>
                                </div>

                                <?php if (!empty($t['experience'])): ?>
                                    <div>
                                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Experience Details</span>
                                        <span class="text-slate-600 block mt-0.5 leading-relaxed"><?php echo htmlspecialchars($t['experience']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($t['address'])): ?>
                                    <div>
                                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Address</span>
                                        <span class="text-slate-600 block mt-0.5 text-[11px] leading-relaxed line-clamp-2"><?php echo htmlspecialchars($t['address']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- 2. Students Section -->
    <div class="space-y-4 pt-2">
        <div class="flex items-center gap-2 border-b border-slate-100 pb-1.5">
            <i data-lucide="users" class="w-5 h-5 text-[#2563EB]"></i>
            <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider">Students Registry</h3>
            <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full font-mono"><?php echo count($students); ?></span>
        </div>

        <?php if (empty($students)): ?>
            <p class="text-xs text-slate-400 py-2 font-medium italic">No student profiles found matching filters.</p>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($students as $s): ?>
                    <div class="bg-white border border-[#E2E8F0] rounded-xl p-5 shadow-sm space-y-4 hover:shadow-md transition-shadow relative">
                        <div class="flex items-center gap-3">
                            <img src="<?php echo htmlspecialchars($s['avatar']); ?>" alt="Avatar" class="w-12 h-12 rounded-full border object-cover bg-slate-50" />
                            <div>
                                <span class="font-extrabold text-sm text-[#0F172A] block"><?php echo htmlspecialchars($s['name']); ?></span>
                                <span class="text-[10px] text-slate-400 font-mono">Admission No: <?php echo htmlspecialchars($s['admission_no'] ?: $s['roll_no']); ?></span>
                            </div>
                        </div>

                        <div class="text-xs space-y-2 border-t pt-3">
                            <div class="grid grid-cols-3 gap-1.5 font-mono">
                                <div>
                                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Roll Key</span>
                                    <span class="text-[#0F172A] font-bold"><?php echo htmlspecialchars($s['roll_no']); ?></span>
                                </div>
                                <div>
                                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Class Term</span>
                                    <span class="text-[#0F172A] font-bold">Sem <?php echo htmlspecialchars($s['semester']); ?> (Sec <?php echo htmlspecialchars($s['section'] ?: 'A'); ?>)</span>
                                </div>
                                <div>
                                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">CGPA</span>
                                    <span class="text-[#2563EB] font-black"><?php echo number_format($s['cgpa'], 2); ?></span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Official Email</span>
                                    <a href="mailto:<?php echo htmlspecialchars($s['email']); ?>" class="text-[#2563EB] hover:underline text-[10px] font-mono break-all"><?php echo htmlspecialchars($s['email']); ?></a>
                                </div>
                                <div>
                                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Personal Email</span>
                                    <span class="text-slate-600 text-[10px] font-mono break-all"><?php echo htmlspecialchars($s['personal_email'] ?: 'Not declared'); ?></span>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-1.5 font-mono">
                                <div>
                                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Gender</span>
                                    <span class="text-[#0F172A] font-semibold truncate block"><?php echo htmlspecialchars($s['gender'] ?: 'Unspecified'); ?></span>
                                </div>
                                <div>
                                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Blood Group</span>
                                    <span class="text-[#0F172A] font-bold"><?php echo htmlspecialchars($s['blood_group'] ?: '-'); ?></span>
                                </div>
                                <div>
                                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Aadhaar</span>
                                    <span class="text-[#0F172A] font-bold"><?php echo $s['aadhaar_number'] ? substr($s['aadhaar_number'], -4) : '-'; ?></span>
                                </div>
                            </div>

                            <?php if (!empty($s['address'])): ?>
                                <div>
                                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Address</span>
                                    <span class="text-slate-600 block mt-0.5 text-[11px] leading-relaxed line-clamp-2"><?php echo htmlspecialchars($s['address']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
