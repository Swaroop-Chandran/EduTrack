<?php
// Central App Entrypoint & Router
session_start();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

$db = Database::connect();

// 1. Authentication Check
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND status = 'active'");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $currentUser = $stmt->fetch();
}

// 2. Render Login if not logged in
if (!$currentUser) {
    include __DIR__ . '/views/login.php';
    exit;
}

// 3. Tab Routing Setup
$currentTab = $_GET['tab'] ?? 'dashboard';

// Fetch specific profile data
$studentProfile = null;
$teacherProfile = null;

if ($currentUser['role'] === 'student') {
    $spStmt = $db->prepare("SELECT sp.*, d.name as department_name, d.code as department_code 
                            FROM student_profiles sp 
                            LEFT JOIN departments d ON sp.department_id = d.id 
                            WHERE sp.user_id = :user_id");
    $spStmt->execute([':user_id' => $currentUser['id']]);
    $studentProfile = $spStmt->fetch();
} elseif ($currentUser['role'] === 'teacher') {
    $tpStmt = $db->prepare("SELECT tp.*, d.name as department_name, d.code as department_code 
                            FROM teacher_profiles tp 
                            LEFT JOIN departments d ON tp.department_id = d.id 
                            WHERE tp.user_id = :user_id");
    $tpStmt->execute([':user_id' => $currentUser['id']]);
    $teacherProfile = $tpStmt->fetch();
}

// Map page titles based on role and tab
function getPageTitle(string $tab, string $role): string {
    $titlesMap = [
        'dashboard'         => ($role === 'admin' ? 'Campus LMS Dashboard Insights' : 'My Academic Terminal Dashboard'),
        'courses'           => ($role === 'student' ? 'My Enrolled Curricula Courses' : 'Instructing Program schedules'),
        'assignments'       => ($role === 'student' ? 'Assignments & Homework tasks' : 'Issued homework deliverables'),
        'submit_assignment' => 'Upload Assignment deliverables',
        'exams'             => ($role === 'student' ? 'Upcoming Exam Schedules' : 'Campus Exam and results controller'),
        'results'           => 'Term Results transcripts details',
        'attendance'        => ($role === 'student' ? 'Live turnout records' : 'Mark active class roll-call'),
        'placements'        => 'Strategic Placement Cell recruitment',
        'profile'           => 'My Institutional Profile details',
        'students'          => 'Students Admissions and Roll configuration',
        'teachers'          => 'Faculty Professor and Instructor schedules',
        'departments'       => 'Academic bodies & sectors settings',
        'announcements'     => 'Push updates broadcaster',
        'analytics'         => 'Cumulative Campus metrics & score charts',
        'settings'          => 'Institutional Settings & SQL Backups'
    ];
    return $titlesMap[$tab] ?? 'EduTrack LMS Navigation Portal';
}

$pageTitle = getPageTitle($currentTab, $currentUser['role']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="EduTrack LMS - A comprehensive, high-fidelity Learning Management System for tracking student academic records, assignments, results, and placements.">
    <title>EduTrack LMS - Enterprise Learning Management System</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@450;500;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563EB',
                        'primary-hover': '#1D4ED8',
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                        mono: ['JetBrains Mono', 'SFMono-Regular', 'Consolas', 'monospace'],
                    }
                }
            }
        }
    </script>
    <!-- Style tweaks and scrollbars -->
    <style>
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #F1F5F9;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }
        @keyframes modalBackdropFadeIn {
            from { opacity: 0; backdrop-filter: blur(0px); background-color: rgba(15, 23, 42, 0); }
            to { opacity: 1; backdrop-filter: blur(4px); background-color: rgba(15, 23, 42, 0.3); }
        }
        @keyframes modalScaleIn {
            from { transform: scale(0.97); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-2px); }
            75% { transform: translateX(2px); }
        }
        .animate-fadeIn {
            animation: fadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .animate-shake {
            animation: shake 0.25s ease-in-out;
        }
        .animate-modalBackdrop {
            animation: modalBackdropFadeIn 0.18s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .animate-modalContent {
            animation: modalScaleIn 0.15s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>
    <!-- Lucide CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="h-screen bg-[#F8FAFC] text-[#0F172A] font-sans flex relative overflow-hidden">
    <!-- Left Navigation Sidebar -->
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <!-- Main Viewport Area Container -->
    <div id="main_content_container" class="flex-1 flex flex-col h-screen transition-all duration-300 w-full" style="padding-left: 240px; padding-top: 56px;">
        <!-- Top Nav Bar Header -->
        <?php include __DIR__ . '/includes/header.php'; ?>

        <!-- Dynamic Content Router Viewport -->
        <main class="flex-1 p-6 lg:p-8 overflow-y-auto block select-text">
            <div class="max-w-7xl mx-auto">
                <?php 
                // Display flash alerts if any exist in the session
                if (isset($_SESSION['flash_success'])) {
                    echo renderAlert($_SESSION['flash_success'], 'success');
                    unset($_SESSION['flash_success']);
                }
                if (isset($_SESSION['flash_danger'])) {
                    echo renderAlert($_SESSION['flash_danger'], 'danger');
                    unset($_SESSION['flash_danger']);
                }
                if (isset($_SESSION['flash_warning'])) {
                    echo renderAlert($_SESSION['flash_warning'], 'warning');
                    unset($_SESSION['flash_warning']);
                }
                if (isset($_SESSION['flash_info'])) {
                    echo renderAlert($_SESSION['flash_info'], 'info');
                    unset($_SESSION['flash_info']);
                }

                // Render matching role dashboard
                switch ($currentUser['role']) {
                    case 'student':
                        include __DIR__ . '/views/student_dashboard.php';
                        break;
                    case 'teacher':
                        include __DIR__ . '/views/teacher_dashboard.php';
                        break;
                    case 'admin':
                        include __DIR__ . '/views/admin_dashboard.php';
                        break;
                    default:
                        echo '<div class="flex items-center justify-center h-full p-12 text-center select-none text-slate-400">Unknown user authentication index logs.</div>';
                }
                ?>
            </div>
        </main>
    </div>

    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
