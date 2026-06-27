<?php
// views/activate_account_view.php
// Fullscreen Account Activation View matching the design theme

$flashDanger = $_SESSION['flash_danger'] ?? null;
unset($_SESSION['flash_danger']);
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activate Account - EduTrack LMS</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
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
            animation: fadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .animate-shake {
            animation: shake 0.25s ease-in-out;
        }
    </style>
</head>
<body class="bg-[#0F172A] text-slate-100 font-sans min-h-screen flex items-center justify-center p-4">
    <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(circle at 50% 50%, #2563EB 0%, transparent 60%);"></div>

    <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-8 space-y-6 relative z-10 animate-fadeIn">
        
        <!-- Logo Header -->
        <div class="flex flex-col items-center text-center space-y-3">
            <div class="w-12 h-12 bg-primary rounded-xl flex items-center justify-center shadow-lg">
                <i data-lucide="shield-check" class="w-7 h-7 text-white"></i>
            </div>
            <h1 class="text-xl font-extrabold tracking-tight text-white">EduTrack Registrar Activation</h1>
            <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider">SECURE DIGITAL ENTRY</p>
        </div>

        <!-- Session Alerts -->
        <?php if ($flashDanger): ?>
            <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 text-xs flex items-start gap-3 animate-shake">
                <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                <span class="font-medium"><?php echo htmlspecialchars($flashDanger); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($flashSuccess): ?>
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-lg text-emerald-400 text-xs flex items-start gap-3">
                <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                <span class="font-medium"><?php echo htmlspecialchars($flashSuccess); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($tokenUser): ?>
            <!-- Valid Token Setup form -->
            <div class="space-y-4">
                <div class="p-4 bg-slate-800/50 border border-slate-800 rounded-xl">
                    <span class="text-[9px] font-bold text-slate-400 block uppercase tracking-wider">Candidate Registry Found</span>
                    <span class="text-sm font-extrabold text-white block mt-0.5"><?php echo htmlspecialchars($tokenUser['name']); ?></span>
                    <span class="text-[10px] text-[#2563EB] font-bold block uppercase mt-1">Role Context: <?php echo htmlspecialchars(strtoupper($tokenUser['role'])); ?></span>
                </div>

                <form action="actions.php?action=complete_activation" method="POST" onsubmit="return validatePasswords(event);" class="space-y-4 text-xs font-bold text-slate-400">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>" />
                    
                    <div>
                        <label for="new_password" class="block mb-1.5 font-bold uppercase tracking-wider">Create Account Password</label>
                        <div class="relative">
                            <input
                                id="new_password"
                                name="new_password"
                                type="password"
                                placeholder="••••••••"
                                class="w-full px-3 py-2.5 bg-slate-800/80 border border-slate-700 rounded-lg text-sm text-white focus:outline-none focus:ring-1 focus:ring-[#2563EB] font-mono"
                                required
                            />
                            <button
                                type="button"
                                onclick="toggleVisibility('new_password', 'eye_1');"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-slate-300"
                            >
                                <i id="eye_1" data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="confirm_password" class="block mb-1.5 font-bold uppercase tracking-wider">Confirm Account Password</label>
                        <div class="relative">
                            <input
                                id="confirm_password"
                                name="confirm_password"
                                type="password"
                                placeholder="••••••••"
                                class="w-full px-3 py-2.5 bg-slate-800/80 border border-slate-700 rounded-lg text-sm text-white focus:outline-none focus:ring-1 focus:ring-[#2563EB] font-mono"
                                required
                            />
                            <button
                                type="button"
                                onclick="toggleVisibility('confirm_password', 'eye_2');"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-slate-300"
                            >
                                <i id="eye_2" data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <div id="client_error_msg" class="p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 text-xs hidden"></div>

                    <button
                        type="submit"
                        class="w-full py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-lg shadow-lg flex items-center justify-center gap-2 cursor-pointer transition-colors duration-150"
                    >
                        <i data-lucide="key" class="w-4 h-4"></i>
                        <span>Complete Activation & Sign In</span>
                    </button>
                </form>
            </div>

        <?php else: ?>
            <!-- Invalid / Expired Token form -->
            <div class="space-y-4">
                <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-center space-y-2">
                    <i data-lucide="alert-triangle" class="w-8 h-8 text-red-400 mx-auto"></i>
                    <h3 class="text-sm font-bold text-white">Activation Link Expired or Invalid</h3>
                    <p class="text-xs text-slate-400 leading-relaxed font-semibold">The one-time secure activation token is either missing, has already been used, or expired past the 24-hour limit. Request a new activation link below.</p>
                </div>

                <form action="actions.php?action=request_activation_link" method="POST" class="space-y-4 text-xs font-bold text-slate-400">
                    <div>
                        <label class="block mb-1.5 uppercase tracking-wider">Select Registry Context</label>
                        <select name="role" id="activate_role" onchange="togglePlaceholder();" class="w-full px-3 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-sm text-white focus:outline-none focus:ring-1 focus:ring-primary" required>
                            <option value="student">Student Scholar</option>
                            <option value="teacher">Faculty Professor</option>
                        </select>
                    </div>

                    <div>
                        <label id="identifier_label" class="block mb-1.5 uppercase tracking-wider">Admission Number</label>
                        <input
                            name="identifier"
                            id="activate_identifier"
                            type="text"
                            placeholder="e.g. ADM-2026-001"
                            class="w-full px-3 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-sm text-white focus:outline-none focus:ring-1 focus:ring-primary"
                            required
                        />
                    </div>

                    <button
                        type="submit"
                        class="w-full py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-lg shadow-lg flex items-center justify-center gap-2 cursor-pointer transition-colors duration-150"
                    >
                        <i data-lucide="mail" class="w-4 h-4"></i>
                        <span>Email New Activation Link</span>
                    </button>
                </form>

                <div class="text-center pt-2">
                    <a href="index.php" class="text-xs text-primary hover:underline font-bold">Back to Institutional Sign In</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleVisibility(inputId, eyeId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(eyeId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            if (window.lucide) window.lucide.createIcons();
        }

        function togglePlaceholder() {
            const role = document.getElementById('activate_role').value;
            const label = document.getElementById('identifier_label');
            const input = document.getElementById('activate_identifier');
            if (role === 'student') {
                label.innerText = "Admission Number";
                input.placeholder = "e.g. ADM-2026-001";
            } else {
                label.innerText = "Employee ID / Badge Code";
                input.placeholder = "e.g. EMP-CSE-001";
            }
        }

        function validatePasswords(event) {
            const pwd = document.getElementById('new_password').value;
            const conf = document.getElementById('confirm_password').value;
            const errorDiv = document.getElementById('client_error_msg');
            
            if (pwd.length < 6) {
                event.preventDefault();
                errorDiv.innerText = "Password must be at least 6 characters long.";
                errorDiv.classList.remove('hidden');
                return false;
            }
            
            if (pwd !== conf) {
                event.preventDefault();
                errorDiv.innerText = "Passwords do not match.";
                errorDiv.classList.remove('hidden');
                return false;
            }
            
            errorDiv.classList.add('hidden');
            return true;
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) window.lucide.createIcons();
        });
    </script>
</body>
</html>
