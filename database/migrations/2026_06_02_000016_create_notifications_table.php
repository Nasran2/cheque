<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cheque_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('type')->default('info');
            $table->enum('status', ['unread', 'read'])->default('unread')->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('scheduled_for');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
