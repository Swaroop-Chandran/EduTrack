<?php
// views/change_password_view.php
// Fullscreen Forced Change Password Screen

$flashDanger = $_SESSION['flash_danger'] ?? null;
unset($_SESSION['flash_danger']);
?>

<div class="w-full max-w-md bg-[#111827] border border-[#374151] rounded-2xl p-8 shadow-2xl space-y-6 text-left animate-fadeIn">
    <!-- Icon & Title -->
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-[#2563EB]/15 border border-[#2563EB]/35 rounded-xl flex items-center justify-center">
            <i data-lucide="shield-alert" class="w-6 h-6 text-[#2563EB]"></i>
        </div>
        <div>
            <h2 class="text-lg font-bold text-white tracking-tight">Security Setup Required</h2>
            <p class="text-xs text-slate-400">First-time login credential registration</p>
        </div>
    </div>

    <div class="text-xs text-slate-400 leading-relaxed bg-[#1F2937]/50 p-4 border border-[#374151] rounded-xl space-y-1.5">
        <p>To access your institutional academic profile, you must replace the administrator-provided temporary password with a secure personal password.</p>
    </div>

    <!-- Errors -->
    <?php if ($flashDanger): ?>
        <div class="p-3 bg-red-950/40 border border-red-900/50 rounded-lg text-red-400 text-xs flex items-start gap-2.5 font-mono">
            <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
            <div><?php echo htmlspecialchars($flashDanger); ?></div>
        </div>
    <?php endif; ?>

    <!-- Password form -->
    <form action="actions.php?action=force_change_password" method="POST" onsubmit="return validateForcePassword(event);" class="space-y-4 text-xs font-bold">
        <div>
            <label for="new_pw" class="block text-slate-400 mb-1.5 font-semibold">New Account Password</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i data-lucide="lock" class="w-4 h-4 text-slate-500"></i>
                </div>
                <input
                    id="new_pw"
                    name="new_password"
                    type="password"
                    placeholder="••••••••"
                    class="w-full pl-9 pr-4 py-2.5 bg-[#1F2937] border border-[#374151] rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-[#2563EB] focus:border-transparent transition-all font-mono"
                    required
                />
            </div>
        </div>

        <div>
            <label for="confirm_pw" class="block text-slate-400 mb-1.5 font-semibold">Confirm Password</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i data-lucide="lock-keyhole" class="w-4 h-4 text-slate-500"></i>
                </div>
                <input
                    id="confirm_pw"
                    name="confirm_password"
                    type="password"
                    placeholder="••••••••"
                    class="w-full pl-9 pr-4 py-2.5 bg-[#1F2937] border border-[#374151] rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-[#2563EB] focus:border-transparent transition-all font-mono"
                    required
                />
            </div>
        </div>

        <button
            type="submit"
            class="w-full py-2.5 px-4 bg-[#2563EB] hover:bg-[#1D4ED8] text-white rounded-xl font-bold tracking-wide transition-colors shadow-sm flex items-center justify-center gap-2 cursor-pointer mt-6"
        >
            <i data-lucide="shield-check" class="w-4 h-4"></i>
            <span>Register Secure Password</span>
        </button>
    </form>
</div>

<script>
    function validateForcePassword(e) {
        const newPw = document.getElementById('new_pw').value.trim();
        const confPw = document.getElementById('confirm_pw').value.trim();
        if (newPw.length < 6) {
            alert("Password must be at least 6 characters long.");
            e.preventDefault();
            return false;
        }
        if (newPw !== confPw) {
            alert("Passwords do not match. Please re-enter.");
            e.preventDefault();
            return false;
        }
        return true;
    }
</script>
