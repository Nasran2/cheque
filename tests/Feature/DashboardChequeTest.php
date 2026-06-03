<?php

use App\Models\Cheque;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows the seeded admin to save a cheque from the dashboard', function () {
    $this->seed();

    $user = User::where('username', 'admin')->firstOrFail();
    $customer = Customer::firstOrFail();
    $before = Cheque::count();

    $response = $this->actingAs($user)->post(route('cheques.store'), [
        'cheque_type' => Cheque::TYPE_CUSTOMER_RECEIVED,
        'cheque_no' => 'TEST' . now()->format('His'),
        'bank_name' => 'Feature Test Bank',
        'cheque_date' => now()->toDateString(),
        'amount' => 12345,
        'customer_id' => $customer->id,
        'status' => Cheque::STATUS_PENDING,
    ]);

    $response->assertRedirect(route('cheques.index'));
    expect(Cheque::count())->toBe($before + 1);
});

it('creates a supplier cheque from an existing customer cheque once', function () {
    $this->seed();

    $user = User::where('username', 'admin')->firstOrFail();
    $customer = Customer::firstOrFail();
    $supplier = Supplier::firstOrFail();

    $sourceCheque = Cheque::create([
        'cheque_type' => Cheque::TYPE_CUSTOMER_RECEIVED,
        'cheque_no' => 'SRC' . now()->format('His'),
        'bank_name' => 'Transfer Test Bank',
        'account_no' => null,
        'cheque_date' => now()->addDays(2)->toDateString(),
        'received_or_issued_date' => now()->toDateString(),
        'amount' => 50000,
        'customer_id' => $customer->id,
        'status' => Cheque::STATUS_PENDING,
    ]);

    $response = $this->actingAs($user)->post(route('cheques.store'), [
        'cheque_type' => Cheque::TYPE_OWN_ISSUED,
        'supplier_id' => $supplier->id,
        'supplier_cheque_mode' => 'received_customer_cheque',
        'source_customer_cheque_id' => $sourceCheque->id,
        'status' => Cheque::STATUS_PENDING,
    ]);

    $response->assertRedirect(route('cheques.index'));

    $sourceCheque->refresh();
    $supplierCheque = Cheque::where('source_customer_cheque_id', $sourceCheque->id)->firstOrFail();

    expect($sourceCheque->is_transferred_to_supplier)->toBeTrue()
        ->and($sourceCheque->given_to_supplier_id)->toBe($supplier->id)
        ->and($supplierCheque->cheque_type)->toBe(Cheque::TYPE_OWN_ISSUED)
        ->and($supplierCheque->supplier_cheque_mode)->toBe('received_customer_cheque')
        ->and((float) $supplierCheque->amount)->toBe(50000.0);

    $duplicateResponse = $this->actingAs($user)->post(route('cheques.store'), [
        'cheque_type' => Cheque::TYPE_OWN_ISSUED,
        'supplier_id' => $supplier->id,
        'supplier_cheque_mode' => 'received_customer_cheque',
        'source_customer_cheque_id' => $sourceCheque->id,
    ]);

    $duplicateResponse->assertSessionHasErrors('source_customer_cheque_id');
});

it('creates combined supplier cheques from own and customer cheques', function () {
    $this->seed();

    $user = User::where('username', 'admin')->firstOrFail();
    $customer = Customer::firstOrFail();
    $supplier = Supplier::firstOrFail();

    $sourceCheque = Cheque::create([
        'cheque_type' => Cheque::TYPE_CUSTOMER_RECEIVED,
        'cheque_no' => 'MIX' . now()->format('His'),
        'bank_name' => 'Mixed Test Bank',
        'account_no' => null,
        'cheque_date' => now()->addDays(4)->toDateString(),
        'received_or_issued_date' => now()->toDateString(),
        'amount' => 75000,
        'customer_id' => $customer->id,
        'status' => Cheque::STATUS_PENDING,
    ]);

    $before = Cheque::count();

    $response = $this->actingAs($user)->post(route('cheques.store'), [
        'cheque_type' => Cheque::TYPE_OWN_ISSUED,
        'supplier_id' => $supplier->id,
        'supplier_cheque_mode' => 'combined_cheques',
        'combined_source_customer_cheque_ids' => [$sourceCheque->id],
        'combined_own_cheques' => [
            [
                'cheque_no' => 'OWN' . now()->format('His'),
                'bank_name' => 'Own Mixed Bank',
                'cheque_date' => now()->addDays(5)->toDateString(),
                'amount' => 25000,
                'status' => Cheque::STATUS_PENDING,
            ],
        ],
    ]);

    $response->assertRedirect(route('cheques.index'));

    $sourceCheque->refresh();

    expect(Cheque::count())->toBe($before + 2)
        ->and($sourceCheque->is_transferred_to_supplier)->toBeTrue()
        ->and(Cheque::where('source_customer_cheque_id', $sourceCheque->id)->exists())->toBeTrue()
        ->and(Cheque::where('cheque_no', 'like', 'OWN%')->where('supplier_id', $supplier->id)->exists())->toBeTrue();
});

it('marks a cheque as passed or returned from list actions', function () {
    $this->seed();

    $user = User::where('username', 'admin')->firstOrFail();
    $customer = Customer::firstOrFail();

    $passedCheque = Cheque::create([
        'cheque_type' => Cheque::TYPE_CUSTOMER_RECEIVED,
        'cheque_no' => 'PASS' . now()->format('His'),
        'bank_name' => 'Status Test Bank',
        'account_no' => null,
        'cheque_date' => now()->toDateString(),
        'received_or_issued_date' => now()->toDateString(),
        'amount' => 10000,
        'customer_id' => $customer->id,
        'status' => Cheque::STATUS_PENDING,
    ]);

    $returnedCheque = Cheque::create([
        'cheque_type' => Cheque::TYPE_CUSTOMER_RECEIVED,
        'cheque_no' => 'RET' . now()->format('His'),
        'bank_name' => 'Return Test Bank',
        'account_no' => null,
        'cheque_date' => now()->toDateString(),
        'received_or_issued_date' => now()->toDateString(),
        'amount' => 12000,
        'customer_id' => $customer->id,
        'status' => Cheque::STATUS_PENDING,
    ]);

    $this->actingAs($user)
        ->post(route('cheques.mark-passed', $passedCheque))
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->post(route('cheques.mark-returned', $returnedCheque), [
            'returned_reason' => 'Test return',
            'return_charge' => 100,
        ])
        ->assertSessionHasNoErrors();

    expect($passedCheque->refresh()->status)->toBe(Cheque::STATUS_PASSED)
        ->and($returnedCheque->refresh()->status)->toBe(Cheque::STATUS_RETURNED)
        ->and($returnedCheque->returned_reason)->toBe('Test return')
        ->and((float) $returnedCheque->return_charge)->toBe(100.0);
});
