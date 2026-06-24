<?php
// views/teacher_dashboard.php
// Teacher Dashboard view matching TeacherDashboard.tsx exactly

if (!isset($currentUser) || $currentUser['role'] !== 'teacher') {
    return;
}

$db = Database::connect();
$teacherId = $currentUser['id'];

// Fetch teacher's courses
$coursesStmt = $db->prepare("SELECT * FROM courses WHERE teacher_id = :teacher_id AND status = 'active'");
$coursesStmt->execute([':teacher_id' => $teacherId]);
$teacherCourses = $coursesStmt->fetchAll();
$teacherCourseIds = array_column($teacherCourses, 'id');
$taughtCount = count($teacherCourses);

// Fetch assignments in taught courses
$assignmentsList = [];
if (!empty($teacherCourseIds)) {
    $inClause = implode(',', array_fill(0, count($teacherCourseIds), '?'));
    $asgStmt = $db->prepare("
        SELECT a.*, c.code as course_code, c.title as course_title 
        FROM assignments a 
        JOIN courses c ON a.course_id = c.id 
        WHERE a.course_id IN ($inClause)
        ORDER BY a.created_at DESC
    ");
    $asgStmt->execute($teacherCourseIds);
    $assignmentsList = $asgStmt->fetchAll();
}
$asgIds = array_column($assignmentsList, 'id');

// Fetch submissions for those assignments
$submissionsList = [];
if (!empty($asgIds)) {
    $inClause = implode(',', array_fill(0, count($asgIds), '?'));
    $subStmt = $db->prepare("
        SELECT s.*, a.title as asg_title, a.max_marks, c.code as course_code, u.name as student_name, u.email as student_email 
        FROM submissions s 
        JOIN assignments a ON s.assignment_id = a.id 
        JOIN courses c ON a.course_id = c.id 
        JOIN users u ON s.student_id = u.id 
        WHERE s.assignment_id IN ($inClause)
        ORDER BY s.submitted_at DESC
    ");
    $subStmt->execute($asgIds);
    $submissionsList = $subStmt->fetchAll();
}

$pendingEvaluationSubmissions = array_filter($submissionsList, fn($s) => $s['status'] === 'submitted');
$pendingEvaluationCount = count($pendingEvaluationSubmissions);

// Fetch exams in taught courses
$examsList = [];
if (!empty($teacherCourseIds)) {
    $inClause = implode(',', array_fill(0, count($teacherCourseIds), '?'));
    $exStmt = $db->prepare("
        SELECT ex.*, c.code as course_code, c.title as course_title 
        FROM exams ex 
        JOIN courses c ON ex.course_id = c.id 
        WHERE ex.course_id IN ($inClause)
        ORDER BY ex.exam_date ASC
    ");
    $exStmt->execute($teacherCourseIds);
    $examsList = $exStmt->fetchAll();
}
?>

<?php if ($currentTab === 'dashboard'): ?>
    <div class="space-y-6 animate-fadeIn text-left">
        <!-- Header -->
        <div class="border-b border-[#E2E8F0] pb-4">
            <h1 class="text-2xl font-bold text-[#0F172A] tracking-tight">
                Faculty Dashboard
            </h1>
            <p class="text-[#64748B] text-xs mt-1">Overview of your assigned courses and student deliverables</p>
        </div>

        <!-- Statistical KPI summary layout cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php 
            echo renderStatCard("Assigned Courses", $taughtCount, "book-open", "navy", "Instructing curricula term");
            echo renderStatCard("Active Assignments", count($assignmentsList), "clipboard-list", "blue", "Weekly active assessments");
            echo renderStatCard("Pending Gradings", $pendingEvaluationCount, "award", "amber", "{$pendingEvaluationCount} homework scripts to mark");
            echo renderStatCard("Campus Engagement", "93%", "activity", "green", "Average student class turnout");
            ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left 8Cols: Submissions Grading Queue -->
            <div class="lg:col-span-8 bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-50">
                    <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider">Submissions Grading Queue</h3>
                    <?php if ($pendingEvaluationCount > 0): ?>
                        <span class="text-xs font-bold text-amber-600 bg-amber-50 border border-amber-100 px-2 py-0.5 rounded-full">
                            <?php echo $pendingEvaluationCount; ?> scripts waiting
                        </span>
                    <?php endif; ?>
                </div>

                <div class="divide-y divide-slate-100 max-h-[480px] overflow-y-auto pr-1 space-y-2">
                    <?php foreach ($pendingEvaluationSubmissions as $sub): ?>
                        <div class="p-4 bg-slate-50 border border-slate-100 hover:border-blue-400/30 rounded-xl flex items-start justify-between gap-4 transition-colors">
                            <div class="space-y-1.5 min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-[10px] font-bold bg-[#E2E8F0] tracking-wide px-1.5 py-0.5 rounded text-[#0F172A]">
                                        <?php echo htmlspecialchars($sub['course_code']); ?>
                                    </span>
                                    <span class="text-[11px] font-bold text-slate-700"><?php echo htmlspecialchars($sub['student_name']); ?></span>
                                </div>
                                <h4 class="text-xs font-bold text-[#0F172A] leading-snug truncate"><?php echo htmlspecialchars($sub['asg_title']); ?></h4>
                                <p class="text-[11px] text-[#64748B] font-mono italic">File: "<?php echo htmlspecialchars($sub['file_path']); ?>"</p>
                                <?php if (!empty($sub['text_submission'])): ?>
                                    <p class="text-[10px] bg-white border border-slate-100 p-2 rounded text-slate-500 font-mono line-clamp-1">"<?php echo htmlspecialchars($sub['text_submission']); ?>"</p>
                                <?php endif; ?>
                            </div>

                            <button onclick="openGradingModal(<?php echo $sub['id']; ?>, <?php echo $sub['max_marks']; ?>, '<?php echo htmlspecialchars(addslashes($sub['student_name'])); ?>', '<?php echo htmlspecialchars(addslashes($sub['asg_title'])); ?>');" class="py-1 px-3 bg-[#2563EB] hover:bg-[#1D4ED8] text-white font-bold text-xs rounded-lg shrink-0">
                                Grade Script
                            </button>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($pendingEvaluationCount === 0): ?>
                        <div class="text-center p-12 text-xs text-slate-400 space-y-2 bg-slate-50/50 border border-dashed rounded-xl">
                            <i data-lucide="smile" class="w-8 h-8 text-emerald-500 mx-auto animate-bounce"></i>
                            <p class="font-bold text-slate-700">Excellent! Registry Cleared</p>
                            <p>No active homework deliverables waiting evaluation records.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right 4Cols: LMS Quick-deck -->
            <div class="lg:col-span-4 space-y-6">
                <div class="bg-[#0F172A] text-white rounded-xl p-6 shadow-md space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#2563EB]">LMS Quick-deck</h3>
                    <div class="space-y-2 text-left">
                        <a href="?tab=attendance" class="w-full text-left py-2 px-3 border border-white/5 hover:border-slate-500 rounded-lg text-xs font-medium flex items-center justify-between block">
                            <span>Record attendance roll-call</span>
                            <i data-lucide="check-square" class="w-4 h-4 text-emerald-400"></i>
                        </a>
                        <a href="?tab=create_assignment" class="w-full text-left py-2 px-3 border border-white/5 hover:border-slate-500 rounded-lg text-xs font-medium flex items-center justify-between block">
                            <span>Issue new assignment</span>
                            <i data-lucide="plus-circle" class="w-4 h-4 text-blue-400"></i>
                        </a>
                        <a href="?tab=upload_material" class="w-full text-left py-2 px-3 border border-white/5 hover:border-slate-500 rounded-lg text-xs font-medium flex items-center justify-between block">
                            <span>Upload lecture resources</span>
                            <i data-lucide="upload-cloud" class="w-4 h-4 text-indigo-400"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grading script Modal Overlay -->
        <div id="grading_modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 animate-modalBackdrop hidden">
            <div class="bg-white border border-[#E2E8F0] rounded-xl p-6 w-full max-w-lg shadow-lg relative animate-modalContent space-y-4">
                <button onclick="closeGradingModal();" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
                <div class="flex items-center gap-2 pb-2 border-b border-slate-50">
                    <i data-lucide="award" class="w-5 h-5 text-[#2563EB]"></i>
                    <h3 class="text-base font-extrabold text-[#0F172A] tracking-tight">Evaluate Script Marks</h3>
                </div>

                <form action="actions.php?action=evaluate_submission" method="POST" class="space-y-4">
                    <input type="hidden" name="submission_id" id="modal_sub_id" value="" />
                    <div class="text-xs text-slate-500 font-semibold space-y-1">
                        <div>Student: <span id="modal_student_name" class="text-slate-800 font-bold"></span></div>
                        <div>Assignment: <span id="modal_asg_title" class="text-slate-800 font-bold"></span></div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase font-mono mb-1.5">Score Points Obtained (Max: <span id="modal_max_marks"></span>)</label>
                        <input
                            type="number"
                            name="marks_obtained"
                            id="modal_marks_input"
                            class="w-full border border-slate-200 p-2 rounded focus:ring-1 focus:ring-blue-500 text-xs"
                            min="0"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase font-mono mb-1.5">Instructor Tutors Review comments</label>
                        <textarea
                            name="feedback"
                            id="modal_feedback_input"
                            rows="4"
                            class="w-full border border-slate-200 p-2 rounded focus:ring-1 focus:ring-blue-500 text-xs"
                            required
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 text-xs font-bold">
                        <button type="button" onclick="closeGradingModal();" class="px-4 py-2 border rounded font-medium hover:bg-slate-50">
                            Close view
                        </button>
                        <button type="submit" class="px-4 py-2 bg-[#10B981] text-white rounded hover:bg-[#059669]">
                            Publish assessment Evaluation
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            function openGradingModal(subId, maxMarks, studentName, asgTitle) {
                document.getElementById('modal_sub_id').value = subId;
                document.getElementById('modal_max_marks').innerText = maxMarks;
                document.getElementById('modal_student_name').innerText = studentName;
                document.getElementById('modal_asg_title').innerText = asgTitle;
                document.getElementById('modal_marks_input').max = maxMarks;
                document.getElementById('modal_marks_input').value = Math.round(maxMarks * 0.9);
                document.getElementById('modal_feedback_input').value = "Well developed structure, codes compilation is solid and requirements were met fully.";
                
                document.getElementById('grading_modal').classList.remove('hidden');
            }
            function closeGradingModal() {
                document.getElementById('grading_modal').classList.add('hidden');
            }
        </script>
    </div>

<?php elseif ($currentTab === 'courses'): ?>
    <!-- TEACHER MY COURSES TAB -->
    <div class="space-y-6 animate-fadeIn text-left">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
                <h1 class="text-2xl font-extrabold text-[#0F172A]">Instructing Course Curricula</h1>
                <p class="text-[#64748B] text-xs">Manage educational materials, upload presentations slides, or check syllabus modules.</p>
            </div>
            
            <a href="?tab=upload_material" class="py-2 px-4 bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-2 cursor-pointer">
                <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                <span>Share Lecture Document</span>
            </a>
        </div>

        <div class="space-y-6">
            <?php foreach ($teacherCourses as $course): 
                // Fetch enrollment count
                $enrStmt = $db->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id = :c_id AND status = 'active'");
                $enrStmt->execute([':c_id' => $course['id']]);
                $activeCount = $enrStmt->fetchColumn();

                // Fetch materials list
                $matStmt = $db->prepare("SELECT * FROM materials WHERE course_id = :c_id");
                $matStmt->execute([':c_id' => $course['id']]);
                $courseMaterials = $matStmt->fetchAll();
            ?>
                <div class="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 border-b border-slate-100 pb-3">
                        <div>
                            <span class="text-[10px] uppercase font-black text-slate-400 font-mono tracking-wider"><?php echo htmlspecialchars($course['code']); ?></span>
                            <h3 class="font-extrabold text-[#0F172A] text-lg tracking-tight mt-0.5"><?php echo htmlspecialchars($course['title']); ?></h3>
                            <p class="text-xs text-slate-500 font-medium italic mt-0.5">Credits weightage: <?php echo $course['credits']; ?> points • Classroom Year: Term III</p>
                        </div>
                        <div class="flex items-center gap-1 bg-[#F1F5F9] p-2 rounded-lg border border-slate-100 font-medium">
                            <i data-lucide="users" class="w-4 h-4 text-[#2563EB]"></i>
                            <span class="text-xs font-bold text-slate-700"><?php echo $activeCount; ?> Registered Students</span>
                        </div>
                    </div>

                    <p class="text-xs text-[#64748B] leading-relaxed max-w-4xl"><?php echo htmlspecialchars($course['description']); ?></p>

                    <!-- Materials list sub-view -->
                    <?php if (!empty($courseMaterials)): ?>
                        <div class="pt-2">
                            <h4 class="text-xs font-black uppercase text-[#0F172A] mb-2 tracking-wider">Uploaded Lecture Resources</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <?php foreach ($courseMaterials as $mat): ?>
                                    <div class="p-3 border rounded-xl bg-slate-50 flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2.5">
                                            <i data-lucide="file-text" class="w-4 h-4 text-[#2563EB]"></i>
                                            <div>
                                                <span class="font-bold text-[#0F172A] block"><?php echo htmlspecialchars($mat['title']); ?></span>
                                                <span class="text-[10px] text-slate-400 font-mono"><?php echo htmlspecialchars($mat['file_path']); ?></span>
                                            </div>
                                        </div>
                                        <span class="text-[10px] font-semibold text-slate-400"><?php echo date('Y-m-d', strtotime($mat['uploaded_at'])); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<?php elseif ($currentTab === 'upload_material'): ?>
    <!-- TEACHER UPLOAD MATERIAL FORM -->
    <div class="bg-white border border-[#E2E8F0] p-8 max-w-xl mx-auto rounded-xl shadow-sm space-y-6 animate-fadeIn text-left">
        <div>
            <h2 class="text-xl font-extrabold text-[#0F172A] tracking-tight">Upload Lecture Document</h2>
            <p class="text-xs text-[#64748B] mt-1">Disclose lecture sheets slideshows and codes writeups directly to course catalog.</p>
        </div>

        <form action="actions.php?action=upload_material" method="POST" class="space-y-4 text-xs font-bold">
            <div>
                <label class="block text-slate-500 mb-1">Target Class Curriculum</label>
                <select name="course_id" class="w-full bg-[#fdfdfd] border border-[#E2E8F0] p-2.5 rounded text-xs font-bold text-[#0F172A]">
                    <?php foreach ($teacherCourses as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['code']); ?> - <?php echo htmlspecialchars($c['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-slate-500 mb-1">Lectures Topic Name</label>
                <input
                    type="text"
                    name="title"
                    placeholder="e.g. Unit III: Normalization and ER Schemes paradigms"
                    class="w-full border border-slate-200 p-2.5 rounded text-xs focus:ring-1 focus:ring-blue-500 outline-none"
                    required
                />
            </div>

            <div>
                <label class="block text-slate-500 mb-1">Description syllabus mapping</label>
                <textarea
                    name="description"
                    placeholder="Detailed explanations regarding homework mapping chapter checklist..."
                    rows="4"
                    class="w-full border border-slate-200 p-2.5 rounded text-xs focus:ring-1 focus:ring-blue-500 outline-none font-mono"
                ></textarea>
            </div>

            <div>
                <label class="block text-slate-500 mb-1">Attach file name mock identifier</label>
                <input
                    type="text"
                    name="file_name"
                    placeholder="normalization_lecture_guide.pdf"
                    class="w-full border border-slate-200 p-2 rounded text-xs font-mono"
                />
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end gap-2 text-xs font-bold">
                <a href="?tab=courses" class="px-4 py-2 border rounded text-slate-600 hover:bg-slate-50 text-center">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-[#0F172A] hover:bg-[#2563EB] text-white rounded">
                    Publish Resources
                </button>
            </div>
        </form>
    </div>

<?php elseif ($currentTab === 'assignments'): ?>
    <!-- TEACHER ASSIGNMENTS TAB -->
    <div class="space-y-6 animate-fadeIn text-left">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#E2E8F0] pb-4">
            <div>
                <h1 class="text-2xl font-extrabold text-[#0F172A]">Issued coursework Assignments</h1>
                <p class="text-[#64748B] text-xs">Create new task deadlines, customize maximum marks, check script uploads.</p>
            </div>

            <a href="?tab=create_assignment" class="py-2 px-4 bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-2 cursor-pointer">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Issue New Task</span>
            </a>
        </div>

        <div class="bg-white border border-[#E2E8F0] rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-[#0F172A] border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 border-b border-[#E2E8F0] font-mono uppercase">
                            <th class="p-4">CODE</th>
                            <th class="p-4">ASSIGNMENT TITLE</th>
                            <th class="p-4">DUE DATE DEADLINE</th>
                            <th class="p-4 text-center">SUBMISSIONS</th>
                            <th class="p-4 text-center">MAX MARKS</th>
                            <th class="p-4 text-right">STATUS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono font-medium">
                        <?php foreach ($assignmentsList as $asg): 
                            $subCount = count(array_filter($submissionsList, fn($s) => $s['assignment_id'] === $asg['id']));
                        ?>
                            <tr class="hover:bg-slate-50/30">
                                <td class="p-4 font-bold"><?php echo htmlspecialchars($asg['course_code']); ?></td>
                                <td class="p-4 font-sans text-slate-800 font-semibold"><?php echo htmlspecialchars($asg['title']); ?></td>
                                <td class="p-4"><?php echo date('Y-m-d H:i', strtotime($asg['due_date'])); ?></td>
                                <td class="p-4 text-center text-[#2563EB] font-bold"><?php echo $subCount; ?> scripts uploaded</td>
                                <td class="p-4 text-center"><?php echo $asg['max_marks']; ?> pts</td>
                                <td class="p-4 text-right">
                                    <?php echo renderBadge($asg['status'], $asg['status'] === 'active' ? 'success' : 'danger'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($assignmentsList)): ?>
                            <tr>
                                <td colSpan="6" class="p-8 text-center text-slate-400 font-sans">
                                    No customized coursework tasks posted for active terms yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($currentTab === 'create_assignment'): ?>
    <!-- TEACHER CREATE ASSIGNMENT FORM -->
    <div class="bg-white border border-[#E2E8F0] p-8 max-w-xl mx-auto rounded-xl shadow-sm space-y-6 animate-fadeIn text-left">
        <div>
            <h2 class="text-xl font-extrabold text-[#0F172A] tracking-tight">Issue New Task</h2>
            <p class="text-xs text-[#64748B] mt-1">Specify target dates, evaluation weights and instructional codes details.</p>
        </div>

        <form action="actions.php?action=create_assignment" method="POST" class="space-y-4 text-xs font-bold">
            <div>
                <label class="block text-slate-500 mb-1">Target Curriculum</label>
                <select name="course_id" class="w-full bg-[#fdfdfd] border border-[#E2E8F0] p-2.5 rounded text-xs font-bold text-[#0F172A]">
                    <?php foreach ($teacherCourses as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['code']); ?> - <?php echo htmlspecialchars($c['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-slate-500 mb-1">Assignment Title Name</label>
                <input
                    type="text"
                    name="title"
                    placeholder="e.g. Lab Exercise-IV: Index structures design query tests"
                    class="w-full border border-slate-200 p-2.5 rounded text-xs focus:ring-1 focus:ring-blue-500 outline-none"
                    required
                />
            </div>

            <div>
                <label class="block text-slate-500 mb-1">Requirements descriptions</label>
                <textarea
                    name="description"
                    placeholder="Write algorithmic assumptions detailed guide map structures..."
                    rows="5"
                    class="w-full border border-slate-200 p-2.5 rounded text-xs font-mono focus:ring-1 focus:ring-blue-500 outline-none"
                    required
                ></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-500 mb-1">Maximum Mark Points</label>
                    <input
                        type="number"
                        name="max_marks"
                        value="50"
                        class="w-full border border-slate-200 p-2 rounded text-xs focus:ring-1 focus:ring-blue-500 outline-none"
                        min="1"
                        required
                    />
                </div>
                <div>
                    <label class="block text-slate-500 mb-1">Due Deadline Date</label>
                    <input
                        type="datetime-local"
                        name="due_date"
                        value="2026-06-15T23:59"
                        class="w-full border border-slate-200 p-2 rounded text-xs font-mono focus:ring-1 focus:ring-blue-500 outline-none"
                        required
                    />
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end gap-2 text-xs font-bold">
                <a href="?tab=assignments" class="px-4 py-2 border rounded text-slate-600 hover:bg-slate-50 text-center">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-[#0F172A] hover:bg-[#2563EB] text-white rounded">
                    Publish Assignments
                </button>
            </div>
        </form>
    </div>

<?php elseif ($currentTab === 'attendance'): ?>
    <!-- TEACHER TAKE ATTENDANCE BATCH FORM -->
    <div class="space-y-6 animate-fadeIn text-left">
        <?php 
        $activeCourseId = (int)($_GET['course_id'] ?? ($teacherCourses[0]['id'] ?? 1));

        // Find enrolled active students in selected course ID
        $stuStmt = $db->prepare("
            SELECT u.*, sp.roll_no, sp.cgpa 
            FROM users u
            JOIN enrollments e ON u.id = e.student_id
            JOIN student_profiles sp ON u.id = sp.user_id
            WHERE e.course_id = :c_id AND e.status = 'active'
        ");
        $stuStmt->execute([':c_id' => $activeCourseId]);
        $enrolledStudents = $stuStmt->fetchAll();
        ?>
        <div class="border-b border-[#E2E8F0] pb-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-[#0F172A]">Mark Class Attendance</h1>
                <p class="text-[#64748B] text-xs">Choose target curriculum, select session date and toggle present/absent states.</p>
            </div>

            <!-- Course selector -->
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="tab" value="attendance" />
                <label class="text-xs font-bold text-slate-500 font-mono">CURRICULUM:</label>
                <select
                    name="course_id"
                    class="bg-white border border-[#E2E8F0] p-2 rounded text-xs font-bold text-[#0F172A]"
                    onchange="this.form.submit();"
                >
                    <?php foreach ($teacherCourses as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $activeCourseId === $c['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['code']); ?> - <?php echo htmlspecialchars($c['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <form action="actions.php?action=mark_attendance" method="POST" class="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-6">
            <input type="hidden" name="course_id" value="<?php echo $activeCourseId; ?>" />
            
            <div class="flex items-center gap-4 max-w-sm">
                <div class="w-full">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Session Date Record</label>
                    <input
                        type="date"
                        name="date"
                        value="<?php echo date('Y-m-d'); ?>"
                        class="w-full border border-[#E2E8F0] p-2 rounded text-xs font-bold focus:outline-none"
                        required
                    />
                </div>
            </div>

            <div class="border-t border-slate-50 pt-4 space-y-3">
                <h3 class="text-xs font-extrabold text-[#0F172A] uppercase tracking-wider mb-2">Class Turnout Roll Call</h3>

                <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                    <?php foreach ($enrolledStudents as $stu): ?>
                        <div class="py-3 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <img src="<?php echo htmlspecialchars($stu['avatar']); ?>" alt="Avatar" class="w-8 h-8 rounded-full object-cover bg-gray-100 border text-xs" />
                                <div>
                                    <span class="text-xs font-bold text-[#0F172A] block"><?php echo htmlspecialchars($stu['name']); ?></span>
                                    <span class="text-[10px] text-slate-400 font-mono block">Roll: <?php echo htmlspecialchars($stu['roll_no']); ?></span>
                                </div>
                            </div>

                            <!-- Radio group styled as buttons -->
                            <div class="flex items-center border border-slate-200 rounded-lg overflow-hidden p-0.5 bg-slate-50 font-bold text-[10px]">
                                <label class="cursor-pointer">
                                    <input type="radio" name="records[<?php echo $stu['id']; ?>]" value="present" checked class="sr-only peer" />
                                    <span class="px-3 py-1.5 rounded-md inline-block text-[#64748B] hover:text-[#0F172A] peer-checked:bg-[#10B981] peer-checked:text-white transition-all">PRESENT</span>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="records[<?php echo $stu['id']; ?>]" value="absent" class="sr-only peer" />
                                    <span class="px-3 py-1.5 rounded-md inline-block text-[#64748B] hover:text-[#0F172A] peer-checked:bg-[#EF4444] peer-checked:text-white transition-all">ABSENT</span>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="records[<?php echo $stu['id']; ?>]" value="late" class="sr-only peer" />
                                    <span class="px-3 py-1.5 rounded-md inline-block text-[#64748B] hover:text-[#0F172A] peer-checked:bg-[#F59E0B] peer-checked:text-white transition-all">LATE</span>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($enrolledStudents)): ?>
                        <div class="p-8 text-center text-slate-400 text-xs font-sans">
                            No registered students found enrolled inside selected course curriculum.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end text-xs font-bold">
                <button type="submit" class="px-5 py-2.5 bg-[#0F172A] hover:bg-[#2563EB] text-white rounded">
                    Save Attendance Batch
                </button>
            </div>
        </form>
    </div>

<?php elseif ($currentTab === 'exams'): ?>
    <!-- TEACHER EXAMS & RESULTS TAB -->
    <div class="space-y-6 animate-fadeIn text-left">
        <div class="border-b border-[#E2E8F0] pb-4">
            <h1 class="text-2xl font-extrabold text-[#0F172A]">Exams & Results Manager</h1>
            <p class="text-[#64748B] text-xs">Schedule midterm/practical examinations and publish student marks cards.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left 6Cols: Exam Schedules -->
            <div class="lg:col-span-6 space-y-4">
                <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider mb-2">My Scheduled Exams</h3>
                
                <div class="space-y-4 max-h-[480px] overflow-y-auto pr-1">
                    <?php foreach ($examsList as $ex): ?>
                        <div class="bg-white border border-[#E2E8F0] p-4 rounded-xl shadow-sm space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="font-mono text-[10px] font-bold bg-[#F1F5F9] text-[#64748B] rounded px-2 py-0.5 border border-[#E2E8F0]">
                                    <?php echo htmlspecialchars($ex['course_code']); ?>
                                </span>
                                <?php echo renderBadge($ex['type'], $ex['type'] === 'external' ? 'danger' : 'info'); ?>
                            </div>
                            <h4 class="font-bold text-[#0F172A] text-sm"><?php echo htmlspecialchars($ex['title']); ?></h4>
                            <div class="grid grid-cols-2 gap-1 text-[11px] font-mono text-slate-500">
                                <div>Date: <?php echo $ex['exam_date']; ?></div>
                                <div>Venue: <?php echo htmlspecialchars($ex['venue']); ?></div>
                                <div>Max Marks: <?php echo $ex['max_marks']; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($examsList)): ?>
                        <div class="p-8 text-center bg-white rounded-xl border text-slate-400">No examinations scheduled yet.</div>
                    <?php endif; ?>
                </div>

                <!-- Schedule New Exam Form -->
                <div class="bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#2563EB]">Schedule Exam Session</h3>
                    <form action="actions.php?action=create_exam" method="POST" class="space-y-4 text-xs font-bold">
                        <div>
                            <label class="block text-slate-500 mb-1">Target Curriculum</label>
                            <select name="course_id" class="w-full bg-[#fdfdfd] border border-[#E2E8F0] p-2 rounded text-xs text-[#0F172A]">
                                <?php foreach ($teacherCourses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['code']); ?> - <?php echo htmlspecialchars($c['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-500 mb-1">Exam Title</label>
                            <input type="text" name="title" placeholder="Midterm, Practical exam..." class="w-full border p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none" required />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-slate-500 mb-1">Exam Date</label>
                                <input type="date" name="exam_date" value="<?php echo date('Y-m-d'); ?>" class="w-full border p-2 rounded font-mono focus:ring-1 focus:ring-blue-500 outline-none" required />
                            </div>
                            <div>
                                <label class="block text-slate-500 mb-1">Start Time</label>
                                <input type="time" name="start_time" value="09:30" class="w-full border p-2 rounded font-mono focus:ring-1 focus:ring-blue-500 outline-none" required />
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block text-slate-500 mb-1">Mins</label>
                                <input type="number" name="duration_minutes" value="120" class="w-full border p-2 rounded" min="1" required />
                            </div>
                            <div>
                                <label class="block text-slate-500 mb-1">Venue</label>
                                <input type="text" name="venue" placeholder="Lab 4B" class="w-full border p-2 rounded" required />
                            </div>
                            <div>
                                <label class="block text-slate-500 mb-1">Type</label>
                                <select name="type" class="w-full border p-2 rounded bg-white text-slate-700">
                                    <option value="internal">Internal</option>
                                    <option value="external">External</option>
                                    <option value="practical">Practical</option>
                                    <option value="viva">Viva</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-slate-500 mb-1">Max Marks</label>
                            <input type="number" name="max_marks" value="100" class="w-full border p-2 rounded" min="1" required />
                        </div>
                        <button type="submit" class="w-full py-2 bg-[#0F172A] hover:bg-[#2563EB] text-white rounded font-bold">
                            Publish Schedule
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right 6Cols: Publish Result Form -->
            <div class="lg:col-span-6 bg-white border border-[#E2E8F0] rounded-xl p-6 shadow-sm space-y-4">
                <h3 class="text-sm font-extrabold text-[#0F172A] uppercase tracking-wider border-b pb-2">Publish Student Results Card</h3>
                
                <form action="actions.php?action=publish_result" method="POST" class="space-y-4 text-xs font-bold">
                    <div>
                        <label class="block text-slate-500 mb-1">Select Exam Session</label>
                        <select name="exam_id" class="w-full bg-[#fdfdfd] border border-[#E2E8F0] p-2 rounded text-xs text-[#0F172A]">
                            <?php foreach ($examsList as $ex): ?>
                                <option value="<?php echo $ex['id']; ?>"><?php echo htmlspecialchars($ex['course_code']); ?> - <?php echo htmlspecialchars($ex['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-slate-500 mb-1">Course Curriculum</label>
                        <select name="course_id" class="w-full bg-[#fdfdfd] border border-[#E2E8F0] p-2 rounded text-xs text-[#0F172A]">
                            <?php foreach ($teacherCourses as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['code']); ?> - <?php echo htmlspecialchars($c['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php 
                    // Query list of all student users to assign marks
                    $allStudStmt = $db->prepare("SELECT id, name FROM users WHERE role = 'student' AND status = 'active' ORDER BY name ASC");
                    $allStudStmt->execute();
                    $allStudents = $allStudStmt->fetchAll();
                    ?>
                    <div>
                        <label class="block text-slate-500 mb-1">Select Student Scholar</label>
                        <select name="student_id" class="w-full bg-[#fdfdfd] border border-[#E2E8F0] p-2 rounded text-xs text-[#0F172A]">
                            <?php foreach ($allStudents as $st): ?>
                                <option value="<?php echo $st['id']; ?>"><?php echo htmlspecialchars($st['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-500 mb-1">Internal Marks (Max: 30)</label>
                            <input type="number" name="internal_marks" value="25" min="0" max="30" step="0.5" class="w-full border p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none" required />
                        </div>
                        <div>
                            <label class="block text-slate-500 mb-1">External Marks (Max: 70)</label>
                            <input type="number" name="external_marks" value="65" min="0" max="70" step="0.5" class="w-full border p-2 rounded focus:ring-1 focus:ring-blue-500 outline-none" required />
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-500 mb-1">Semester</label>
                        <input type="number" name="semester" value="3" class="w-full border p-2 rounded" required />
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-[#10B981] hover:bg-[#059669] text-white rounded font-bold transition-colors">
                        Publish Grade Card Result
                    </button>
                </form>
            </div>
        </div>
    </div>

<?php elseif ($currentTab === 'analytics'): ?>
    <!-- TEACHER COURSE ANALYTICS TAB -->
    <div class="space-y-6 animate-fadeIn text-left">
        <div class="border-b border-[#E2E8F0] pb-4">
            <h1 class="text-2xl font-extrabold text-[#0F172A]">Course Analytics Dashboard</h1>
            <p class="text-[#64748B] text-xs">Verify class turnouts, student distribution marks averages and statistics.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white border border-[#E2E8F0] p-6 rounded-xl shadow-sm space-y-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-[#2563EB]">Student Turnout Indicators</h3>
                <div class="space-y-3 text-xs">
                    <div>
                        <div class="flex justify-between font-bold mb-1">
                            <span>Cloud Computing (CS401)</span>
                            <span class="text-emerald-600">95%</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-emerald-500 h-2 rounded-full" style="width: 95%;"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between font-bold mb-1">
                            <span>Database Systems (CS301)</span>
                            <span class="text-[#2563EB]">91%</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-[#2563EB] h-2 rounded-full" style="width: 91%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-[#E2E8F0] p-6 rounded-xl shadow-sm space-y-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-[#2563EB]">Grade Distributions</h3>
                <div class="flex items-end justify-between h-32 pt-4 px-2 font-mono font-bold text-[10px] text-slate-400 text-center">
                    <div class="w-8">
                        <div class="bg-[#2563EB] h-20 rounded-t"></div>
                        <span class="block mt-1">A</span>
                    </div>
                    <div class="w-8">
                        <div class="bg-emerald-500 h-16 rounded-t"></div>
                        <span class="block mt-1">B+</span>
                    </div>
                    <div class="w-8">
                        <div class="bg-amber-500 h-10 rounded-t"></div>
                        <span class="block mt-1">B</span>
                    </div>
                    <div class="w-8">
                        <div class="bg-indigo-500 h-6 rounded-t"></div>
                        <span class="block mt-1">C</span>
                    </div>
                    <div class="w-8">
                        <div class="bg-red-500 h-2 rounded-t"></div>
                        <span class="block mt-1">F</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($currentTab === 'profile'): ?>
    <!-- TEACHER PROFILE TAB -->
    <div class="bg-white border border-[#E2E8F0] p-8 max-w-2xl mx-auto rounded-xl shadow-sm space-y-6 animate-fadeIn text-left">
        <div class="flex items-center gap-4 pb-4 border-b border-[#E2E8F0]">
            <img src="<?php echo htmlspecialchars($currentUser['avatar']); ?>" alt="Profile" class="w-16 h-16 rounded-full border object-cover" />
            <div>
                <h2 class="text-xl font-extrabold text-[#0F172A]"><?php echo htmlspecialchars($currentUser['name']); ?></h2>
                <p class="text-xs text-slate-500 font-medium">Designation: <?php echo htmlspecialchars($teacherProfile['designation'] ?? 'Professor'); ?></p>
                <p class="text-xs text-slate-400 font-mono mt-0.5">Emp ID: <?php echo htmlspecialchars($teacherProfile['employee_id'] ?? 'EMP-CSE-001'); ?></p>
            </div>
        </div>

        <form action="actions.php?action=edit_profile" method="POST" class="space-y-4 text-xs font-bold">
            <div>
                <label class="block text-slate-500 mb-1">Official Instructor Email</label>
                <input type="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" class="w-full border p-2.5 rounded bg-gray-50 text-gray-500" readonly />
            </div>

            <div>
                <label class="block text-slate-500 mb-1">Mobile Contact Phone</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($currentUser['phone'] ?? '9482562150'); ?>" class="w-full border p-2.5 rounded focus:ring-1 focus:ring-blue-500" required />
            </div>

            <div>
                <label class="block text-slate-500 mb-1">Academic Qualifications</label>
                <input type="text" value="<?php echo htmlspecialchars($teacherProfile['qualification'] ?? 'Ph.D., IIT Bombay'); ?>" class="w-full border p-2.5 rounded bg-gray-50 text-gray-500" readonly />
            </div>

            <div>
                <label class="block text-slate-500 mb-1">Academic Department Sector</label>
                <input type="text" value="<?php echo htmlspecialchars($teacherProfile['department_name'] ?? 'CSE'); ?>" class="w-full border p-2.5 rounded bg-gray-50 text-gray-500" readonly />
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end">
                <button type="submit" class="py-2.5 px-6 bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-xs font-bold rounded-lg shadow-sm">
                    Save Profile Changes
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>
