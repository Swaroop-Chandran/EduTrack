<?php
// views/student_dashboard.php
// Student Academic Terminal Dashboard matching StudentDashboard.tsx exactly

if (!isset($currentUser) || $currentUser['role'] !== 'student') {
    return;
}

$db = Database::connect();
$studentId = $currentUser['id'];

// 1. Query general collections
// Enrolled Courses
$coursesStmt = $db->prepare("
    SELECT c.*, e.status as enrollment_status 
    FROM courses c 
    JOIN enrollments e ON c.id = e.course_id 
    WHERE e.student_id = :student_id
");
$coursesStmt->execute([':student_id' => $studentId]);
$studentCourses = $coursesStmt->fetchAll();
$studentCourseIds = array_column($studentCourses, 'id');
$enrolledCount = count(array_filter($studentCourses, fn($c) => $c['enrollment_status'] === 'active'));

// Assignments in enrolled courses
$assignmentsList = [];
if (!empty($studentCourseIds)) {
    $inClause = implode(',', array_fill(0, count($studentCourseIds), '?'));
    $asgStmt = $db->prepare("
        SELECT a.*, c.code as course_code, c.title as course_title 
        FROM assignments a 
        JOIN courses c ON a.course_id = c.id 
        WHERE a.course_id IN ($inClause)
    ");
    $asgStmt->execute($studentCourseIds);
    $assignmentsList = $asgStmt->fetchAll();
}

// Submissions by current student
$subStmt = $db->prepare("SELECT * FROM submissions WHERE student_id = :student_id");
$subStmt->execute([':student_id' => $studentId]);
$mySubmissions = $subStmt->fetchAll();
$submittedAsgIds = array_column($mySubmissions, 'assignment_id');

// Pending Assignments (active and not submitted)
$pendingAssignments = array_filter($assignmentsList, function($a) use ($submittedAsgIds) {
    return $a['status'] === 'active' && !in_array($a['id'], $submittedAsgIds);
});

// Attendance records
$attStmt = $db->prepare("SELECT * FROM attendance WHERE student_id = :student_id");
$attStmt->execute([':student_id' => $studentId]);
$myAttendance = $attStmt->fetchAll();
$totalClasses = count($myAttendance);
$presentClasses = count(array_filter($myAttendance, fn($a) => in_array($a['status'], ['present', 'late'])));
$attendancePercentage = $totalClasses > 0 ? (int)round(($presentClasses / $totalClasses) * 100) : 100;

// Exams in enrolled courses
$examsList = [];
if (!empty($studentCourseIds)) {
    $inClause = implode(',', array_fill(0, count($studentCourseIds), '?'));
    $exStmt = $db->prepare("
        SELECT ex.*, c.code as course_code, c.title as course_title 
        FROM exams ex 
        JOIN courses c ON ex.course_id = c.id 
        WHERE ex.course_id IN ($inClause)
    ");
    $exStmt->execute($studentCourseIds);
    $examsList = $exStmt->fetchAll();
}
$upcomingExams = array_filter($examsList, fn($ex) => strtotime($ex['exam_date']) >= strtotime('2026-06-05'));
usort($upcomingExams, fn($a, $b) => strtotime($a['exam_date']) <=> strtotime($b['exam_date']));
$nextExam = $upcomingExams[0] ?? null;

// Results
$resStmt = $db->prepare("
    SELECT r.*, c.code as course_code, c.title as course_title 
    FROM results r 
    JOIN courses c ON r.course_id = c.id 
    WHERE r.student_id = :student_id
");
$resStmt->execute([':student_id' => $studentId]);
$myResults = $resStmt->fetchAll();

// Placements joined with applications
$placeStmt = $db->prepare("
    SELECT p.*, pa.status as app_status, pa.applied_at 
    FROM placements p 
    LEFT JOIN placement_applications pa ON p.id = pa.placement_id AND pa.student_id = :student_id
    ORDER BY p.deadline ASC
");
$placeStmt->execute([':student_id' => $studentId]);
$allPlacements = $placeStmt->fetchAll();

// Filtered placement opportunities (CGPA eligibility checks, etc.)
$cgpaValue = (float)($studentProfile['cgpa'] ?? 8.4);
?>

<?php if ($currentTab === 'dashboard'): ?>
    <div class="space-y-6 animate-fadeIn text-left">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
                <h1 class="text-2xl font-bold text-[#0F172A] tracking-tight">
                    Student Dashboard
                </h1>
                <p class="text-[#64748B] text-xs mt-1">Overview of your academic profile and progress</p>
            </div>
            <a href="?tab=exams" class="px-4 py-2 bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-2 cursor-pointer transition-transform duration-75 active:scale-95">
                <i data-lucide="calendar-days" class="w-4 h-4"></i>
                <span>View Exam Schedules</span>
            </a>
        </div>

        <!-- Statistical layout cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php 
            echo renderStatCard("Enrolled Courses", $enrolledCount, "book-open", "blue", "Active learning curricula");
            echo renderStatCard("Pending Tasks", count($pendingAssignments), "clipboard-list", "amber", count($pendingAssignments) > 0 ? count($pendingAssignments) . " assignments due" : "All tasks cleared");
            
            $attColor = $attendancePercentage >= 85 ? 'green' : ($attendancePercentage >= 75 ? 'amber' : 'red');
            echo renderStatCard("Class Attendance", $attendancePercentage . "%", "check-square", $attColor, "Academic cutoff rate: 85%");
            echo renderStatCard("Academic CGPA", number_format($cgpaValue, 2), "award", "navy", "Batch percentiles rank: Top 15%");
            ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left 8Cols: Continue Learning & Recent Results -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Courses Grid container -->
                <div class="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-[#E2E8F0]">
                        <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider">Continue Learning</h3>
                        <a href="?tab=courses" class="text-xs font-bold text-[#2563EB] hover:underline">View All Active</a>
                    </div>
                    <div class="space-y-4">
                        <?php 
                        $sliceCourses = array_slice($studentCourses, 0, 3);
                        foreach ($sliceCourses as $course): 
                            $progressSeed = $course['id'] === 1 ? 65 : ($course['id'] === 2 ? 85 : ($course['id'] === 3 ? 12 : 30));
                        ?>
                            <a href="?tab=courses" class="bg-[#F8FAFC] border border-[#E2E8F0] hover:border-[#2563EB]/40 p-4 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 cursor-pointer transition-colors group block">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-[#2563EB]/10 text-[#2563EB] rounded-lg flex items-center justify-center shrink-0">
                                        <i data-lucide="book-marked" class="w-6 h-6"></i>
                                    </div>
                                    <div>
                                        <span class="text-[10px] uppercase font-black text-slate-400 tracking-wider font-mono"><?php echo htmlspecialchars($course['code']); ?></span>
                                        <h4 class="text-sm font-bold text-[#0F172A] group-hover:text-[#2563EB] transition-colors mt-0.5"><?php echo htmlspecialchars($course['title']); ?></h4>
                                        <p class="text-xs text-[#64748B] mt-1 italic">Credits: <?php echo $course['credits']; ?> • Sem: <?php echo $course['semester']; ?></p>
                                    </div>
                                </div>
                                <!-- Progress Bar -->
                                <div class="w-full sm:w-48 space-y-1.5 shrink-0">
                                    <div class="flex justify-between items-center text-[10px] font-bold text-[#64748B]">
                                        <span>Course Progress</span>
                                        <span class="text-[#2563EB]"><?php echo $progressSeed; ?>%</span>
                                    </div>
                                    <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                                        <div class="bg-[#2563EB] h-1.5 rounded-full" style="width: <?php echo $progressSeed; ?>%;"></div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Recent Results -->
                <div class="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-[#E2E8F0]">
                        <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider">Recent Semester Results</h3>
                        <a href="?tab=results" class="text-xs font-bold text-[#2563EB] hover:underline">Detailed Transcripts</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-[#F8FAFC] border-b border-[#E2E8F0] text-[#64748B]">
                                    <th class="p-3 font-semibold uppercase tracking-wider">Course Code</th>
                                    <th class="p-3 font-semibold uppercase tracking-wider">Course Name</th>
                                    <th class="p-3 font-semibold uppercase tracking-wider">Total Marks</th>
                                    <th class="p-3 font-semibold uppercase tracking-wider text-right">Grade Scale</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                <?php 
                                $sliceResults = array_slice($myResults, 0, 3);
                                foreach ($sliceResults as $res): 
                                ?>
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-3 font-mono text-[#0F172A]"><?php echo htmlspecialchars($res['course_code']); ?></td>
                                        <td class="p-3 text-slate-700"><?php echo htmlspecialchars($res['course_title']); ?></td>
                                        <td class="p-3 text-slate-700"><?php echo $res['total_marks']; ?> / 100</td>
                                        <td class="p-3 text-right">
                                            <?php echo renderBadge($res['grade'], $res['status'] === 'pass' ? 'success' : 'danger'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($myResults)): ?>
                                    <tr>
                                        <td colSpan="4" class="p-6 text-center text-slate-400">No grade result records disclosed for current semester.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right 4Cols: Deadlines widget & Exam countdown -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Upcoming Deadlines Widget -->
                <div class="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-[#E2E8F0]">
                        <i data-lucide="clock" class="w-4 h-4 text-[#2563EB]"></i>
                        <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider">Upcoming Tasks</h3>
                    </div>
                    <div class="space-y-4">
                        <?php 
                        $slicePending = array_slice($pendingAssignments, 0, 3);
                        foreach ($slicePending as $asg): 
                            $isDueToday = ($asg['id'] === 1); // Mock today check
                        ?>
                            <div class="p-3 border border-[#E2E8F0] rounded-xl hover:border-[#2563EB]/30 transition-colors bg-white">
                                <div class="flex justify-between items-start gap-2 mb-1.5">
                                    <h4 class="text-xs font-bold text-[#0F172A] line-clamp-1"><?php echo htmlspecialchars($asg['title']); ?></h4>
                                    <?php if ($isDueToday): ?>
                                        <span class="text-[9px] font-black uppercase tracking-wider bg-red-100 text-red-700 px-1.5 py-0.5 rounded shrink-0">Due Today</span>
                                    <?php else: ?>
                                        <span class="text-[9.5px] font-semibold text-slate-400 shrink-0 font-mono">Active</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-[11px] text-[#64748B] line-clamp-2 leading-relaxed mb-3"><?php echo htmlspecialchars($asg['description']); ?></p>
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-mono bg-[#F1F5F9] text-[#64748B] rounded px-1.5 py-0.5 border border-[#E2E8F0]">
                                        <?php echo htmlspecialchars($asg['course_code']); ?>
                                    </span>
                                    <a href="?tab=submit_assignment&assignment_id=<?php echo $asg['id']; ?>" class="text-[10px] font-extrabold text-[#2563EB] hover:underline">Submit Deliverable →</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($pendingAssignments)): ?>
                            <div class="p-4 text-center text-xs text-slate-400 space-y-2">
                                <i data-lucide="smile" class="w-6 h-6 text-emerald-500 mx-auto animate-bounce"></i>
                                <p class="font-semibold text-slate-700">All Assignments Cleared!</p>
                                <p class="text-[10px]">No pending submissions in database queue.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Exam countdown widget -->
                <?php if ($nextExam): ?>
                    <div class="bg-[#0F172A] text-white rounded-xl p-6 shadow-md border border-white/10 space-y-4 relative overflow-hidden">
                        <div class="absolute right-0 bottom-0 translate-x-1/4 translate-y-1/4 text-white/5 pointer-events-none scale-150">
                            <i data-lucide="alert-circle" class="w-32 h-32"></i>
                        </div>
                        <div class="flex items-center gap-2 text-white/80 uppercase font-black tracking-widest text-[10px]">
                            <div class="w-1.5 h-1.5 rounded-full bg-red-500 animate-ping"></div>
                            <span>Next Exam Countdown</span>
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-sm font-bold text-white leading-snug"><?php echo htmlspecialchars($nextExam['title']); ?></h4>
                            <p class="text-xs text-slate-400"><?php echo htmlspecialchars($nextExam['venue']); ?></p>
                        </div>
                        <div class="p-2.5 bg-white/5 border border-white/10 rounded-lg text-center text-xs font-mono font-bold tracking-widest text-blue-400">
                            Date: <?php echo $nextExam['exam_date']; ?> at <?php echo date('h:i A', strtotime($nextExam['start_time'])); ?>
                        </div>
                        <a href="?tab=exams" class="w-full py-2 bg-[#0F172A] hover:bg-[#2563EB] text-white text-xs font-bold rounded-lg transition-colors border border-white/10 block text-center">
                            View Directions & Rules
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php elseif ($currentTab === 'courses'): ?>
    <!-- STUDENT MY COURSES TAB -->
    <div class="space-y-6 animate-fadeIn text-left">
        <?php 
        $activeCourseFilter = $_GET['filter'] ?? 'all';
        $courseSearch = $_GET['search'] ?? '';

        // Filter arrays in PHP
        $filteredCourses = array_filter($studentCourses, function($course) use ($activeCourseFilter, $courseSearch) {
            $matchesFilter = ($activeCourseFilter === 'all') 
                || ($activeCourseFilter === 'active' && $course['enrollment_status'] === 'active') 
                || ($activeCourseFilter === 'completed' && $course['enrollment_status'] === 'completed');
            
            $matchesSearch = empty($courseSearch) 
                || (str_contains(strtolower($course['title']), strtolower($courseSearch)))
                || (str_contains(strtolower($course['code']), strtolower($courseSearch)));

            return $matchesFilter && $matchesSearch;
        });
        ?>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
                <h1 class="text-2xl font-extrabold text-[#0F172A] tracking-tight">Enrolled Courses Registry</h1>
                <p class="text-[#64748B] text-xs">Search, filter, and track course curricula schedules progress.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="flex items-center border border-[#E2E8F0] rounded-lg p-1 bg-white shadow-sm">
                    <?php foreach (['all', 'active', 'completed'] as $filter): ?>
                        <a href="?tab=courses&filter=<?php echo $filter; ?>&search=<?php echo urlencode($courseSearch); ?>" class="px-3 py-1 text-xs font-bold rounded-md transition-all <?php echo $activeCourseFilter === $filter ? 'bg-[#2563EB] text-white' : 'text-[#64748B] hover:text-[#0F172A]'; ?>">
                            <?php echo strtoupper($filter); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Search bar -->
        <form method="GET" class="relative">
            <input type="hidden" name="tab" value="courses" />
            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($activeCourseFilter); ?>" />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
            </div>
            <input
                type="text"
                name="search"
                value="<?php echo htmlspecialchars($courseSearch); ?>"
                placeholder="Search enrolled course name or department code..."
                class="w-full pl-9 pr-4 py-2.5 bg-white border border-[#E2E8F0] rounded-xl text-xs text-[#0F172A] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent shadow-sm"
                onchange="this.form.submit();"
            />
        </form>

        <!-- Course Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($filteredCourses as $course): 
                $progressSeed = $course['id'] === 1 ? 65 : ($course['id'] === 2 ? 85 : ($course['id'] === 3 ? 12 : 30));
            ?>
                <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden flex flex-col hover:shadow-md transition-shadow relative">
                    <div class="h-2 bg-gradient-to-r from-[#2563EB] to-blue-400 w-full"></div>
                    <div class="p-6 flex flex-col flex-1 space-y-4">
                        <div class="flex justify-between items-start">
                            <div class="w-10 h-10 bg-[#2563EB]/10 text-[#2563EB] rounded-xl flex items-center justify-center">
                                <i data-lucide="book-open" class="w-5 h-5"></i>
                            </div>
                            <?php echo renderBadge($course['enrollment_status'] === 'active' ? 'In Progress' : 'Completed', $course['enrollment_status'] === 'active' ? 'warning' : 'success'); ?>
                        </div>

                        <div class="space-y-1">
                            <span class="text-[10px] font-mono font-bold text-slate-400 tracking-wider"><?php echo htmlspecialchars($course['code']); ?></span>
                            <h3 class="text-sm font-bold text-[#0F172A] leading-snug line-clamp-2"><?php echo htmlspecialchars($course['title']); ?></h3>
                            <p class="text-xs text-[#64748B] line-clamp-3 leading-relaxed mt-1"><?php echo htmlspecialchars($course['description']); ?></p>
                        </div>

                        <!-- Progress slider -->
                        <div class="mt-auto pt-4 border-t border-slate-50 space-y-1.5">
                            <div class="flex justify-between items-center text-[10px] font-bold text-[#64748B]">
                                <span>Curriculum Progress</span>
                                <span class="text-[#2563EB] font-black"><?php echo $progressSeed; ?>%</span>
                            </div>
                            <div class="w-full bg-[#F1F5F9] h-1.5 rounded-full overflow-hidden">
                                <div class="bg-[#2563EB] h-1.5 rounded-full" style="width: <?php echo $progressSeed; ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($filteredCourses)): ?>
                <div class="col-span-full p-12 bg-white rounded-xl text-center border border-[#E2E8F0] text-slate-400 space-y-3">
                    <i data-lucide="inbox" class="w-12 h-12 mx-auto text-slate-300"></i>
                    <p class="font-bold text-slate-700 text-sm">No Enrolled Courses Found</p>
                    <p class="text-xs">Adjust filters or search parameters. Contact admins if courses are missing.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($currentTab === 'assignments'): ?>
    <!-- STUDENT ASSIGNMENTS TAB -->
    <div class="space-y-6 animate-fadeIn text-left">
        <?php 
        $activeAsgTab = $_GET['sub_tab'] ?? 'pending';

        // Filter submissions
        $subSubmitted = array_filter($mySubmissions, fn($s) => $s['status'] === 'submitted');
        $subGraded = array_filter($mySubmissions, fn($s) => $s['status'] === 'evaluated');
        ?>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
                <h1 class="text-2xl font-extrabold text-[#0F172A] tracking-tight">Course Deliverables & Tasks</h1>
                <p class="text-[#64748B] text-xs">Track, submit and review evaluations feedback from tutors.</p>
            </div>
            
            <!-- Filters -->
            <div class="flex border border-[#E2E8F0] bg-white rounded-lg p-1 shadow-sm font-bold text-xs">
                <a href="?tab=assignments&sub_tab=pending" class="px-4 py-1.5 rounded-md transition-all <?php echo $activeAsgTab === 'pending' ? 'bg-[#2563EB] text-white' : 'text-[#64748B] hover:text-[#0F172A]'; ?>">
                    PENDING TASKS (<?php echo count($pendingAssignments); ?>)
                </a>
                <a href="?tab=assignments&sub_tab=submitted" class="px-4 py-1.5 rounded-md transition-all <?php echo $activeAsgTab === 'submitted' ? 'bg-[#2563EB] text-white' : 'text-[#64748B] hover:text-[#0F172A]'; ?>">
                    SUBMITTED (<?php echo count($subSubmitted); ?>)
                </a>
                <a href="?tab=assignments&sub_tab=graded" class="px-4 py-1.5 rounded-md transition-all <?php echo $activeAsgTab === 'graded' ? 'bg-[#2563EB] text-white' : 'text-[#64748B] hover:text-[#0F172A]'; ?>">
                    GRADED FEEDBACK (<?php echo count($subGraded); ?>)
                </a>
            </div>
        </div>

        <div class="space-y-4">
            <?php if ($activeAsgTab === 'pending'): ?>
                <?php foreach ($pendingAssignments as $asg): ?>
                    <div class="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6 hover:border-amber-400/40 transition-colors">
                        <div class="space-y-2 max-w-2xl">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs font-bold bg-[#F1F5F9] text-[#64748B] rounded px-2 py-0.5 border border-[#E2E8F0]">
                                    <?php echo htmlspecialchars($asg['course_code']); ?> - <?php echo htmlspecialchars($asg['course_title']); ?>
                                </span>
                                <span class="text-xs text-[#EF4444] font-semibold bg-red-50 px-2.5 py-0.5 rounded-full border border-red-100 flex items-center gap-1">
                                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                    <span>Due: <?php echo date('Y-m-d H:i', strtotime($asg['due_date'])); ?></span>
                                </span>
                            </div>
                            <h3 class="font-bold text-[#0F172A] text-base leading-tight mt-1"><?php echo htmlspecialchars($asg['title']); ?></h3>
                            <p class="text-xs text-[#64748B] leading-relaxed"><?php echo htmlspecialchars($asg['description']); ?></p>
                        </div>
                        <div class="shrink-0 flex flex-col sm:flex-row md:flex-col items-stretch md:items-end gap-3 w-full md:w-auto">
                            <span class="text-xs font-bold text-slate-500 font-mono bg-slate-50 p-2.5 rounded-lg border border-[#E2E8F0] block text-center md:text-right md:w-32">
                                Max Marks: <?php echo $asg['max_marks']; ?>
                            </span>
                            <a href="?tab=submit_assignment&assignment_id=<?php echo $asg['id']; ?>" class="py-2.5 px-4 bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-xs font-bold rounded-lg shadow-sm text-center cursor-pointer md:w-32 transform active:scale-95 transition-transform block">
                                File Submit
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($pendingAssignments)): ?>
                    <div class="p-12 text-center bg-white rounded-xl border border-[#E2E8F0] text-slate-400 space-y-3">
                        <i data-lucide="check-circle" class="w-12 h-12 text-[#10B981] mx-auto animate-pulse"></i>
                        <p class="font-bold text-slate-700 text-sm">No Pending Assignments</p>
                        <p class="text-xs text-slate-500">You are all caught up! Academic deliverables records are thoroughly updated.</p>
                    </div>
                <?php endif; ?>

            <?php elseif ($activeAsgTab === 'submitted'): ?>
                <?php foreach ($subSubmitted as $sub): 
                    $asg = array_values(array_filter($assignmentsList, fn($a) => $a['id'] === $sub['assignment_id']))[0] ?? null;
                ?>
                    <div class="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs font-bold bg-[#F1F5F9] text-[#64748B] rounded px-2 py-0.5 border border-[#E2E8F0]">
                                    <?php echo htmlspecialchars($asg['course_code'] ?? 'CS'); ?>
                                </span>
                                <span class="text-xs text-blue-700 font-semibold bg-blue-50 px-2.5 py-0.5 rounded-full border border-blue-150">
                                    Status: Received, Pending Evaluation
                                </span>
                            </div>
                            <h3 class="font-bold text-[#0F172A] text-base leading-tight mt-1"><?php echo htmlspecialchars($asg['title'] ?? 'Assignment'); ?></h3>
                            <p class="text-xs text-slate-400 font-mono italic">Submitted file: <?php echo htmlspecialchars($sub['file_path']); ?></p>
                            <?php if (!empty($sub['text_submission'])): ?>
                                <div class="p-3 bg-slate-50 rounded-lg text-xs text-[#64748B] border border-slate-100 max-w-2xl font-mono">
                                    "<?php echo htmlspecialchars($sub['text_submission']); ?>"
                                </div>
                            <?php endif; ?>
                            <span class="text-[10px] block text-slate-400">
                                Uploaded timestamp: <?php echo date('Y-m-d H:i:s', strtotime($sub['submitted_at'])); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($subSubmitted)): ?>
                    <div class="p-12 text-center bg-white rounded-xl border border-[#E2E8F0] text-slate-400">
                        No submitted assignments found.
                    </div>
                <?php endif; ?>

            <?php elseif ($activeAsgTab === 'graded'): ?>
                <?php foreach ($subGraded as $sub): 
                    $asg = array_values(array_filter($assignmentsList, fn($a) => $a['id'] === $sub['assignment_id']))[0] ?? null;
                ?>
                    <div class="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm relative overflow-hidden hover:border-[#10B981]/30 transition-all border-l-4 border-l-[#10B981]">
                        <div class="flex flex-col md:flex-row justify-between gap-4 items-start md:items-center mb-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold bg-[#F1F5F9] text-[#64748B] rounded px-2 py-0.5 border border-[#E2E8F0]">
                                        <?php echo htmlspecialchars($asg['course_code'] ?? 'CS'); ?>
                                    </span>
                                    <span class="text-[10.5px] uppercase font-black text-emerald-700 tracking-wider font-mono">Graded & Evaluated</span>
                                </div>
                                <h3 class="font-bold text-[#0F172A] text-base leading-tight mt-2"><?php echo htmlspecialchars($asg['title'] ?? 'Assignment'); ?></h3>
                            </div>
                            <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-150 text-center shrink-0 min-w-32">
                                <span class="text-2xl font-black text-emerald-800"><?php echo $sub['marks_obtained']; ?></span>
                                <span class="text-xs text-emerald-600 block pt-0.5 font-bold">/ <?php echo $asg['max_marks'] ?? 100; ?> marks</span>
                            </div>
                        </div>
                        <div class="bg-[#F8FAFC] border border-[#E2E8F0] p-4 rounded-xl space-y-1.5 text-xs">
                            <p class="font-black text-[#0F172A] uppercase tracking-wide">Instructor Feedback Comments:</p>
                            <p class="text-[#64748B] leading-relaxed">
                                "<?php echo htmlspecialchars($sub['feedback'] ?? 'Excellent execution, deliverables met coursework criteria requirements.'); ?>"
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($subGraded)): ?>
                    <div class="p-12 text-center bg-white rounded-xl border border-[#E2E8F0] text-slate-400">
                        No graded feedbacks found.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($currentTab === 'submit_assignment'): ?>
    <!-- SUBMIT ASSIGNMENT FORM VIEW -->
    <?php 
    $asgId = (int)($_GET['assignment_id'] ?? 0);
    $asgObj = null;
    foreach ($assignmentsList as $a) {
        if ($a['id'] === $asgId) {
            $asgObj = $a;
            break;
        }
    }
    ?>
    <div class="bg-white border border-[#E2E8F0] rounded-xl p-8 max-w-3xl mx-auto shadow-sm space-y-6 animate-fadeIn text-left">
        <?php if (!$asgObj): ?>
            <div class="text-center p-6 space-y-4">
                <i data-lucide="link-2-off" class="w-12 h-12 text-red-400 mx-auto"></i>
                <p class="font-bold text-slate-700">Invalid Deliverable Reference</p>
                <a href="?tab=assignments" class="py-2 px-4 bg-[#2563EB] text-white rounded-lg text-xs font-bold inline-block">Back to Registry</a>
            </div>
        <?php else: ?>
            <form action="actions.php?action=submit_assignment" method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="assignment_id" value="<?php echo $asgId; ?>" />
                <div>
                    <a href="?tab=assignments" class="flex items-center gap-1 text-xs text-[#2563EB] hover:underline mb-4 font-bold">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        <span>Back to Deliverables</span>
                    </a>
                    <span class="text-[11px] font-mono font-bold uppercase text-[#64748B] tracking-widest block mb-1">
                        <?php echo htmlspecialchars($asgObj['course_code']); ?> • <?php echo htmlspecialchars($asgObj['course_title']); ?>
                    </span>
                    <h2 class="text-2xl font-bold text-[#0F172A] tracking-tight"><?php echo htmlspecialchars($asgObj['title']); ?></h2>
                    <p class="text-xs text-[#64748B] mt-2 leading-relaxed bg-[#F8FAFC] border border-[#E2E8F0] p-3 rounded-lg font-mono">
                        <?php echo htmlspecialchars($asgObj['description']); ?>
                    </p>
                </div>

                <div class="border-t border-[#E2E8F0] pt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-[#64748B] uppercase tracking-wider mb-2">
                            Text Submission Description
                        </label>
                        <textarea
                            name="text_submission"
                            placeholder="Enter details about your homework algorithms, assumptions or design structure writeups here..."
                            rows="5"
                            class="w-full bg-[#fdfdfd] border border-[#E2E8F0] focus:ring-2 focus:ring-[#2563EB] focus:border-transparent rounded-xl p-3 text-xs text-[#0F172A] focus:outline-none transition-all font-mono"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[#64748B] uppercase tracking-wider mb-2">
                            Attach deliverable file (Max 10MB)
                        </label>
                        
                        <div class="border-2 border-dashed border-[#E2E8F0] hover:border-[#2563EB]/60 bg-[#F8FAFC] rounded-xl p-8 text-center cursor-pointer transition-all relative">
                            <input
                                type="file"
                                name="submission_file"
                                id="submission_file_input"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                onchange="handleFileSelected(this);"
                            />
                            <i data-lucide="upload-cloud" class="w-10 h-10 text-[#94A3B8] mx-auto mb-3"></i>
                            <p class="text-xs font-bold text-[#0F172A]">Drag & Drop your Assignment file here</p>
                            <p class="text-[10px] text-[#64748B] mt-1">Allowed formats: PDF, DOC, DOCX, ZIP files (Max 10MB limit)</p>
                            
                            <div id="file_selected_display" class="mt-4 p-2 bg-[#D1FAE5] border border-emerald-200 text-emerald-800 text-xs rounded-lg inline-flex items-center gap-2 font-mono hidden">
                                <i data-lucide="check" class="w-4 h-4 text-emerald-600"></i>
                                <span id="file_selected_name"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-[#E2E8F0] flex justify-end gap-3 font-bold text-xs">
                    <a href="?tab=assignments" class="py-2.5 px-4 rounded-lg border border-[#E2E8F0] text-slate-700 hover:bg-slate-50 text-center">
                        Cancel submission
                    </a>
                    <button
                        type="submit"
                        class="py-2.5 px-5 bg-[#0F172A] hover:bg-[#2563EB] text-white rounded-lg shadow-sm"
                    >
                        Post Submission File
                    </button>
                </div>
            </form>
            <script>
                function handleFileSelected(input) {
                    const display = document.getElementById('file_selected_display');
                    const text = document.getElementById('file_selected_name');
                    if (input.files && input.files[0]) {
                        text.innerText = "Selected File: \"" + input.files[0].name + "\"";
                        display.classList.remove('hidden');
                    } else {
                        display.classList.add('hidden');
                    }
                }
            </script>
        <?php endif; ?>
    </div>

<?php elseif ($currentTab === 'exams'): ?>
    <!-- STUDENT EXAMS TAB -->
    <div class="space-y-6 animate-fadeIn text-left">
        <div class="border-b border-[#E2E8F0] pb-4">
            <h1 class="text-2xl font-extrabold text-[#0F172A] tracking-tight">Examination Schedules</h1>
            <p class="text-[#64748B] text-xs">Exams countdown schedules detailed checklists and venue maps.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            <div class="space-y-4">
                <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider mb-2">Upcoming Exam Sessions</h3>
                <?php foreach ($upcomingExams as $ex): ?>
                    <div class="bg-white border border-[#E2E8F0] p-5 rounded-xl shadow-sm space-y-3">
                        <div class="flex justify-between items-start gap-2">
                            <div>
                                <span class="font-mono text-[10px] font-bold bg-[#F1F5F9] text-[#64748B] rounded px-2 py-0.5 border border-[#E2E8F0]">
                                    <?php echo htmlspecialchars($ex['course_code']); ?>
                                </span>
                                <h4 class="font-bold text-[#0F172A] mt-1.5"><?php echo htmlspecialchars($ex['title']); ?></h4>
                            </div>
                            <?php echo renderBadge($ex['type'], $ex['type'] === 'external' ? 'danger' : 'info'); ?>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs font-mono font-bold text-slate-500 bg-slate-50 p-2.5 rounded-lg border border-[#E2E8F0]">
                            <div>Date: <?php echo $ex['exam_date']; ?></div>
                            <div>Start: <?php echo date('h:i A', strtotime($ex['start_time'])); ?></div>
                            <div>Time: <?php echo $ex['duration_minutes']; ?> mins</div>
                            <div>Venue: <?php echo htmlspecialchars($ex['venue']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($upcomingExams)): ?>
                    <div class="p-8 text-center bg-white rounded-xl border text-slate-400">No upcoming examinations.</div>
                <?php endif; ?>
            </div>

            <!-- Rules checklist -->
            <div class="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
                <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider border-b pb-2">Academic Integrity & Conduct Rules</h3>
                <ul class="space-y-3 text-xs text-[#64748B] leading-relaxed">
                    <li class="flex items-start gap-2.5">
                        <div class="w-1.5 h-1.5 rounded-full bg-[#2563EB] mt-1.5 shrink-0"></div>
                        <p>Scholars must display official identification card badges before entering venues.</p>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <div class="w-1.5 h-1.5 rounded-full bg-[#2563EB] mt-1.5 shrink-0"></div>
                        <p>Any electronic devices, smart devices, watches, calculators, or unauthorized scripts must be surrendered to invigilators before entry.</p>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <div class="w-1.5 h-1.5 rounded-full bg-[#2563EB] mt-1.5 shrink-0"></div>
                        <p>Late arrivals beyond 15 minutes of scheduled times will disqualify candidates from script collection.</p>
                    </li>
                </ul>
            </div>
        </div>
    </div>

<?php elseif ($currentTab === 'results'): ?>
    <!-- STUDENT RESULTS TAB -->
    <div class="space-y-6 animate-fadeIn text-left">
        <?php 
        $selectedSem = (int)($_GET['sem'] ?? 3);
        $filteredResults = array_filter($myResults, fn($r) => (int)$r['semester'] === $selectedSem);
        ?>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
                <h1 class="text-2xl font-extrabold text-[#0F172A] tracking-tight">Academic Grade Transcript</h1>
                <p class="text-[#64748B] text-xs">Verify total credit indexes, term GPA parameters, and results audits.</p>
            </div>
            
            <div class="flex items-center gap-2">
                <label class="text-xs font-bold text-slate-500 font-mono">SEMESTER:</label>
                <div class="flex border border-[#E2E8F0] bg-white rounded-lg p-1 shadow-sm font-bold text-xs">
                    <?php foreach ([1, 2, 3, 4] as $sem): ?>
                        <a href="?tab=results&sem=<?php echo $sem; ?>" class="px-3 py-1 rounded-md <?php echo $selectedSem === $sem ? 'bg-[#2563EB] text-white' : 'text-[#64748B] hover:text-[#0F172A]'; ?>">
                            Sem <?php echo $sem; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#F8FAFC] border-b border-[#E2E8F0] text-slate-500 font-mono uppercase">
                            <th class="p-4">Course Code</th>
                            <th class="p-4">Course Name</th>
                            <th class="p-4 text-center">Internal Marks</th>
                            <th class="p-4 text-center">External Marks</th>
                            <th class="p-4 text-center">Total Marks</th>
                            <th class="p-4 text-right">Grade Scale</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium font-mono">
                        <?php foreach ($filteredResults as $res): ?>
                            <tr class="hover:bg-slate-50/30">
                                <td class="p-4 font-bold"><?php echo htmlspecialchars($res['course_code']); ?></td>
                                <td class="p-4 font-sans text-slate-800 font-semibold"><?php echo htmlspecialchars($res['course_title']); ?></td>
                                <td class="p-4 text-center"><?php echo $res['internal_marks']; ?> / 30</td>
                                <td class="p-4 text-center"><?php echo $res['external_marks']; ?> / 70</td>
                                <td class="p-4 text-center text-[#2563EB] font-black"><?php echo $res['total_marks']; ?> / 100</td>
                                <td class="p-4 text-right">
                                    <?php echo renderBadge($res['grade'], $res['status'] === 'pass' ? 'success' : 'danger'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($filteredResults)): ?>
                            <tr>
                                <td colSpan="6" class="p-8 text-center text-slate-400 font-sans">No grade records published in database for Semester <?php echo $selectedSem; ?>.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($currentTab === 'attendance'): ?>
    <!-- STUDENT ATTENDANCE TAB -->
    <div class="space-y-6 animate-fadeIn text-left">
        <div class="border-b border-[#E2E8F0] pb-4">
            <h1 class="text-2xl font-extrabold text-[#0F172A] tracking-tight">Turnout Attendance Records</h1>
            <p class="text-[#64748B] text-xs">Observe live attendance checklists, total hours attended, and safe levels cutoff.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <div class="lg:col-span-8 bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100">
                    <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider">Attendance statistics by Course</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-[#F8FAFC] border-b border-[#E2E8F0] text-slate-500 font-mono uppercase">
                                <th class="p-4">CODE</th>
                                <th class="p-4">COURSE TITLE</th>
                                <th class="p-4 text-center">ATTENDED SESSIONS</th>
                                <th class="p-4 text-center">TURNOUT RATE</th>
                                <th class="p-4 text-right">STATUS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium font-mono">
                            <?php foreach ($studentCourses as $course): 
                                $cAtt = array_filter($myAttendance, fn($a) => $a['course_id'] === $course['id']);
                                $cTotal = count($cAtt);
                                $cPresent = count(array_filter($cAtt, fn($a) => in_array($a['status'], ['present', 'late'])));
                                $cPercent = $cTotal > 0 ? (int)round(($cPresent / $cTotal) * 100) : 100;
                                $statusSafety = attendanceStatus($cPercent);
                            ?>
                                <tr class="hover:bg-slate-50/30">
                                    <td class="p-4 font-bold"><?php echo htmlspecialchars($course['code']); ?></td>
                                    <td class="p-4 font-sans text-slate-800 font-semibold"><?php echo htmlspecialchars($course['title']); ?></td>
                                    <td class="p-4 text-center"><?php echo $cPresent; ?> / <?php echo $cTotal; ?> classes</td>
                                    <td class="p-4 text-center text-[#2563EB] font-black"><?php echo $cPercent; ?>%</td>
                                    <td class="p-4 text-right">
                                        <?php if ($statusSafety === 'safe'): ?>
                                            <?php echo renderBadge("Safe Cutoff", "success"); ?>
                                        <?php elseif ($statusSafety === 'warning'): ?>
                                            <?php echo renderBadge("Warning Alert", "warning"); ?>
                                        <?php else: ?>
                                            <?php echo renderBadge("Critical Cutoff", "danger"); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Attendance stats -->
            <div class="lg:col-span-4 bg-[#0F172A] text-white rounded-xl p-6 shadow-md border border-white/10 space-y-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-[#2563EB]">Institutional Attendance Audits</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    EduTrack LMS regulations mandate a minimum of <strong>85% attendance turnout rate</strong> for eligibility in end-semester theory examinations.
                </p>
                <div class="p-4 bg-white/5 border border-white/10 rounded-xl space-y-2">
                    <div class="flex justify-between text-xs font-bold">
                        <span>Overall Rate:</span>
                        <span class="<?php echo $attendancePercentage >= 85 ? 'text-[#10B981]' : ($attendancePercentage >= 75 ? 'text-[#F59E0B]' : 'text-[#EF4444]'); ?>"><?php echo $attendancePercentage; ?>%</span>
                    </div>
                    <div class="w-full bg-slate-800 h-2 rounded-full overflow-hidden">
                        <div class="h-2 rounded-full <?php echo $attendancePercentage >= 85 ? 'bg-[#10B981]' : ($attendancePercentage >= 75 ? 'bg-[#F59E0B]' : 'bg-[#EF4444]'); ?>" style="width: <?php echo $attendancePercentage; ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($currentTab === 'placements'): ?>
    <!-- STUDENT PLACEMENT CELL TAB -->
    <div class="space-y-6 animate-fadeIn text-left">
        <div class="border-b border-[#E2E8F0] pb-4">
            <h1 class="text-2xl font-extrabold text-[#0F172A] tracking-tight">Strategic Placements Cell</h1>
            <p class="text-[#64748B] text-xs">Apply to internship/full-time openings, track application status steps.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($allPlacements as $job): 
                $isEligible = ($cgpaValue >= 8.0 || !str_contains($job['eligibility'], 'CGPA > 8.0'));
                $appliedStatus = $job['app_status'];
            ?>
                <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm p-6 space-y-4 hover:shadow-md transition-shadow flex flex-col relative">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-wider"><?php echo htmlspecialchars($job['company']); ?></span>
                            <h3 class="text-base font-extrabold text-[#0F172A] mt-1 leading-snug"><?php echo htmlspecialchars($job['role']); ?></h3>
                            <p class="text-xs text-slate-500 font-medium italic mt-0.5"><?php echo htmlspecialchars($job['location']); ?> • Stipend: <?php echo htmlspecialchars($job['stipend']); ?></p>
                        </div>
                        <?php echo renderBadge($job['type'], $job['type'] === 'internship' ? 'info' : 'success'); ?>
                    </div>

                    <p class="text-xs text-[#64748B] leading-relaxed line-clamp-3"><?php echo htmlspecialchars($job['description']); ?></p>

                    <div class="bg-slate-50 border border-slate-100 p-3 rounded-lg text-[11px] text-slate-600 font-mono">
                        <strong>Eligibility:</strong> <?php echo htmlspecialchars($job['eligibility']); ?>
                    </div>

                    <div class="mt-auto pt-4 border-t border-slate-50 flex items-center justify-between">
                        <span class="text-[10px] font-bold text-[#EF4444] font-mono uppercase">Deadline: <?php echo $job['deadline']; ?></span>
                        
                        <?php if ($appliedStatus): ?>
                            <span class="text-xs font-bold py-1.5 px-3 rounded bg-blue-50 text-blue-700 border border-blue-150">
                                Applied: <?php echo strtoupper($appliedStatus); ?>
                            </span>
                        <?php elseif ($job['status'] === 'closed'): ?>
                            <span class="text-xs font-bold py-1.5 px-3 rounded bg-gray-50 text-gray-500 border border-gray-150">Closed</span>
                        <?php elseif (!$isEligible): ?>
                            <span class="text-xs font-bold py-1.5 px-3 rounded bg-red-50 text-red-700 border border-red-100" title="CGPA requirements not met">Ineligible</span>
                        <?php else: ?>
                            <a href="actions.php?action=apply_placement&placement_id=<?php echo $job['id']; ?>" class="py-1.5 px-4 bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-xs font-bold rounded shadow-sm cursor-pointer transition-transform active:scale-95">
                                Apply Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<?php elseif ($currentTab === 'profile'): 
    // Fetch uploaded documents for the student
    $docStmt = $db->prepare("SELECT * FROM student_documents WHERE student_profile_id = :profile_id");
    $docStmt->execute([':profile_id' => $studentProfile['id']]);
    $studentDocs = $docStmt->fetchAll();
    $docsMap = [];
    foreach ($studentDocs as $doc) {
        $docsMap[$doc['document_type']] = $doc['file_path'];
    }

    $editModeDefault = false;
    if (isset($_SESSION['profile_edit_failed'])) {
        $editModeDefault = true;
        unset($_SESSION['profile_edit_failed']);
    }
?>
    <!-- STUDENT PROFILE TAB -->
    <div class="max-w-4xl mx-auto space-y-6 animate-fadeIn text-left">
        
        <!-- Header Panel -->
        <div class="bg-white border border-[#E2E8F0] p-6 rounded-xl shadow-sm flex flex-col sm:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <img src="<?php echo htmlspecialchars($currentUser['avatar']); ?>" alt="Profile" class="w-16 h-16 rounded-full border object-cover" />
                <div>
                    <h2 class="text-xl font-extrabold text-[#0F172A]"><?php echo htmlspecialchars($currentUser['name']); ?></h2>
                    <p class="text-xs text-slate-500 font-medium">Department: <?php echo htmlspecialchars($studentProfile['department_name'] ?? 'CSE'); ?></p>
                    <p class="text-xs text-slate-400 font-mono mt-0.5">Roll No: <?php echo htmlspecialchars($studentProfile['roll_no'] ?? 'CS-2025-001'); ?></p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-center px-4 py-2 bg-slate-50 border border-slate-100 rounded-lg shrink-0">
                    <span class="text-xs text-slate-400 block font-bold uppercase tracking-wider">CGPA</span>
                    <span class="text-lg font-black text-[#2563EB] font-mono"><?php echo number_format($cgpaValue, 2); ?></span>
                </div>
                <button type="button" id="edit_profile_btn" onclick="toggleEditMode(true);" class="py-2.5 px-4 bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-xs font-bold rounded-lg shadow-sm cursor-pointer transition-transform active:scale-95 flex items-center gap-1.5">
                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                    <span>Edit Profile Details</span>
                </button>
            </div>
        </div>

        <!-- 1. VIEW MODE PANEL -->
        <div id="profile_view_panel" class="space-y-6">
            <!-- Personal Info Card -->
            <div class="bg-white border border-[#E2E8F0] p-6 rounded-xl shadow-sm space-y-4">
                <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider border-b border-[#E2E8F0] pb-2 flex items-center gap-2">
                    <i data-lucide="user" class="w-4 h-4 text-[#2563EB]"></i>
                    <span>Personal Information</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Full Name</span>
                        <span class="text-xs font-semibold text-[#0F172A] mt-1 block"><?php echo htmlspecialchars($currentUser['name']); ?></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Gender</span>
                        <span class="text-xs font-semibold text-[#0F172A] mt-1 block"><?php echo htmlspecialchars($studentProfile['gender'] ?? 'Not specified'); ?></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Date of Birth</span>
                        <span class="text-xs font-semibold text-[#0F172A] mt-1 block font-mono"><?php echo htmlspecialchars($studentProfile['dob'] ?? 'Not specified'); ?></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Religion</span>
                        <span class="text-xs font-semibold text-[#0F172A] mt-1 block"><?php echo htmlspecialchars($studentProfile['religion'] ?? 'Not specified'); ?></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Caste</span>
                        <span class="text-xs font-semibold text-[#0F172A] mt-1 block"><?php echo htmlspecialchars($studentProfile['caste'] ?? 'Not specified'); ?></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Nationality</span>
                        <span class="text-xs font-semibold text-[#0F172A] mt-1 block"><?php echo htmlspecialchars($studentProfile['nationality'] ?? 'Not specified'); ?></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Blood Group</span>
                        <span class="text-xs font-semibold text-[#0F172A] mt-1 block font-mono"><?php echo htmlspecialchars($studentProfile['blood_group'] ?? 'Not specified'); ?></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Aadhaar Number</span>
                        <span class="text-xs font-semibold text-[#0F172A] mt-1 block font-mono"><?php echo htmlspecialchars($studentProfile['aadhaar_number'] ?? 'Not specified'); ?></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Mobile Phone</span>
                        <span class="text-xs font-semibold text-[#0F172A] mt-1 block font-mono"><?php echo htmlspecialchars($currentUser['phone'] ?? 'Not specified'); ?></span>
                    </div>
                    <div class="md:col-span-2 lg:col-span-3">
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Institutional Email</span>
                        <span class="text-xs font-semibold text-[#0F172A] mt-1 block font-mono"><?php echo htmlspecialchars($currentUser['email']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Parent Info Card -->
            <div class="bg-white border border-[#E2E8F0] p-6 rounded-xl shadow-sm space-y-4">
                <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider border-b border-[#E2E8F0] pb-2 flex items-center gap-2">
                    <i data-lucide="users" class="w-4 h-4 text-[#2563EB]"></i>
                    <span>Parent Information</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Father details -->
                    <div class="space-y-3 bg-[#F8FAFC] p-4 rounded-xl border border-[#E2E8F0]">
                        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Father's Profile</h4>
                        <div class="grid grid-cols-1 gap-2.5">
                            <div>
                                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Name</span>
                                <span class="text-xs font-semibold text-[#0F172A] block"><?php echo htmlspecialchars($studentProfile['father_name'] ?? 'Not specified'); ?></span>
                            </div>
                            <div>
                                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Phone Number</span>
                                <span class="text-xs font-semibold text-[#0F172A] block font-mono"><?php echo htmlspecialchars($studentProfile['father_phone'] ?? 'Not specified'); ?></span>
                            </div>
                            <div>
                                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Email</span>
                                <span class="text-xs font-semibold text-[#0F172A] block font-mono"><?php echo htmlspecialchars($studentProfile['father_email'] ?? 'Not specified'); ?></span>
                            </div>
                            <div>
                                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Occupation</span>
                                <span class="text-xs font-semibold text-[#0F172A] block"><?php echo htmlspecialchars($studentProfile['father_occupation'] ?? 'Not specified'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Mother details -->
                    <div class="space-y-3 bg-[#F8FAFC] p-4 rounded-xl border border-[#E2E8F0]">
                        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Mother's Profile</h4>
                        <div class="grid grid-cols-1 gap-2.5">
                            <div>
                                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Name</span>
                                <span class="text-xs font-semibold text-[#0F172A] block"><?php echo htmlspecialchars($studentProfile['mother_name'] ?? 'Not specified'); ?></span>
                            </div>
                            <div>
                                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Phone Number</span>
                                <span class="text-xs font-semibold text-[#0F172A] block font-mono"><?php echo htmlspecialchars($studentProfile['mother_phone'] ?? 'Not specified'); ?></span>
                            </div>
                            <div>
                                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Email</span>
                                <span class="text-xs font-semibold text-[#0F172A] block font-mono"><?php echo htmlspecialchars($studentProfile['mother_email'] ?? 'Not specified'); ?></span>
                            </div>
                            <div>
                                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Occupation</span>
                                <span class="text-xs font-semibold text-[#0F172A] block"><?php echo htmlspecialchars($studentProfile['mother_occupation'] ?? 'Not specified'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Annual Income -->
                    <div class="md:col-span-2">
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Annual Family Income</span>
                        <span class="text-sm font-bold text-[#0F172A] mt-1 block font-mono">
                            <?php echo isset($studentProfile['annual_income']) ? 'INR ' . number_format($studentProfile['annual_income'], 2) : 'Not specified'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Address Info Card -->
            <div class="bg-white border border-[#E2E8F0] p-6 rounded-xl shadow-sm space-y-4">
                <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider border-b border-[#E2E8F0] pb-2 flex items-center gap-2">
                    <i data-lucide="map-pin" class="w-4 h-4 text-[#2563EB]"></i>
                    <span>Address Information</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Present Address</span>
                        <p class="text-xs font-semibold text-[#0F172A] mt-1 leading-relaxed whitespace-pre-line"><?php echo htmlspecialchars($studentProfile['present_address'] ?? 'Not specified'); ?></p>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Permanent Address</span>
                        <p class="text-xs font-semibold text-[#0F172A] mt-1 leading-relaxed whitespace-pre-line"><?php echo htmlspecialchars($studentProfile['address'] ?? 'Not specified'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Documents Card -->
            <div class="bg-white border border-[#E2E8F0] p-6 rounded-xl shadow-sm space-y-4">
                <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider border-b border-[#E2E8F0] pb-2 flex items-center gap-2">
                    <i data-lucide="file-text" class="w-4 h-4 text-[#2563EB]"></i>
                    <span>Documents Upload status</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php 
                    $docLabels = [
                        '10th_certificate' => '10th Certificate',
                        'transfer_certificate' => 'Transfer Certificate (TC)',
                        'caste_certificate' => 'Caste Certificate',
                        'plus_two_certificate' => '+2 / Equivalent Certificate'
                    ];
                    foreach ($docLabels as $key => $label):
                        $hasDoc = isset($docsMap[$key]);
                    ?>
                        <div class="p-4 border border-[#E2E8F0] rounded-xl flex items-center justify-between gap-4 bg-[#F8FAFC]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 <?php echo $hasDoc ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-400'; ?>">
                                    <i data-lucide="<?php echo $hasDoc ? 'file-check-2' : 'file-warning'; ?>" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-xs font-bold text-[#0F172A] truncate"><?php echo $label; ?></h4>
                                    <span class="text-[9px] uppercase tracking-wider font-black font-mono <?php echo $hasDoc ? 'text-emerald-600' : 'text-slate-400'; ?>">
                                        <?php echo $hasDoc ? 'Uploaded' : 'Missing'; ?>
                                    </span>
                                </div>
                            </div>
                            <?php if ($hasDoc): ?>
                                <div class="flex items-center gap-2 shrink-0">
                                    <a href="uploads/documents/<?php echo htmlspecialchars($docsMap[$key]); ?>" target="_blank" class="p-1.5 border border-[#E2E8F0] hover:border-[#2563EB] bg-white rounded-lg text-[#64748B] hover:text-[#2563EB] transition-colors" title="View Document">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    </a>
                                    <a href="uploads/documents/<?php echo htmlspecialchars($docsMap[$key]); ?>" download="<?php echo htmlspecialchars($key . '_' . $currentUser['id']); ?>" class="p-1.5 border border-[#E2E8F0] hover:border-[#2563EB] bg-white rounded-lg text-[#64748B] hover:text-[#2563EB] transition-colors" title="Download Document">
                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 2. EDIT MODE PANEL (FORM) -->
        <div id="profile_edit_panel" class="hidden">
            <!-- Client validation error box -->
            <div id="validation_warning_box" class="p-4 mb-6 bg-red-50 border border-red-100 rounded-lg text-red-600 text-xs flex items-start gap-3 hidden animate-shake">
                <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                <div class="flex-1">
                    <p class="font-bold uppercase tracking-wider mb-1">Validation Errors:</p>
                    <p id="validation_warning_msg" class="font-semibold leading-relaxed"></p>
                </div>
            </div>

            <form action="actions.php?action=edit_profile" method="POST" enctype="multipart/form-data" onsubmit="return validateProfileForm(event);" class="space-y-6">
                <!-- Personal Info Inputs Card -->
                <div class="bg-white border border-[#E2E8F0] p-6 rounded-xl shadow-sm space-y-4">
                    <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider border-b border-[#E2E8F0] pb-2 flex items-center gap-2">
                        <i data-lucide="user" class="w-4 h-4 text-[#2563EB]"></i>
                        <span>Personal Information</span>
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label for="edit_name" class="block text-[10px] font-bold text-[#64748B] uppercase tracking-wider mb-1.5">Full Name *</label>
                            <input type="text" name="name" id="edit_name" value="<?php echo htmlspecialchars($currentUser['name']); ?>" class="w-full px-3 py-2 bg-[#F8FAFC] border border-[#E2E8F0] rounded-lg text-xs font-semibold text-[#0F172A] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent transition-all" required />
                        </div>
                        <div>
                            <label for="edit_gender" class="block text-[10px] font-bold text-[#64748B] uppercase tracking-wider mb-1.5">Gender</label>
                            <select name="gender" id="edit_gender" class="w-full px-3 py-2 bg-[#F8FAFC] border border-[#E2E8F0] rounded-lg text-xs font-semibold text-[#0F172A] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent transition-all">
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo ($studentProfile['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($studentProfile['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($studentProfile['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                <option value="Prefer not to say" <?php echo ($studentProfile['gender'] ?? '') === 'Prefer not to say' ? 'selected' : ''; ?>>Prefer not to say</option>
                            </select>
                        </div>
                        <div>
                            <label for="edit_dob" class="block text-[10px] font-bold text-[#64748B] uppercase tracking-wider mb-1.5">Date of Birth</label>
                            <input type="date" name="dob" id="edit_dob" value="<?php echo htmlspecialchars($studentProfile['dob'] ?? ''); ?>" class="w-full px-3 py-2 bg-[#F8FAFC] border border-[#E2E8F0] rounded-lg text-xs font-semibold text-[#0F172A] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent transition-all font-mono" />
                        </div>
                        <div>
                            <label for="edit_religion" class="block text-[10px] font-bold text-[#64748B] uppercase tracking-wider mb-1.5">Religion</label>
                            <input type="text" name="religion" id="edit_religion" value="<?php echo htmlspecialchars($studentProfile['religion'] ?? ''); ?>" class="w-full px-3 py-2 bg-[#F8FAFC] border border-[#E2E8F0] rounded-lg text-xs font-semibold text-[#0F172A] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent transition-all" />
                        </div>
                        <div>
                            <label for="edit_caste" class="block text-[10px] font-bold text-[#64748B] uppercase tracking-wider mb-1.5">Caste</label>
                            <input type="text" name="caste" id="edit_caste" value="<?php echo htmlspecialchars($studentProfile['caste'] ?? ''); ?>" class="w-full px-3 py-2 bg-[#F8FAFC] border border-[#E2E8F0] rounded-lg text-xs font-semibold text-[#0F172A] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent transition-all" />
                        </div>
                        <div>
                            <label for="edit_nationality" class="block text-[10px] font-bold text-[#64748B] uppercase tracking-wider mb-1.5">Nationality</label>
                            <input type="text" name="nationality" id="edit_nationality" value="<?php echo htmlspecialchars($studentProfile['nationality'] ?? ''); ?>" class="w-full px-3 py-2 bg-[#F8FAFC] border border-[#E2E8F0] rounded-lg text-xs font-semibold text-[#0F172A] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent transition-all" />
                        </div>
                        <div>
                            <label for="edit_blood_group" class="block text-[10px] font-bold text-[#64748B] uppercase tracking-wider mb-1.5">Blood Group</label>
                            <input type="text" name="blood_group" id="edit_blood_group" value="<?php echo htmlspecialchars($studentProfile['blood_group'] ?? ''); ?>" placeholder="e.g. O+, AB-" class="w-full px-3 py-2 bg-[#F8FAFC] border border-[#E2E8F0] rounded-lg text-xs font-semibold text-[#0F172A] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent transition-all font-mono" />
                        </div>
                        <div>
                            <label for="edit_aadhaar" class="block text-[10px] font-bold text-[#64748B] uppercase tracking-wider mb-1.5">Aadhaar Number</label>
                            <input type="text" name="aadhaar_number" id="edit_aadhaar" value="<?php echo htmlspecialchars($studentProfile['aadhaar_number'] ?? ''); ?>" placeholder="12 digit Aadhaar" class="w-full px-3 py-2 bg-[#F8FAFC] border border-[#E2E8F0] rounded-lg text-xs font-semibold text-[#0F172A] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent transition-all font-mono" />
                        </div>
                        <div>
                            <label for="edit_phone" class="block text-[10px] font-bold text-[#64748B] uppercase tracking-wider mb-1.5">Mobile Phone *</label>
                            <input type="text" name="phone" id="edit_phone" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>" class="w-full px-3 py-2 bg-[#F8FAFC] border border-[#E2E8F0] rounded-lg text-xs font-semibold text-[#0F172A] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent transition-all font-mono" required />
                        </div>
                        <div class="md:col-span-2 lg:col-span-3">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Institutional Email (Read-only)</label>
                            <input type="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" class="w-full px-3 py-2 bg-slate-50 border border-slate-100 rounded-lg text-xs font-semibold text-slate-400 cursor-not-allowed font-mono" readonly />
                        </div>
                    </div>
                </div>

                <!-- Parent Info Inputs Card -->
                <div class="bg-white border border-[#E2E8F0] p-6 rounded-xl shadow-sm space-y-4">
                    <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider border-b border-[#E2E8F0] pb-2 flex items-center gap-2">
                        <i data-lucide="users" class="w-4 h-4 text-[#2563EB]"></i>
                        <span>Parent Information</span>
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Father inputs -->
                        <div class="space-y-4 bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <h4 class="text-[10px] font-black text-[#2563EB] uppercase tracking-wider">Father's Profile Details</h4>
                            
                            <div>
                                <label for="edit_father_name" class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1">Father's Name</label>
                                <input type="text" name="father_name" id="edit_father_name" value="<?php echo htmlspecialchars($studentProfile['father_name'] ?? ''); ?>" class="w-full px-3 py-2 bg-white border border-[#E2E8F0] rounded-lg text-xs font-semibold text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#2563EB] focus:border-transparent transition-all" />
                            </div>
                            <div>
                                <label for="edit_father_phone" class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1">Father's Phone Number</label>
                                <input type="text" name="father_phone" id="edit_father_phone" value="<?php echo htmlspecialchars($studentProfile['father_phone'] ?? ''); ?>" class="w-full px-3 py-2 bg-white border border-[#E2E8F0] rounded-lg text-xs font-semibold text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#2563EB] focus:border-transparent transition-all font-mono" />
                            </div>
                            <div>
                                <label for="edit_father_email" class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1">Father's Email</label>
                                <input type="email" name="father_email" id="edit_father_email" value="<?php echo htmlspecialchars($studentProfile['father_email'] ?? ''); ?>" class="w-full px-3 py-2 bg-white border border-[#E2E8F0] rounded-lg text-xs font-semibold text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#2563EB] focus:border-transparent transition-all font-mono" />
                            </div>
                            <div>
                                <label for="edit_father_occupation" class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1">Father's Occupation</label>
                                <input type="text" name="father_occupation" id="edit_father_occupation" value="<?php echo htmlspecialchars($studentProfile['father_occupation'] ?? ''); ?>" class="w-full px-3 py-2 bg-white border border-[#E2E8F0] rounded-lg text-xs font-semibold text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#2563EB] focus:border-transparent transition-all" />
                            </div>
                        </div>

                        <!-- Mother inputs -->
                        <div class="space-y-4 bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <h4 class="text-[10px] font-black text-[#2563EB] uppercase tracking-wider">Mother's Profile Details</h4>
                            
                            <div>
                                <label for="edit_mother_name" class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1">Mother's Name</label>
                                <input type="text" name="mother_name" id="edit_mother_name" value="<?php echo htmlspecialchars($studentProfile['mother_name'] ?? ''); ?>" class="w-full px-3 py-2 bg-white border border-[#E2E8F0] rounded-lg text-xs font-semibold text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#2563EB] focus:border-transparent transition-all" />
                            </div>
                            <div>
                                <label for="edit_mother_phone" class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1">Mother's Phone Number</label>
                                <input type="text" name="mother_phone" id="edit_mother_phone" value="<?php echo htmlspecialchars($studentProfile['mother_phone'] ?? ''); ?>" class="w-full px-3 py-2 bg-white border border-[#E2E8F0] rounded-lg text-xs font-semibold text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#2563EB] focus:border-transparent transition-all font-mono" />
                            </div>
                            <div>
                                <label for="edit_mother_email" class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1">Mother's Email</label>
                                <input type="email" name="mother_email" id="edit_mother_email" value="<?php echo htmlspecialchars($studentProfile['mother_email'] ?? ''); ?>" class="w-full px-3 py-2 bg-white border border-[#E2E8F0] rounded-lg text-xs font-semibold text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#2563EB] focus:border-transparent transition-all font-mono" />
                            </div>
                            <div>
                                <label for="edit_mother_occupation" class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1">Mother's Occupation</label>
                                <input type="text" name="mother_occupation" id="edit_mother_occupation" value="<?php echo htmlspecialchars($studentProfile['mother_occupation'] ?? ''); ?>" class="w-full px-3 py-2 bg-white border border-[#E2E8F0] rounded-lg text-xs font-semibold text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#2563EB] focus:border-transparent transition-all" />
                            </div>
                        </div>

                        <!-- Income input -->
                        <div class="md:col-span-2">
                            <label for="edit_income" class="block text-[10px] font-bold text-[#64748B] uppercase tracking-wider mb-1.5">Annual Family Income (in INR)</label>
                            <input type="number" name="annual_income" id="edit_income" value="<?php echo htmlspecialchars($studentProfile['annual_income'] ?? ''); ?>" placeholder="e.g. 450000" class="w-full px-3 py-2 bg-[#F8FAFC] border border-[#E2E8F0] rounded-lg text-xs font-semibold text-[#0F172A] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent transition-all font-mono" />
                        </div>
                    </div>
                </div>

                <!-- Address Inputs Card -->
                <div class="bg-white border border-[#E2E8F0] p-6 rounded-xl shadow-sm space-y-4">
                    <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider border-b border-[#E2E8F0] pb-2 flex items-center gap-2">
                        <i data-lucide="map-pin" class="w-4 h-4 text-[#2563EB]"></i>
                        <span>Address Information</span>
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit_present_address" class="block text-[10px] font-bold text-[#64748B] uppercase tracking-wider mb-1.5">Present Address</label>
                            <textarea name="present_address" id="edit_present_address" rows="3" class="w-full px-3 py-2 bg-[#F8FAFC] border border-[#E2E8F0] rounded-lg text-xs font-semibold text-[#0F172A] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent transition-all"><?php echo htmlspecialchars($studentProfile['present_address'] ?? ''); ?></textarea>
                        </div>
                        <div>
                            <label for="edit_permanent_address" class="block text-[10px] font-bold text-[#64748B] uppercase tracking-wider mb-1.5">Permanent Address</label>
                            <textarea name="permanent_address" id="edit_permanent_address" rows="3" class="w-full px-3 py-2 bg-[#F8FAFC] border border-[#E2E8F0] rounded-lg text-xs font-semibold text-[#0F172A] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent transition-all"><?php echo htmlspecialchars($studentProfile['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Documents Inputs Card -->
                <div class="bg-white border border-[#E2E8F0] p-6 rounded-xl shadow-sm space-y-4">
                    <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider border-b border-[#E2E8F0] pb-2 flex items-center gap-2">
                        <i data-lucide="file-text" class="w-4 h-4 text-[#2563EB]"></i>
                        <span>Documents Upload (Max 10MB each, PDF/JPG/JPEG/PNG)</span>
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($docLabels as $key => $label): 
                            $hasDoc = isset($docsMap[$key]);
                        ?>
                            <div class="space-y-2 p-4 border border-[#E2E8F0] rounded-xl bg-[#F8FAFC]">
                                <label for="edit_<?php echo $key; ?>" class="block text-xs font-bold text-[#0F172A]"><?php echo $label; ?></label>
                                
                                <div class="flex items-center gap-3">
                                    <input type="file" name="<?php echo $key; ?>" id="edit_<?php echo $key; ?>" data-label="<?php echo $label; ?>" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-blue-50 file:text-[#2563EB] hover:file:bg-blue-100 transition-colors" />
                                </div>
                                
                                <?php if ($hasDoc): ?>
                                    <div class="flex items-center gap-1.5 mt-1.5">
                                        <span class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100 flex items-center gap-1 font-mono">
                                            <i data-lucide="check" class="w-3 h-3"></i>
                                            <span>Current: <?php echo htmlspecialchars(basename($docsMap[$key])); ?></span>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="flex justify-end gap-3 font-bold text-xs pt-4 border-t border-[#E2E8F0]">
                    <button type="button" onclick="toggleEditMode(false);" class="py-2.5 px-5 rounded-lg border border-[#E2E8F0] text-slate-700 hover:bg-slate-50 text-center cursor-pointer transition-colors">
                        Cancel Changes
                    </button>
                    <button type="submit" class="py-2.5 px-6 bg-[#2563EB] hover:bg-[#1D4ED8] text-white rounded-lg shadow-sm cursor-pointer transition-colors">
                        Save Profile Changes
                    </button>
                </div>
            </form>
        </div>

    </div>

    <!-- Toggle & Client Validation Scripts -->
    <script>
        function toggleEditMode(isEdit) {
            const viewPanel = document.getElementById('profile_view_panel');
            const editPanel = document.getElementById('profile_edit_panel');
            const editBtn = document.getElementById('edit_profile_btn');
            
            if (isEdit) {
                viewPanel.classList.add('hidden');
                editPanel.classList.remove('hidden');
                if (editBtn) editBtn.classList.add('hidden');
            } else {
                viewPanel.classList.remove('hidden');
                editPanel.classList.add('hidden');
                if (editBtn) editBtn.classList.remove('hidden');
                
                // Clear any leftover validation errors on cancel
                document.getElementById('validation_warning_box').classList.add('hidden');
            }
        }

        function validateProfileForm(event) {
            const errors = [];
            const name = document.getElementById('edit_name').value.trim();
            const phone = document.getElementById('edit_phone').value.trim();
            const aadhaar = document.getElementById('edit_aadhaar').value.trim();
            
            const fatherPhone = document.getElementById('edit_father_phone').value.trim();
            const fatherEmail = document.getElementById('edit_father_email').value.trim();
            
            const motherPhone = document.getElementById('edit_mother_phone').value.trim();
            const motherEmail = document.getElementById('edit_mother_email').value.trim();
            
            const income = document.getElementById('edit_income').value.trim();

            // Core details
            if (!name) {
                errors.push("Full Name is required.");
            }
            if (!phone) {
                errors.push("Mobile Contact Phone is required.");
            }
            
            // Aadhaar Check
            if (aadhaar && !/^\d{12}$/.test(aadhaar)) {
                errors.push("Aadhaar Number must be exactly 12 digits.");
            }
            
            // Emails Check
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (fatherEmail && !emailRegex.test(fatherEmail)) {
                errors.push("Father's Email format is invalid.");
            }
            if (motherEmail && !emailRegex.test(motherEmail)) {
                errors.push("Mother's Email format is invalid.");
            }
            
            // Phone Check (Allowing + prefix, spaces, dashes, 10 to 15 chars)
            const phoneRegex = /^\+?[0-9\s\-]{10,15}$/;
            if (fatherPhone && !phoneRegex.test(fatherPhone)) {
                errors.push("Father's Phone number must be a valid phone number (10-15 digits).");
            }
            if (motherPhone && !phoneRegex.test(motherPhone)) {
                errors.push("Mother's Phone number must be a valid phone number (10-15 digits).");
            }
            
            // Annual income positive value
            if (income !== '') {
                const incomeFloat = parseFloat(income);
                if (isNaN(incomeFloat) || incomeFloat < 0) {
                    errors.push("Annual Family Income must be a positive number.");
                }
            }

            // Document uploads checks
            const fileKeys = ['10th_certificate', 'transfer_certificate', 'caste_certificate', 'plus_two_certificate'];
            const allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
            const maxBytes = 10 * 1024 * 1024; // 10MB limit

            fileKeys.forEach(key => {
                const fileInput = document.getElementById('edit_' + key);
                if (fileInput && fileInput.files && fileInput.files[0]) {
                    const file = fileInput.files[0];
                    const ext = file.name.split('.').pop().toLowerCase();
                    if (!allowedExts.includes(ext)) {
                        errors.push(fileInput.getAttribute('data-label') + " must be a PDF, JPG, JPEG, or PNG file.");
                    }
                    if (file.size > maxBytes) {
                        errors.push(fileInput.getAttribute('data-label') + " exceeds the 10MB size limit.");
                    }
                }
            });

            if (errors.length > 0) {
                event.preventDefault();
                const alertBox = document.getElementById('validation_warning_box');
                const alertMsg = document.getElementById('validation_warning_msg');
                alertMsg.innerHTML = errors.join('<br>');
                alertBox.classList.remove('hidden');
                
                // Scroll page smoothly to show validation box
                alertBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
                return false;
            }
            return true;
        }

        // Initialize state on DOM ready
        document.addEventListener('DOMContentLoaded', () => {
            const startInEdit = <?php echo $editModeDefault ? 'true' : 'false'; ?>;
            if (startInEdit) {
                toggleEditMode(true);
            }
        });
    </script>
<?php endif; ?>
