<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Cheque Management System')</title>

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
                        success: '#16A34A',
                        warning: '#F97316',
                        danger: '#EF4444',
                        purplePay: '#6D5DF6',
                    },
                    boxShadow: {
                        soft: '0 12px 35px rgba(15, 23, 42, 0.10)',
                    }
                }
            }
        }
    </script>

    <style>
        body { -webkit-tap-highlight-color: transparent; }
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>

<body class="min-h-screen overflow-x-hidden bg-slate-100 text-slate-900">
    @php
        $navActive = 'bg-primary text-white shadow-lg shadow-primary/20';
        $navInactive = 'text-white/75 hover:bg-white/10 hover:text-white';
        $bottomActive = 'text-primary';
        $bottomInactive = 'text-slate-500';
        $headerAlertCount = \App\Models\Cheque::whereIn('status', [
            \App\Models\Cheque::STATUS_PENDING,
            \App\Models\Cheque::STATUS_DEPOSITED,
            \App\Models\Cheque::STATUS_HOLD,
        ])->whereDate('cheque_date', today())->count();
    @endphp

    <div id="mobileOverlay" class="fixed inset-0 z-40 hidden bg-slate-950/50 lg:hidden"></div>

    <div class="min-h-screen lg:flex">
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-gradient-to-b from-navy via-navyLight to-slate-950 text-white transition-transform duration-300 lg:translate-x-0">
            <div class="flex h-24 items-center gap-3 border-b border-white/10 px-6">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/30">
                    <i class="fa-solid fa-shield-halved text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-lg font-extrabold leading-tight">Cheque Management</h1>
                    <p class="text-xs text-white/60">ERP Solution</p>
                </div>
            </div>

            <nav class="flex-1 space-y-2 px-4 py-6">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-4 rounded-2xl px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('dashboard') ? $navActive : $navInactive }}">
                    <i class="fa-solid fa-house w-5"></i>
                    Dashboard
                </a>
                <a href="{{ route('customers.index') }}" class="flex items-center gap-4 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('customers.*') ? $navActive : $navInactive }}">
                    <i class="fa-solid fa-users w-5"></i>
                    Customers
                </a>
                <a href="{{ route('suppliers.index') }}" class="flex items-center gap-4 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('suppliers.*') ? $navActive : $navInactive }}">
                    <i class="fa-solid fa-truck-field w-5"></i>
                    Suppliers
                </a>
                <a href="{{ route('cheques.index') }}" class="flex items-center gap-4 rounded-2xl px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('cheques.index') ? $navActive : $navInactive }}">
                    <i class="fa-solid fa-money-check-dollar w-5"></i>
                    Cheques
                </a>
                <a href="{{ route('cheques.pending') }}" class="flex items-center gap-4 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('cheques.pending') ? $navActive : $navInactive }}">
                    <i class="fa-regular fa-clock w-5"></i>
                    Pending
                </a>
                <a href="{{ route('cheques.passed') }}" class="flex items-center gap-4 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('cheques.passed') ? $navActive : $navInactive }}">
                    <i class="fa-regular fa-circle-check w-5"></i>
                    Passed
                </a>
                <a href="{{ route('cheques.returned') }}" class="flex items-center gap-4 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('cheques.returned') ? $navActive : $navInactive }}">
                    <i class="fa-solid fa-rotate-left w-5"></i>
                    Returned
                </a>
                <a href="{{ route('cheques.upcoming') }}" class="flex items-center gap-4 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('cheques.upcoming') ? $navActive : $navInactive }}">
                    <i class="fa-regular fa-calendar-days w-5"></i>
                    Upcoming
                </a>
                {{-- ── Reports with sub-links ─────────────────────────── --}}
                @php $onReports = request()->routeIs('reports.*'); @endphp
                <div>
                    <a href="{{ route('reports.index') }}"
                        class="flex items-center gap-4 rounded-2xl px-4 py-3 text-sm font-medium transition {{ $onReports ? $navActive : $navInactive }}">
                        <i class="fa-solid fa-chart-column w-5"></i>
                        <span class="flex-1">Reports</span>
                        <i class="fa-solid fa-angle-{{ $onReports ? 'down' : 'right' }} text-xs opacity-60"></i>
                    </a>

                    {{-- Sub-links: always visible when on reports, hidden otherwise --}}
                    <div class="{{ $onReports ? 'block' : 'hidden' }} mt-1 space-y-0.5 pl-4" id="reportsSubMenu">
                        @php
                            $subReports = [
                                ['route' => 'reports.all-cheques',    'icon' => 'fa-solid fa-list',              'label' => 'All Cheques'],
                                ['route' => 'reports.pending-cheques','icon' => 'fa-regular fa-clock',           'label' => 'Pending'],
                                ['route' => 'reports.passed-cheques', 'icon' => 'fa-regular fa-circle-check',   'label' => 'Passed'],
                                ['route' => 'reports.returned-cheques','icon'=> 'fa-solid fa-rotate-left',      'label' => 'Returned'],
                                ['route' => 'reports.upcoming-cheques','icon'=> 'fa-regular fa-calendar-days',  'label' => 'Upcoming'],
                                ['route' => 'reports.bank-wise',      'icon' => 'fa-solid fa-building-columns', 'label' => 'Bank-wise'],
                                ['route' => 'reports.customer-wise',  'icon' => 'fa-solid fa-users',            'label' => 'Customer-wise'],
                                ['route' => 'reports.supplier-wise',  'icon' => 'fa-solid fa-truck-field',      'label' => 'Supplier-wise'],
                                ['route' => 'reports.monthly-summary','icon' => 'fa-solid fa-chart-bar',        'label' => 'Monthly Summary'],
                            ];
                        @endphp

                        @foreach ($subReports as $sub)
                            @php $isActive = request()->routeIs($sub['route']); @endphp
                            <a href="{{ route($sub['route']) }}"
                                class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition
                                    {{ $isActive
                                        ? 'bg-white/20 text-white'
                                        : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center">
                                    <i class="{{ $sub['icon'] }} text-[11px]"></i>
                                </span>
                                {{ $sub['label'] }}
                                @if ($isActive)
                                    <span class="ml-auto h-1.5 w-1.5 rounded-full bg-white"></span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
                <a href="{{ route('settings.index') }}" class="flex items-center gap-4 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('settings.*') ? $navActive : $navInactive }}">
                    <i class="fa-solid fa-gear w-5"></i>
                    Settings
                </a>
            </nav>

            <div class="border-t border-white/10 p-4">
                <div class="mb-4 rounded-2xl bg-white/10 p-4">
                    <p class="text-xs text-white/50">Logged in as</p>
                    <h3 class="mt-1 text-sm font-bold">{{ auth()->user()->name ?? 'Admin User' }}</h3>
                    <p class="text-xs text-white/50">{{ auth()->user()->email ?? 'admin@example.com' }}</p>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-white/10 px-4 py-3 text-sm font-semibold text-white/80 transition hover:bg-red-500 hover:text-white">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        Logout
                    </button>
                </form>

                <div class="mt-4 flex items-center justify-center gap-2 text-xs text-white/40">
                    <i class="fa-solid fa-code text-teal"></i>
                    Powered by <span class="font-semibold text-white/70">Twinsofte.com</span>
                </div>
            </div>
        </aside>

        <div class="min-h-screen min-w-0 flex-1 lg:ml-72">
            <header class="sticky top-0 z-30 bg-gradient-to-r from-navy via-navyLight to-primary px-4 py-4 text-white shadow-lg lg:hidden">
                <div class="flex items-center justify-between">
                    <button type="button" id="openSidebar" class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10" aria-label="Open navigation">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    <div class="text-center">
                        <h1 class="text-base font-extrabold">@yield('mobile_title', 'Cheque Dashboard')</h1>
                        <p class="text-[11px] text-white/60">Cheque Management System</p>
                    </div>
                    <a href="{{ route('cheques.upcoming') }}" class="relative flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10" title="View upcoming and due cheques">
                        <i class="fa-regular fa-bell text-lg"></i>
                        @if ($headerAlertCount > 0)
                            <span class="absolute right-2 top-2 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold">
                                {{ $headerAlertCount }}
                            </span>
                        @endif
                    </a>
                </div>
            </header>

            <header class="hidden border-b border-slate-200 bg-white/90 px-8 backdrop-blur lg:sticky lg:top-0 lg:z-30 lg:flex lg:h-20 lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-2xl font-extrabold text-navy">@yield('page_title', 'Dashboard')</h2>
                    <p class="mt-1 text-sm text-slate-500">Manage cheques, customers, suppliers, and reports</p>
                </div>

                <div class="flex items-center gap-4">
                    <form method="GET" action="{{ route('cheques.index') }}" class="relative hidden xl:block">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search cheques, customers, suppliers, banks..." class="w-96 rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-14 text-sm outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10">
                        <button type="submit" class="absolute right-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl bg-primary text-white transition hover:bg-blue-700" title="Search">
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </button>
                    </form>

                    <a href="{{ route('cheques.upcoming') }}" class="relative flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-600 transition hover:bg-primary hover:text-white" title="View upcoming and due cheques">
                        <i class="fa-regular fa-bell text-lg"></i>
                        @if ($headerAlertCount > 0)
                            <span class="absolute right-2 top-2 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[11px] font-bold text-white">{{ $headerAlertCount }}</span>
                        @endif
                    </a>

                    <a href="{{ route('cheques.create') }}" class="flex items-center gap-2 rounded-2xl bg-primary px-5 py-3 text-sm font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-blue-700">
                        <i class="fa-solid fa-plus"></i>
                        Add New Cheque
                    </a>

                    <div class="flex items-center gap-3 rounded-2xl bg-slate-100 px-3 py-2">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-white">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="hidden xl:block">
                            <h4 class="text-sm font-bold text-slate-800">{{ auth()->user()->name ?? 'Admin User' }}</h4>
                            <p class="text-xs text-slate-500">Administrator</p>
                        </div>
                    </div>
                </div>
            </header>

            <main class="min-h-screen px-4 pb-28 pt-5 sm:px-6 lg:px-8 lg:pb-8 lg:pt-8">
                <div class="mb-5 lg:hidden">
                    <form method="GET" action="{{ route('cheques.index') }}" class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search cheques..." class="w-full rounded-2xl border border-slate-200 bg-white py-4 pl-11 pr-4 text-sm shadow-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                    </form>
                </div>

                @yield('content')
            </main>
        </div>
    </div>

    <nav class="fixed bottom-0 left-0 right-0 z-40 border-t border-slate-200 bg-white px-3 py-2 shadow-[0_-10px_30px_rgba(15,23,42,0.08)] lg:hidden">
        <div class="grid grid-cols-5 items-center">
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center gap-1 rounded-2xl py-2 {{ request()->routeIs('dashboard') ? $bottomActive : $bottomInactive }}">
                <i class="fa-solid fa-house text-lg"></i>
                <span class="text-[11px] font-bold">Dashboard</span>
            </a>
            <a href="{{ route('cheques.pending') }}" class="flex flex-col items-center justify-center gap-1 rounded-2xl py-2 {{ request()->routeIs('cheques.pending') ? $bottomActive : $bottomInactive }}">
                <i class="fa-regular fa-clock text-lg"></i>
                <span class="text-[11px] font-medium">Pending</span>
            </a>
            <a href="{{ route('cheques.create') }}" class="-mt-8 flex flex-col items-center justify-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-primary text-white shadow-lg shadow-primary/30">
                    <i class="fa-solid fa-plus text-xl"></i>
                </div>
                <span class="mt-1 text-[11px] font-bold text-primary">Add</span>
            </a>
            <a href="{{ route('cheques.passed') }}" class="flex flex-col items-center justify-center gap-1 rounded-2xl py-2 {{ request()->routeIs('cheques.passed') ? $bottomActive : $bottomInactive }}">
                <i class="fa-regular fa-circle-check text-lg"></i>
                <span class="text-[11px] font-medium">Passed</span>
            </a>
            <a href="{{ route('cheques.upcoming') }}" class="flex flex-col items-center justify-center gap-1 rounded-2xl py-2 {{ request()->routeIs('cheques.upcoming') ? $bottomActive : $bottomInactive }}">
                <i class="fa-regular fa-calendar-days text-lg"></i>
                <span class="text-[11px] font-medium">Upcoming</span>
            </a>
        </div>
    </nav>

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileOverlay');
        const openSidebar = document.getElementById('openSidebar');

        function closeMobileSidebar() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }

        openSidebar?.addEventListener('click', () => {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        });
        overlay?.addEventListener('click', closeMobileSidebar);
        sidebar?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMobileSidebar));
    </script>

    @stack('scripts')
</body>
</html>
