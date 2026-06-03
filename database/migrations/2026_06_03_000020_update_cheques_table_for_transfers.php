<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cheques', function (Blueprint $table) {
            // Make account_no nullable
            $table->string('account_no')->nullable()->change();

            // Add transfer columns
            $table->foreignId('original_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('given_to_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('source_customer_cheque_id')->nullable()->constrained('cheques')->nullOnDelete();
            $table->boolean('is_transferred_to_supplier')->default(false);
            $table->date('transferred_date')->nullable();
            $table->text('transfer_note')->nullable();

            // Drop unique constraint cheques_cheque_no_bank_name_account_no_unique
            try {
                $table->dropUnique('cheques_cheque_no_bank_name_account_no_unique');
            } catch (\Exception $e) {
                // Ignore if it doesn't exist
            }
        });
    }

    public function down(): void
    {
        Schema::table('cheques', function (Blueprint $table) {
            // Revert account_no to non-nullable (might fail if nulls exist, but standard rollback fallback)
            $table->string('account_no')->nullable(false)->change();

            $table->dropForeign(['original_customer_id']);
            $table->dropColumn('original_customer_id');

            $table->dropForeign(['given_to_supplier_id']);
            $table->dropColumn('given_to_supplier_id');

            $table->dropForeign(['source_customer_cheque_id']);
            $table->dropColumn('source_customer_cheque_id');

            $table->dropColumn('is_transferred_to_supplier');
            $table->dropColumn('transferred_date');
            $table->dropColumn('transfer_note');

            try {
                $table->unique(['cheque_no', 'bank_name', 'account_no']);
            } catch (\Exception $e) {
                // Ignore if unique index already exists
            }
        });
    }
};
