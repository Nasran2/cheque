<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cheque_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('cheque_settings', 'group')) {
                $table->string('group')->nullable()->after('id')->index();
            }
            if (! Schema::hasColumn('cheque_settings', 'type')) {
                $table->string('type')->nullable()->after('value');
            }
            if (! Schema::hasColumn('cheque_settings', 'description')) {
                $table->text('description')->nullable()->after('type');
            }
        });

        Schema::table('notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('notifications', 'reminder_day')) {
                $table->integer('reminder_day')->nullable()->after('type');
            }
            if (! Schema::hasColumn('notifications', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('scheduled_for');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cheque_settings', function (Blueprint $table) {
            $table->dropColumn(['group', 'type', 'description']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['reminder_day', 'sent_at']);
        });
    }
};
