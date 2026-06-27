<?php
// views/login.php
// Public Admissions & Login Portal Matching the Institutional Theme

require_once dirname(__DIR__) . '/config/db.php';
$db = Database::connect();

// Fetch departments for registration dropdown
$deptStmt = $db->query("SELECT id, name FROM departments ORDER BY name ASC");
$departments = $deptStmt->fetchAll();

$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

$flashDanger = $_SESSION['flash_danger'] ?? null;
unset($_SESSION['flash_danger']);

$showOtpScreen = $_SESSION['otp_screen'] ?? false;
$showPasswordScreen = $_SESSION['password_setup_screen'] ?? false;
$otpPurpose = $_SESSION['otp_purpose'] ?? '';

$statusCheckResult = $_SESSION['status_check_result'] ?? null;
unset($_SESSION['status_check_result']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Institutional Portal - EduTrack LMS</title>
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
        <div class="bg-[#0B0F19] lg:w-[45%] flex-shrink-0 p-12 lg:p-20 flex flex-col justify-between relative overflow-hidden min-h-[450px] lg:min-h-screen">
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
        <div class="lg:w-[55%] bg-white flex flex-col justify-between p-8 lg:p-16 min-h-[600px] lg:min-h-screen">
            <div class="w-full max-w-md mx-auto my-auto space-y-6 animate-fadeIn">
                <!-- Heading -->
                <div>
                    <h2 id="login_panel_title" class="text-2xl lg:text-[28px] font-bold text-[#0F172A] tracking-tight">
                        <?php 
                        if ($showOtpScreen) echo "OTP Verification Required";
                        elseif ($showPasswordScreen) echo "Define Security Password";
                        else echo "Institutional Sign In";
                        ?>
                    </h2>
                    <p id="login_panel_desc" class="text-[#64748B] text-sm mt-1.5">
                        <?php 
                        if ($showOtpScreen) echo "Provide the 6-digit verification code sent to your registered email.";
                        elseif ($showPasswordScreen) echo "Create a secure account password to complete your activation.";
                        else echo "Please enter your credentials to access the learning portal.";
                        ?>
                    </p>
                </div>

                <!-- Tabs (Only display if not on OTP or Password screens) -->
                <?php if (!$showOtpScreen && !$showPasswordScreen): ?>
                    <div class="flex border-b border-[#E2E8F0] gap-4 mb-4 overflow-x-auto whitespace-nowrap">
                        <button type="button" onclick="switchTab('signin');" id="btn_tab_signin" class="pb-2 text-sm font-bold border-b-2 border-[#2563EB] text-[#2563EB] cursor-pointer">Sign In</button>
                        <button type="button" onclick="switchTab('register');" id="btn_tab_register" class="pb-2 text-sm font-semibold border-b-2 border-transparent text-[#64748B] hover:text-[#0F172A] cursor-pointer">Apply Online</button>
                        <button type="button" onclick="switchTab('status');" id="btn_tab_status" class="pb-2 text-sm font-semibold border-b-2 border-transparent text-[#64748B] hover:text-[#0F172A] cursor-pointer">Check Status</button>
                        <button type="button" onclick="switchTab('forgot');" id="btn_tab_forgot" class="pb-2 text-sm font-semibold border-b-2 border-transparent text-[#64748B] hover:text-[#0F172A] cursor-pointer">Forgot Password</button>
                    </div>
                <?php endif; ?>

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

                <?php if ($showOtpScreen): ?>
                    <!-- OTP Verification form -->
                    <form id="otp_verify_form" action="actions.php?action=<?php echo ($otpPurpose === 'public_registration') ? 'verify_registration_otp' : 'verify_otp'; ?>" method="POST" class="space-y-4 font-semibold text-xs text-slate-500">
                        <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl">
                            <span class="text-[9px] font-black text-slate-400 block uppercase tracking-wider">Verification Scope</span>
                            <span class="text-sm font-extrabold text-[#0F172A] block mt-0.5"><?php echo htmlspecialchars($_SESSION['otp_name'] ?? 'Applicant'); ?></span>
                            <span class="text-[10px] text-slate-500 block mt-1">Sent code to: <span class="font-mono text-[#2563EB]"><?php echo htmlspecialchars($_SESSION['otp_email'] ?? ''); ?></span></span>
                            <span class="text-[9px] uppercase font-black tracking-widest text-[#2563EB] mt-2 block">Code Expiry: 10 minutes</span>
                        </div>

                        <div>
                            <label for="otp_input" class="block mb-1.5 font-bold">Enter 6-Digit OTP Code</label>
                            <input
                                id="otp_input"
                                name="otp"
                                type="text"
                                maxlength="6"
                                placeholder="••••••"
                                class="w-full text-center tracking-[0.5em] px-3 py-3 bg-white border border-[#E2E8F0] rounded-lg text-lg font-bold text-[#0F172A] placeholder-[#94A3B8] focus:outline-none focus:ring-2 focus:ring-[#2563EB] focus:border-transparent font-mono"
                                required
                            />
                        </div>

                        <button
                            type="submit"
                            class="w-full py-2.5 px-4 bg-[#2563EB] hover:bg-[#1D4ED8] text-white rounded-lg text-sm font-semibold tracking-wide transition-colors duration-150 shadow-sm flex items-center justify-center gap-2 cursor-pointer mt-4"
                        >
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                            <span>Verify & Complete Registration</span>
                        </button>

                        <div class="text-center pt-2 flex flex-col items-center justify-center gap-2">
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-400">Didn't receive code?</span>
                                <a href="actions.php?action=<?php echo ($otpPurpose === 'public_registration') ? 'resend_registration_otp' : 'resend_otp'; ?>" id="btn_resend_otp" class="text-[#2563EB] font-bold opacity-50 cursor-not-allowed pointer-events-none transition-all">
                                    Resend OTP<span id="resend_timer"> (Wait 60s)</span>
                                </a>
                            </div>
                            <div class="pt-2 border-t border-slate-100 w-full">
                                <a href="actions.php?action=cancel_verification" class="text-xs text-[#2563EB] hover:underline font-bold">Cancel & Return to Sign In</a>
                            </div>
                        </div>
                    </form>

                <?php elseif ($showPasswordScreen): ?>
                    <!-- Password Setup form (Only for Reset path) -->
                    <form id="password_setup_form" action="actions.php?action=setup_registered_password" method="POST" onsubmit="return validateSetupPasswords(event);" class="space-y-4 font-semibold text-xs text-slate-500">
                        <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl">
                            <span class="text-[9px] font-black text-slate-400 block uppercase tracking-wider">Candidate Registry</span>
                            <span class="text-sm font-extrabold text-[#0F172A] block mt-0.5"><?php echo htmlspecialchars($_SESSION['otp_name'] ?? 'User Profile'); ?></span>
                            <span class="text-[10px] text-emerald-600 font-bold block uppercase tracking-wider mt-1">Identity Verified Successfully</span>
                        </div>

                        <div>
                            <label for="setup_new_password" class="block mb-1.5 font-bold uppercase tracking-wider">Create Account Password</label>
                            <div class="relative">
                                <input
                                    id="setup_new_password"
                                    name="new_password"
                                    type="password"
                                    placeholder="••••••••"
                                    class="w-full px-3 py-2.5 bg-white border border-[#E2E8F0] rounded-lg text-sm text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#2563EB] font-mono"
                                    required
                                />
                                <button
                                    type="button"
                                    onclick="togglePasswordVisibility('setup_new_password', 'setup_eye_icon1');"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-[#0F172A]"
                                >
                                    <i id="setup_eye_icon1" data-lucide="eye" class="w-4 h-4 text-[#94A3B8]"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="setup_confirm_password" class="block mb-1.5 font-bold uppercase tracking-wider">Confirm Account Password</label>
                            <div class="relative">
                                <input
                                    id="setup_confirm_password"
                                    name="confirm_password"
                                    type="password"
                                    placeholder="••••••••"
                                    class="w-full px-3 py-2.5 bg-white border border-[#E2E8F0] rounded-lg text-sm text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#2563EB] font-mono"
                                    required
                                />
                                <button
                                    type="button"
                                    onclick="togglePasswordVisibility('setup_confirm_password', 'setup_eye_icon2');"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-[#0F172A]"
                                >
                                    <i id="setup_eye_icon2" data-lucide="eye" class="w-4 h-4 text-[#94A3B8]"></i>
                                </button>
                            </div>
                        </div>

                        <div id="setup_client_error" class="p-3 bg-red-50 border border-red-100 rounded-lg text-red-600 text-xs hidden"></div>

                        <button
                            type="submit"
                            class="w-full py-2.5 px-4 bg-[#2563EB] hover:bg-[#1D4ED8] text-white rounded-lg text-sm font-semibold tracking-wide transition-colors duration-150 shadow-sm flex items-center justify-center gap-2 cursor-pointer mt-4"
                        >
                            <i data-lucide="key" class="w-4 h-4"></i>
                            <span>Activate & Reset Password</span>
                        </button>

                        <div class="text-center pt-3 border-t border-slate-100 mt-4 w-full">
                            <a href="actions.php?action=cancel_verification" class="text-xs text-[#2563EB] hover:underline font-bold">Cancel & Return to Sign In</a>
                        </div>
                    </form>

                <?php else: ?>
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
                                <span onclick="switchTab('forgot');" class="text-xs font-semibold text-[#2563EB] hover:underline cursor-pointer">Forgot Password?</span>
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

                    <!-- Student Admission Registration Form -->
                    <form id="register_form" action="actions.php?action=submit_admission_request" method="POST" enctype="multipart/form-data" class="space-y-4 hidden font-semibold text-xs text-slate-500" onsubmit="return validateRegisterPasswords(event);">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="reg_name" class="block mb-1.5 font-bold">Full Name</label>
                                <input id="reg_name" name="name" type="text" placeholder="e.g. Arjun Nair" class="w-full px-3 py-2 bg-white border border-[#E2E8F0] rounded-lg text-xs text-[#0F172A] focus:ring-1 focus:ring-primary" required />
                            </div>
                            <div>
                                <label for="reg_email" class="block mb-1.5 font-bold">Personal Email</label>
                                <input id="reg_email" name="email" type="email" placeholder="e.g. nair@gmail.com" class="w-full px-3 py-2 bg-white border border-[#E2E8F0] rounded-lg text-xs text-[#0F172A] focus:ring-1 focus:ring-primary" required />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="reg_phone" class="block mb-1.5 font-bold">Mobile Number</label>
                                <input id="reg_phone" name="phone" type="text" placeholder="e.g. 9845621350" class="w-full px-3 py-2 bg-white border border-[#E2E8F0] rounded-lg text-xs text-[#0F172A] focus:ring-1 focus:ring-primary" required />
                            </div>
                            <div>
                                <label for="reg_dob" class="block mb-1.5 font-bold">Date of Birth</label>
                                <input id="reg_dob" name="dob" type="date" class="w-full px-3 py-2 bg-white border border-[#E2E8F0] rounded-lg text-xs text-[#0F172A] focus:ring-1 focus:ring-primary" required />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="reg_gender" class="block mb-1.5 font-bold">Gender</label>
                                <select id="reg_gender" name="gender" class="w-full px-3 py-2 bg-white border border-[#E2E8F0] rounded-lg text-xs text-[#0F172A] focus:ring-1 focus:ring-primary" required>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label for="reg_dept" class="block mb-1.5 font-bold">Preferred Dept.</label>
                                <select id="reg_dept" name="department_id" class="w-full px-3 py-2 bg-white border border-[#E2E8F0] rounded-lg text-xs text-[#0F172A] focus:ring-1 focus:ring-primary" required>
                                    <?php foreach ($departments as $d): ?>
                                        <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="reg_prog" class="block mb-1.5 font-bold">Programme/Course</label>
                                <input id="reg_prog" name="programme" type="text" placeholder="e.g. B.Tech" class="w-full px-3 py-2 bg-white border border-[#E2E8F0] rounded-lg text-xs text-[#0F172A] focus:ring-1 focus:ring-primary" required />
                            </div>
                            <div>
                                <label for="reg_year" class="block mb-1.5 font-bold">Academic Year</label>
                                <input id="reg_year" name="academic_year" type="text" value="2026-2027" class="w-full px-3 py-2 bg-white border border-[#E2E8F0] rounded-lg text-xs text-[#0F172A] focus:ring-1 focus:ring-primary" required readonly />
                            </div>
                        </div>

                        <div>
                            <label for="reg_address" class="block mb-1.5 font-bold">Residential Address</label>
                            <textarea id="reg_address" name="address" rows="2" placeholder="Street, City, State..." class="w-full px-3 py-2 bg-white border border-[#E2E8F0] rounded-lg text-xs text-[#0F172A] focus:ring-1 focus:ring-primary" required></textarea>
                        </div>

                        <!-- File uploads -->
                        <div class="p-3 bg-slate-50 border border-slate-100 rounded-xl space-y-3">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Document Attachments (Max 10MB each)</span>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block mb-1 text-[10px] text-slate-500 font-bold">10th Certificate *</label>
                                    <input type="file" name="doc_10th" class="w-full text-[10px]" accept=".pdf,.jpg,.jpeg,.png" required />
                                </div>
                                <div>
                                    <label class="block mb-1 text-[10px] text-slate-500 font-bold">+2 Certificate *</label>
                                    <input type="file" name="doc_12th" class="w-full text-[10px]" accept=".pdf,.jpg,.jpeg,.png" required />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block mb-1 text-[10px] text-slate-500 font-bold">TC *</label>
                                    <input type="file" name="doc_tc" class="w-full text-[10px]" accept=".pdf,.jpg,.jpeg,.png" required />
                                </div>
                                <div>
                                    <label class="block mb-1 text-[10px] text-slate-500 font-bold">Caste (Optional)</label>
                                    <input type="file" name="doc_caste" class="w-full text-[10px]" accept=".pdf,.jpg,.jpeg,.png" />
                                </div>
                            </div>
                        </div>

                        <!-- Credentials setup -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="reg_pwd" class="block mb-1.5 font-bold">Define Password</label>
                                <input id="reg_pwd" name="password" type="password" placeholder="••••••••" class="w-full px-3 py-2 bg-white border border-[#E2E8F0] rounded-lg text-xs text-[#0F172A] focus:ring-1 focus:ring-primary font-mono" required />
                            </div>
                            <div>
                                <label for="reg_conf" class="block mb-1.5 font-bold">Confirm Password</label>
                                <input id="reg_conf" name="confirm_password" type="password" placeholder="••••••••" class="w-full px-3 py-2 bg-white border border-[#E2E8F0] rounded-lg text-xs text-[#0F172A] focus:ring-1 focus:ring-primary font-mono" required />
                            </div>
                        </div>
                        <div id="register_client_error" class="p-3 bg-red-50 border border-red-100 rounded-lg text-red-600 text-xs hidden"></div>

                        <button
                            type="submit"
                            class="w-full py-2.5 px-4 bg-[#2563EB] hover:bg-[#1D4ED8] text-white rounded-lg text-sm font-semibold tracking-wide transition-colors duration-150 shadow-sm flex items-center justify-center gap-2 cursor-pointer mt-4"
                        >
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                            <span>Submit Admission Request</span>
                        </button>
                    </form>

                    <!-- Application status checker tab -->
                    <?php if ($statusCheckResult): ?>
                        <div class="space-y-4 font-semibold text-xs text-slate-600 animate-fadeIn">
                            <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Application Query Result</span>
                                    <span class="text-[10px] text-slate-400 font-mono">ID: #<?php echo htmlspecialchars($statusCheckResult['id']); ?></span>
                                </div>
                                <h3 class="text-sm font-extrabold text-[#0F172A]"><?php echo htmlspecialchars($statusCheckResult['name']); ?></h3>
                                <p class="text-xs text-slate-500">Program: <span class="text-[#0F172A] font-bold"><?php echo htmlspecialchars($statusCheckResult['programme']); ?></span> (<?php echo htmlspecialchars($statusCheckResult['department_name']); ?>)</p>
                                <p class="text-xs text-slate-500">Academic Year: <span class="text-[#0F172A] font-bold"><?php echo htmlspecialchars($statusCheckResult['academic_year']); ?></span></p>
                                
                                <div class="mt-3 flex items-center gap-2">
                                    <span class="text-slate-500 font-bold">Current Status:</span>
                                    <?php 
                                    $st = $statusCheckResult['status'];
                                    if ($st === 'Approved') {
                                        echo '<span class="px-2 py-0.5 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded text-[10px] font-bold uppercase">Approved</span>';
                                    } elseif ($st === 'Rejected') {
                                        echo '<span class="px-2 py-0.5 bg-red-50 text-red-600 border border-red-100 rounded text-[10px] font-bold uppercase">Rejected</span>';
                                    } elseif ($st === 'Under Review') {
                                        echo '<span class="px-2 py-0.5 bg-amber-50 text-amber-600 border border-amber-100 rounded text-[10px] font-bold uppercase">Under Review</span>';
                                    } else {
                                        echo '<span class="px-2 py-0.5 bg-slate-100 text-slate-600 border border-slate-200 rounded text-[10px] font-bold uppercase">Pending</span>';
                                    }
                                    ?>
                                </div>

                                <?php if (!empty($statusCheckResult['remarks'])): ?>
                                    <div class="p-3 bg-slate-100/50 border border-slate-200 rounded-lg text-xs text-slate-600 mt-2 font-medium">
                                        <strong>Remarks:</strong> <?php echo htmlspecialchars($statusCheckResult['remarks']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <a href="actions.php?action=cancel_verification" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-[#0F172A] rounded-lg text-xs font-bold text-center block transition-colors duration-150 cursor-pointer">Clear Search Result</a>
                        </div>
                    <?php else: ?>
                        <form id="status_form" action="actions.php?action=query_admission_status" method="POST" class="space-y-4 hidden font-semibold text-xs text-slate-500">
                            <div>
                                <label for="status_email" class="block mb-1.5 font-bold">Registered Email Address</label>
                                <input
                                    id="status_email"
                                    name="email"
                                    type="email"
                                    placeholder="e.g. student@gmail.com"
                                    class="w-full px-3 py-2.5 bg-white border border-[#E2E8F0] rounded-lg text-sm text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#2563EB]"
                                    required
                                />
                            </div>

                            <div>
                                <label for="status_phone" class="block mb-1.5 font-bold">Registered Mobile Number</label>
                                <input
                                    id="status_phone"
                                    name="phone"
                                    type="text"
                                    placeholder="e.g. 9845621350"
                                    class="w-full px-3 py-2.5 bg-white border border-[#E2E8F0] rounded-lg text-sm text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#2563EB]"
                                    required
                                />
                            </div>

                            <button
                                type="submit"
                                class="w-full py-2.5 px-4 bg-[#2563EB] hover:bg-[#1D4ED8] text-white rounded-lg text-sm font-semibold tracking-wide transition-colors duration-150 shadow-sm flex items-center justify-center gap-2 cursor-pointer mt-4"
                            >
                                <i data-lucide="search" class="w-4 h-4"></i>
                                <span>Query Application Status</span>
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- Forgot Password Form -->
                    <form id="forgot_form" action="actions.php?action=verify_forgot_password" method="POST" class="space-y-4 hidden font-semibold text-xs text-slate-500">
                        <div>
                            <label class="block mb-1.5 font-bold">Select Role Context</label>
                            <select name="role" id="forgot_role" onchange="toggleForgotIdentifierPlaceholder();" class="w-full px-3 py-2.5 bg-white border border-[#E2E8F0] rounded-lg text-sm text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#2563EB]" required>
                                <option value="student">Student Scholar</option>
                                <option value="teacher">Faculty Professor</option>
                            </select>
                        </div>

                        <div>
                            <label for="forgot_identifier" id="forgot_identifier_label" class="block mb-1.5 font-bold font-mono">Admission Number</label>
                            <input
                                id="forgot_identifier"
                                name="identifier"
                                type="text"
                                placeholder="e.g. ADM-2026-001"
                                class="w-full px-3 py-2.5 bg-white border border-[#E2E8F0] rounded-lg text-sm text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#2563EB]"
                                required
                            />
                        </div>

                        <div>
                            <label for="forgot_email" class="block mb-1.5 font-bold">Registered Email Address</label>
                            <input
                                id="forgot_email"
                                name="email"
                                type="email"
                                placeholder="e.g. student@edutrack.com"
                                class="w-full px-3 py-2.5 bg-white border border-[#E2E8F0] rounded-lg text-sm text-[#0F172A] focus:outline-none focus:ring-1 focus:ring-[#2563EB] font-mono"
                                required
                            />
                        </div>

                        <button
                            type="submit"
                            class="w-full py-2.5 px-4 bg-[#2563EB] hover:bg-[#1D4ED8] text-white rounded-lg text-sm font-semibold tracking-wide transition-colors duration-150 shadow-sm flex items-center justify-center gap-2 cursor-pointer mt-4"
                        >
                            <i data-lucide="mail" class="w-4 h-4"></i>
                            <span>Email Verification Code</span>
                        </button>
                    </form>
                <?php endif; ?>

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
            const btnRegister = document.getElementById('btn_tab_register');
            const btnStatus = document.getElementById('btn_tab_status');
            const btnForgot = document.getElementById('btn_tab_forgot');
            
            const formSignin = document.getElementById('signin_form');
            const formRegister = document.getElementById('register_form');
            const formStatus = document.getElementById('status_form');
            const formForgot = document.getElementById('forgot_form');
            
            const title = document.getElementById('login_panel_title');
            const desc = document.getElementById('login_panel_desc');

            // Reset tab styles
            [btnSignin, btnRegister, btnStatus, btnForgot].forEach(btn => {
                if (btn) {
                    btn.className = "pb-2 text-sm font-semibold border-b-2 border-transparent text-[#64748B] hover:text-[#0F172A] cursor-pointer";
                }
            });
            [formSignin, formRegister, formStatus, formForgot].forEach(form => {
                if (form) form.classList.add('hidden');
            });

            if (tabId === 'signin') {
                if (btnSignin) btnSignin.className = "pb-2 text-sm font-bold border-b-2 border-[#2563EB] text-[#2563EB] cursor-pointer";
                if (formSignin) formSignin.classList.remove('hidden');
                title.innerText = "Institutional Sign In";
                desc.innerText = "Please enter your credentials to access the learning portal.";
            } else if (tabId === 'register') {
                if (btnRegister) btnRegister.className = "pb-2 text-sm font-bold border-b-2 border-[#2563EB] text-[#2563EB] cursor-pointer";
                if (formRegister) formRegister.classList.remove('hidden');
                title.innerText = "Admission Request Submission";
                desc.innerText = "Fill out all fields below to request admission setup on the LMS.";
            } else if (tabId === 'status') {
                if (btnStatus) btnStatus.className = "pb-2 text-sm font-bold border-b-2 border-[#2563EB] text-[#2563EB] cursor-pointer";
                if (formStatus) formStatus.classList.remove('hidden');
                title.innerText = "Track Application Status";
                desc.innerText = "Search registry status for your submitted admissions request.";
            } else {
                if (btnForgot) btnForgot.className = "pb-2 text-sm font-bold border-b-2 border-[#2563EB] text-[#2563EB] cursor-pointer";
                if (formForgot) formForgot.classList.remove('hidden');
                title.innerText = "Forgot Account Password";
                desc.innerText = "Enter your username and registered email to dispatch reset OTP verification.";
            }
        }

        function toggleForgotIdentifierPlaceholder() {
            const role = document.getElementById('forgot_role').value;
            const label = document.getElementById('forgot_identifier_label');
            const input = document.getElementById('forgot_identifier');
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

        function validateSetupPasswords(event) {
            const pwd = document.getElementById('setup_new_password').value;
            const conf = document.getElementById('setup_confirm_password').value;
            const errorDiv = document.getElementById('setup_client_error');
            
            if (pwd.length < 6) {
                event.preventDefault();
                errorDiv.innerText = "Password must be at least 6 characters.";
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

        function validateRegisterPasswords(event) {
            const pwd = document.getElementById('reg_pwd').value;
            const conf = document.getElementById('reg_conf').value;
            const errorDiv = document.getElementById('register_client_error');
            
            if (pwd.length < 6) {
                event.preventDefault();
                errorDiv.innerText = "Password must be at least 6 characters.";
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

        // Resend OTP Countdown logic
        <?php if ($showOtpScreen): ?>
            let countdown = 60;
            const timerEl = document.getElementById('resend_timer');
            const btnResend = document.getElementById('btn_resend_otp');
            
            if (timerEl && btnResend) {
                const interval = setInterval(() => {
                    countdown--;
                    if (countdown <= 0) {
                        clearInterval(interval);
                        timerEl.innerText = "";
                        btnResend.removeAttribute('disabled');
                        btnResend.classList.remove('opacity-50', 'cursor-not-allowed', 'pointer-events-none');
                    } else {
                        timerEl.innerText = ` (Wait ${countdown}s)`;
                    }
                }, 1000);
            }
        <?php endif; ?>

        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            if (tabParam === 'register') {
                switchTab('register');
            } else if (tabParam === 'check_status') {
                switchTab('status');
            } else if (tabParam === 'forgot') {
                switchTab('forgot');
            } else {
                switchTab('signin');
            }
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
</body>
</html>
