<?php

namespace App\Http\Controllers;

use App\Models\Cheque;
use App\Models\ChequeSetting;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Services\TextitSmsService;
use App\Support\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmsController extends Controller
{
    public function __construct(protected TextitSmsService $sms) {}

    // ── SMS Settings ──────────────────────────────────────────────────────────

    public function settings(): View
    {
        $settings = $this->smsSettings();
        $recentLogs = SmsLog::with('template')
            ->latest()
            ->limit(10)
            ->get();

        return view('settings.sms', compact('settings', 'recentLogs'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sms_enabled'                   => ['sometimes', 'boolean'],
            'sms_provider'                  => ['nullable', 'string', 'max:50'],
            'textit_user_id'                => ['nullable', 'string', 'max:50'],
            'textit_password'               => ['nullable', 'string', 'max:255'],
            'textit_base_url'               => ['nullable', 'url', 'max:255'],
            'sms_method'                    => ['nullable', 'in:GET,POST'],
            'sms_ref_prefix'                => ['nullable', 'string', 'max:10'],
            'daily_sms_time'                => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'received_cheque_sms_enabled'   => ['sometimes', 'boolean'],
            'customer_reminder_sms_enabled' => ['sometimes', 'boolean'],
            'supplier_reminder_sms_enabled' => ['sometimes', 'boolean'],
            'returned_cheque_sms_enabled'   => ['sometimes', 'boolean'],
            'passed_cheque_sms_enabled'     => ['sometimes', 'boolean'],
            'overdue_cheque_sms_enabled'    => ['sometimes', 'boolean'],
        ]);

        $booleans = [
            'sms_enabled', 'received_cheque_sms_enabled', 'customer_reminder_sms_enabled', 'supplier_reminder_sms_enabled',
            'returned_cheque_sms_enabled', 'passed_cheque_sms_enabled', 'overdue_cheque_sms_enabled',
        ];

        foreach ($this->smsDefaults() as $key => $meta) {
            if ($key === 'textit_password') {
                // Only update password if a new value was submitted
                $raw = $request->input('textit_password_new', '');
                if (! empty($raw)) {
                    ChequeSetting::setValue($key, encrypt($raw), 'sms', 'password');
                }
                continue;
            }

            $value = in_array($key, $booleans)
                ? ($request->boolean($key) ? '1' : '0')
                : ($request->input($key, $meta['default'] ?? ''));

            ChequeSetting::setValue($key, $value, 'sms', $meta['type'] ?? 'text');
        }

        return redirect()->route('settings.sms')->with('success', 'SMS settings saved successfully.');
    }

    // ── SMS Templates ─────────────────────────────────────────────────────────

    public function templates(): View
    {
        $templates = SmsTemplate::orderBy('id')->get();
        $variables = TextitSmsService::availableVariables();

        return view('settings.sms-templates', compact('templates', 'variables'));
    }

    public function updateTemplate(Request $request, SmsTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'template_name' => ['required', 'string', 'max:120'],
            'message'       => ['required', 'string', 'max:2000'],
            'status'        => ['required', 'in:active,inactive'],
        ]);

        $template->update(array_merge($validated, [
            'updated_by' => auth()->id(),
        ]));

        return redirect()->route('settings.sms.templates')
            ->with('success', "Template \"{$template->template_name}\" updated.");
    }

    public function toggleTemplate(SmsTemplate $template): JsonResponse
    {
        $template->update([
            'status'     => $template->isActive() ? 'inactive' : 'active',
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'status'  => $template->status,
            'label'   => $template->isActive() ? 'Active' : 'Inactive',
            'success' => true,
        ]);
    }

    // ── Test SMS ──────────────────────────────────────────────────────────────

    public function testSms(Request $request): JsonResponse
    {
        $request->validate([
            'phone'   => ['required', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:500'],
        ]);

        $result = $this->sms->sendTestSms($request->phone, $request->message);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? 'Test SMS sent successfully. Message ID: ' . ($result['message_id'] ?? 'N/A')
                : 'Failed to send SMS: ' . ($result['error'] ?? 'Unknown error'),
            'response' => $result['response'],
        ]);
    }

    // ── Manual SMS from Cheque ────────────────────────────────────────────────

    public function sendManualSms(Request $request, Cheque $cheque): JsonResponse
    {
        $request->validate([
            'phone'        => ['required', 'string', 'max:20'],
            'message'      => ['required', 'string', 'max:500'],
            'template_key' => ['nullable', 'string', 'exists:sms_templates,template_key'],
        ]);

        // Resolve party details for variable replacement
        $vars = $this->buildChequeVars($cheque);

        // If message came pre-built from form, use it directly; otherwise resolve template
        $message = $request->message;

        $templateId = null;
        if ($request->filled('template_key')) {
            $tpl = SmsTemplate::getByKey($request->template_key);
            if ($tpl) {
                $templateId = $tpl->id;
                $message    = $this->sms->replaceVars($tpl->message, $vars);
            }
        }

        $ref = substr($this->sms->refPrefix() . '-' . $cheque->cheque_no, 0, 15);

        $result = $this->sms->sendSms($request->phone, $message, $ref, null, [
            'cheque_id'       => $cheque->id,
            'sms_template_id' => $templateId,
            'recipient_type'  => 'manual',
        ]);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? 'SMS sent to ' . $request->phone
                : 'Send failed: ' . ($result['error'] ?? 'Unknown error'),
        ]);
    }

    // ── Recent Logs (JSON) ────────────────────────────────────────────────────

    public function logs(): JsonResponse
    {
        $logs = SmsLog::with('template', 'cheque')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (SmsLog $log) => [
                'id'           => $log->id,
                'phone'        => $log->phone,
                'message'      => \Str::limit($log->message, 80),
                'status'       => $log->status,
                'provider'     => $log->provider,
                'template'     => $log->template?->template_name,
                'cheque_no'    => $log->cheque?->cheque_no,
                'sent_at'      => $log->sent_at?->format('d M Y H:i'),
                'created_at'   => $log->created_at->format('d M Y H:i'),
            ]);

        return response()->json($logs);
    }

    // ── Private Helpers ───────────────────────────────────────────────────────

    private function smsSettings(): array
    {
        $defaults = $this->smsDefaults();
        $stored   = ChequeSetting::query()->where('group', 'sms')->pluck('value', 'key');

        $result = [];
        foreach ($defaults as $key => $meta) {
            $result[$key] = $stored[$key] ?? ($meta['default'] ?? null);
        }

        return $result;
    }

    private function smsDefaults(): array
    {
        return [
            'sms_enabled'                   => ['label' => 'Enable SMS Gateway',          'type' => 'boolean', 'default' => '0'],
            'sms_provider'                  => ['label' => 'SMS Provider',                'type' => 'text',    'default' => 'textit'],
            'textit_user_id'                => ['label' => 'Textit User ID / Phone',      'type' => 'text',    'default' => ''],
            'textit_password'               => ['label' => 'Textit Password',             'type' => 'password','default' => ''],
            'textit_base_url'               => ['label' => 'API Base URL',                'type' => 'url',     'default' => 'https://textit.biz/sendmsg'],
            'sms_method'                    => ['label' => 'HTTP Method',                 'type' => 'select',  'default' => 'GET'],
            'sms_ref_prefix'                => ['label' => 'Reference Prefix',            'type' => 'text',    'default' => 'CHEQUE'],
            'daily_sms_time'                => ['label' => 'Daily SMS Send Time',         'type' => 'time',    'default' => '09:00'],
            'received_cheque_sms_enabled'   => ['label' => 'Received Cheque SMS',         'type' => 'boolean', 'default' => '0'],
            'customer_reminder_sms_enabled' => ['label' => 'Customer Reminder SMS',       'type' => 'boolean', 'default' => '0'],
            'supplier_reminder_sms_enabled' => ['label' => 'Supplier Reminder SMS',       'type' => 'boolean', 'default' => '0'],
            'returned_cheque_sms_enabled'   => ['label' => 'Returned Cheque SMS',         'type' => 'boolean', 'default' => '0'],
            'passed_cheque_sms_enabled'     => ['label' => 'Passed Cheque SMS',           'type' => 'boolean', 'default' => '0'],
            'overdue_cheque_sms_enabled'    => ['label' => 'Overdue Cheque SMS',          'type' => 'boolean', 'default' => '0'],
        ];
    }

    private function buildChequeVars(Cheque $cheque): array
    {
        $cheque->load(['customer', 'supplier']);
        $company = ChequeSetting::getValue('company_name', 'Cheque Management System');

        return [
            'company_name'   => $company,
            'system_name'    => $company,
            'customer_name'  => $cheque->customer?->name ?? '',
            'supplier_name'  => $cheque->supplier?->name ?? '',
            'payee_name'     => $cheque->customer?->name ?? $cheque->supplier?->name ?? '',
            'cheque_no'      => $cheque->cheque_no,
            'bank_name'      => $cheque->bank_name,
            'branch_name'    => $cheque->branch_name ?? '',
            'cheque_date'    => $cheque->cheque_date?->toDateString(),
            'amount'         => $cheque->amount,
            'status'         => $cheque->status,
            'return_reason'  => $cheque->returned_reason ?? '',
            'return_charge'  => $cheque->return_charge ?? 0,
            'contact_phone'  => $cheque->customer?->phone ?? $cheque->supplier?->phone ?? '',
        ];
    }
}
