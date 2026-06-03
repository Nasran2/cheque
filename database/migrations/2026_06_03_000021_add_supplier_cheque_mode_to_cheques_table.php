<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cheques', function (Blueprint $table) {
            if (! Schema::hasColumn('cheques', 'supplier_cheque_mode')) {
                $table->string('supplier_cheque_mode')->nullable()->after('transfer_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cheques', function (Blueprint $table) {
            if (Schema::hasColumn('cheques', 'supplier_cheque_mode')) {
                $table->dropColumn('supplier_cheque_mode');
            }
        });
    }
};
