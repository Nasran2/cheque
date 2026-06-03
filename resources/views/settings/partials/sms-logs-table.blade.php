@if ($recentLogs->isEmpty())
    <div class="py-8 text-center text-sm text-slate-400">
        <i class="fa-solid fa-inbox mb-2 block text-2xl"></i>
        No SMS logs yet.
    </div>
@else
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-slate-200">
                    <th class="pb-2 pr-4 text-left text-xs font-bold text-slate-500">Phone</th>
                    <th class="pb-2 pr-4 text-left text-xs font-bold text-slate-500">Message</th>
                    <th class="pb-2 pr-4 text-left text-xs font-bold text-slate-500">Status</th>
                    <th class="pb-2 text-left text-xs font-bold text-slate-500">Sent At</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recentLogs as $log)
                    <tr class="border-b border-slate-100 last:border-0">
                        <td class="py-3 pr-4 text-sm font-semibold text-navy">{{ $log->phone }}</td>
                        <td class="max-w-xs truncate py-3 pr-4 text-xs text-slate-600">{{ \Str::limit($log->message, 80) }}</td>
                        <td class="py-3 pr-4">
                            @if ($log->status === 'sent')
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">Sent</span>
                            @elseif ($log->status === 'failed')
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">Failed</span>
                            @else
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">Pending</span>
                            @endif
                        </td>
                        <td class="py-3 text-xs text-slate-400">
                            {{ $log->sent_at?->format('d M Y H:i') ?? $log->created_at->format('d M Y H:i') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
