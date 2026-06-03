@php
    use App\Models\Cheque;
    use App\Support\ChequePresentation;
    use App\Support\Currency;

    $statusColors = [
        'passed'    => '#16A34A',
        'pending'   => '#F97316',
        'deposited' => '#0EA5E9',
        'returned'  => '#EF4444',
        'cancelled' => '#94A3B8',
        'hold'      => '#6B7280',
    ];

    $filterLabels = [];
    if (!empty($filters['from_date']))   $filterLabels[] = 'From: ' . \Carbon\Carbon::parse($filters['from_date'])->format('d M Y');
    if (!empty($filters['to_date']))     $filterLabels[] = 'To: '   . \Carbon\Carbon::parse($filters['to_date'])->format('d M Y');
    if (!empty($filters['cheque_type'])) {
        $filterLabels[] = 'Type: ' . match ($filters['cheque_type']) {
            'customer_received' => 'Customer Received',
            'own_issued' => 'Own Issued',
            'customer_cheque_given_to_supplier' => 'Customer Cheque Given to Supplier',
            default => ucfirst($filters['cheque_type']),
        };
    }
    if (!empty($filters['bank']))        $filterLabels[] = 'Bank: ' . $filters['bank'];
    if (!empty($filters['cheque_no']))   $filterLabels[] = 'Cheque No: ' . $filters['cheque_no'];
    if (!empty($filters['period']))      $filterLabels[] = 'Period: ' . ucwords(str_replace('_', ' ', $filters['period']));
    if (!empty($filters['amount_min']))  $filterLabels[] = 'Min: ' . Currency::formatLkr($filters['amount_min']);
    if (!empty($filters['amount_max']))  $filterLabels[] = 'Max: ' . Currency::formatLkr($filters['amount_max']);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportTitle }} — {{ $settings['company_name'] }}</title>
    <style>
        /* ── Base ──────────────────────────────────────────────────────── */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: #f8fafc;
            padding: 0;
        }

        /* ── Print wrapper ─────────────────────────────────────────────── */
        .page {
            max-width: 210mm;
            margin: 0 auto;
            background: #fff;
            padding: 16mm 14mm 12mm;
        }

        /* ── Print button bar (screen only) ────────────────────────────── */
        .print-bar {
            background: #061A3A;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            gap: 12px;
        }
        .print-bar-title { font-size: 13px; font-weight: 700; }
        .print-bar-actions { display: flex; gap: 8px; }
        .btn-print {
            background: #0B5CFF;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 8px 18px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-back {
            background: rgba(255,255,255,0.15);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 8px 18px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ── Letterhead ────────────────────────────────────────────────── */
        .letterhead {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            border-bottom: 3px solid #0B5CFF;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .letterhead-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .letterhead-icon {
            width: 46px;
            height: 46px;
            background: linear-gradient(135deg, #0B5CFF, #082A5E);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 22px;
            font-weight: 900;
            letter-spacing: -1px;
        }
        .letterhead-company { font-size: 18px; font-weight: 900; color: #061A3A; line-height: 1.2; }
        .letterhead-tagline  { font-size: 9px; color: #64748b; margin-top: 2px; }
        .letterhead-right    { text-align: right; font-size: 9px; color: #64748b; line-height: 1.7; }
        .letterhead-right strong { color: #1e293b; }

        /* ── Report title block ────────────────────────────────────────── */
        .report-title-block {
            background: linear-gradient(135deg, #f1f5f9, #e8f0fe);
            border-left: 4px solid #0B5CFF;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 14px;
        }
        .report-title-block h1 { font-size: 16px; font-weight: 900; color: #061A3A; }
        .report-meta { display: flex; gap: 20px; margin-top: 6px; flex-wrap: wrap; }
        .report-meta span { font-size: 9px; color: #64748b; }
        .report-meta strong { color: #1e293b; }

        /* ── Filter badge strip ────────────────────────────────────────── */
        .filters-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 14px;
        }
        .filter-chip {
            background: #EFF6FF;
            color: #0B5CFF;
            border: 1px solid #BFDBFE;
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 9px;
            font-weight: 700;
        }

        /* ── Summary boxes ─────────────────────────────────────────────── */
        .summary-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }
        .summary-box {
            border-radius: 10px;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
        }
        .summary-box.blue   { background: #EFF6FF; border-color: #BFDBFE; }
        .summary-box.green  { background: #F0FDF4; border-color: #BBF7D0; }
        .summary-box.teal   { background: #F0FDFA; border-color: #99F6E4; }
        .summary-box.purple { background: #F5F3FF; border-color: #DDD6FE; }
        .summary-label { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; }
        .summary-value { font-size: 13px; font-weight: 900; margin-top: 4px; }
        .summary-box.blue   .summary-value { color: #0B5CFF; }
        .summary-box.green  .summary-value { color: #16A34A; }
        .summary-box.teal   .summary-value { color: #0D9488; }
        .summary-box.purple .summary-value { color: #7C3AED; }

        /* ── Table ─────────────────────────────────────────────────────── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 10.5px;
        }
        thead tr {
            background: #061A3A;
            color: #fff;
        }
        thead th {
            padding: 8px 10px;
            text-align: left;
            font-weight: 700;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }
        thead th.right { text-align: right; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:nth-child(odd)  { background: #fff; }
        tbody td {
            padding: 7px 10px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        tbody td.right { text-align: right; }
        tbody td.cheque-no { font-weight: 700; color: #061A3A; }
        tbody td.amount    { font-weight: 700; color: #0B5CFF; }
        tfoot tr { background: #f1f5f9; border-top: 2px solid #0B5CFF; }
        tfoot td { padding: 8px 10px; font-weight: 700; }

        /* ── Status badge ──────────────────────────────────────────────── */
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 8.5px;
            font-weight: 700;
            white-space: nowrap;
        }
        .type-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 8.5px;
            font-weight: 700;
            white-space: nowrap;
        }
        .badge-received { background: #DBEAFE; color: #1D4ED8; }
        .badge-issued   { background: #EDE9FE; color: #6D28D9; }
        .badge-transferred { background: #FEF3C7; color: #D97706; }
        .badge-passed   { background: #DCFCE7; color: #15803D; }
        .badge-pending  { background: #FEF3C7; color: #B45309; }
        .badge-returned { background: #FEE2E2; color: #DC2626; }
        .badge-hold     { background: #F1F5F9; color: #475569; }

        /* ── Bank group heading ────────────────────────────────────────── */
        .bank-group-header {
            background: linear-gradient(90deg, #EFF6FF, #F8FAFC);
            border-left: 3px solid #7C3AED;
            border-radius: 8px;
            padding: 8px 12px;
            margin: 14px 0 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .bank-name { font-weight: 800; color: #061A3A; font-size: 11px; }
        .bank-total { font-weight: 700; color: #7C3AED; font-size: 11px; }

        /* ── Monthly group ─────────────────────────────────────────────── */
        .month-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            margin-bottom: 8px;
            background: #fff;
        }
        .month-label { font-weight: 800; color: #061A3A; font-size: 12px; }
        .month-count { font-size: 9px; color: #64748b; }
        .month-amounts { display: flex; gap: 16px; text-align: right; }
        .month-amounts > div > p:first-child { font-size: 8px; color: #94A3B8; }
        .month-amounts > div > p:last-child  { font-size: 11px; font-weight: 700; }

        /* ── Progress bar ──────────────────────────────────────────────── */
        .progress-bar { height: 4px; background: #e2e8f0; border-radius: 99px; margin-top: 6px; overflow: hidden; }
        .progress-fill { height: 100%; background: #0B5CFF; border-radius: 99px; }

        /* ── Signature & footer ────────────────────────────────────────── */
        .sign-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 32px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
        }
        .sign-box { width: 180px; }
        .sign-line { border-top: 1px solid #061A3A; padding-top: 6px; font-size: 10px; color: #64748b; }
        .doc-footer {
            margin-top: 14px;
            text-align: center;
            font-size: 8.5px;
            color: #94A3B8;
            border-top: 1px solid #f1f5f9;
            padding-top: 8px;
        }

        /* ── Print media ───────────────────────────────────────────────── */
        @media print {
            body     { background: #fff; font-size: 10px; }
            .print-bar { display: none !important; }
            .page    { max-width: 100%; padding: 8mm 10mm; margin: 0; }
            table    { font-size: 9.5px; }
            thead th { font-size: 9px; padding: 6px 8px; }
            tbody td { padding: 5px 8px; }
            @page    { size: A4; margin: 0; }
        }
    </style>
</head>
<body>

{{-- ── Browser Print Bar (hidden on print) ───────────────────────────────── --}}
<div class="print-bar">
    <div class="print-bar-title">
        📄 {{ $reportTitle }}
        <span style="font-weight:400;opacity:0.7;font-size:11px;"> — {{ $settings['company_name'] }}</span>
    </div>
    <div class="print-bar-actions">
        <a href="javascript:history.back()" class="btn-back">← Back</a>
        <button class="btn-print" onclick="window.print()">🖨 Print / Save PDF</button>
    </div>
</div>

<div class="page">

    {{-- ── Letterhead ──────────────────────────────────────────────────── --}}
    <div class="letterhead">
        <div class="letterhead-logo">
            <div class="letterhead-icon">C</div>
            <div>
                <div class="letterhead-company">{{ $settings['letterhead_title'] }}</div>
                <div class="letterhead-tagline">Powered by Twinsofte.com</div>
            </div>
        </div>
        <div class="letterhead-right">
            @if($settings['company_phone'])
                <div>📞 <strong>{{ $settings['company_phone'] }}</strong></div>
            @endif
            @if($settings['company_email'])
                <div>✉ <strong>{{ $settings['company_email'] }}</strong></div>
            @endif
            @if($settings['company_address'])
                <div>{{ $settings['company_address'] }}</div>
            @endif
        </div>
    </div>

    {{-- ── Report Title Block ───────────────────────────────────────────── --}}
    <div class="report-title-block">
        <h1>{{ $reportTitle }}</h1>
        <div class="report-meta">
            <span>Generated: <strong>{{ now()->format('d M Y, h:i A') }}</strong></span>
            <span>By: <strong>{{ $generatedBy }}</strong></span>
            <span>Total Records: <strong>{{ $cheques->count() }}</strong></span>
        </div>
    </div>

    {{-- ── Active Filters ───────────────────────────────────────────────── --}}
    @if (!empty($filterLabels))
        <div class="filters-strip">
            <span style="font-size:9px;font-weight:700;color:#64748b;margin-right:4px;">Filters:</span>
            @foreach ($filterLabels as $fl)
                <span class="filter-chip">{{ $fl }}</span>
            @endforeach
        </div>
    @endif

    {{-- ── Summary Boxes ────────────────────────────────────────────────── --}}
    <div class="summary-row">
        <div class="summary-box blue">
            <div class="summary-label">Total Cheques</div>
            <div class="summary-value">{{ number_format($cheques->count()) }}</div>
        </div>
        <div class="summary-box green">
            <div class="summary-label">Total Amount</div>
            <div class="summary-value">{{ Currency::formatLkr($totalAmount) }}</div>
        </div>
        <div class="summary-box teal">
            <div class="summary-label">Customer Received</div>
            <div class="summary-value">{{ Currency::formatLkr($customerAmount) }}</div>
        </div>
        <div class="summary-box purple">
            <div class="summary-label">Own Issued</div>
            <div class="summary-value">{{ Currency::formatLkr($ownAmount) }}</div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{-- BANK-WISE LAYOUT                                                     --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    @if ($reportType === 'bank-wise' && $bankGroups)

        @foreach ($bankGroups as $bankName => $group)
            <div class="bank-group-header">
                <span class="bank-name">🏦 {{ $bankName }}</span>
                <span class="bank-total">{{ $group['count'] }} cheques &nbsp;|&nbsp; {{ Currency::formatLkr($group['amount']) }}</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Cheque No</th>
                        <th>Type</th>
                        <th>Customer / Supplier</th>
                        <th>Date</th>
                        <th class="right">Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($group['cheques'] as $cheque)
                        <tr>
                            <td class="cheque-no">{{ $cheque->cheque_no }}</td>
                            <td>
                                <span class="type-badge {{ $cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED ? 'badge-received' : ($cheque->cheque_type === Cheque::TYPE_OWN_ISSUED ? 'badge-issued' : 'badge-transferred') }}">
                                    {{ ChequePresentation::typeLabel($cheque->cheque_type) }}
                                </span>
                            </td>
                            <td>
                                @if ($cheque->cheque_type === Cheque::TYPE_TRANSFER)
                                    <strong>From Customer:</strong> {{ $cheque->customer?->name ?? '—' }} <br>
                                    <strong>To Supplier:</strong> {{ $cheque->supplier?->name ?? '—' }}
                                @elseif ($cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED && $cheque->is_transferred_to_supplier)
                                    {{ $cheque->customer?->name ?? '—' }} <br>
                                    <span style="font-size: 8.5px; color: #d97706; font-weight: bold;">Given to Supplier: {{ $cheque->givenToSupplier?->name ?? '—' }}</span>
                                @else
                                    {{ $cheque->customer?->name ?? $cheque->supplier?->name ?? '—' }}
                                @endif
                            </td>
                            <td>{{ $cheque->cheque_date?->format('d M Y') }}</td>
                            <td class="amount right">{{ Currency::formatLkr($cheque->amount) }}</td>
                            <td>
                                <span class="status-badge badge-{{ $cheque->status }}">{{ ChequePresentation::statusLabel($cheque->status) }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{-- CUSTOMER / SUPPLIER WISE LAYOUT                                      --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    @elseif (in_array($reportType, ['customer-wise', 'supplier-wise'], true) && $partyGroups)

        @foreach ($partyGroups as $partyName => $group)
            <div class="bank-group-header">
                <span class="bank-name">{{ $reportType === 'customer-wise' ? '👥' : '🚚' }} {{ $partyName }}</span>
                <span class="bank-total">{{ $group['count'] }} cheques &nbsp;|&nbsp; {{ Currency::formatLkr($group['amount']) }}</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Cheque No</th>
                        <th>Type</th>
                        <th>Customer</th>
                        <th>Supplier</th>
                        <th>Bank</th>
                        <th>Date</th>
                        <th class="right">Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($group['cheques'] as $cheque)
                        <tr>
                            <td class="cheque-no">{{ $cheque->cheque_no }}</td>
                            <td>
                                <span class="type-badge {{ $cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED ? 'badge-received' : ($cheque->cheque_type === Cheque::TYPE_OWN_ISSUED ? 'badge-issued' : 'badge-transferred') }}">
                                    {{ $cheque->isOwnIssued() && $cheque->supplier_cheque_mode === 'received_customer_cheque' ? 'Customer Cheque' : ChequePresentation::typeLabel($cheque->cheque_type) }}
                                </span>
                            </td>
                            <td>{{ $cheque->customer?->name ?? $cheque->originalCustomer?->name ?? '—' }}</td>
                            <td>{{ $cheque->supplier?->name ?? $cheque->givenToSupplier?->name ?? '—' }}</td>
                            <td>{{ $cheque->bank_name }}</td>
                            <td>{{ $cheque->cheque_date?->format('d M Y') }}</td>
                            <td class="amount right">{{ Currency::formatLkr($cheque->amount) }}</td>
                            <td>
                                <span class="status-badge badge-{{ $cheque->status }}">{{ ChequePresentation::statusLabel($cheque->status) }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{-- MONTHLY SUMMARY LAYOUT                                               --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    @elseif ($reportType === 'monthly-summary' && $monthGroups)

        @php $maxAmt = $monthGroups->max('amount'); @endphp
        @foreach ($monthGroups as $group)
            <div class="month-row">
                <div>
                    <div class="month-label">{{ $group['label'] }}</div>
                    <div class="month-count">{{ $group['count'] }} cheque(s)</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width:{{ $maxAmt > 0 ? round(($group['amount']/$maxAmt)*100) : 0 }}%"></div>
                    </div>
                </div>
                <div class="month-amounts">
                    <div>
                        <p>Customer Rcvd</p>
                        <p style="color:#0D9488">{{ Currency::formatLkr($group['customer']) }}</p>
                    </div>
                    <div>
                        <p>Own Issued</p>
                        <p style="color:#7C3AED">{{ Currency::formatLkr($group['own']) }}</p>
                    </div>
                    <div>
                        <p>Total</p>
                        <p style="color:#0B5CFF">{{ Currency::formatLkr($group['amount']) }}</p>
                    </div>
                </div>
            </div>
        @endforeach

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{-- STANDARD CHEQUE TABLE                                                --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    @else
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cheque No</th>
                    <th>Type</th>
                    <th>Customer / Supplier</th>
                    <th>Bank</th>
                    <th>Branch</th>
                    <th>Cheque Date</th>
                    <th class="right">Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cheques as $i => $cheque)
                    <tr>
                        <td style="color:#94A3B8">{{ $i + 1 }}</td>
                        <td class="cheque-no">{{ $cheque->cheque_no }}</td>
                        <td>
                            <span class="type-badge {{ $cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED ? 'badge-received' : ($cheque->cheque_type === Cheque::TYPE_OWN_ISSUED ? 'badge-issued' : 'badge-transferred') }}">
                                {{ ChequePresentation::typeLabel($cheque->cheque_type) }}
                            </span>
                        </td>
                        <td>
                            @if ($cheque->cheque_type === Cheque::TYPE_TRANSFER)
                                <strong>From Customer:</strong> {{ $cheque->customer?->name ?? '—' }} <br>
                                <strong>To Supplier:</strong> {{ $cheque->supplier?->name ?? '—' }}
                            @elseif ($cheque->cheque_type === Cheque::TYPE_CUSTOMER_RECEIVED && $cheque->is_transferred_to_supplier)
                                {{ $cheque->customer?->name ?? '—' }} <br>
                                <span style="font-size: 8.5px; color: #d97706; font-weight: bold;">Given to Supplier: {{ $cheque->givenToSupplier?->name ?? '—' }}</span>
                            @else
                                {{ $cheque->customer?->name ?? $cheque->supplier?->name ?? '—' }}
                            @endif
                        </td>
                        <td>{{ $cheque->bank_name }}</td>
                        <td>{{ $cheque->branch_name ?: '—' }}</td>
                        <td>{{ $cheque->cheque_date?->format('d M Y') }}</td>
                        <td class="amount right">{{ Currency::formatLkr($cheque->amount) }}</td>
                        <td>
                            <span class="status-badge badge-{{ $cheque->status }}">
                                {{ ChequePresentation::statusLabel($cheque->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center;padding:20px;color:#94A3B8">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($cheques->count())
                <tfoot>
                    <tr>
                        <td colspan="7" style="color:#061A3A">
                            Total — {{ number_format($cheques->count()) }} records
                        </td>
                        <td class="right" style="color:#0B5CFF;font-size:12px;">{{ Currency::formatLkr($totalAmount) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    @endif

    {{-- ── Signature & Footer ──────────────────────────────────────────── --}}
    <div class="sign-row">
        <div class="sign-box">
            <div class="sign-line">{{ $settings['signature_name'] }}</div>
        </div>
        <div style="text-align:right;font-size:9px;color:#94A3B8">
            <div>{{ $settings['company_name'] }}</div>
            <div style="margin-top:3px;">{{ now()->format('d M Y') }}</div>
        </div>
    </div>

    <div class="doc-footer">
        {{ $settings['footer_text'] }}
    </div>
</div>

<script>
    // Auto-trigger print dialog when the page loads
    window.addEventListener('load', () => {
        // Small delay to ensure styles are fully applied
        setTimeout(() => window.print(), 600);
    });
</script>

</body>
</html>
