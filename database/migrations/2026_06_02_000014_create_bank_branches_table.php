<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->string('city', 100)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->timestamps();

            $table->unique(['bank_id', 'name']);
            $table->index(['bank_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_branches');
    }
};
