<?php

namespace App\Http\Controllers;

use App\Models\ChequeSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.index', [
            'settings' => $this->settings(),
            'defaults' => $this->defaults(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        foreach ($this->defaults() as $group => $items) {
            foreach ($items as $key => $meta) {
                $value = $request->input($key, $meta['type'] === 'boolean' ? '0' : ($meta['default'] ?? null));
                ChequeSetting::setValue($key, $value, $group, $meta['type']);
            }
        }

        return redirect()->route('settings.index')->with('success', 'Settings saved successfully.');
    }

    private function settings(): array
    {
        $values = ChequeSetting::query()->pluck('value', 'key');

        return collect($this->defaults())
            ->flatMap(fn (array $items) => $items)
            ->mapWithKeys(fn (array $meta, string $key) => [$key => $values[$key] ?? ($meta['default'] ?? null)])
            ->all();
    }

    private function defaults(): array
    {
        return [
            'general' => [
                'company_name' => ['label' => 'Company Name', 'type' => 'text', 'default' => 'Cheque Management System'],
                'company_phone' => ['label' => 'Company Phone', 'type' => 'text', 'default' => ''],
                'company_email' => ['label' => 'Company Email', 'type' => 'email', 'default' => ''],
                'company_address' => ['label' => 'Company Address', 'type' => 'textarea', 'default' => ''],
                'currency_symbol' => ['label' => 'Currency Symbol', 'type' => 'text', 'default' => 'Rs'],
                'date_format' => ['label' => 'Date Format', 'type' => 'text', 'default' => 'd M Y'],
                'timezone' => ['label' => 'Timezone', 'type' => 'text', 'default' => 'Asia/Colombo'],
            ],
            'cheque_rules' => [
                'enable_approval' => ['label' => 'Enable cheque approval system', 'type' => 'boolean', 'default' => '0'],
                'attachment_required' => ['label' => 'Enable attachment required', 'type' => 'boolean', 'default' => '0'],
                'duplicate_validation' => ['label' => 'Enable duplicate cheque validation', 'type' => 'boolean', 'default' => '1'],
                'allow_same_number_different_account' => ['label' => 'Allow same cheque number for different bank/account', 'type' => 'boolean', 'default' => '1'],
                'pending_as_temporary_paid' => ['label' => 'Enable pending cheque as temporary paid', 'type' => 'boolean', 'default' => '0'],
                'default_cheque_status' => ['label' => 'Default cheque status', 'type' => 'text', 'default' => 'pending'],
                'default_return_charge' => ['label' => 'Default return charge in Rs', 'type' => 'number', 'default' => '0'],
            ],
            'customer_reminders' => [
                'customer_reminders_enabled' => ['label' => 'Enable customer cheque reminders', 'type' => 'boolean', 'default' => '1'],
                'customer_reminder_days' => ['label' => 'Reminder before days for customers', 'type' => 'text', 'default' => '5,2,1'],
                'customer_sms_enabled' => ['label' => 'Enable customer SMS reminder', 'type' => 'boolean', 'default' => '0'],
                'customer_whatsapp_enabled' => ['label' => 'Enable customer WhatsApp reminder', 'type' => 'boolean', 'default' => '0'],
                'customer_email_enabled' => ['label' => 'Enable customer email reminder', 'type' => 'boolean', 'default' => '0'],
                'customer_reminder_template' => ['label' => 'Customer reminder message template', 'type' => 'textarea', 'default' => 'Reminder: cheque {cheque_no} for {amount} is due on {date}.'],
            ],
            'supplier_reminders' => [
                'supplier_reminders_enabled' => ['label' => 'Enable own cheque reminders', 'type' => 'boolean', 'default' => '1'],
                'supplier_reminder_days' => ['label' => 'Reminder before days for suppliers', 'type' => 'text', 'default' => '7,5,2,1'],
                'supplier_sms_enabled' => ['label' => 'Enable supplier SMS reminder', 'type' => 'boolean', 'default' => '0'],
                'supplier_whatsapp_enabled' => ['label' => 'Enable supplier WhatsApp reminder', 'type' => 'boolean', 'default' => '0'],
                'supplier_email_enabled' => ['label' => 'Enable supplier email reminder', 'type' => 'boolean', 'default' => '0'],
                'supplier_reminder_template' => ['label' => 'Supplier reminder message template', 'type' => 'textarea', 'default' => 'Reminder: own cheque {cheque_no} for {amount} is due on {date}.'],
            ],
            'notifications' => [
                'dashboard_reminder_badge' => ['label' => 'Show dashboard reminder badge', 'type' => 'boolean', 'default' => '1'],
                'overdue_alert_badge' => ['label' => 'Show overdue alert badge', 'type' => 'boolean', 'default' => '1'],
                'today_cheque_badge' => ['label' => 'Show today cheque badge', 'type' => 'boolean', 'default' => '1'],
                'reminder_send_time' => ['label' => 'Send reminder at what time', 'type' => 'text', 'default' => '09:00'],
                'daily_summary_notification' => ['label' => 'Enable daily cheque summary notification', 'type' => 'boolean', 'default' => '1'],
            ],
            'pdf' => [
                'letterhead_title' => ['label' => 'Company letterhead title', 'type' => 'text', 'default' => 'Cheque Management System'],
                'letterhead_address' => ['label' => 'Company address line', 'type' => 'text', 'default' => ''],
                'letterhead_contact' => ['label' => 'Company phone/email line', 'type' => 'text', 'default' => ''],
                'footer_text' => ['label' => 'Footer text', 'type' => 'text', 'default' => 'Generated by Cheque Management System | Powered by Twinsofte.com'],
                'signature_name' => ['label' => 'Authorized signature name', 'type' => 'text', 'default' => 'Authorized Signature'],
                'show_generated_by' => ['label' => 'Show generated by user', 'type' => 'boolean', 'default' => '1'],
                'show_generated_datetime' => ['label' => 'Show generated date/time', 'type' => 'boolean', 'default' => '1'],
                'show_page_numbers' => ['label' => 'Show page numbers', 'type' => 'boolean', 'default' => '1'],
            ],
            'banks_permissions' => [
                'bank_settings_note' => ['label' => 'Bank/branch management note', 'type' => 'textarea', 'default' => 'Manage bank and branch lists from the Banks module when enabled.'],
                'can_view_settings' => ['label' => 'Who can view settings', 'type' => 'text', 'default' => 'admin'],
                'can_export_reports' => ['label' => 'Who can export reports', 'type' => 'text', 'default' => 'admin'],
                'can_mark_passed' => ['label' => 'Who can mark passed', 'type' => 'text', 'default' => 'admin'],
                'can_mark_returned' => ['label' => 'Who can mark returned', 'type' => 'text', 'default' => 'admin'],
                'can_approve_cheques' => ['label' => 'Who can approve cheques', 'type' => 'text', 'default' => 'admin'],
            ],
        ];
    }
}
