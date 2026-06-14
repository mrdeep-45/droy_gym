<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DROY GYM MANAGEMENT - Login</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

        *{
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: #0b0f19;
        }

        .login-image {
            background-image: url('{{ $actual_url."/uploads/gym_banner.jpg" }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .glass-card {
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .input-field {
            transition: all 0.2s ease-in-out;
        }

        .input-field:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
        }

        .spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255,255,255,.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>

<body class="text-slate-200 antialiased">

<div class="min-h-screen flex">

    <!-- LEFT SIDE: Branding & Stats -->
    <div class="hidden lg:flex lg:w-1/2 xl:w-3/5 relative login-image">
        <!-- Premium Vignette Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-tr from-[#090d16] via-[#0f172a]/85 to-transparent"></div>

        <div class="relative z-10 flex flex-col justify-between p-16 w-full">
            <div>
                <!-- Cleaned up Logo frame -->
                <div class="inline-flex p-3 bg-slate-900/80 border border-slate-700/50 backdrop-blur-md rounded-2xl mb-8">
                    <img src="{{ $actual_url.'/uploads/gym_logo.png' }}" class="h-16 w-auto object-contain mix-blend-lighten">
                </div>

                <h1 class="text-6xl font-extrabold tracking-tight text-white uppercase">
                    Droy <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-orange-500">Gym</span>
                </h1>

                <p class="text-lg mt-3 text-slate-300 font-medium tracking-wide">
                    Next-Gen Centralized Intelligence Hub
                </p>

                <p class="mt-4 max-w-md text-slate-400 leading-relaxed text-sm">
                    Optimize and scale gym operations. Seamlessly manage memberships, professional trainers, attendance, automatic billing routines, and rich data analytics.
                </p>
            </div>

            <div class="space-y-10">
                <!-- Grid Stats Section -->
                <div class="grid grid-cols-2 gap-4 max-w-lg">
                    <div class="bg-slate-900/50 border border-white/5 backdrop-blur-md rounded-2xl p-5">
                        <div class="text-3xl font-extrabold text-white tracking-tight">1,000+</div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mt-1">Active Members</div>
                    </div>

                    <div class="bg-slate-900/50 border border-white/5 backdrop-blur-md rounded-2xl p-5">
                        <div class="text-3xl font-extrabold text-white tracking-tight">25+</div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mt-1">Elite Trainers</div>
                    </div>

                    <div class="bg-slate-900/50 border border-white/5 backdrop-blur-md rounded-2xl p-5">
                        <div class="text-3xl font-extrabold text-emerald-400 tracking-tight">98%</div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mt-1">Retention Rate</div>
                    </div>

                    <div class="bg-slate-900/50 border border-white/5 backdrop-blur-md rounded-2xl p-5">
                        <div class="text-3xl font-extrabold text-red-400 tracking-tight">24/7</div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mt-1">System Uptime</div>
                    </div>
                </div>

                <!-- Features list items -->
                <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-300 font-medium border-t border-slate-800 pt-6 max-w-lg">
                    <div class="flex items-center gap-2"><span class="text-red-500">✓</span> Memberships</div>
                    <div class="flex items-center gap-2"><span class="text-red-500">✓</span> Performance Rosters</div>
                    <div class="flex items-center gap-2"><span class="text-red-500">✓</span> Automated Check-ins</div>
                    <div class="flex items-center gap-2"><span class="text-red-500">✓</span> Subscriptions</div>
                    <div class="flex items-center gap-2"><span class="text-red-500">✓</span> Financial Analytics</div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT SIDE: Auth Form -->
    <div class="w-full lg:w-1/2 xl:w-2/5 flex items-center justify-center p-8 bg-[#090d16] border-l border-slate-900">
        <div class="w-full max-w-md space-y-6">
            
            <!-- Upper Header Row Meta Data -->
            <div class="flex justify-between items-center px-2">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-emerald-500 bg-emerald-500/10 px-3 py-1.5 rounded-full border border-emerald-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Secure Terminal
                </div>
                <div id="current-time" class="text-xs font-medium text-slate-500 tracking-wider font-mono"></div>
            </div>

            <!-- Main Auth Card Container -->
            <div class="glass-card rounded-3xl shadow-2xl border border-white/10 overflow-hidden">
                <div class="p-10">
                    
                    <!-- Headings & Internal Logo Form -->
                    <div class="text-center mb-8">
                        <div class="lg:hidden inline-flex p-2.5 bg-slate-900 border border-slate-700 rounded-xl mb-4">
                            <img src="{{ $actual_url.'/uploads/gym_logo.png' }}" class="h-12 w-auto object-contain mix-blend-lighten">
                        </div>
                        <h2 class="text-3xl font-bold tracking-tight text-white">
                            Welcome Back
                        </h2>
                        <p class="text-slate-400 text-sm mt-2">
                            Provide administrative access tokens to continue
                        </p>
                    </div>

                    <!-- Login Form Submission -->
                    <form id="loginForm" class="space-y-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                                Email Address
                            </label>
                            <input
                                type="email"
                                id="email"
                                placeholder="admin@droygym.com"
                                class="input-field w-full rounded-xl border border-slate-800 bg-slate-950/60 text-white px-4 py-3.5 text-sm focus:outline-none placeholder:text-slate-600">
                        </div>

                        <div>
                            <div class="flex justify-between mb-2">
                                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                    Password
                                </label>
                                <a href="#" class="text-xs font-semibold text-red-400 hover:text-red-300 transition-colors">
                                    Reset Password?
                                </a>
                            </div>

                            <div class="relative">
                                <input
                                    type="password"
                                    id="password"
                                    placeholder="••••••••"
                                    class="input-field w-full rounded-xl border border-slate-800 bg-slate-950/60 text-white px-4 py-3.5 pr-12 text-sm focus:outline-none placeholder:text-slate-700">
                                
                                <button
                                    type="button"
                                    id="togglePassword"
                                    class="absolute right-4 top-3.5 text-slate-500 hover:text-slate-300 transition-colors text-sm">
                                    👁
                                </button>
                            </div>
                        </div>

                        <!-- Utilities Actions Layout Area -->
                        <div class="flex justify-between items-center text-sm pt-1">
                            <label class="flex items-center gap-2.5 cursor-pointer select-none">
                                <input type="checkbox" id="remember" class="w-4 h-4 rounded border-slate-800 bg-slate-950 text-red-600 focus:ring-0 focus:ring-offset-0">
                                <span class="text-xs font-medium text-slate-400">Remember session</span>
                            </label>

                            <a href="" class="text-xs font-semibold text-slate-300 bg-slate-800 hover:bg-slate-700 px-3 py-1.5 rounded-lg border border-slate-700/50 transition-colors">
                                Quick Attendance
                            </a>
                        </div>

                        <!-- Primary Interactive Control Action Button -->
                        <button
                            type="submit"
                            id="submitButton"
                            class="w-full mt-2 py-3.5 rounded-xl text-white font-bold text-sm bg-gradient-to-r from-red-600 to-orange-600 hover:from-red-700 hover:to-orange-700 shadow-lg shadow-red-900/20 active:scale-[0.99] transition-all tracking-wide uppercase">
                            Authenticate
                        </button>
                    </form>
                </div>

                <!-- Micro Footer Identity Notation Frame -->
                <div class="border-t border-white/5 bg-slate-950/40 py-4 text-center text-[10px] font-semibold tracking-widest text-slate-500 uppercase font-mono">
                    © {{ date('Y') }} DROY GYM SYSTEM | v1.0.0
                </div>
            </div>

        </div>
    </div>

</div>

<script>
function updateTime() {
    const now = new Date();
    document.getElementById('current-time').innerHTML = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'});
}
setInterval(updateTime, 1000);
updateTime();

$('#togglePassword').click(function(){
    let password = $('#password');
    if(password.attr('type') === 'password') {
        password.attr('type', 'text');
    } else {
        password.attr('type', 'password');
    }
});

$(function(){
    const $form = $('#loginForm');
    const $submitBtn = $('#submitButton');
    const originalBtnText = $submitBtn.html();

    $form.submit(function(e){
        e.preventDefault();
        const email = $('#email').val().trim();
        const password = $('#password').val().trim();

        if(!email) {
            iziToast.warning({ title: 'System Warning', message: 'Please provide email configuration credentials.' });
            return;
        }

        if(!password) {
            iziToast.warning({ title: 'System Warning', message: 'Secure terminal access password required.' });
            return;
        }

        $submitBtn.html('<span class="spinner mr-2"></span> Verifying Token...');
        $submitBtn.prop('disabled', true);

        $.post('{{ route("login_process") }}', {
            _token: $('meta[name="csrf-token"]').attr('content'),
            email: email,
            password: password
        })
        .done(function(response){
            if(response.success) {
                iziToast.success({ title: 'Success', message: response.message });
                setTimeout(function(){
                    window.location.href = response.redirect_url;
                }, 1000);
            } else {
                iziToast.warning({ title: 'Failed', message: response.message });
            }
        })
        .fail(function(xhr){
            iziToast.error({ title: 'System Error', message: 'Handshake protocol failed.' });
        })
        .always(function(){
            $submitBtn.html(originalBtnText);
            $submitBtn.prop('disabled', false);
        });
    });
});
</script>

</body>
</html>