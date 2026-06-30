<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('credit_installments', 'late_fee_amount')) {
            Schema::table('credit_installments', function (Blueprint $table): void {
                $table->decimal('late_fee_amount', 12, 2)->default(0)->after('interest_amount');
            });
        }

        if (! Schema::hasColumn('credit_installments', 'late_fee_days')) {
            Schema::table('credit_installments', function (Blueprint $table): void {
                $table->unsignedSmallInteger('late_fee_days')->default(0)->after('late_fee_amount');
            });
        }

        if (! Schema::hasColumn('credit_installments', 'late_fee_applied_at')) {
            Schema::table('credit_installments', function (Blueprint $table): void {
                $table->timestamp('late_fee_applied_at')->nullable()->after('overdue_at');
            });
        }

        if (! Schema::hasColumn('credit_installments', 'late_fee_applied_by')) {
            Schema::table('credit_installments', function (Blueprint $table): void {
                $table->unsignedBigInteger('late_fee_applied_by')->nullable()->after('late_fee_applied_at');
            });
        }

        if (! Schema::hasColumn('credit_installments', 'late_fee_notes')) {
            Schema::table('credit_installments', function (Blueprint $table): void {
                $table->string('late_fee_notes', 255)->nullable()->after('late_fee_applied_by');
            });
        }
    }

    public function down(): void
    {
        Schema::table('credit_installments', function (Blueprint $table): void {
            foreach ([
                'late_fee_notes',
                'late_fee_applied_by',
                'late_fee_applied_at',
                'late_fee_days',
                'late_fee_amount',
            ] as $column) {
                if (Schema::hasColumn('credit_installments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
