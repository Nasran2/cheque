@if ($errors->any())
    <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
        {{ $errors->first() }}
    </div>
@endif

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="mb-2 block text-sm font-bold text-navy">Customer Name <span class="text-danger">*</span></label>
        <input name="name" value="{{ old('name', $customer->name) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10" required>
    </div>
    <div>
        <label class="mb-2 block text-sm font-bold text-navy">Business Name</label>
        <input name="business_name" value="{{ old('business_name', $customer->business_name) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
    </div>
    <div>
        <label class="mb-2 block text-sm font-bold text-navy">Phone</label>
        <input name="phone" value="{{ old('phone', $customer->phone) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
    </div>
    <div>
        <label class="mb-2 block text-sm font-bold text-navy">Second Phone</label>
        <input name="phone_2" value="{{ old('phone_2', $customer->phone_2) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
    </div>
    <div>
        <label class="mb-2 block text-sm font-bold text-navy">Email</label>
        <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
    </div>
    <div>
        <label class="mb-2 block text-sm font-bold text-navy">NIC</label>
        <input name="nic" value="{{ old('nic', $customer->nic) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
    </div>
    <div>
        <label class="mb-2 block text-sm font-bold text-navy">VAT No</label>
        <input name="vat_no" value="{{ old('vat_no', $customer->vat_no) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
    </div>
    <div>
        <label class="mb-2 block text-sm font-bold text-navy">City</label>
        <input name="city" value="{{ old('city', $customer->city) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
    </div>
    <div>
        <label class="mb-2 block text-sm font-bold text-navy">Opening Balance</label>
        <input type="number" step="0.01" min="0" name="opening_balance" value="{{ old('opening_balance', $customer->opening_balance ?? 0) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
    </div>
    <div>
        <label class="mb-2 block text-sm font-bold text-navy">Credit Limit</label>
        <input type="number" step="0.01" min="0" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
    </div>
    <div>
        <label class="mb-2 block text-sm font-bold text-navy">Status</label>
        <select name="status" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
            <option value="active" @selected(old('status', $customer->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $customer->status ?? 'active') === 'inactive')>Inactive</option>
        </select>
    </div>
    <div class="md:col-span-2">
        <label class="mb-2 block text-sm font-bold text-navy">Address</label>
        <textarea name="address" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">{{ old('address', $customer->address) }}</textarea>
    </div>
    <div class="md:col-span-2">
        <label class="mb-2 block text-sm font-bold text-navy">Notes</label>
        <textarea name="notes" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">{{ old('notes', $customer->notes) }}</textarea>
    </div>
</div>

<div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
    <a href="{{ route('customers.index') }}" class="rounded-2xl bg-slate-100 px-5 py-3 text-center text-sm font-bold text-slate-700">Cancel</a>
    <button class="rounded-2xl bg-primary px-5 py-3 text-sm font-bold text-white shadow-lg shadow-primary/20">Save Customer</button>
</div>
