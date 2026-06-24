<?php
// Central Form Submissions and DB Operations Controller
session_start();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

$db = Database::connect();

$action = $_GET['action'] ?? '';

// Check if user is logged in for protected actions
$protectedActions = [
    'logout', 'submit_assignment', 'apply_placement', 'create_assignment',
    'upload_material', 'evaluate_submission', 'mark_attendance', 'create_exam',
    'publish_result', 'add_student', 'edit_student', 'delete_student',
    'add_teacher', 'edit_teacher', 'delete_teacher', 'add_department',
    'edit_department', 'add_course', 'edit_course', 'add_enrollment',
    'remove_enrollment', 'add_placement', 'update_placement_status',
    'update_placement_app_status', 'send_announcement',
    'mark_notification_read', 'clear_notifications', 'edit_profile'
];

if (in_array($action, $protectedActions) && !isset($_SESSION['user_id'])) {
    $_SESSION['flash_danger'] = 'You must be logged in to perform this action.';
    header('Location: index.php');
    exit;
}

// Fetch current user details
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $uStmt = $db->prepare("SELECT * FROM users WHERE id = :id AND status = 'active'");
    $uStmt->execute([':id' => $_SESSION['user_id']]);
    $currentUser = $uStmt->fetch();
}

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            $_SESSION['login_error'] = 'Please fill in both email and password fields.';
            header('Location: index.php');
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['login_error'] = 'User account does not exist';
            header('Location: index.php');
            exit;
        }

        if ($user['password'] !== $password) {
            $_SESSION['login_error'] = 'Incorrect email or password';
            header('Location: index.php');
            exit;
        }

        if ($user['status'] !== 'active') {
            $_SESSION['login_error'] = 'This user account has been deactivated';
            header('Location: index.php');
            exit;
        }

        // Successfully logged in
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['flash_success'] = 'Welcome back, ' . $user['name'] . '!';
        header('Location: index.php');
        exit;

    case 'logout':
        unset($_SESSION['user_id']);
        session_destroy();
        session_start();
        $_SESSION['flash_success'] = 'You have successfully signed out.';
        header('Location: index.php');
        exit;

    case 'submit_assignment':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?tab=assignments');
            exit;
        }

        $asgId = (int)($_POST['assignment_id'] ?? 0);
        $textSubmission = trim($_POST['text_submission'] ?? '');
        $fileName = 'assignment_upload.pdf';

        // Check if a file was uploaded
        if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
            $uploadsDir = __DIR__ . '/uploads/';
            if (!file_exists($uploadsDir)) {
                mkdir($uploadsDir, 0777, true);
            }
            // Validate size (10MB)
            if ($_FILES['submission_file']['size'] > 10 * 1024 * 1024) {
                $_SESSION['flash_danger'] = 'File exceeds 10MB size limit.';
                header("Location: index.php?tab=submit_assignment&assignment_id={$asgId}");
                exit;
            }
            // Validate extension
            $ext = strtolower(pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'doc', 'docx', 'zip'])) {
                $_SESSION['flash_danger'] = 'Allowed file extensions are: .pdf, .doc, .docx, .zip';
                header("Location: index.php?tab=submit_assignment&assignment_id={$asgId}");
                exit;
            }

            $fileName = basename($_FILES['submission_file']['name']);
            move_uploaded_file($_FILES['submission_file']['tmp_name'], $uploadsDir . $fileName);
        } elseif (!empty($_POST['mock_file_name'])) {
            $fileName = trim($_POST['mock_file_name']);
        }

        if (empty($textSubmission) && $fileName === 'assignment_upload.pdf') {
            $_SESSION['flash_danger'] = 'Please enter a submission text description or attach a file.';
            header("Location: index.php?tab=submit_assignment&assignment_id={$asgId}");
            exit;
        }

        // Insert or Update submission
        $subStmt = $db->prepare("INSERT INTO submissions (assignment_id, student_id, file_path, text_submission, submitted_at, marks_obtained, feedback, status) 
                                 VALUES (:asg_id, :student_id, :file_path, :text_sub, NOW(), NULL, NULL, 'submitted')
                                 ON DUPLICATE KEY UPDATE 
                                     file_path = VALUES(file_path),
                                     text_submission = VALUES(text_submission),
                                     submitted_at = NOW(),
                                     status = 'submitted',
                                     marks_obtained = NULL,
                                     feedback = NULL");
        $subStmt->execute([
            ':asg_id' => $asgId,
            ':student_id' => $currentUser['id'],
            ':file_path' => $fileName,
            ':text_sub' => $textSubmission
        ]);

        // Find teacher who created this assignment to notify them
        $asgStmt = $db->prepare("SELECT a.*, c.title as course_title FROM assignments a JOIN courses c ON a.course_id = c.id WHERE a.id = :asg_id");
        $asgStmt->execute([':asg_id' => $asgId]);
        $asgObj = $asgStmt->fetch();

        if ($asgObj) {
            $notifStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, is_read) 
                                       VALUES (:teacher_id, 'Assignment Submitted', :msg, 'info', 0)");
            $notifStmt->execute([
                ':teacher_id' => $asgObj['teacher_id'],
                ':msg' => "{$currentUser['name']} submitted assignment: \"{$asgObj['title']}\""
            ]);
        }

        $_SESSION['flash_success'] = 'Assignment submission uploaded and recorded successfully!';
        header('Location: index.php?tab=assignments');
        exit;

    case 'apply_placement':
        $placeId = (int)($_GET['placement_id'] ?? 0);
        if ($placeId <= 0) {
            header('Location: index.php?tab=placements');
            exit;
        }

        // Verify if already applied
        $checkStmt = $db->prepare("SELECT id FROM placement_applications WHERE placement_id = :p_id AND student_id = :s_id");
        $checkStmt->execute([':p_id' => $placeId, ':s_id' => $currentUser['id']]);
        if ($checkStmt->fetch()) {
            $_SESSION['flash_danger'] = 'You have already applied for this placement';
            header('Location: index.php?tab=placements');
            exit;
        }

        $appStmt = $db->prepare("INSERT INTO placement_applications (placement_id, student_id, applied_at, status) VALUES (:p_id, :s_id, NOW(), 'applied')");
        $appStmt->execute([':p_id' => $placeId, ':s_id' => $currentUser['id']]);

        $_SESSION['flash_success'] = 'Application submitted successfully!';
        header('Location: index.php?tab=placements');
        exit;

    case 'create_assignment':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?tab=assignments');
            exit;
        }

        $courseId = (int)($_POST['course_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $dueDate = trim($_POST['due_date'] ?? '');
        $maxMarks = (int)($_POST['max_marks'] ?? 100);

        if (empty($title) || empty($description)) {
            $_SESSION['flash_danger'] = 'Title and Description details are required.';
            header('Location: index.php?tab=assignments');
            exit;
        }

        $stmt = $db->prepare("INSERT INTO assignments (course_id, teacher_id, title, description, due_date, max_marks, status) 
                              VALUES (:course_id, :teacher_id, :title, :description, :due_date, :max_marks, 'active')");
        $stmt->execute([
            ':course_id' => $courseId,
            ':teacher_id' => $currentUser['id'],
            ':title' => $title,
            ':description' => $description,
            ':due_date' => date('Y-m-d H:i:s', strtotime($dueDate)),
            ':max_marks' => $maxMarks
        ]);

        // Notify enrolled students in that course
        $cStmt = $db->prepare("SELECT title FROM courses WHERE id = :id");
        $cStmt->execute([':id' => $courseId]);
        $courseTitle = $cStmt->fetchColumn() ?: 'course';

        $enrStmt = $db->prepare("SELECT student_id FROM enrollments WHERE course_id = :c_id AND status = 'active'");
        $enrStmt->execute([':c_id' => $courseId]);
        $students = $enrStmt->fetchAll(PDO::FETCH_COLUMN);

        $notifStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, is_read) 
                                   VALUES (:student_id, 'New Assignment Added', :msg, 'warning', 0)");
        foreach ($students as $studentId) {
            $notifStmt->execute([
                ':student_id' => $studentId,
                ':msg' => "New assignment: \"{$title}\" has been added for {$courseTitle}"
            ]);
        }

        $_SESSION['flash_success'] = 'Course assignment issued and student warnings triggered successfully!';
        header('Location: index.php?tab=assignments');
        exit;

    case 'upload_material':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?tab=courses');
            exit;
        }

        $courseId = (int)($_POST['course_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $fileName = trim($_POST['file_name'] ?? 'lecture_slides.pdf');

        if (empty($title)) {
            $_SESSION['flash_danger'] = 'Material title field is mandatory.';
            header('Location: index.php?tab=courses');
            exit;
        }

        $stmt = $db->prepare("INSERT INTO materials (course_id, teacher_id, title, description, file_path, file_type) 
                              VALUES (:course_id, :teacher_id, :title, :description, :file_path, 'pdf')");
        $stmt->execute([
            ':course_id' => $courseId,
            ':teacher_id' => $currentUser['id'],
            ':title' => $title,
            ':description' => $description,
            ':file_path' => $fileName ?: 'lecture_slides.pdf'
        ]);

        $_SESSION['flash_success'] = 'Lectures course resource posted successfully!';
        header('Location: index.php?tab=courses');
        exit;

    case 'evaluate_submission':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php');
            exit;
        }

        $subId = (int)($_POST['submission_id'] ?? 0);
        $marks = (int)($_POST['marks_obtained'] ?? 0);
        $feedback = trim($_POST['feedback'] ?? '');

        // Fetch submission details to construct notification
        $subStmt = $db->prepare("SELECT s.*, a.title as asg_title, a.max_marks 
                                 FROM submissions s 
                                 JOIN assignments a ON s.assignment_id = a.id 
                                 WHERE s.id = :id");
        $subStmt->execute([':id' => $subId]);
        $sub = $subStmt->fetch();

        if (!$sub) {
            $_SESSION['flash_danger'] = 'Submission not found.';
            header('Location: index.php');
            exit;
        }

        $updateStmt = $db->prepare("UPDATE submissions SET marks_obtained = :marks, feedback = :feedback, status = 'evaluated' WHERE id = :id");
        $updateStmt->execute([
            ':marks' => $marks,
            ':feedback' => $feedback,
            ':id' => $subId
        ]);

        // Send Student Notification
        $notifStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, is_read) 
                                   VALUES (:student_id, 'Assignment Evaluated', :msg, 'success', 0)");
        $notifStmt->execute([
            ':student_id' => $sub['student_id'],
            ':msg' => "Your assignment: \"{$sub['asg_title']}\" has been graded: {$marks}/{$sub['max_marks']}"
        ]);

        $_SESSION['flash_success'] = 'Marks assessment evaluation successfully recorded.';
        header('Location: index.php');
        exit;

    case 'mark_attendance':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php');
            exit;
        }

        $courseId = (int)($_POST['course_id'] ?? 0);
        $date = trim($_POST['date'] ?? '');
        $records = $_POST['records'] ?? []; // Map of studentId => status ('present', 'absent', 'late')

        if (empty($date) || empty($records)) {
            $_SESSION['flash_danger'] = 'Session date and turnout records are required.';
            header('Location: index.php?tab=attendance');
            exit;
        }

        $stmt = $db->prepare("INSERT INTO attendance (student_id, course_id, date, status, marked_by) 
                              VALUES (:student_id, :course_id, :date, :status, :marked_by) 
                              ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by)");

        foreach ($records as $studentId => $status) {
            $stmt->execute([
                ':student_id' => (int)$studentId,
                ':course_id' => $courseId,
                ':date' => $date,
                ':status' => $status,
                ':marked_by' => $currentUser['id']
            ]);
        }

        $_SESSION['flash_success'] = 'Batch class session attendance updated in database successfully.';
        header('Location: index.php');
        exit;

    case 'create_exam':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?tab=exams');
            exit;
        }

        $courseId = (int)($_POST['course_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $examDate = trim($_POST['exam_date'] ?? '');
        $startTime = trim($_POST['start_time'] ?? '');
        $duration = (int)($_POST['duration_minutes'] ?? 120);
        $venue = trim($_POST['venue'] ?? '');
        $type = trim($_POST['type'] ?? 'internal');
        $maxMarks = (int)($_POST['max_marks'] ?? 100);

        if (empty($title) || empty($venue)) {
            $_SESSION['flash_danger'] = 'Exam Title and Venue are necessary fields.';
            header('Location: index.php?tab=exams');
            exit;
        }

        $stmt = $db->prepare("INSERT INTO exams (course_id, title, exam_date, start_time, duration_minutes, venue, type, max_marks, created_by) 
                              VALUES (:course_id, :title, :exam_date, :start_time, :duration, :venue, :type, :max_marks, :created_by)");
        $stmt->execute([
            ':course_id' => $courseId,
            ':title' => $title,
            ':exam_date' => $examDate,
            ':start_time' => date('H:i:s', strtotime($startTime)),
            ':duration' => $duration,
            ':venue' => $venue,
            ':type' => $type,
            ':max_marks' => $maxMarks,
            ':created_by' => $currentUser['id']
        ]);

        // Notify enrolled students in that course
        $cStmt = $db->prepare("SELECT title FROM courses WHERE id = :id");
        $cStmt->execute([':id' => $courseId]);
        $courseTitle = $cStmt->fetchColumn() ?: 'course';

        $enrStmt = $db->prepare("SELECT student_id FROM enrollments WHERE course_id = :c_id AND status = 'active'");
        $enrStmt->execute([':c_id' => $courseId]);
        $students = $enrStmt->fetchAll(PDO::FETCH_COLUMN);

        $notifStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, is_read) 
                                   VALUES (:student_id, 'New Exam Scheduled', :msg, 'danger', 0)");
        foreach ($students as $studentId) {
            $notifStmt->execute([
                ':student_id' => $studentId,
                ':msg' => "An exam has been scheduled for {$courseTitle}: \"{$title}\" on {$examDate}"
            ]);
        }

        $_SESSION['flash_success'] = 'Examination schedule published officially.';
        header('Location: index.php?tab=exams');
        exit;

    case 'publish_result':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?tab=exams');
            exit;
        }

        $studentId = (int)($_POST['student_id'] ?? 0);
        $courseId = (int)($_POST['course_id'] ?? 0);
        $examId = (int)($_POST['exam_id'] ?? 0);
        $intMarks = (float)($_POST['internal_marks'] ?? 0);
        $extMarks = (float)($_POST['external_marks'] ?? 0);
        $semester = (int)($_POST['semester'] ?? 3);

        $totalMarks = $intMarks + $extMarks;
        $grade = gradeFromMarks($totalMarks);
        $status = ($totalMarks >= 50.0) ? 'pass' : 'fail';

        $stmt = $db->prepare("INSERT INTO results (student_id, course_id, exam_id, internal_marks, external_marks, total_marks, grade, semester, status) 
                              VALUES (:student_id, :course_id, :exam_id, :int_marks, :ext_marks, :total_marks, :grade, :semester, :status) 
                              ON DUPLICATE KEY UPDATE 
                                  internal_marks = VALUES(internal_marks), 
                                  external_marks = VALUES(external_marks), 
                                  total_marks = VALUES(total_marks), 
                                  grade = VALUES(grade), 
                                  status = VALUES(status)");
        $stmt->execute([
            ':student_id' => $studentId,
            ':course_id' => $courseId,
            ':exam_id' => $examId,
            ':int_marks' => $intMarks,
            ':ext_marks' => $extMarks,
            ':total_marks' => $totalMarks,
            ':grade' => $grade,
            ':semester' => $semester,
            ':status' => $status
        ]);

        // Recalculate CGPA for the student
        $resStmt = $db->prepare("SELECT grade FROM results WHERE student_id = :student_id");
        $resStmt->execute([':student_id' => $studentId]);
        $grades = $resStmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($grades) > 0) {
            $cgpaSum = 0.0;
            foreach ($grades as $g) {
                $g = strtoupper($g);
                if (str_starts_with($g, 'A'))     $cgpaSum += 9.5;
                elseif ($g === 'B+')              $cgpaSum += 8.5;
                elseif ($g === 'B')               $cgpaSum += 7.5;
                elseif ($g === 'C')               $cgpaSum += 6.5;
                elseif ($g === 'D')               $cgpaSum += 5.5;
                else                              $cgpaSum += 0.0;
            }
            $finalCgpa = round($cgpaSum / count($grades), 2);
            $spStmt = $db->prepare("UPDATE student_profiles SET cgpa = :cgpa WHERE user_id = :student_id");
            $spStmt->execute([':cgpa' => $finalCgpa, ':student_id' => $studentId]);
        }

        // Notify student
        $cStmt = $db->prepare("SELECT title FROM courses WHERE id = :id");
        $cStmt->execute([':id' => $courseId]);
        $courseTitle = $cStmt->fetchColumn() ?: 'Course';

        $notifStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, is_read) 
                                   VALUES (:student_id, 'Exam Result Published', :msg, 'success', 0)");
        $notifStmt->execute([
            ':student_id' => $studentId,
            ':msg' => "Your results for {$courseTitle} have been published. Grade: \"{$grade}\""
        ]);

        $_SESSION['flash_success'] = 'Student examination results published onto grade book.';
        header('Location: index.php?tab=exams');
        exit;

    case 'add_student':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?tab=students');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $rollNo = trim($_POST['roll_no'] ?? '');
        $deptId = (int)($_POST['department_id'] ?? 1);
        $year = (int)($_POST['year'] ?? 1);
        $semester = (int)($_POST['semester'] ?? 1);
        $dob = trim($_POST['dob'] ?? '');
        $address = trim($_POST['address'] ?? '');

        // Create User
        $userStmt = $db->prepare("INSERT INTO users (name, email, password, role, avatar, phone, status) 
                                  VALUES (:name, :email, 'student123', 'student', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150', :phone, 'active')");
        $userStmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone
        ]);
        $newUserId = $db->lastInsertId();

        // Create Profile
        $profStmt = $db->prepare("INSERT INTO student_profiles (user_id, roll_no, department_id, year, semester, cgpa, dob, address) 
                                  VALUES (:user_id, :roll_no, :dept_id, :year, :semester, 0.00, :dob, :address)");
        $profStmt->execute([
            ':user_id' => $newUserId,
            ':roll_no' => $rollNo,
            ':dept_id' => $deptId,
            ':year' => $year,
            ':semester' => $semester,
            ':dob' => $dob ?: null,
            ':address' => $address
        ]);

        // Send Notification
        $notifStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, is_read) 
                                   VALUES (:user_id, 'Welcome to EduTrack LMS!', :msg, 'success', 0)");
        $notifStmt->execute([
            ':user_id' => $newUserId,
            ':msg' => 'Hi there! Your official student account is created. Start tracking your attendance, courses curriculum schedules, and placements alerts in real-time.'
        ]);

        $_SESSION['flash_success'] = 'New student official profile initialized successfully.';
        header('Location: index.php?tab=students');
        exit;

    case 'edit_student':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?tab=students');
            exit;
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $rollNo = trim($_POST['roll_no'] ?? '');
        $deptId = (int)($_POST['department_id'] ?? 1);
        $year = (int)($_POST['year'] ?? 1);
        $semester = (int)($_POST['semester'] ?? 1);
        $dob = trim($_POST['dob'] ?? '');
        $address = trim($_POST['address'] ?? '');

        // Update User
        $userStmt = $db->prepare("UPDATE users SET name = :name, email = :email, phone = :phone WHERE id = :id");
        $userStmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':id' => $userId
        ]);

        // Update Profile
        $profStmt = $db->prepare("UPDATE student_profiles 
                                  SET roll_no = :roll_no, department_id = :dept_id, year = :year, semester = :semester, dob = :dob, address = :address 
                                  WHERE user_id = :user_id");
        $profStmt->execute([
            ':roll_no' => $rollNo,
            ':dept_id' => $deptId,
            ':year' => $year,
            ':semester' => $semester,
            ':dob' => $dob ?: null,
            ':address' => $address,
            ':user_id' => $userId
        ]);

        $_SESSION['flash_success'] = 'Student information registry details saved successfully.';
        header('Location: index.php?tab=students');
        exit;

    case 'delete_student':
        $userId = (int)($_GET['user_id'] ?? 0);
        if ($userId > 0) {
            $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $_SESSION['flash_danger'] = 'Profile removed completely.';
        }
        header('Location: index.php?tab=students');
        exit;

    case 'add_teacher':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?tab=teachers');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $empId = trim($_POST['employee_id'] ?? '');
        $deptId = (int)($_POST['department_id'] ?? 1);
        $designation = trim($_POST['designation'] ?? '');
        $qualification = trim($_POST['qualification'] ?? '');

        // Create User
        $userStmt = $db->prepare("INSERT INTO users (name, email, password, role, avatar, phone, status) 
                                  VALUES (:name, :email, 'teacher123', 'teacher', 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150', :phone, 'active')");
        $userStmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone
        ]);
        $newUserId = $db->lastInsertId();

        // Create Profile
        $profStmt = $db->prepare("INSERT INTO teacher_profiles (user_id, employee_id, department_id, designation, qualification) 
                                  VALUES (:user_id, :emp_id, :dept_id, :designation, :qualification)");
        $profStmt->execute([
            ':user_id' => $newUserId,
            ':emp_id' => $empId,
            ':dept_id' => $deptId,
            ':designation' => $designation,
            ':qualification' => $qualification
        ]);

        $_SESSION['flash_success'] = 'New professor profile deployed successfully.';
        header('Location: index.php?tab=teachers');
        exit;

    case 'edit_teacher':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?tab=teachers');
            exit;
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $empId = trim($_POST['employee_id'] ?? '');
        $deptId = (int)($_POST['department_id'] ?? 1);
        $designation = trim($_POST['designation'] ?? '');
        $qualification = trim($_POST['qualification'] ?? '');

        // Update User
        $userStmt = $db->prepare("UPDATE users SET name = :name, email = :email, phone = :phone WHERE id = :id");
        $userStmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':id' => $userId
        ]);

        // Update Profile
        $profStmt = $db->prepare("UPDATE teacher_profiles 
                                  SET employee_id = :emp_id, department_id = :dept_id, designation = :designation, qualification = :qualification 
                                  WHERE user_id = :user_id");
        $profStmt->execute([
            ':emp_id' => $empId,
            ':dept_id' => $deptId,
            ':designation' => $designation,
            ':qualification' => $qualification,
            ':user_id' => $userId
        ]);

        $_SESSION['flash_success'] = 'Teacher credentials updated.';
        header('Location: index.php?tab=teachers');
        exit;

    case 'delete_teacher':
        $userId = (int)($_GET['user_id'] ?? 0);
        if ($userId > 0) {
            $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $_SESSION['flash_danger'] = 'Teacher profile deleted.';
        }
        header('Location: index.php?tab=teachers');
        exit;

    case 'add_department':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?tab=departments');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $headId = $_POST['head_id'] ?? null;
        if ($headId === '') $headId = null;

        $stmt = $db->prepare("INSERT INTO departments (name, code, head_id) VALUES (:name, :code, :head_id)");
        $stmt->execute([
            ':name' => $name,
            ':code' => $code,
            ':head_id' => $headId
        ]);

        $_SESSION['flash_success'] = 'New academic department initialized.';
        header('Location: index.php?tab=departments');
        exit;

    case 'edit_department':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?tab=departments');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $headId = $_POST['head_id'] ?? null;
        if ($headId === '') $headId = null;

        $stmt = $db->prepare("UPDATE departments SET name = :name, code = :code, head_id = :head_id WHERE id = :id");
        $stmt->execute([
            ':name' => $name,
            ':code' => $code,
            ':head_id' => $headId,
            ':id' => $id
        ]);

        $_SESSION['flash_success'] = 'Department attributes updated.';
        header('Location: index.php?tab=departments');
        exit;

    case 'add_course':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?tab=courses');
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $deptId = (int)($_POST['department_id'] ?? 1);
        $teacherId = (int)($_POST['teacher_id'] ?? 1);
        $credits = (int)($_POST['credits'] ?? 3);
        $semester = (int)($_POST['semester'] ?? 1);
        $description = trim($_POST['description'] ?? '');

        $stmt = $db->prepare("INSERT INTO courses (title, code, department_id, teacher_id, credits, semester, description, status) 
                              VALUES (:title, :code, :dept_id, :teacher_id, :credits, :semester, :description, 'active')");
        $stmt->execute([
            ':title' => $title,
            ':code' => $code,
            ':dept_id' => $deptId,
            ':teacher_id' => $teacherId,
            ':credits' => $credits,
            ':semester' => $semester,
            ':description' => $description
        ]);

        $_SESSION['flash_success'] = 'Course curriculum deployed to active term catalogs.';
        header('Location: index.php?tab=courses');
        exit;

    case 'edit_course':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?tab=courses');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $deptId = (int)($_POST['department_id'] ?? 1);
        $teacherId = (int)($_POST['teacher_id'] ?? 1);
        $credits = (int)($_POST['credits'] ?? 3);
        $semester = (int)($_POST['semester'] ?? 1);
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';

        $stmt = $db->prepare("UPDATE courses 
                              SET title = :title, code = :code, department_id = :dept_id, teacher_id = :teacher_id, credits = :credits, semester = :semester, description = :description, status = :status 
                              WHERE id = :id");
        $stmt->execute([
            ':title' => $title,
            ':code' => $code,
            ':dept_id' => $deptId,
            ':teacher_id' => $teacherId,
            ':credits' => $credits,
            ':semester' => $semester,
            ':description' => $description,
            ':status' => $status,
            ':id' => $id
        ]);

        $_SESSION['flash_success'] = 'Course catalog details modified.';
        header('Location: index.php?tab=courses');
        exit;

    case 'add_enrollment':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?tab=courses');
            exit;
        }

        $studentId = (int)($_POST['student_id'] ?? 0);
        $courseId = (int)($_POST['course_id'] ?? 0);

        // Check duplicate
        $checkStmt = $db->prepare("SELECT id FROM enrollments WHERE student_id = :s_id AND course_id = :c_id");
        $checkStmt->execute([':s_id' => $studentId, ':c_id' => $courseId]);
        if ($checkStmt->fetch()) {
            $_SESSION['flash_danger'] = 'Student is already enrolled in this course';
            header('Location: index.php?tab=courses');
            exit;
        }

        $stmt = $db->prepare("INSERT INTO enrollments (student_id, course_id, status) VALUES (:s_id, :c_id, 'active')");
        $stmt->execute([':s_id' => $studentId, ':c_id' => $courseId]);

        // Notify student about enrollment
        $cStmt = $db->prepare("SELECT title, code FROM courses WHERE id = :id");
        $cStmt->execute([':id' => $courseId]);
        $cObj = $cStmt->fetch();

        if ($cObj) {
            $notifStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, is_read) 
                                       VALUES (:student_id, 'Enrolled in New Course', :msg, 'success', 0)");
            $notifStmt->execute([
                ':student_id' => $studentId,
                ':msg' => "You have been officially enrolled in course: \"{$cObj['title']}\" ({$cObj['code']})"
            ]);
        }

        $_SESSION['flash_success'] = 'Enrolled successfully!';
        header('Location: index.php?tab=courses');
        exit;

    case 'remove_enrollment':
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM enrollments WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $_SESSION['flash_danger'] = 'Enrollment deleted.';
        }
        header('Location: index.php?tab=courses');
        exit;

    case 'add_placement':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?tab=placements');
            exit;
        }

        $company = trim($_POST['company'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $type = trim($_POST['type'] ?? 'internship');
        $location = trim($_POST['location'] ?? '');
        $stipend = trim($_POST['stipend'] ?? '');
        $eligibility = trim($_POST['eligibility'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $deadline = trim($_POST['deadline'] ?? '');

        $stmt = $db->prepare("INSERT INTO placements (company, role, type, location, stipend, eligibility, description, deadline, posted_by, status) 
                              VALUES (:company, :role, :type, :location, :stipend, :eligibility, :description, :deadline, :posted_by, 'open')");
        $stmt->execute([
            ':company' => $company,
            ':role' => $role,
            ':type' => $type,
            ':location' => $location,
            ':stipend' => $stipend,
            ':eligibility' => $eligibility,
            ':description' => $description,
            ':deadline' => $deadline,
            ':posted_by' => $currentUser['id']
        ]);

        // Notify active students
        $stuStmt = $db->prepare("SELECT id FROM users WHERE role = 'student' AND status = 'active'");
        $stuStmt->execute();
        $studentIds = $stuStmt->fetchAll(PDO::FETCH_COLUMN);

        $notifStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, is_read) 
                                   VALUES (:student_id, 'New Placement Opportunity', :msg, 'info', 0)");
        foreach ($studentIds as $studentId) {
            $notifStmt->execute([
                ':student_id' => $studentId,
                ':msg' => "New opening posted at {$company}: \"{$role}\". Deadline to apply: {$deadline}"
            ]);
        }

        $_SESSION['flash_success'] = 'New vacancy opening advertised to student terminals.';
        header('Location: index.php?tab=placements');
        exit;

    case 'update_placement_status':
        $placeId = (int)($_GET['placement_id'] ?? 0);
        $status = $_GET['status'] ?? 'open';

        if ($placeId > 0 && in_array($status, ['open', 'closed'])) {
            $stmt = $db->prepare("UPDATE placements SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $status, ':id' => $placeId]);
            $_SESSION['flash_success'] = 'Placement opening status updated.';
        }
        header('Location: index.php?tab=placements');
        exit;

    case 'update_placement_app_status':
        $appId = (int)($_GET['app_id'] ?? 0);
        $status = $_GET['status'] ?? 'applied';

        $allowed = ['applied', 'shortlisted', 'interview', 'offered', 'rejected'];
        if ($appId > 0 && in_array($status, $allowed)) {
            $stmt = $db->prepare("UPDATE placement_applications SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $status, ':id' => $appId]);

            // Notify student
            $appStmt = $db->prepare("SELECT pa.student_id, p.role, p.company 
                                     FROM placement_applications pa 
                                     JOIN placements p ON pa.placement_id = p.id 
                                     WHERE pa.id = :id");
            $appStmt->execute([':id' => $appId]);
            $application = $appStmt->fetch();

            if ($application) {
                $type = 'warning';
                if ($status === 'offered') $type = 'success';
                if ($status === 'rejected') $type = 'danger';

                $notifStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, is_read) 
                                           VALUES (:student_id, 'Placement Status Updated', :msg, :type, 0)");
                $notifStmt->execute([
                    ':student_id' => $application['student_id'],
                    ':msg' => "Your application for \"{$application['role']}\" at {$application['company']} status has been updated to \"" . strtoupper($status) . "\"",
                    ':type' => $type
                ]);
            }

            $_SESSION['flash_success'] = 'Placement application status updated.';
        }
        header('Location: index.php?tab=placements');
        exit;

    case 'send_announcement':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?tab=dashboard');
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $audience = trim($_POST['audience'] ?? 'all');
        $deptId = $_POST['department_id'] ?? null;
        if ($deptId === '') $deptId = null;

        if (empty($title) || empty($body)) {
            $_SESSION['flash_danger'] = 'Announcement fields cannot be empty.';
            header('Location: index.php?tab=announcements');
            exit;
        }

        $stmt = $db->prepare("INSERT INTO announcements (title, body, audience, department_id, created_by) 
                              VALUES (:title, :body, :audience, :dept_id, :created_by)");
        $stmt->execute([
            ':title' => $title,
            ':body' => $body,
            ':audience' => $audience,
            ':dept_id' => $deptId,
            ':created_by' => $currentUser['id']
        ]);

        // Get target users list
        $targetUserQuery = "SELECT id FROM users WHERE status = 'active'";
        $params = [];

        if ($audience === 'students') {
            $targetUserQuery .= " AND role = 'student'";
        } elseif ($audience === 'teachers') {
            $targetUserQuery .= " AND role = 'teacher'";
        } elseif ($audience === 'department' && $deptId !== null) {
            $targetUserQuery = "
                SELECT u.id FROM users u
                LEFT JOIN student_profiles sp ON u.id = sp.user_id AND u.role = 'student'
                LEFT JOIN teacher_profiles tp ON u.id = tp.user_id AND u.role = 'teacher'
                WHERE u.status = 'active' AND (sp.department_id = :dept_id1 OR tp.department_id = :dept_id2)
            ";
            $params = [':dept_id1' => $deptId, ':dept_id2' => $deptId];
        }

        $usersStmt = $db->prepare($targetUserQuery);
        $usersStmt->execute($params);
        $userIds = $usersStmt->fetchAll(PDO::FETCH_COLUMN);

        // Notify target users
        $notifStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, is_read) 
                                   VALUES (:user_id, 'New Announcement Posted', :msg, 'info', 0)");
        $shortBody = mb_substr($body, 0, 80) . '...';
        foreach ($userIds as $uId) {
            $notifStmt->execute([
                ':user_id' => $uId,
                ':msg' => "Announcement: \"{$title}\" - {$shortBody}"
            ]);
        }

        $_SESSION['flash_success'] = 'Push announcements broadcast is online.';
        header('Location: index.php?tab=dashboard');
        exit;

    case 'mark_notification_read':
        $notifId = (int)($_GET['id'] ?? 0);
        if ($notifId > 0) {
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id");
            $stmt->execute([':id' => $notifId, ':user_id' => $currentUser['id']]);
        }
        // Redirect back to same page they were on
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit;

    case 'clear_notifications':
        $stmt = $db->prepare("DELETE FROM notifications WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $currentUser['id']]);
        $_SESSION['flash_success'] = 'Notifications cleared.';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit;

    case 'edit_profile':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?tab=profile');
            exit;
        }

        if ($currentUser['role'] === 'student') {
            // Fetch student profile record first
            $spStmt = $db->prepare("SELECT * FROM student_profiles WHERE user_id = :user_id");
            $spStmt->execute([':user_id' => $currentUser['id']]);
            $studentProfile = $spStmt->fetch();

            if (!$studentProfile) {
                $_SESSION['flash_danger'] = 'Student profile not found in database.';
                header('Location: index.php?tab=profile');
                exit;
            }
            $profileId = $studentProfile['id'];

            // 1. Retrieve all POST data
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $dob = trim($_POST['dob'] ?? '');
            $gender = trim($_POST['gender'] ?? '');
            $religion = trim($_POST['religion'] ?? '');
            $caste = trim($_POST['caste'] ?? '');
            $nationality = trim($_POST['nationality'] ?? '');
            $blood_group = trim($_POST['blood_group'] ?? '');
            $aadhaar_number = trim($_POST['aadhaar_number'] ?? '');
            
            $father_name = trim($_POST['father_name'] ?? '');
            $father_phone = trim($_POST['father_phone'] ?? '');
            $father_email = trim($_POST['father_email'] ?? '');
            $father_occupation = trim($_POST['father_occupation'] ?? '');
            
            $mother_name = trim($_POST['mother_name'] ?? '');
            $mother_phone = trim($_POST['mother_phone'] ?? '');
            $mother_email = trim($_POST['mother_email'] ?? '');
            $mother_occupation = trim($_POST['mother_occupation'] ?? '');
            
            $annual_income = trim($_POST['annual_income'] ?? '');
            $present_address = trim($_POST['present_address'] ?? '');
            $permanent_address = trim($_POST['permanent_address'] ?? '');

            // 2. Server-side validation
            $errors = [];
            if (empty($name)) {
                $errors[] = 'Full Name is required.';
            }
            if (empty($phone)) {
                $errors[] = 'Mobile Contact Phone is required.';
            }
            if (!empty($aadhaar_number) && !preg_match('/^\d{12}$/', $aadhaar_number)) {
                $errors[] = 'Aadhaar Number must be exactly 12 digits.';
            }
            if (!empty($father_email) && !filter_var($father_email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Father\'s Email must be a valid email address.';
            }
            if (!empty($mother_email) && !filter_var($mother_email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Mother\'s Email must be a valid email address.';
            }
            if (!empty($father_phone) && !preg_match('/^\+?[0-9\s\-]{10,15}$/', $father_phone)) {
                $errors[] = 'Father\'s Phone must be a valid phone number (10-15 digits).';
            }
            if (!empty($mother_phone) && !preg_match('/^\+?[0-9\s\-]{10,15}$/', $mother_phone)) {
                $errors[] = 'Mother\'s Phone must be a valid phone number (10-15 digits).';
            }
            if ($annual_income !== '' && (!is_numeric($annual_income) || floatval($annual_income) < 0)) {
                $errors[] = 'Annual Family Income must be a positive number.';
            }

            // 3. Document File Validation
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
            $allowedMimes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
            $maxFileSize = 10 * 1024 * 1024; // 10MB limit

            $documentKeys = [
                '10th_certificate',
                'transfer_certificate',
                'caste_certificate',
                'plus_two_certificate'
            ];

            $uploadedDocs = [];
            foreach ($documentKeys as $docKey) {
                if (isset($_FILES[$docKey]) && $_FILES[$docKey]['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES[$docKey];
                    
                    // Validate file size
                    if ($file['size'] > $maxFileSize) {
                        $errors[] = ucwords(str_replace('_', ' ', $docKey)) . ' exceeds 10MB size limit.';
                        continue;
                    }
                    
                    // Validate extension
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExtensions)) {
                        $errors[] = ucwords(str_replace('_', ' ', $docKey)) . ' must be PDF, JPG, JPEG, or PNG format.';
                        continue;
                    }
                    
                    // Validate MIME type
                    $mime = '';
                    if (function_exists('mime_content_type')) {
                        $mime = mime_content_type($file['tmp_name']);
                    }
                    if (empty($mime)) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        if ($finfo) {
                            $mime = finfo_file($finfo, $file['tmp_name']);
                            finfo_close($finfo);
                        }
                    }
                    if (!empty($mime) && !in_array($mime, $allowedMimes)) {
                        $errors[] = ucwords(str_replace('_', ' ', $docKey)) . ' has an invalid file type.';
                        continue;
                    }
                    
                    $uploadedDocs[$docKey] = $file;
                }
            }

            // If there are errors, save them and redirect back
            if (!empty($errors)) {
                $_SESSION['profile_edit_failed'] = true;
                $_SESSION['flash_danger'] = implode('<br>', $errors);
                header('Location: index.php?tab=profile');
                exit;
            }

            // 4. Save uploaded files securely
            $docsDir = __DIR__ . '/uploads/documents/';
            if (!file_exists($docsDir)) {
                mkdir($docsDir, 0777, true);
            }

            foreach ($uploadedDocs as $docType => $file) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $safeName = 'student_' . $profileId . '_' . $docType . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $targetPath = $docsDir . $safeName;
                
                // Get existing file to remove it
                $existingStmt = $db->prepare("SELECT file_path FROM student_documents WHERE student_profile_id = :profile_id AND document_type = :doc_type");
                $existingStmt->execute([
                    ':profile_id' => $profileId,
                    ':doc_type' => $docType
                ]);
                $oldFile = $existingStmt->fetchColumn();

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    if ($oldFile) {
                        $oldFilePath = $docsDir . basename($oldFile);
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }
                    }
                    
                    // Insert or replace record in DB
                    $docInsert = $db->prepare("
                        INSERT INTO student_documents (student_profile_id, document_type, file_path, uploaded_at)
                        VALUES (:profile_id, :doc_type, :file_path, NOW())
                        ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), uploaded_at = NOW()
                    ");
                    $docInsert->execute([
                        ':profile_id' => $profileId,
                        ':doc_type' => $docType,
                        ':file_path' => $safeName
                    ]);
                } else {
                    $_SESSION['flash_danger'] = 'Could not save file upload: ' . $docType;
                    header('Location: index.php?tab=profile');
                    exit;
                }
            }

            // 5. Update user and profile table
            $uStmt = $db->prepare("UPDATE users SET name = :name, phone = :phone WHERE id = :id");
            $uStmt->execute([
                ':name' => $name,
                ':phone' => $phone,
                ':id' => $currentUser['id']
            ]);

            $pStmt = $db->prepare("
                UPDATE student_profiles SET 
                    dob = :dob, 
                    address = :permanent_address,
                    gender = :gender,
                    religion = :religion,
                    caste = :caste,
                    nationality = :nationality,
                    blood_group = :blood_group,
                    aadhaar_number = :aadhaar_number,
                    father_name = :father_name,
                    father_phone = :father_phone,
                    father_email = :father_email,
                    father_occupation = :father_occupation,
                    mother_name = :mother_name,
                    mother_phone = :mother_phone,
                    mother_email = :mother_email,
                    mother_occupation = :mother_occupation,
                    annual_income = :annual_income,
                    present_address = :present_address
                WHERE user_id = :user_id
            ");
            $pStmt->execute([
                ':dob' => $dob ?: null,
                ':permanent_address' => $permanent_address ?: null,
                ':gender' => $gender ?: null,
                ':religion' => $religion ?: null,
                ':caste' => $caste ?: null,
                ':nationality' => $nationality ?: null,
                ':blood_group' => $blood_group ?: null,
                ':aadhaar_number' => $aadhaar_number ?: null,
                ':father_name' => $father_name ?: null,
                ':father_phone' => $father_phone ?: null,
                ':father_email' => $father_email ?: null,
                ':father_occupation' => $father_occupation ?: null,
                ':mother_name' => $mother_name ?: null,
                ':mother_phone' => $mother_phone ?: null,
                ':mother_email' => $mother_email ?: null,
                ':mother_occupation' => $mother_occupation ?: null,
                ':annual_income' => ($annual_income !== '') ? floatval($annual_income) : null,
                ':present_address' => $present_address ?: null,
                ':user_id' => $currentUser['id']
            ]);

        } else {
            // Logic for non-student profiles
            $phone = trim($_POST['phone'] ?? '');
            $dob = trim($_POST['dob'] ?? '');
            $address = trim($_POST['address'] ?? '');

            $uStmt = $db->prepare("UPDATE users SET phone = :phone WHERE id = :id");
            $uStmt->execute([':phone' => $phone, ':id' => $currentUser['id']]);

            if ($currentUser['role'] === 'teacher') {
                $pStmt = $db->prepare("UPDATE teacher_profiles SET dob = :dob, address = :address WHERE user_id = :user_id");
                // Wait, teacher_profiles doesn't have dob or address? Let's check original. Original was only:
                // if ($currentUser['role'] === 'student') { UPDATE student_profiles ... }
                // So original didn't update teacher profiles (since teacher profile table doesn't have dob/address).
                // Let's keep it safe.
            }
        }

        $_SESSION['flash_success'] = 'Institutional Profile details updated successfully.';
        header('Location: index.php?tab=profile');
        exit;

    default:
        $_SESSION['flash_danger'] = 'Invalid action requested.';
        header('Location: index.php');
        exit;
}
