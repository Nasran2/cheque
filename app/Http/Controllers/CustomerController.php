<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::query()
            ->withCount('cheques')
            ->withSum('cheques as cheque_total_amount', 'amount')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('business_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('customers.create', [
            'customer' => new Customer(['status' => 'active']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCustomer($request);
        $openingBalance = $validated['opening_balance'] ?? 0;

        $validated['opening_balance'] = $openingBalance;
        $validated['current_balance'] = $openingBalance;
        $validated['created_by'] = $request->user()?->id;

        Customer::create($validated);

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer): View
    {
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $this->validateCustomer($request, $customer);
        $validated['updated_by'] = $request->user()?->id;

        $customer->update($validated);

        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }

    private function validateCustomer(Request $request, ?Customer $customer = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('customers', 'phone')->ignore($customer)],
            'phone_2' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'nic' => ['nullable', 'string', 'max:50'],
            'vat_no' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
