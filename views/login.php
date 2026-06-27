<?php
// views/login.php
// Login Page matched to the design reference image

$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

$flashDanger = $_SESSION['flash_danger'] ?? null;
unset($_SESSION['flash_danger']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EduTrack LMS</title>
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
            animation: fadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .animate-shake {
            animation: shake 0.25s ease-in-out;
        }
    </style>
    <!-- Lucide CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-white text-[#0F172A] font-sans">
    <div class="min-h-screen flex flex-col lg:flex-row w-full">
        
        <!-- Left Side: Branding (Dark professional background) -->
        <div class="bg-[#0B0F19] lg:w-[48%] flex-shrink-0 p-12 lg:p-20 flex flex-col justify-between relative overflow-hidden min-h-[450px] lg:min-h-screen">
            <!-- Decorative subtle radial glow -->
            <div class="absolute inset-0 opacity-15 pointer-events-none" style="background-image: radial-gradient(circle at 100% 0%, #2563EB 0%, transparent 60%);"></div>

            <!-- Logo and System Name -->
            <div class="z-10 flex items-center gap-3">
                <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-md">
                    <i data-lucide="graduation-cap" class="w-7 h-7 text-[#2563EB]"></i>
                </div>
                <span class="text-xl font-bold text-white tracking-tight">EduTrack LMS</span>
            </div>

            <!-- Value Proposition Heading and Paragraph -->
            <div class="z-10 my-auto py-12 lg:py-0 max-w-md space-y-6">
                <h1 class="text-3xl lg:text-[40px] font-bold text-white leading-[1.2] tracking-tight">
                    Empowering Modern Institutional Learning Excellence
                </h1>
                <p class="text-[#94A3B8] text-sm leading-relaxed">
                    Access your centralized academic ecosystem. EduTrack provides a secure, unified platform for advanced curriculum management, professional development, and real-time educational analytics designed for high-stakes corporate and academic environments.
                </p>
            </div>

            <!-- Feature Highlights Footer -->
            <div class="z-10 grid grid-cols-2 gap-6 border-t border-white/10 pt-8">
                <div class="flex items-center gap-3">
                    <div class="text-[#2563EB] shrink-0">
                        <i data-lucide="shield-check" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Secure Access</h3>
                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block mt-0.5">ENTERPRISE ENCRYPTION</span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-[#2563EB] shrink-0">
                        <i data-lucide="link" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Unified Portal</h3>
                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block mt-0.5">SSO COMPATIBLE</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Form Shell (White panel) -->
        <div class="lg:w-[52%] bg-white flex flex-col justify-between p-8 lg:p-16 min-h-[600px] lg:min-h-screen">
            <div class="w-full max-w-md mx-auto my-auto space-y-6 animate-fadeIn">
                <!-- Heading -->
                <div>
                    <h2 id="login_panel_title" class="text-2xl lg:text-[28px] font-bold text-[#0F172A] tracking-tight">Institutional Sign In</h2>
                    <p id="login_panel_desc" class="text-[#64748B] text-sm mt-1.5">Please enter your credentials to access the learning portal.</p>
                </div>

                <!-- Tabs -->
                <div class="flex border-b border-[#E2E8F0] gap-4 mb-4">
                    <button type="button" onclick="switchTab('signin');" id="btn_tab_signin" class="pb-2 text-sm font-bold border-b-2 border-[#2563EB] text-[#2563EB] cursor-pointer">Sign In</button>
                    <button type="button" onclick="switchTab('activate');" id="btn_tab_activate" class="pb-2 text-sm font-semibold border-b-2 border-transparent text-[#64748B] hover:text-[#0F172A] cursor-pointer">Activate Account</button>
                </div>

                <!-- Session Alerts -->
                <?php if ($flashSuccess): ?>
                    <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-lg text-emerald-600 text-xs flex items-start gap-3 relative animate-fadeIn">
                        <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                        <div class="flex-1 font-medium"><?php echo htmlspecialchars($flashSuccess); ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($flashDanger): ?>
                    <div class="p-4 bg-red-50 border border-red-100 rounded-lg text-red-600 text-xs flex items-start gap-3 relative animate-shake">
                        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                        <div class="flex-1 font-medium"><?php echo htmlspecialchars($flashDanger); ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($loginError): ?>
                    <div id="login_error_box" class="p-4 bg-red-50 border border-red-100 rounded-lg text-red-600 text-xs flex items-start gap-3 relative animate-shake">
                        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                        <div class="flex-1 font-medium"><?php echo htmlspecialchars($loginError); ?></div>
                        <button onclick="document.getElementById('login_error_box').style.display='none';" class="text-red-400 hover:text-red-600">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Sign In Form -->
                <form id="signin_form" action="actions.php?action=login" method="POST" class="space-y-5">
                    <div>
                        <label for="email_addr" class="block text-xs font-semibold text-[#64748B] mb-2">
                            Institutional Email / Admission No / Employee ID
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-[#94A3B8] font-semibold text-xs">ID</span>
                            <input
                                id="email_addr"
                                name="email"
                                type="text"
                                placeholder="Admission No, Emp ID, or email..."
                                class="w-full pl-9 pr-4 py-2.5 bg-white border border-[#E2E8F0] rounded-lg text-sm text-[#0F172A] placeholder-[#94A3B8] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent transition-all"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label for="password_input" class="text-xs font-semibold text-[#64748B]">
                                Password
                            </label>
                            <span class="text-xs font-semibold text-[#2563EB] hover:underline cursor-pointer">Forgot Password?</span>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i data-lucide="lock" class="w-4 h-4 text-[#94A3B8]"></i>
                            </div>
                            <input
                                id="password_input"
                                name="password"
                                type="password"
                                placeholder="••••••••"
                                class="w-full pl-10 pr-10 py-2.5 bg-white border border-[#E2E8F0] rounded-lg text-sm text-[#0F172A] placeholder-[#94A3B8] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent transition-all"
                                required
                            />
                            <button
                                type="button"
                                onclick="togglePasswordVisibility('password_input', 'password_eye_icon');"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-[#0F172A]"
                            >
                                <i id="password_eye_icon" data-lucide="eye" class="w-4 h-4 text-[#94A3B8]"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember computer -->
                    <div class="flex items-center gap-2 text-xs text-[#64748B] pt-1">
                        <input
                            type="checkbox"
                            name="remember"
                            id="remember_me"
                            class="h-4 w-4 rounded border-[#E2E8F0] text-[#2563EB] focus:ring-[#2563EB] bg-white cursor-pointer"
                        />
                        <label for="remember_me" class="cursor-pointer">Remember this device for 30 days</label>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="w-full py-2.5 px-4 bg-[#2563EB] hover:bg-[#1D4ED8] text-white rounded-lg text-sm font-semibold tracking-wide transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#2563EB] shadow-sm flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <span>Sign In</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </form>

                <!-- First-time Activation Form -->
                <form id="activate_form" action="actions.php?action=activate_account" method="POST" class="space-y-4 hidden font-semibold text-xs">
                    <div>
                        <label class="block text-[#64748B] mb-1.5 font-bold">Select Role Context</label>
                        <select name="role" id="activate_role" onchange="toggleIdentifierPlaceholder();" class="w-full px-3 py-2.5 bg-white border border-[#E2E8F0] rounded-lg text-sm text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#2563EB]" required>
                            <option value="student">Student Scholar</option>
                            <option value="teacher">Faculty Professor</option>
                        </select>
                    </div>

                    <div>
                        <label for="activate_identifier" id="activate_identifier_label" class="block text-[#64748B] mb-1.5 font-bold">Admission Number</label>
                        <input
                            id="activate_identifier"
                            name="identifier"
                            type="text"
                            placeholder="e.g. ADM-2026-001"
                            class="w-full px-3 py-2.5 bg-white border border-[#E2E8F0] rounded-lg text-sm text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#2563EB]"
                            required
                        />
                    </div>

                    <div>
                        <label for="activate_verification" class="block text-[#64748B] mb-1.5 font-bold">Verification: Date of Birth OR Registered Mobile/Email</label>
                        <input
                            id="activate_verification"
                            name="verification_val"
                            type="text"
                            placeholder="e.g. YYYY-MM-DD or registered email/phone"
                            class="w-full px-3 py-2.5 bg-white border border-[#E2E8F0] rounded-lg text-sm text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#2563EB]"
                            required
                        />
                    </div>

                    <div>
                        <label for="activate_new_password" class="block text-[#64748B] mb-1.5 font-bold">Create Password</label>
                        <div class="relative">
                            <input
                                id="activate_new_password"
                                name="new_password"
                                type="password"
                                placeholder="••••••••"
                                class="w-full px-3 py-2.5 bg-white border border-[#E2E8F0] rounded-lg text-sm text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#2563EB] font-mono"
                                required
                            />
                            <button
                                type="button"
                                onclick="togglePasswordVisibility('activate_new_password', 'activate_eye_icon1');"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-[#0F172A]"
                            >
                                <i id="activate_eye_icon1" data-lucide="eye" class="w-4 h-4 text-[#94A3B8]"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="activate_confirm_password" class="block text-[#64748B] mb-1.5 font-bold">Confirm Password</label>
                        <div class="relative">
                            <input
                                id="activate_confirm_password"
                                name="confirm_password"
                                type="password"
                                placeholder="••••••••"
                                class="w-full px-3 py-2.5 bg-white border border-[#E2E8F0] rounded-lg text-sm text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#2563EB] font-mono"
                                required
                            />
                            <button
                                type="button"
                                onclick="togglePasswordVisibility('activate_confirm_password', 'activate_eye_icon2');"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-[#0F172A]"
                            >
                                <i id="activate_eye_icon2" data-lucide="eye" class="w-4 h-4 text-[#94A3B8]"></i>
                            </button>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="w-full py-2.5 px-4 bg-[#2563EB] hover:bg-[#1D4ED8] text-white rounded-lg text-sm font-semibold tracking-wide transition-colors duration-150 shadow-sm flex items-center justify-center gap-2 cursor-pointer mt-4"
                    >
                        <i data-lucide="key" class="w-4 h-4"></i>
                        <span>Activate My Account</span>
                    </button>
                </form>

                <!-- Institutional Help Desk Widget -->
                <div class="p-4 bg-[#F8FAFC] border border-[#E2E8F0] rounded-xl flex items-start gap-4">
                    <div class="w-10 h-10 bg-white border border-[#E2E8F0] rounded-lg flex items-center justify-center text-[#64748B] shrink-0 shadow-sm">
                        <i data-lucide="help-circle" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-[#0F172A]">Institutional Help Desk</h4>
                        <p class="text-[11px] text-[#64748B] mt-0.5">Submit a support ticket for access issues</p>
                    </div>
                </div>

                <!-- Sandbox Credentials box -->
                <div id="demo_hints_box" class="p-4 bg-[#F8FAFC] border border-[#E2E8F0] rounded-xl space-y-3 relative">
                    <button
                        onclick="document.getElementById('demo_hints_box').style.display='none';"
                        class="absolute top-2 right-2 text-gray-400 hover:text-gray-600"
                    >
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                    <div class="flex items-center gap-2 text-xs font-black text-[#0F172A] uppercase tracking-wide">
                        <i data-lucide="key" class="w-4 h-4 text-[#2563EB]"></i>
                        <span>Sandbox Credentials</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            type="button"
                            onclick="handleQuickLogin('student@edutrack.com', 'student123');"
                            class="py-1.5 px-1 text-center font-semibold text-xs border border-[#E2E8F0] hover:border-[#2563EB] rounded-md transition-colors bg-white hover:bg-blue-50 text-[#2563EB] shadow-sm text-ellipsis overflow-hidden whitespace-nowrap"
                        >
                            Student
                        </button>
                        <button
                            type="button"
                            onclick="handleQuickLogin('teacher@edutrack.com', 'teacher123');"
                            class="py-1.5 px-1 text-center font-semibold text-xs border border-[#E2E8F0] hover:border-[#2563EB] rounded-md transition-colors bg-white hover:bg-blue-50 text-emerald-700 shadow-sm text-ellipsis overflow-hidden whitespace-nowrap"
                        >
                            Teacher
                        </button>
                        <button
                            type="button"
                            onclick="handleQuickLogin('admin@edutrack.com', 'admin123');"
                            class="py-1.5 px-1 text-center font-semibold text-xs border border-[#E2E8F0] hover:border-[#2563EB] rounded-md transition-colors bg-white hover:bg-blue-50 text-amber-700 shadow-sm text-ellipsis overflow-hidden whitespace-nowrap"
                        >
                            Admin
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer (Responsive) -->
            <div class="flex flex-col sm:flex-row items-center justify-between text-xs text-[#94A3B8] border-t border-[#E2E8F0] pt-6 mt-8 gap-2 w-full shrink-0">
                <span>© 2024 EduTrack LMS</span>
                <span>v4.8.2-enterprise</span>
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-[#10B981]"></span>
                    <span>System Status: Operational</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        function handleQuickLogin(email, password) {
            switchTab('signin');
            document.getElementById('email_addr').value = email;
            document.getElementById('password_input').value = password;
        }

        function switchTab(tabId) {
            const btnSignin = document.getElementById('btn_tab_signin');
            const btnActivate = document.getElementById('btn_tab_activate');
            const formSignin = document.getElementById('signin_form');
            const formActivate = document.getElementById('activate_form');
            const title = document.getElementById('login_panel_title');
            const desc = document.getElementById('login_panel_desc');

            if (tabId === 'signin') {
                btnSignin.className = "pb-2 text-sm font-bold border-b-2 border-[#2563EB] text-[#2563EB] cursor-pointer";
                btnActivate.className = "pb-2 text-sm font-semibold border-b-2 border-transparent text-[#64748B] hover:text-[#0F172A] cursor-pointer";
                formSignin.classList.remove('hidden');
                formActivate.classList.add('hidden');
                title.innerText = "Institutional Sign In";
                desc.innerText = "Please enter your credentials to access the learning portal.";
            } else {
                btnSignin.className = "pb-2 text-sm font-semibold border-b-2 border-transparent text-[#64748B] hover:text-[#0F172A] cursor-pointer";
                btnActivate.className = "pb-2 text-sm font-bold border-b-2 border-[#2563EB] text-[#2563EB] cursor-pointer";
                formSignin.classList.add('hidden');
                formActivate.classList.remove('hidden');
                title.innerText = "Activate Your Account";
                desc.innerText = "Verification details matching institutional registrar data is required.";
            }
        }

        function toggleIdentifierPlaceholder() {
            const role = document.getElementById('activate_role').value;
            const label = document.getElementById('activate_identifier_label');
            const input = document.getElementById('activate_identifier');
            if (role === 'student') {
                label.innerText = "Admission Number";
                input.placeholder = "e.g. ADM-2026-001";
            } else {
                label.innerText = "Employee ID / Badge Code";
                input.placeholder = "e.g. EMP-CSE-001";
            }
        }

        function togglePasswordVisibility(inputId, eyeId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(eyeId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
</body>
</html>
