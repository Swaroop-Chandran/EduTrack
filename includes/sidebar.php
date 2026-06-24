<?php
// Sidebar Navigation Component

if (!isset($currentUser)) {
    return;
}

// Define nav items based on user role
function getNavItems(string $role): array {
    switch ($role) {
        case 'student':
            return [
                ['id' => 'dashboard', 'name' => 'Dashboard', 'icon' => 'layout-dashboard'],
                ['id' => 'courses', 'name' => 'My Courses', 'icon' => 'book-open'],
                ['id' => 'assignments', 'name' => 'Assignments', 'icon' => 'clipboard-list'],
                ['id' => 'exams', 'name' => 'Upcoming Exams', 'icon' => 'calendar-clock'],
                ['id' => 'results', 'name' => 'My Results', 'icon' => 'file-bar-chart-2'],
                ['id' => 'attendance', 'name' => 'Attendance Record', 'icon' => 'check-square'],
                ['id' => 'placements', 'name' => 'Placement Cell', 'icon' => 'briefcase'],
                ['id' => 'profile', 'name' => 'My Profile', 'icon' => 'user-square-2']
            ];
        case 'teacher':
            return [
                ['id' => 'dashboard', 'name' => 'Dashboard', 'icon' => 'layout-dashboard'],
                ['id' => 'courses', 'name' => 'My Courses', 'icon' => 'book-open'],
                ['id' => 'assignments', 'name' => 'Assignments', 'icon' => 'clipboard-list'],
                ['id' => 'attendance', 'name' => 'Take Attendance', 'icon' => 'check-square'],
                ['id' => 'exams', 'name' => 'Exams & Results', 'icon' => 'calendar-clock'],
                ['id' => 'analytics', 'name' => 'Course Analytics', 'icon' => 'line-chart'],
                ['id' => 'profile', 'name' => 'Teacher Profile', 'icon' => 'user-square-2']
            ];
        case 'admin':
            return [
                ['id' => 'dashboard', 'name' => 'LMS Insights', 'icon' => 'layout-dashboard'],
                ['id' => 'students', 'name' => 'Students Registry', 'icon' => 'users-2'],
                ['id' => 'teachers', 'name' => 'Teachers Registry', 'icon' => 'graduation-cap'],
                ['id' => 'departments', 'name' => 'Departments', 'icon' => 'building-2'],
                ['id' => 'courses', 'name' => 'Courses Config', 'icon' => 'book-open'],
                ['id' => 'placements', 'name' => 'Placement Cell', 'icon' => 'briefcase'],
                ['id' => 'announcements', 'name' => 'Announcements', 'icon' => 'megaphone'],
                ['id' => 'analytics', 'name' => 'Campus Analytics', 'icon' => 'activity']
            ];
        default:
            return [];
    }
}

$navItems = getNavItems($currentUser['role']);

// Role Badge styling
$badgeColor = 'bg-gray-500/20 text-gray-400';
if ($currentUser['role'] === 'student') $badgeColor = 'bg-blue-500/20 text-blue-400';
if ($currentUser['role'] === 'teacher') $badgeColor = 'bg-emerald-500/20 text-emerald-400';
if ($currentUser['role'] === 'admin')   $badgeColor = 'bg-amber-500/20 text-amber-500';
?>

