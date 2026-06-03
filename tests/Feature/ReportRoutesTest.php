<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows customer-wise and supplier-wise reports with exports', function () {
    $this->seed();

    $user = User::where('username', 'admin')->firstOrFail();

    $this->actingAs($user)
        ->get(route('reports.customer-wise'))
        ->assertOk()
        ->assertSee('Customer-wise Cheque Report');

    $this->actingAs($user)
        ->get(route('reports.supplier-wise'))
        ->assertOk()
        ->assertSee('Supplier-wise Cheque Report');

    $this->actingAs($user)
        ->get(route('reports.export.pdf', 'customer-wise'))
        ->assertOk()
        ->assertSee('Customer-wise Cheque Report');

    $this->actingAs($user)
        ->get(route('reports.export.excel', 'supplier-wise'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});
