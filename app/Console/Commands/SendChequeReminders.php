<?php

namespace App\Console\Commands;

use App\Models\Cheque;
use App\Models\ChequeSetting;
use App\Models\Notification as ChequeNotification;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Services\TextitSmsService;
use App\Support\Currency;
use Illuminate\Console\Command;

class SendChequeReminders extends Command
{
    protected $signature = 'cheques:send-reminders';

    protected $description = 'Create cheque reminder notifications (and SMS if enabled) for customer and own issued cheques.';

    public function handle(TextitSmsService $sms): int
    {
        $created    = 0;
        $smsSent    = 0;
        $smsFailed  = 0;

        // ── Customer Reminders ────────────────────────────────────────────────
        if (ChequeSetting::getValue('customer_reminders_enabled', '1') === '1') {
            [$n, $s, $f] = $this->createReminderNotifications(
                Cheque::TYPE_CUSTOMER_RECEIVED,
                'customer_cheque_reminder',
                $this->daysFromSetting('customer_reminder_days', '5,2,1'),
                $sms,
                ChequeSetting::getValue('customer_reminder_sms_enabled', '0') === '1'
            );
            $created   += $n;
            $smsSent   += $s;
            $smsFailed += $f;
        }

        // ── Supplier Reminders ────────────────────────────────────────────────
        if (ChequeSetting::getValue('supplier_reminders_enabled', '1') === '1') {
            [$n, $s, $f] = $this->createReminderNotifications(
                Cheque::TYPE_OWN_ISSUED,
                'supplier_cheque_reminder',
                $this->daysFromSetting('supplier_reminder_days', '7,5,2,1'),
                $sms,
                ChequeSetting::getValue('supplier_reminder_sms_enabled', '0') === '1'
            );
            $created   += $n;
            $smsSent   += $s;
            $smsFailed += $f;
        }

        // ── Overdue Alerts ────────────────────────────────────────────────────
        [$n, $s, $f] = $this->createOverdueNotifications(
            $sms,
            ChequeSetting::getValue('overdue_cheque_sms_enabled', '0') === '1'
        );
        $created   += $n;
        $smsSent   += $s;
        $smsFailed += $f;

        $this->info("Created {$created} reminder notifications.");
        $this->info("SMS sent: {$smsSent} | SMS failed: {$smsFailed}");

        return self::SUCCESS;
    }

    // ── Private: Reminder Notifications ──────────────────────────────────────

    /**
     * @return array{int, int, int}  [notificationsCreated, smsSent, smsFailed]
     */
    private function createReminderNotifications(
        string $chequeType,
        string $templateKey,
        array  $days,
        TextitSmsService $sms,
        bool   $smsEnabled
    ): array {
        $created   = 0;
        $smsSent   = 0;
        $smsFailed = 0;

        $template   = SmsTemplate::getByKey($templateKey);
        $companyName = ChequeSetting::getValue('company_name', 'Cheque Management System');

        foreach ($days as $day) {
            $dueDate = today()->addDays($day);

            $cheques = Cheque::with(['customer', 'supplier'])
                ->where('cheque_type', $chequeType)
                ->whereIn('status', [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED, Cheque::STATUS_HOLD])
                ->whereDate('cheque_date', $dueDate)
                ->get();

            foreach ($cheques as $cheque) {
                $party   = $cheque->customer?->name ?? $cheque->supplier?->name ?? 'No party';
                $message = "Cheque {$cheque->cheque_no} for {$party} worth " . Currency::formatLkr($cheque->amount) . " is due on {$cheque->cheque_date->format('d M Y')}.";

                $notification = ChequeNotification::firstOrCreate([
                    'cheque_id'    => $cheque->id,
                    'type'         => $templateKey,
                    'reminder_day' => $day,
                ], [
                    'title'         => 'Cheque Reminder',
                    'message'       => $message,
                    'status'        => 'unread',
                    'scheduled_for' => now(),
                    'sent_at'       => now(),
                ]);

                if ($notification->wasRecentlyCreated) {
                    $created++;
                }

                // ── Send SMS if enabled ───────────────────────────────────────
                if ($smsEnabled && $template && $template->isActive()) {
                    $recipientType = $chequeType === Cheque::TYPE_CUSTOMER_RECEIVED ? 'customer' : 'supplier';
                    $recipientId   = $chequeType === Cheque::TYPE_CUSTOMER_RECEIVED ? $cheque->customer_id : $cheque->supplier_id;
                    $phone         = $cheque->customer?->phone ?? $cheque->supplier?->phone ?? null;

                    if ($phone && ! SmsLog::alreadySentToday($cheque->id, $recipientType, $template->id)) {
                        $vars = $this->buildVars($cheque, $companyName, $day);
                        $smsMessage = $sms->replaceVars($template->message, $vars);
                        $ref        = substr(
                            ChequeSetting::getValue('sms_ref_prefix', 'CHEQUE') . '-' . $cheque->cheque_no,
                            0, 15
                        );

                        $result = $sms->sendSms($phone, $smsMessage, $ref, null, [
                            'cheque_id'       => $cheque->id,
                            'sms_template_id' => $template->id,
                            'recipient_type'  => $recipientType,
                            'recipient_id'    => $recipientId,
                        ]);

                        $result['success'] ? $smsSent++ : $smsFailed++;

                        $this->line(
                            $result['success']
                                ? "  <info>✓ SMS sent to {$phone} for cheque {$cheque->cheque_no}</info>"
                                : "  <error>✗ SMS failed for {$cheque->cheque_no}: " . ($result['error'] ?? 'Unknown') . "</error>"
                        );
                    }
                }
            }
        }

        return [$created, $smsSent, $smsFailed];
    }