<aside id="sidebar_container" class="fixed left-0 top-0 h-screen bg-[#0F172A] text-white shadow-xl flex flex-col z-50 transition-all duration-300 w-[240px]">
    <!-- Header Wordmark branding -->
    <div class="p-6 flex items-center justify-between border-b border-white/10 shrink-0">
        <div class="flex items-center gap-3 overflow-hidden">
            <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center text-white shrink-0 font-black">
                E
            </div>
            <div class="sidebar-text flex flex-col">
                <span class="font-bold text-sm tracking-tight text-white leading-tight">EduTrack LMS</span>
                <span class="text-[10px] text-gray-400 uppercase tracking-widest font-semibold mt-0.5">Enterprise</span>
            </div>
        </div>
        
        <button onclick="toggleSidebar();" class="text-gray-400 hover:text-white p-1 rounded-md hover:bg-white/5">
            <i id="sidebar_arrow_icon" data-lucide="chevron-left" class="w-4 h-4"></i>
        </button>
    </div>

    <!-- Nav Link Lists container -->
    <nav class="flex-1 py-6 space-y-1 block h-fit overflow-y-auto px-3">
        <?php foreach ($navItems as $item): 
            $isActive = ($currentTab === $item['id']);
            $activeClass = $isActive 
                ? 'bg-primary text-white font-semibold' 
                : 'text-[#94A3B8] hover:text-white hover:bg-white/5';
        ?>
            <a href="?tab=<?php echo urlencode($item['id']); ?>" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 relative <?php echo $activeClass; ?>">
                <i data-lucide="<?php echo $item['icon']; ?>" class="w-5 h-5 flex-shrink-0"></i>
                <span class="sidebar-text truncate tracking-wide"><?php echo htmlspecialchars($item['name']); ?></span>
                <?php if ($isActive): ?>
                    <div class="sidebar-collapsed-indicator absolute right-0 top-1/2 -translate-y-1/2 w-1.5 h-8 bg-white rounded-l-md hidden"></div>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Footer Profile badge & logout -->
    <div class="p-4 border-t border-white/10 space-y-4 shrink-0 bg-black/10">
        <div class="flex items-center gap-3 overflow-hidden">
            <img src="<?php echo htmlspecialchars($currentUser['avatar']); ?>" alt="<?php echo htmlspecialchars($currentUser['name']); ?>" class="w-10 h-10 rounded-full border border-white/10 object-cover shrink-0 bg-white/20" />
            <div class="sidebar-text flex flex-col min-w-0 flex-1">
                <span class="font-semibold text-xs text-white truncate leading-tight">
                    <?php echo htmlspecialchars($currentUser['name']); ?>
                </span>
                <span class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-full mt-1 w-max <?php echo $badgeColor; ?>">
                    <?php echo htmlspecialchars($currentUser['role']); ?>
                </span>
            </div>
        </div>

        <form action="actions.php?action=logout" method="POST" class="w-full">
            <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2 border border-white/10 hover:border-red-500/40 text-[#94A3B8] hover:text-red-500 rounded-lg text-xs font-semibold bg-white/5 hover:bg-red-500/5 transition-all cursor-pointer">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span class="sidebar-text">Sign Out Session</span>
            </button>
        </form>
    </div>
</aside>

<script>
function toggleSidebar() {
    const isExpanded = localStorage.getItem('sidebar_expanded') !== 'false';
    localStorage.setItem('sidebar_expanded', !isExpanded);
    applySidebarState();
}

function applySidebarState() {
    const isExpanded = localStorage.getItem('sidebar_expanded') !== 'false';
    const sidebar = document.getElementById('sidebar_container');
    const mainContent = document.getElementById('main_content_container');
    const header = document.getElementById('header_container');
    const texts = document.querySelectorAll('.sidebar-text');
    const collapsedIndicators = document.querySelectorAll('.sidebar-collapsed-indicator');
    const arrowIcon = document.getElementById('sidebar_arrow_icon');

    if (isExpanded) {
        // Expanded layout
        sidebar.style.width = '240px';
        if (mainContent) mainContent.style.paddingLeft = '240px';
        if (header) header.style.paddingLeft = '256px';
        texts.forEach(el => el.style.display = 'flex');
        collapsedIndicators.forEach(el => el.style.display = 'none');
        if (arrowIcon) arrowIcon.setAttribute('data-lucide', 'chevron-left');
    } else {
        // Collapsed layout
        sidebar.style.width = '68px';
        if (mainContent) mainContent.style.paddingLeft = '68px';
        if (header) header.style.paddingLeft = '84px';
        texts.forEach(el => el.style.display = 'none');
        collapsedIndicators.forEach(el => el.style.display = 'block');
        if (arrowIcon) arrowIcon.setAttribute('data-lucide', 'chevron-right');
    }
    
    // Trigger Lucide refresh to show updated arrow icons correctly
    if (window.lucide) {
        window.lucide.createIcons();
    }
}

// Initial sizing on load
document.addEventListener('DOMContentLoaded', () => {
    // Check mobile screens
    if (window.innerWidth < 1024) {
        localStorage.setItem('sidebar_expanded', 'false');
    }
    applySidebarState();
});
</script>
