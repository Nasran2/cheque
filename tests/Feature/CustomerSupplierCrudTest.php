<?php

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates customers with current balance equal to opening balance', function () {
    $this->seed();

    $user = User::where('username', 'admin')->firstOrFail();

    $this->actingAs($user)->post(route('customers.store'), [
        'name' => 'New Customer',
        'business_name' => 'New Customer Trading',
        'phone' => '0771234567',
        'email' => 'customer@example.com',
        'opening_balance' => 125000,
        'credit_limit' => 500000,
        'status' => 'active',
    ])->assertRedirect(route('customers.index'));

    $customer = Customer::where('phone', '0771234567')->firstOrFail();

    expect((float) $customer->opening_balance)->toBe(125000.0)
        ->and((float) $customer->current_balance)->toBe(125000.0);
});

it('creates suppliers with current balance equal to opening balance', function () {
    $this->seed();

    $user = User::where('username', 'admin')->firstOrFail();

    $this->actingAs($user)->post(route('suppliers.store'), [
        'name' => 'New Supplier',
        'business_name' => 'New Supplier Holdings',
        'phone' => '0777654321',
        'email' => 'supplier@example.com',
        'bank_name' => 'Commercial Bank',
        'bank_branch' => 'Colombo',
        'account_name' => 'New Supplier',
        'account_no' => '123456789',
        'opening_balance' => 95000,
        'status' => 'active',
    ])->assertRedirect(route('suppliers.index'));

    $supplier = Supplier::where('phone', '0777654321')->firstOrFail();

    expect((float) $supplier->opening_balance)->toBe(95000.0)
        ->and((float) $supplier->current_balance)->toBe(95000.0);
});
