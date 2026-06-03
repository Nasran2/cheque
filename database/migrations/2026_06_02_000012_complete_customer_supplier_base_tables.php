<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'business_name')) {
                $table->string('business_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('customers', 'phone_2')) {
                $table->string('phone_2', 30)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('customers', 'nic')) {
                $table->string('nic', 50)->nullable()->after('email');
            }
            if (! Schema::hasColumn('customers', 'vat_no')) {
                $table->string('vat_no', 100)->nullable()->after('nic');
            }
            if (! Schema::hasColumn('customers', 'city')) {
                $table->string('city', 100)->nullable()->after('address');
            }
            if (! Schema::hasColumn('customers', 'opening_balance')) {
                $table->decimal('opening_balance', 15, 2)->default(0)->after('city');
            }
            if (! Schema::hasColumn('customers', 'current_balance')) {
                $table->decimal('current_balance', 15, 2)->default(0)->after('opening_balance');
            }
            if (! Schema::hasColumn('customers', 'credit_limit')) {
                $table->decimal('credit_limit', 15, 2)->nullable()->after('current_balance');
            }
            if (! Schema::hasColumn('customers', 'status')) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('credit_limit');
            }
            if (! Schema::hasColumn('customers', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            if (! Schema::hasColumn('customers', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('customers', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('customers', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'business_name')) {
                $table->string('business_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('suppliers', 'phone_2')) {
                $table->string('phone_2', 30)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('suppliers', 'vat_no')) {
                $table->string('vat_no', 100)->nullable()->after('email');
            }
            if (! Schema::hasColumn('suppliers', 'city')) {
                $table->string('city', 100)->nullable()->after('address');
            }
            if (! Schema::hasColumn('suppliers', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('city');
            }
            if (! Schema::hasColumn('suppliers', 'bank_branch')) {
                $table->string('bank_branch')->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('suppliers', 'account_name')) {
                $table->string('account_name')->nullable()->after('bank_branch');
            }
            if (! Schema::hasColumn('suppliers', 'account_no')) {
                $table->string('account_no', 100)->nullable()->after('account_name');
            }
            if (! Schema::hasColumn('suppliers', 'opening_balance')) {
                $table->decimal('opening_balance', 15, 2)->default(0)->after('account_no');
            }
            if (! Schema::hasColumn('suppliers', 'current_balance')) {
                $table->decimal('current_balance', 15, 2)->default(0)->after('opening_balance');
            }
            if (! Schema::hasColumn('suppliers', 'status')) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('current_balance');
            }
            if (! Schema::hasColumn('suppliers', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            if (! Schema::hasColumn('suppliers', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('suppliers', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('suppliers', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'business_name',
                'phone_2',
                'nic',
                'vat_no',
                'city',
                'opening_balance',
                'current_balance',
                'credit_limit',
                'status',
                'notes',
                'deleted_at',
            ]);
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'business_name',
                'phone_2',
                'vat_no',
                'city',
                'bank_name',
                'bank_branch',
                'account_name',
                'account_no',
                'opening_balance',
                'current_balance',
                'status',
                'notes',
                'deleted_at',
            ]);
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
        });
    }
};
