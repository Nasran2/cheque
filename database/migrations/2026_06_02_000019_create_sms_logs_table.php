<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cheque_id')->nullable()->index();
            $table->unsignedBigInteger('sms_template_id')->nullable()->index();
            $table->string('recipient_type')->nullable();  // customer / supplier / manual
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('phone', 30);
            $table->text('message');
            $table->string('provider', 30)->default('textit');
            $table->string('ref', 15)->nullable();
            $table->text('response')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('cheque_id')->references('id')->on('cheques')->nullOnDelete();
            $table->foreign('sms_template_id')->references('id')->on('sms_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
