<?php

namespace Database\Seeders;

use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;

class SmsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'template_key'  => 'customer_cheque_reminder',
                'template_name' => 'Customer Cheque Reminder',
                'message'       => 'Dear {customer_name}, your cheque no {cheque_no} for {amount} is due on {cheque_date}. Please ensure funds are available. {company_name}',
                'status'        => 'active',
            ],
            [
                'template_key'  => 'supplier_cheque_reminder',
                'template_name' => 'Supplier Cheque Reminder',
                'message'       => 'Reminder: Our cheque no {cheque_no} issued to {supplier_name} for {amount} is due on {cheque_date}. {company_name}',
                'status'        => 'active',
            ],
            [
                'template_key'  => 'customer_cheque_returned',
                'template_name' => 'Customer Cheque Returned',
                'message'       => 'Dear {customer_name}, your cheque no {cheque_no} for {amount} has been returned. Reason: {return_reason}. Return charge: {return_charge}. Please contact us. {company_name}',
                'status'        => 'active',
            ],
            [
                'template_key'  => 'supplier_cheque_returned',
                'template_name' => 'Own Cheque Returned',
                'message'       => 'Notice: Our cheque no {cheque_no} issued to {supplier_name} for {amount} has been marked as returned/cancelled. Reason: {return_reason}. {company_name}',
                'status'        => 'active',
            ],
            [
                'template_key'  => 'customer_cheque_passed',
                'template_name' => 'Customer Cheque Passed',
                'message'       => 'Dear {customer_name}, your cheque no {cheque_no} for {amount} has been successfully passed. Thank you. {company_name}',
                'status'        => 'active',
            ],
            [
                'template_key'  => 'supplier_cheque_passed',
                'template_name' => 'Own Cheque Passed',
                'message'       => 'Cheque no {cheque_no} issued to {supplier_name} for {amount} has been successfully passed. {company_name}',
                'status'        => 'active',
            ],
            [
                'template_key'  => 'customer_cheque_overdue',
                'template_name' => 'Overdue Customer Cheque',
                'message'       => 'Dear {customer_name}, your cheque no {cheque_no} for {amount} was due on {cheque_date} and is now overdue by {overdue_days} days. Please contact us. {company_name}',
                'status'        => 'active',
            ],
            [
                'template_key'  => 'supplier_cheque_overdue',
                'template_name' => 'Overdue Supplier Cheque',
                'message'       => 'Alert: Our cheque no {cheque_no} issued to {supplier_name} for {amount} was due on {cheque_date} and is overdue by {overdue_days} days. {company_name}',
                'status'        => 'active',
            ],
            [
                'template_key'  => 'daily_cheque_summary',
                'template_name' => 'Daily Cheque Summary',
                'message'       => 'Daily Cheque Summary - Today: {today_count}, Upcoming: {upcoming_count}, Overdue: {overdue_count}, Total Amount: {amount}. {company_name}',
                'status'        => 'active',
            ],
            [
                'template_key'  => 'test_sms',
                'template_name' => 'Test SMS',
                'message'       => 'This is a test SMS from {company_name} Cheque Management System. Powered by Twinsofte.com',
                'status'        => 'active',
            ],
        ];

        foreach ($templates as $tpl) {
            SmsTemplate::updateOrCreate(
                ['template_key' => $tpl['template_key']],
                $tpl
            );
        }

        $this->command->info('✅ 10 SMS templates seeded successfully.');
    }
}
