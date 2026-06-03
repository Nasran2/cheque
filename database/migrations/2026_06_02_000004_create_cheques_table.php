<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->string('cheque_type');
            $table->string('cheque_no');
            $table->string('bank_name');
            $table->string('branch_name')->nullable();
            $table->string('account_no');
            $table->date('cheque_date');
            $table->date('received_or_issued_date');
            $table->decimal('amount', 15, 2);
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->string('status');
            $table->date('deposited_date')->nullable();
            $table->date('passed_date')->nullable();
            $table->date('returned_date')->nullable();
            $table->date('cancelled_date')->nullable();
            $table->string('returned_reason')->nullable();
            $table->decimal('return_charge', 15, 2)->default(0);
            $table->foreignId('replacement_cheque_id')->nullable()->constrained('cheques')->nullOnDelete();
            $table->string('attachment')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['cheque_no', 'bank_name', 'account_no']);
            $table->index(['cheque_type', 'status']);
            $table->index(['cheque_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
