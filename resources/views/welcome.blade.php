<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cheque Management System - Login</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0B5CFF',
                        navy: '#061A3A',
                        navyLight: '#082A5E',
                        teal: '#16B8B2',
                    },
                    boxShadow: {
                        soft: '0 18px 45px rgba(15, 23, 42, 0.12)',
                        phone: '0 35px 90px rgba(15, 23, 42, 0.22)',
                    }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen bg-slate-100 font-sans text-slate-900">
    <main class="min-h-screen bg-[radial-gradient(circle_at_20%_20%,rgba(11,92,255,0.10),transparent_28%),radial-gradient(circle_at_80%_70%,rgba(22,184,178,0.15),transparent_30%)] px-4 py-6 sm:px-6 lg:flex lg:items-center lg:justify-center lg:p-8">
        <section class="mx-auto grid w-full max-w-6xl overflow-hidden rounded-[32px] bg-white shadow-soft lg:grid-cols-[1.05fr_0.95fr]">
            <div class="relative hidden overflow-hidden bg-gradient-to-br from-navy via-navyLight to-slate-950 p-10 text-white lg:flex lg:min-h-[760px] lg:flex-col lg:justify-between">
                <div class="absolute inset-x-0 bottom-0 h-56 bg-teal/10 [clip-path:ellipse(78%_58%_at_50%_100%)]"></div>
                <div class="absolute left-8 top-20 h-72 w-[1px] bg-cyan-300/30"></div>
                <div class="absolute bottom-24 right-10 h-28 w-28 rounded-full border border-cyan-300/20"></div>

                <div class="relative z-10">
                    <div class="mb-12 inline-flex items-center gap-3 rounded-2xl bg-white/10 px-4 py-3 backdrop-blur">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary">
                            <i class="fa-solid fa-money-check-dollar text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold">Cheque Management</h2>
                            <p class="text-sm text-white/70">POS / ERP Module</p>
                        </div>
                    </div>

                    <div class="mx-auto mb-10 flex h-40 w-40 items-center justify-center rounded-[40px] border border-white/15 bg-white/10 shadow-2xl backdrop-blur">
                        <div class="relative">
                            <i class="fa-solid fa-shield-halved text-8xl text-white"></i>
                            <i class="fa-solid fa-pen-nib absolute -right-6 top-8 -rotate-12 text-5xl text-teal"></i>
                            <span class="absolute -bottom-4 -right-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-teal text-white shadow-lg">
                                <i class="fa-solid fa-lock text-xl"></i>
                            </span>
                        </div>
                    </div>

                    <h1 class="max-w-lg text-4xl font-extrabold leading-tight">
                        Cheque Management System
                    </h1>
                    <p class="mt-4 max-w-md text-lg font-medium text-teal-200">
                        Secure business cheque tracking
                    </p>
                    <p class="mt-5 max-w-md text-base leading-7 text-white/70">
                        Manage customer cheques, own cheques, pending payments, passed cheques,
                        returned cheques, supplier payables, and customer dues in one clean system.
                    </p>
                </div>

                <div class="relative z-10 flex items-center gap-3 text-sm text-white/70">
                    <i class="fa-solid fa-code text-teal"></i>
                    <span>Powered by <strong class="text-white">Twinsofte.com</strong></span>
                </div>
            </div>

            <div class="relative flex min-h-screen items-center justify-center bg-slate-50 px-4 py-6 sm:min-h-[760px] sm:px-8 lg:p-12">
                <div class="absolute inset-x-0 top-0 h-[330px] rounded-b-[46px] bg-gradient-to-br from-navy via-navyLight to-primary lg:hidden"></div>
                <div class="absolute inset-x-0 top-[260px] h-28 bg-teal/20 [clip-path:ellipse(80%_60%_at_50%_0%)] lg:hidden"></div>

                <div class="relative z-10 w-full max-w-md">
                    <div class="mb-7 text-center text-white lg:hidden">
                        <div class="mx-auto mb-5 flex h-24 w-24 items-center justify-center rounded-[28px] border border-white/20 bg-white/10 shadow-xl backdrop-blur">
                            <div class="relative">
                                <i class="fa-solid fa-money-check-dollar text-5xl text-white"></i>
                                <span class="absolute -bottom-2 -right-3 flex h-9 w-9 items-center justify-center rounded-full bg-teal text-white shadow-lg">
                                    <i class="fa-solid fa-lock text-sm"></i>
                                </span>
                            </div>
                        </div>
                        <h1 class="text-3xl font-extrabold leading-tight">Cheque Management System</h1>
                        <p class="mt-2 text-sm font-medium text-teal-200">Secure business cheque tracking</p>
                    </div>

                    <div class="mb-8 hidden text-center lg:block">
                        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-3xl bg-primary text-white shadow-lg">
                            <i class="fa-solid fa-money-check-dollar text-3xl"></i>
                        </div>
                        <h2 class="text-3xl font-extrabold text-navy">Welcome Back</h2>
                        <p class="mt-2 text-slate-500">Sign in to manage your cheques</p>
                    </div>

                    <div class="rounded-[30px] bg-white p-6 shadow-soft sm:p-8">
                        @if ($errors->any())
                            <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form action="{{ route('login.attempt') }}" method="POST" class="space-y-5">
                            @csrf

                            <div>
                                <label for="login" class="mb-2 block text-sm font-semibold text-slate-800">
                                    Email or Username
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-primary">
                                        <i class="fa-regular fa-user"></i>
                                    </span>
                                    <input
                                        type="text"
                                        id="login"
                                        name="login"
                                        value="{{ old('login') }}"
                                        placeholder="Enter email or username"
                                        autocomplete="username"
                                        required
                                        autofocus
                                        class="w-full rounded-2xl border border-slate-200 bg-white py-4 pl-12 pr-4 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10"
                                    >
                                </div>
                            </div>

                            <div>
                                <label for="password" class="mb-2 block text-sm font-semibold text-slate-800">
                                    Password
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-primary">
                                        <i class="fa-solid fa-lock"></i>
                                    </span>
                                    <input
                                        type="password"
                                        id="password"
                                        name="password"
                                        placeholder="Enter your password"
                                        autocomplete="current-password"
                                        required
                                        class="w-full rounded-2xl border border-slate-200 bg-white py-4 pl-12 pr-12 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10"
                                    >
                                    <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-primary" aria-label="Show password">
                                        <i id="eyeIcon" class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-4 text-sm">
                                <label class="flex cursor-pointer items-center gap-2 text-slate-600">
                                    <input
                                        type="checkbox"
                                        name="remember"
                                        checked
                                        class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
                                    >
                                    Remember me
                                </label>

                                <a href="#" class="font-semibold text-primary hover:underline">
                                    Forgot Password?
                                </a>
                            </div>

                            <button type="submit" class="flex w-full items-center justify-center gap-3 rounded-2xl bg-primary py-4 text-base font-bold text-white shadow-lg shadow-primary/25 transition hover:bg-blue-700 active:scale-[0.99]">
                                Sign In
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>

                            <div class="flex items-center justify-center gap-2 pt-2 text-sm text-slate-500">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-teal/10 text-teal">
                                    <i class="fa-solid fa-shield-halved text-xs"></i>
                                </span>
                                Your data is encrypted and secure
                            </div>
                        </form>
                    </div>

                    <div class="mt-5 grid grid-cols-3 gap-0 overflow-hidden rounded-[26px] bg-white p-4 shadow-soft">
                        <div class="px-2 text-center">
                            <div class="mx-auto mb-2 flex h-11 w-11 items-center justify-center rounded-xl bg-teal/10 text-teal">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <h3 class="text-xs font-bold text-slate-800">Secure Access</h3>
                            <p class="mt-1 text-[10px] text-slate-500">Protected login</p>
                        </div>
                        <div class="border-x border-slate-100 px-2 text-center">
                            <div class="mx-auto mb-2 flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </div>
                            <h3 class="text-xs font-bold text-slate-800">Tracking</h3>
                            <p class="mt-1 text-[10px] text-slate-500">Live updates</p>
                        </div>
                        <div class="px-2 text-center">
                            <div class="mx-auto mb-2 flex h-11 w-11 items-center justify-center rounded-xl bg-teal/10 text-teal">
                                <i class="fa-solid fa-users-gear"></i>
                            </div>
                            <h3 class="text-xs font-bold text-slate-800">Roles</h3>
                            <p class="mt-1 text-[10px] text-slate-500">Control</p>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-center gap-2 text-sm text-slate-500 lg:hidden">
                        <i class="fa-solid fa-code text-primary"></i>
                        <span>Powered by <strong class="text-navy">Twinsofte.com</strong></span>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');

            if (password.type === 'password') {
                password.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
