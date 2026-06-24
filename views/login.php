<?php
// views/login.php
// Login Page template matching Login.tsx UI exactly

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@450;500;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1D4ED8',
                        'primary-hover': '#1E40AF',
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                        mono: ['JetBrains Mono', 'SFMono-Regular', 'Consolas', 'monospace'],
                    }
                }
            }
        }
    </script>
    <!-- Style tweaks -->
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
<body class="bg-[#F8FAFC] text-[#0F172A] font-sans">
    <div class="min-h-screen flex flex-col md:flex-row w-full">
        <!-- Left Side: Branding & Value Proposition (55% width) -->
        <div class="bg-[#0F172A] flex-shrink-0 w-full md:w-[55%] min-h-[400px] md:min-h-screen p-8 md:p-16 flex flex-col justify-between relative overflow-hidden">
            <!-- Decorative Grid Panel -->
            <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(circle at 100% 0%, #1D4ED8 0%, transparent 50%);"></div>

            <!-- Logo Area -->
            <div class="z-10 flex items-center gap-3">
                <div class="w-10 h-10 bg-[#1D4ED8] rounded-xl flex items-center justify-center text-white shadow-md">
                    <i data-lucide="graduation-cap" class="w-6 h-6"></i>
                </div>
                <span class="text-xl font-bold text-white tracking-tight">EduTrack LMS</span>
            </div>

            <!-- Value Proposal Header -->
            <div class="z-10 mt-16 md:mt-0 max-w-lg space-y-6">
                <h1 class="text-4xl md:text-5xl font-extrabold text-white leading-tight tracking-tight">
                    Empower Learning.<br />Track Progress.
                </h1>
                <p class="text-[#94A3B8] text-base leading-relaxed">
                    A comprehensive, high-fidelity Learning Management System orchestrating students schedules, assignments evaluations, results disclosures and placements.
                </p>
                <ul class="space-y-4 pt-4">
                    <li class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-6 h-6 rounded-full bg-[#1D4ED8]/20 flex items-center justify-center mt-0.5">
                            <i data-lucide="check" class="w-4 h-4 text-[#1D4ED8]"></i>
                        </div>
                        <p class="text-white font-medium text-sm">Advanced curriculum management & attendance tracking.</p>
                    </li>
                    <li class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-6 h-6 rounded-full bg-[#1D4ED8]/20 flex items-center justify-center mt-0.5">
                            <i data-lucide="check" class="w-4 h-4 text-[#1D4ED8]"></i>
                        </div>
                        <p class="text-white font-medium text-sm">Real-time grades distribution analytics and performance insights.</p>
                    </li>
                    <li class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-6 h-6 rounded-full bg-[#1D4ED8]/20 flex items-center justify-center mt-0.5">
                            <i data-lucide="check" class="w-4 h-4 text-[#1D4ED8]"></i>
                        </div>
                        <p class="text-white font-medium text-sm">Seamless placements applications, calendars and notifications.</p>
                    </li>
                </ul>
            </div>

            <!-- Outer Legal credits -->
            <div class="z-10 mt-16 md:mt-0 flex flex-wrap gap-6 text-xs text-[#64748B]">
                <span class="hover:text-white transition-colors cursor-pointer">Privacy Policies</span>
                <span class="hover:text-white transition-colors cursor-pointer">Terms of Service</span>
                <span class="ml-auto">© 2026 EduTrack Inc. All rights reserved.</span>
            </div>
        </div>

        <!-- Right Side: Form Shell (45% width) -->
        <div class="w-full md:w-[45%] bg-white flex flex-col justify-center items-center p-8 md:p-16 min-h-[600px] md:min-h-screen">
            <div class="w-full max-w-md space-y-8 animate-fadeIn">
                <!-- Header Greetings -->
                <div>
                    <h2 class="text-3xl font-bold text-[#0F172A] tracking-tight">Welcome back</h2>
                    <p class="text-[#64748B] mt-2 text-sm">Please sign in to access your administrative, teacher or student dashboards portal.</p>
                </div>

                <!-- Feedback Errors dismissible -->
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
                <form action="actions.php?action=login" method="POST" class="space-y-6">
                    <div>
                        <label htmlFor="email_addr" class="block text-xs font-bold text-[#64748B] uppercase tracking-wider mb-2">
                            Email Address
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="mail" class="w-5 h-5 text-[#94A3B8]"></i>
                            </div>
                            <input
                                id="email_addr"
                                name="email"
                                type="email"
                                placeholder="Enter your email address"
                                class="w-full pl-10 pr-4 py-2.5 bg-[#F8FAFC] border border-[#E2E8F0] rounded-lg text-sm text-[#0F172A] placeholder-[#94A3B8] focus:outline-none focus:ring-2 focus:ring-[#1D4ED8] focus:border-transparent transition-all"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label htmlFor="password_input" class="block text-xs font-bold text-[#64748B] uppercase tracking-wider mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="lock" class="w-5 h-5 text-[#94A3B8]"></i>
                            </div>
                            <input
                                id="password_input"
                                name="password"
                                type="password"
                                placeholder="Password"
                                class="w-full pl-10 pr-10 py-2.5 bg-[#F8FAFC] border border-[#E2E8F0] rounded-lg text-sm text-[#0F172A] placeholder-[#94A3B8] focus:outline-none focus:ring-2 focus:ring-[#1D4ED8] focus:border-transparent transition-all"
                                required
                            />
                            <button
                                type="button"
                                onclick="togglePasswordVisibility();"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-[#0F172A]"
                            >
                                <i id="password_eye_icon" data-lucide="eye" class="w-5 h-5 text-[#94A3B8]"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember & forgot bar -->
                    <div class="flex items-center justify-between text-xs pt-1">
                        <label class="flex items-center gap-2 text-[#64748B] cursor-pointer">
                            <input
                                type="checkbox"
                                name="remember"
                                class="h-4 w-4 rounded border-[#E2E8F0] text-[#1D4ED8] focus:ring-[#1D4ED8] bg-[#F8FAFC]"
                            />
                            <span>Remember this computer</span>
                        </label>
                        <span class="text-[#1D4ED8] font-bold hover:underline cursor-pointer">Forgot passcode?</span>
                    </div>

                    <!-- Blue primary button action -->
                    <button
                        type="submit"
                        class="w-full py-3 px-4 bg-[#1D4ED8] hover:bg-[#1E40AF] text-white rounded-lg text-sm font-semibold tracking-wide transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1D4ED8] shadow-sm transform hover:-translate-y-px active:translate-y-px active:shadow-none"
                    >
                        Sign In to Dashboard
                    </button>
                </form>

                <!-- Seed demo credential indicators box (drawer) -->
                <div id="demo_hints_box" class="p-4 bg-[#F1F5F9] border border-[#E2E8F0] rounded-lg space-y-3 relative">
                    <button
                        onclick="document.getElementById('demo_hints_box').style.display='none';"
                        class="absolute top-2 right-2 text-gray-400 hover:text-gray-600"
                    >
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                    <div class="flex items-center gap-2 text-xs font-black text-[#0F172A] tracking-wide uppercase">
                        <i data-lucide="key" class="w-4 h-4 text-[#1D4ED8]"></i>
                        <span>Demo Sandbox Credentials</span>
                    </div>
                    <p class="text-xs text-[#64748B]">Click any role button below to instantly populate credentials form:</p>
                    <div class="grid grid-cols-3 gap-2 pt-1">
                        <button
                            type="button"
                            onclick="handleQuickLogin('student@edutrack.com', 'student123');"
                            class="py-2 px-1 text-center font-semibold text-xs border border-[#E2E8F0] hover:border-[#1D4ED8] rounded-md transition-colors bg-white hover:bg-blue-50 text-[#1D4ED8] shadow-sm text-ellipsis overflow-hidden whitespace-nowrap"
                        >
                            Student Arjun
                        </button>
                        <button
                            type="button"
                            onclick="handleQuickLogin('teacher@edutrack.com', 'teacher123');"
                            class="py-2 px-1 text-center font-semibold text-xs border border-[#E2E8F0] hover:border-[#1D4ED8] rounded-md transition-colors bg-white hover:bg-blue-50 text-emerald-700 shadow-sm text-ellipsis overflow-hidden whitespace-nowrap"
                        >
                            Teacher Jenkins
                        </button>
                        <button
                            type="button"
                            onclick="handleQuickLogin('admin@edutrack.com', 'admin123');"
                            class="py-2 px-1 text-center font-semibold text-xs border border-[#E2E8F0] hover:border-[#1D4ED8] rounded-md transition-colors bg-white hover:bg-blue-50 text-amber-700 shadow-sm text-ellipsis overflow-hidden whitespace-nowrap"
                        >
                            Admin System
                        </button>
                    </div>
                </div>

                <!-- Legal / assistance block -->
                <div class="text-center pt-2">
                    <p class="text-[#64748B] text-xs">
                        Don't have an institutional login? <strong class="text-[#1D4ED8] hover:underline cursor-pointer">Request department admission</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Quick login credentials population
        function handleQuickLogin(email, password) {
            document.getElementById('email_addr').value = email;
            document.getElementById('password_input').value = password;
        }

        // Toggles password input type and icon
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

        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
</body>
</html>
