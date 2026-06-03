<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Cheque;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate([
            'username' => 'admin',
        ], [
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'permissions' => [],
        ]);

        $customers = collect([
            'Al-Fatah Stores',
            'Global Traders',
            'Khan Electronics',
            'Super Mart',
            'Noman & Sons',
        ])->mapWithKeys(fn (string $name) => [$name => Customer::updateOrCreate(['name' => $name], [
            'status' => 'active',
            'opening_balance' => 0,
            'current_balance' => 0,
        ])]);

        $suppliers = collect([
            'ABC Suppliers',
            'Zain Traders',
            'Best Goods Ltd.',
            'Lucky Enterprises',
        ])->mapWithKeys(fn (string $name) => [$name => Supplier::updateOrCreate(['name' => $name], [
            'status' => 'active',
            'opening_balance' => 0,
            'current_balance' => 0,
        ])]);

        $cheques = [
            ['00014532', Cheque::TYPE_CUSTOMER_RECEIVED, 'Al-Fatah Stores', null, 'Commercial Bank', '2026-06-05', 125000, Cheque::STATUS_PENDING],
            ['00014531', Cheque::TYPE_CUSTOMER_RECEIVED, 'Global Traders', null, 'BOC Bank', '2026-06-04', 250000, Cheque::STATUS_PENDING],
            ['00014530', Cheque::TYPE_OWN_ISSUED, null, 'ABC Suppliers', 'Bank Alfalah', '2026-06-01', 180000, Cheque::STATUS_PASSED],
            ['00014529', Cheque::TYPE_CUSTOMER_RECEIVED, 'Khan Electronics', null, 'MCB Bank', '2026-05-30', 320000, Cheque::STATUS_PASSED],
            ['00014528', Cheque::TYPE_OWN_ISSUED, null, 'Zain Traders', 'Meezan Bank', '2026-05-28', 210000, Cheque::STATUS_RETURNED],
            ['00014527', Cheque::TYPE_OWN_ISSUED, null, 'Best Goods Ltd.', 'Habib Bank', '2026-05-25', 95000, Cheque::STATUS_HOLD],
            ['00014526', Cheque::TYPE_CUSTOMER_RECEIVED, 'Super Mart', null, 'Bank Alfalah', '2026-05-20', 430000, Cheque::STATUS_PASSED],
            ['00014525', Cheque::TYPE_CUSTOMER_RECEIVED, 'Noman & Sons', null, 'Meezan Bank', '2026-05-15', 75000, Cheque::STATUS_PENDING],
            ['00014540', Cheque::TYPE_CUSTOMER_RECEIVED, 'Al-Fatah Stores', null, 'Commercial Bank', '2026-06-10', 150000, Cheque::STATUS_PENDING],
            ['00014541', Cheque::TYPE_CUSTOMER_RECEIVED, 'Global Traders', null, 'BOC Bank', '2026-06-15', 200000, Cheque::STATUS_PENDING],
            ['00014542', Cheque::TYPE_CUSTOMER_RECEIVED, 'Khan Electronics', null, 'MCB Bank', '2026-06-20', 320000, Cheque::STATUS_PENDING],
            ['00014543', Cheque::TYPE_OWN_ISSUED, null, 'Lucky Enterprises', 'Habib Bank', '2026-06-22', 250000, Cheque::STATUS_PENDING],
        ];

        foreach ($cheques as [$number, $type, $customerName, $supplierName, $bank, $date, $amount, $status]) {
            Cheque::updateOrCreate([
                'cheque_no' => $number,
                'bank_name' => $bank,
                'account_no' => 'Main Account',
            ], [
                'cheque_type' => $type,
                'branch_name' => null,
                'cheque_date' => $date,
                'received_or_issued_date' => now()->toDateString(),
                'amount' => $amount,
                'customer_id' => $customerName ? $customers[$customerName]->id : null,
                'supplier_id' => $supplierName ? $suppliers[$supplierName]->id : null,
                'status' => $status,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);
        }

        $this->call(SmsTemplateSeeder::class);
    }
}
