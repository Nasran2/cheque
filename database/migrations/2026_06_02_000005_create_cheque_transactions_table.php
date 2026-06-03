<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheque_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cheque_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['cheque_id', 'new_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheque_transactions');
    }
};
