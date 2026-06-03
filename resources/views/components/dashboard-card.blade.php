@props(['icon', 'title', 'count' => null, 'amount' => 0, 'color' => 'primary', 'sub' => 'Amount'])

@php
    use App\Support\Currency;

    $colorClass = match ($color) {
        'warning' => 'bg-orange-100 text-warning',
        'success' => 'bg-green-100 text-success',
        'danger' => 'bg-red-100 text-danger',
        'teal' => 'bg-teal/10 text-teal',
        'purplePay' => 'bg-purple-100 text-purplePay',
        default => 'bg-blue-100 text-primary',
    };

    $textClass = match ($color) {
        'warning' => 'text-warning',
        'success' => 'text-success',
        'danger' => 'text-danger',
        'teal' => 'text-teal',
        'purplePay' => 'text-purplePay',
        default => 'text-primary',
    };
@endphp

<div class="rounded-3xl bg-white p-5 shadow-soft">
    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl {{ $colorClass }}">
        <i class="{{ $icon }}"></i>
    </div>
    <p class="text-sm text-slate-500">{{ $title }}</p>
    @if (! is_null($count))
        <h3 class="mt-1 text-2xl font-extrabold text-navy">{{ number_format($count) }}</h3>
        <p class="mt-2 text-xs font-semibold {{ $textClass }}">{{ Currency::formatLkr($amount) }}</p>
    @else
        <h3 class="mt-1 text-xl font-extrabold text-navy">{{ Currency::formatLkr($amount) }}</h3>
        <p class="mt-2 text-xs font-semibold {{ $textClass }}">{{ $sub }}</p>
    @endif
</div>
