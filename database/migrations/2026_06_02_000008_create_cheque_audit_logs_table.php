<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheque_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cheque_id')->nullable()->constrained('cheques')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('device')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['cheque_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheque_audit_logs');
    }
};
