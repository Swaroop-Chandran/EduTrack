<?php
// Header Navigation Component

if (!isset($currentUser)) {
    return;
}

$db = Database::connect();

// Fetch notifications for current user
$notifStmt = $db->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC");
$notifStmt->execute([':user_id' => $currentUser['id']]);
$myNotifications = $notifStmt->fetchAll();

// Count unread
$unreadCount = 0;
foreach ($myNotifications as $notif) {
    if ((int)$notif['is_read'] === 0) {
        $unreadCount++;
    }
}

// Notification helpers
function getNotifClass(string $type): string {
    switch ($type) {
        case 'success': return 'bg-emerald-50 text-emerald-600';
        case 'warning': return 'bg-amber-50 text-amber-600';
        case 'danger': return 'bg-red-50 text-red-600';
        default: return 'bg-blue-50 text-blue-600';
    }
}

function getNotifIcon(string $type): string {
    switch ($type) {
        case 'success': return 'check-circle-2';
        case 'warning': return 'alert-triangle';
        case 'danger': return 'alert-circle';
        default: return 'info';
    }
}
?>

<header id="header_container" class="fixed top-0 right-0 h-14 bg-white border-b border-[#E2E8F0] flex items-center justify-between px-6 z-40 transition-all duration-300 left-0 w-full" style="padding-left: 256px;">
    <!-- Collapsible sidebar toggler & Page Title -->
    <div class="flex items-center gap-4">
        <button onclick="toggleSidebar();" class="p-1.5 rounded-lg text-[#64748B] hover:text-[#0F172A] hover:bg-gray-100 md:hidden">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
        <div class="hidden sm:flex items-center gap-2">
            <i data-lucide="graduation-cap" class="w-5 h-5 text-primary"></i>
            <h2 class="text-sm font-bold text-[#0F172A] tracking-tight"><?php echo htmlspecialchars($pageTitle); ?></h2>
        </div>
    </div>

    <!-- Right-side status displays and action widgets -->
    <div class="flex items-center gap-3">
        <!-- Dynamic System Clock Display -->
        <div class="hidden lg:flex items-center gap-1.5 px-3 py-1 bg-[#F1F5F9] rounded-full text-xs text-[#64748B] font-semibold border border-[#E2E8F0]">
            <i data-lucide="clock" class="w-3.5 h-3.5 text-primary"></i>
            <span id="system_utc_clock">UTC <?php echo date('Y-m-d'); ?></span>
        </div>

        <!-- Notifications dropdown menu -->
        <div class="relative" id="notifications_dropdown_wrapper">
            <button onclick="toggleNotificationsMenu(event);" class="w-10 h-10 rounded-full flex items-center justify-center text-[#64748B] hover:bg-[#F1F5F9] transition-all relative border border-transparent hover:border-[#E2E8F0] cursor-pointer">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="absolute top-1.5 right-1.5 w-4 h-4 bg-[#EF4444] text-white text-[9px] font-black rounded-full flex items-center justify-center border-2 border-white">
                        <?php echo $unreadCount; ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- Notifications Center Dropdown panel -->
            <div id="notifications_menu" class="hidden absolute right-0 mt-2 w-80 bg-white border border-[#E2E8F0] rounded-xl shadow-xl z-50 overflow-hidden animate-fadeIn select-none">
                <div class="px-4 py-3 bg-[#F8FAFC] border-b border-[#E2E8F0] flex items-center justify-between">
                    <span class="font-bold text-xs text-[#0F172A] uppercase tracking-wider">Notifications Center</span>
                    <?php if (count($myNotifications) > 0): ?>
                        <form action="actions.php?action=clear_notifications" method="POST" class="inline">
                            <button type="submit" class="text-[10px] text-primary font-bold hover:underline">Clear All</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="divide-y divide-[#E2E8F0] max-h-72 overflow-y-auto">
                    <?php if (count($myNotifications) === 0): ?>
                        <div class="p-6 text-center text-xs text-[#64748B] space-y-2">
                            <i data-lucide="inbox" class="w-8 h-8 text-gray-300 mx-auto"></i>
                            <p class="font-semibold">All caught up!</p>
                            <p class="text-[10px]">No unread alerts in database queue.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($myNotifications as $notif): 
                            $notifClass = getNotifClass($notif['type']);
                            $notifIcon = getNotifIcon($notif['type']);
                            $isUnread = ((int)$notif['is_read'] === 0);
                        ?>
                            <div onclick="markNotifRead(<?php echo $notif['id']; ?>);" class="p-3 text-left transition-colors cursor-pointer hover:bg-gray-50 flex gap-3 items-start relative <?php echo $isUnread ? 'bg-blue-50/20' : ''; ?>">
                                <div class="p-1.5 rounded-lg shrink-0 <?php echo $notifClass; ?>">
                                    <i data-lucide="<?php echo $notifIcon; ?>" class="w-4 h-4"></i>
                                </div>
                                <div class="flex-1 min-w-0 space-y-1">
                                    <p class="text-xs font-semibold text-[#0F172A] truncate">
                                        <?php echo htmlspecialchars($notif['title']); ?>
                                    </p>
                                    <p class="text-[11px] text-[#64748B] leading-relaxed">
                                        <?php echo htmlspecialchars($notif['message']); ?>
                                    </p>
                                    <span class="text-[9px] text-[#94A3B8] block">
                                        <?php echo date('H:i', strtotime($notif['created_at'])); ?>
                                    </span>
                                </div>
                                <?php if ($isUnread): ?>
                                    <span class="w-2 h-2 rounded-full bg-primary mt-2 shrink-0"></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Profile Avatar & options menu dropdown -->
        <div class="relative" id="profile_dropdown_wrapper">
            <button onclick="toggleProfileMenu(event);" class="flex items-center gap-2 hover:bg-[#F1F5F9] p-1 pr-3 rounded-full border border-transparent hover:border-[#E2E8F0] transition-colors cursor-pointer shrink-0">
                <img src="<?php echo htmlspecialchars($currentUser['avatar']); ?>" alt="<?php echo htmlspecialchars($currentUser['name']); ?>" class="w-8 h-8 rounded-full border border-white object-cover bg-gray-100" />
                <span class="hidden md:block font-semibold text-xs text-[#0F172A]">Account</span>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-[#64748B] hidden md:block"></i>
            </button>

            <!-- Profile dropdown options panel -->
            <div id="profile_menu" class="hidden absolute right-0 mt-2 w-56 bg-white border border-[#E2E8F0] rounded-xl shadow-xl z-50 overflow-hidden animate-fadeIn py-2">
                <div class="px-4 py-3 border-b border-[#E2E8F0]">
                    <p class="font-bold text-xs text-[#0F172A] truncate"><?php echo htmlspecialchars($currentUser['name']); ?></p>
                    <p class="text-[10px] text-[#64748B] truncate mt-0.5"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                </div>

                <div class="py-1 text-left">
                    <a href="?tab=profile" class="w-full text-left px-4 py-2 text-xs font-medium text-[#0F172A] hover:bg-gray-50 flex items-center gap-2">
                        <i data-lucide="user" class="w-4 h-4 text-[#64748B]"></i>
                        <span>My Profile Details</span>
                    </a>

                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <a href="?tab=settings" class="w-full text-left px-4 py-2 text-xs font-medium text-[#0F172A] hover:bg-gray-50 flex items-center gap-2">
                            <i data-lucide="sliders" class="w-4 h-4 text-[#64748B]"></i>
                            <span>LMS Admin Settings</span>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="border-t border-[#E2E8F0] pt-1">
                    <form action="actions.php?action=logout" method="POST" class="w-full">
                        <button type="submit" class="w-full text-left px-4 py-2.5 text-xs font-semibold text-[#EF4444] hover:bg-red-50 flex items-center gap-2">
                            <i data-lucide="log-out" class="w-4 h-4"></i>
                            <span>Logout Session</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
function toggleNotificationsMenu(e) {
    e.stopPropagation();
    const menu = document.getElementById('notifications_menu');
    const profileMenu = document.getElementById('profile_menu');
    menu.classList.toggle('hidden');
    profileMenu.classList.add('hidden');
}

function toggleProfileMenu(e) {
    e.stopPropagation();
    const menu = document.getElementById('profile_menu');
    const notifMenu = document.getElementById('notifications_menu');
    menu.classList.toggle('hidden');
    notifMenu.classList.add('hidden');
}

// Close dropdowns on clicking outside the wrapper
document.addEventListener('click', (e) => {
    const notifWrapper = document.getElementById('notifications_dropdown_wrapper');
    const profileWrapper = document.getElementById('profile_dropdown_wrapper');

    if (notifWrapper && !notifWrapper.contains(e.target)) {
        document.getElementById('notifications_menu').classList.add('hidden');
    }
    if (profileWrapper && !profileWrapper.contains(e.target)) {
        document.getElementById('profile_menu').classList.add('hidden');
    }
});

// Function to call the backend action and mark notification read
function markNotifRead(id) {
    const form = document.createElement('form');
    form.action = 'actions.php?action=mark_notification_read&id=' + id;
    form.method = 'POST';
    document.body.appendChild(form);
    form.submit();
}
</script>
