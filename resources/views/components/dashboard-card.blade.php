@props(['icon', 'title', 'count' => null, 'amount' => 0, 'color' => 'primary', 'sub' => 'Amount'])

@php
    use App\Support\Currency;

    $colorClass = match ($color) {
        'warning' => 'bg-orange-50 text-orange-600 border border-orange-100/70',
        'success' => 'bg-emerald-50 text-emerald-600 border border-emerald-100/70',
        'danger' => 'bg-red-50 text-red-600 border border-red-100/70',
        'teal' => 'bg-teal-50 text-teal-600 border border-teal-100/70',
        'purplePay' => 'bg-purple-50 text-purple-600 border border-purple-100/70',
        default => 'bg-blue-50 text-primary border border-blue-100/70',
    };

    $textClass = match ($color) {
        'warning' => 'text-orange-600',
        'success' => 'text-emerald-600',
        'danger' => 'text-red-600',
        'teal' => 'text-teal-600',
        'purplePay' => 'text-purple-600',
        default => 'text-primary',
    };

    $formattedAmount = number_format((float) $amount, 2, '.', ',');
@endphp

<div class="rounded-3xl bg-white p-5 md:p-6 shadow-soft hover:shadow-lg hover:-translate-y-1 transition-all duration-300 border border-slate-100/40 flex flex-col justify-between h-full group">
    <div class="flex items-start justify-between">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $colorClass }} text-base transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3">
            <i class="{{ $icon }}"></i>
        </div>
        @if (! is_null($count))
            <span class="rounded-full bg-slate-50 border border-slate-100 px-3 py-1.2 text-xs font-bold text-navy shadow-sm transition-colors duration-300 group-hover:bg-slate-100">
                {{ number_format($count) }} Cheque{{ $count === 1 ? '' : 's' }}
            </span>
        @endif
    </div>
    
    <div class="mt-5">
        <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 group-hover:text-slate-500 transition-colors duration-300">{{ $title }}</p>
        <h3 class="mt-2 flex items-baseline gap-1 text-navy leading-none tracking-tight">
            <span class="text-sm font-bold text-slate-400 group-hover:text-slate-500 transition-colors duration-300">Rs</span>
            <span class="text-xl md:text-2xl font-black leading-none">{{ $formattedAmount }}</span>
        </h3>
        @if (is_null($count))
            <p class="mt-1 text-[10px] font-bold {{ $textClass }} uppercase tracking-wider">{{ $sub }}</p>
        @endif
    </div>
</div>
