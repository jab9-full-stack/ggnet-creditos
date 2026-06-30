<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'credit_blocked_at')) {
                $table->timestamp('credit_blocked_at')->nullable()->index();
            }

            if (! Schema::hasColumn('clients', 'credit_block_reason')) {
                $table->string('credit_block_reason')->nullable();
            }

            if (! Schema::hasColumn('clients', 'credit_block_source')) {
                $table->string('credit_block_source')->nullable();
            }

            if (! Schema::hasColumn('clients', 'credit_last_delinquency_at')) {
                $table->timestamp('credit_last_delinquency_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            foreach ([
                'credit_last_delinquency_at',
                'credit_block_source',
                'credit_block_reason',
                'credit_blocked_at',
            ] as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
