<?php

namespace App\Http\Requests;

use App\Models\Cheque;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChequeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $cheque = $this->route('cheque');
        $bankName = $this->input('bank_name');

        return [
            'cheque_type' => ['required', Rule::in([Cheque::TYPE_CUSTOMER_RECEIVED, Cheque::TYPE_OWN_ISSUED])],
            'cheque_no' => [
                'required',
                'string',
                'max:50',
                Rule::unique('cheques', 'cheque_no')
                    ->ignore($cheque?->id)
                    ->where(function ($query) use ($bankName) {
                        return $query->where('bank_name', $bankName);
                    }),
            ],
            'bank_name' => ['required', 'string', 'max:120'],
            'branch_name' => ['nullable', 'string', 'max:120'],
            'cheque_date' => ['required', 'date'],
            'received_or_issued_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'customer_id' => ['required_if:cheque_type,' . Cheque::TYPE_CUSTOMER_RECEIVED, 'nullable', 'integer', 'exists:customers,id'],
            'supplier_id' => ['required_if:cheque_type,' . Cheque::TYPE_OWN_ISSUED, 'nullable', 'integer', 'exists:suppliers,id'],
            'invoice_id' => ['nullable', 'integer'],
            'purchase_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }
}
