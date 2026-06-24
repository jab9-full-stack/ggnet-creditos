<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_installments', function (Blueprint $table): void {
            $table->timestamp('overdue_at')->nullable()->after('paid_at');
            $table->index(['status', 'overdue_at']);
        });
    }

    public function down(): void
    {
        Schema::table('credit_installments', function (Blueprint $table): void {
            $table->dropIndex(['status', 'overdue_at']);
            $table->dropColumn('overdue_at');
        });
    }
};
