<?php
// views/login.php
// Login Page matched to the design reference image

$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
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
            <div class="w-full max-w-md mx-auto my-auto space-y-8 animate-fadeIn">
                <!-- Heading -->
                <div>
                    <h2 class="text-2xl lg:text-[28px] font-bold text-[#0F172A] tracking-tight">Institutional Sign In</h2>
                    <p class="text-[#64748B] text-sm mt-1.5">Please enter your credentials to access the learning portal.</p>
                </div>

                <!-- Errors dismissible -->
                <?php if ($loginError): ?>
                    <div id="login_error_box" class="p-4 bg-red-50 border border-red-100 rounded-lg text-red-600 text-sm flex items-start gap-3 relative animate-shake">
                        <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                        <div class="flex-1 font-medium"><?php echo htmlspecialchars($loginError); ?></div>
                        <button onclick="document.getElementById('login_error_box').style.display='none';" class="text-red-400 hover:text-red-600">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Interactive Form -->
                <form action="actions.php?action=login" method="POST" class="space-y-5">
                    <div>
                        <label for="email_addr" class="block text-xs font-semibold text-[#64748B] mb-2">
                            Institutional Email
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-[#94A3B8] font-medium text-sm">@</span>
                            <input
                                id="email_addr"
                                name="email"
                                type="email"
                                placeholder="name@institution.edu"
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
                                onclick="togglePasswordVisibility();"
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
                        class="w-full py-2.5 px-4 bg-[#2563EB] hover:bg-[#1D4ED8] text-white rounded-lg text-sm font-semibold tracking-wide transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#2563EB] shadow-sm flex items-center justify-center gap-2"
                    >
                        <span>Sign In</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
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
            document.getElementById('email_addr').value = email;
            document.getElementById('password_input').value = password;
        }

        function togglePasswordVisibility() {
            const input = document.getElementById('password_input');
            const icon = document.getElementById('password_eye_icon');
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