    // ── Private: Overdue Notifications ───────────────────────────────────────

    /**
     * @return array{int, int, int}
     */
    private function createOverdueNotifications(TextitSmsService $sms, bool $smsEnabled): array
    {
        $created   = 0;
        $smsSent   = 0;
        $smsFailed = 0;
        $companyName = ChequeSetting::getValue('company_name', 'Cheque Management System');

        $cheques = Cheque::with(['customer', 'supplier'])
            ->whereIn('status', [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED, Cheque::STATUS_HOLD])
            ->whereDate('cheque_date', '<', today())
            ->get();

        foreach ($cheques as $cheque) {
            $notification = ChequeNotification::firstOrCreate([
                'cheque_id'    => $cheque->id,
                'type'         => 'overdue_cheque_alert',
                'reminder_day' => 0,
            ], [
                'title'         => 'Overdue Cheque Alert',
                'message'       => "Cheque {$cheque->cheque_no} is overdue. Amount: " . Currency::formatLkr($cheque->amount),
                'status'        => 'unread',
                'scheduled_for' => now(),
                'sent_at'       => now(),
            ]);

            if ($notification->wasRecentlyCreated) {
                $created++;
            }

            // ── SMS for overdue ───────────────────────────────────────────────
            if ($smsEnabled) {
                $isCustomer  = $cheque->isCustomerReceived();
                $tplKey      = $isCustomer ? 'customer_cheque_overdue' : 'supplier_cheque_overdue';
                $template    = SmsTemplate::getByKey($tplKey);
                $phone       = $cheque->customer?->phone ?? $cheque->supplier?->phone ?? null;
                $recipientType = $isCustomer ? 'customer' : 'supplier';
                $recipientId = $isCustomer ? $cheque->customer_id : $cheque->supplier_id;

                if ($phone && $template && $template->isActive()
                    && ! SmsLog::alreadySentToday($cheque->id, $recipientType, $template->id)) {
                    $overdueDays = $cheque->cheque_date->diffInDays(today());
                    $vars = $this->buildVars($cheque, $companyName);
                    $vars['overdue_days'] = $overdueDays;
                    $smsMessage = $sms->replaceVars($template->message, $vars);
                    $ref = substr(ChequeSetting::getValue('sms_ref_prefix', 'CHEQUE') . '-' . $cheque->cheque_no, 0, 15);

                    $result = $sms->sendSms($phone, $smsMessage, $ref, null, [
                        'cheque_id'       => $cheque->id,
                        'sms_template_id' => $template->id,
                        'recipient_type'  => $recipientType,
                        'recipient_id'    => $recipientId,
                    ]);

                    $result['success'] ? $smsSent++ : $smsFailed++;
                }
            }
        }

        return [$created, $smsSent, $smsFailed];
    }

    // ── Private: Helpers ──────────────────────────────────────────────────────

    private function buildVars(Cheque $cheque, string $companyName, int $daysLeft = 0): array
    {
        return [
            'company_name'  => $companyName,
            'system_name'   => $companyName,
            'customer_name' => $cheque->customer?->name ?? '',
            'supplier_name' => $cheque->supplier?->name ?? '',
            'payee_name'    => $cheque->customer?->name ?? $cheque->supplier?->name ?? '',
            'cheque_no'     => $cheque->cheque_no,
            'bank_name'     => $cheque->bank_name,
            'branch_name'   => $cheque->branch_name ?? '',
            'cheque_date'   => $cheque->cheque_date?->toDateString(),
            'amount'        => $cheque->amount,
            'status'        => $cheque->status,
            'return_reason' => $cheque->returned_reason ?? '',
            'return_charge' => $cheque->return_charge ?? 0,
            'contact_phone' => $cheque->customer?->phone ?? $cheque->supplier?->phone ?? '',
            'days_left'     => $daysLeft,
        ];
    }

    private function daysFromSetting(string $key, string $default): array
    {
        return collect(explode(',', ChequeSetting::getValue($key, $default)))
            ->map(fn (string $day) => (int) trim($day))
            ->filter(fn (int $day) => $day >= 0)
            ->unique()
            ->values()
            ->all();
    }
}
