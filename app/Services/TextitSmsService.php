<?php

namespace App\Services;

use App\Models\ChequeSetting;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Support\Currency;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TextitSmsService
{
    // ── Configuration ─────────────────────────────────────────────────────────

    protected function config(string $key, mixed $default = null): mixed
    {
        return ChequeSetting::getValue($key, $default);
    }

    protected function isEnabled(): bool
    {
        return $this->config('sms_enabled', '0') === '1';
    }

    protected function userId(): string
    {
        return (string) $this->config('textit_user_id', '');
    }

    protected function password(): string
    {
        $raw = $this->config('textit_password', '');
        if (empty($raw)) {
            return '';
        }
        try {
            return decrypt($raw);
        } catch (\Throwable) {
            return $raw; // already plain (legacy)
        }
    }

    protected function baseUrl(): string
    {
        return rtrim((string) $this->config('textit_base_url', 'https://textit.biz/sendmsg'), '/');
    }

    protected function method(): string
    {
        return strtoupper((string) $this->config('sms_method', 'GET'));
    }

    protected function refPrefix(): string
    {
        return (string) $this->config('sms_ref_prefix', 'CHEQUE');
    }

    // ── Phone Formatter ───────────────────────────────────────────────────────

    /**
     * Normalise any Sri Lankan phone number to international format 94XXXXXXXXX.
     *
     * Examples:
     *   0771234567      → 94771234567
     *   +94771234567    → 94771234567
     *   94771234567     → 94771234567
     */
    public function formatPhone(string $phone): string
    {
        // Strip spaces, dashes, parentheses, plus signs
        $phone = preg_replace('/[\s\-\(\)\+]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '94' . substr($phone, 1);
        }

        return $phone;
    }

    // ── Template Variable Replacement ─────────────────────────────────────────

    /**
     * Replace {variable} placeholders in a message template.
     *
     * Handles formatting:
     *   {amount}      → Rs 125,000.00
     *   {cheque_date} → 20 May 2026
     */
    public function resolveTemplate(string $templateKey, array $vars = []): string
    {
        $template = SmsTemplate::getByKey($templateKey);
        if (! $template) {
            return '';
        }

        return $this->replaceVars($template->message, $vars);
    }

    public function replaceVars(string $message, array $vars): string
    {
        // Format money fields
        foreach (['amount', 'return_charge'] as $moneyField) {
            if (isset($vars[$moneyField])) {
                $vars[$moneyField] = Currency::formatLkr($vars[$moneyField]);
            }
        }

        // Format date fields
        foreach (['cheque_date'] as $dateField) {
            if (isset($vars[$dateField])) {
                try {
                    $vars[$dateField] = Carbon::parse($vars[$dateField])->format('d M Y');
                } catch (\Throwable) {
                    // keep as-is
                }
            }
        }

        // Replace {key} placeholders
        foreach ($vars as $key => $value) {
            $message = str_replace('{' . $key . '}', (string) $value, $message);
        }

        return $message;
    }

    // ── Core SMS Sender ───────────────────────────────────────────────────────

    /**
     * Send an SMS via Textit.biz HTTP API.
     *
     * @param  string       $to       Recipient phone (any format)
     * @param  string       $message  Plain text message
     * @param  string|null  $ref      Passthrough reference (max 15 chars)
     * @param  string|null  $schedule Schedule date/time
     * @param  array        $logMeta  Extra data to save in sms_logs
     * @return array{success: bool, message_id: string|null, error: string|null, response: string}
     */
    public function sendSms(
        string $to,
        string $message,
        ?string $ref = null,
        ?string $schedule = null,
        array $logMeta = []
    ): array {
        $to = $this->formatPhone($to);

        // Build the log record first (status: pending)
        $log = SmsLog::create(array_merge([
            'phone'          => $to,
            'message'        => $message,
            'provider'       => 'textit',
            'ref'            => $ref ? substr($ref, 0, 15) : null,
            'status'         => 'pending',
            'created_by'     => auth()->id(),
        ], $logMeta));

        $result = $this->callApi($to, $message, $ref, $schedule);

        // Update log with result
        $log->update([
            'status'   => $result['success'] ? 'sent' : 'failed',
            'response' => $result['response'] ?? null,
            'sent_at'  => $result['success'] ? now() : null,
        ]);

        return $result;
    }

    /**
     * Send a test SMS (no cheque association).
     */
    public function sendTestSms(string $to, string $message): array
    {
        return $this->sendSms($to, $message, $this->refPrefix() . '-TEST');
    }

    // ── API Caller ────────────────────────────────────────────────────────────

    protected function callApi(
        string $to,
        string $message,
        ?string $ref = null,
        ?string $schedule = null
    ): array {
        $userId   = $this->userId();
        $password = $this->password();

        if (empty($userId) || empty($password)) {
            return [
                'success'    => false,
                'message_id' => null,
                'error'      => 'Textit credentials not configured.',
                'response'   => '',
            ];
        }

        $params = [
            'id'   => $userId,
            'pw'   => $password,
            'to'   => $to,
            'text' => $message,
        ];

        if ($ref) {
            $params['ref'] = substr($ref, 0, 15);
        }

        if ($schedule) {
            $params['schd'] = $schedule;
        }

        try {
            if ($this->method() === 'POST') {
                $result = $this->callPost($params);
            } else {
                $result = $this->callGet($params);
            }

            return $this->checkResponse($result);
        } catch (\Throwable $e) {
            Log::error('TextitSmsService error: ' . $e->getMessage(), ['to' => $to]);

            return [
                'success'    => false,
                'message_id' => null,
                'error'      => $e->getMessage(),
                'response'   => '',
            ];
        }
    }

    protected function callGet(array $params): string
    {
        $url = $this->baseUrl() . '/?' . http_build_query($params);

        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 15,
            ],
        ]);

        $lines = @file($url, 0, $context);

        return $lines ? trim($lines[0]) : '';
    }

    protected function callPost(array $params): string
    {
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($params),
                'timeout' => 15,
            ],
        ]);

        $result = @file_get_contents($this->baseUrl() . '/', false, $context);

        return $result ? trim($result) : '';
    }

    // ── Response Parser ───────────────────────────────────────────────────────

    /**
     * Parse Textit.biz response string.
     *
     * OK: <msgid>   → success
     * ERROR: <msg>  → failure
     */
    public function checkResponse(string $response): array
    {
        $parts = explode(':', $response, 2);
        $status = trim($parts[0] ?? '');
        $detail = trim($parts[1] ?? '');

        if (strtoupper($status) === 'OK') {
            return [
                'success'    => true,
                'message_id' => $detail ?: null,
                'error'      => null,
                'response'   => $response,
            ];
        }

        return [
            'success'    => false,
            'message_id' => null,
            'error'      => $detail ?: ($response ?: 'Unknown error'),
            'response'   => $response,
        ];
    }

    // ── Available Template Variables ──────────────────────────────────────────

    public static function availableVariables(): array
    {
        return [
            '{company_name}',
            '{customer_name}',
            '{supplier_name}',
            '{payee_name}',
            '{cheque_no}',
            '{bank_name}',
            '{branch_name}',
            '{cheque_date}',
            '{amount}',
            '{status}',
            '{return_reason}',
            '{return_charge}',
            '{days_left}',
            '{overdue_days}',
            '{contact_phone}',
            '{system_name}',
        ];
    }
}
