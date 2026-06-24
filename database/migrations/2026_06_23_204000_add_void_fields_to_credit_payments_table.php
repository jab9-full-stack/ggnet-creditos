<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_payments', function (Blueprint $table): void {
            $table->string('status', 30)->default('applied')->after('code');
            $table->timestamp('voided_at')->nullable()->after('paid_at');

            $table->foreignIdFor(User::class, 'voided_by')
                ->nullable()
                ->after('received_by')
                ->constrained('users')
                ->nullOnDelete();

            $table->text('void_reason')->nullable()->after('voided_by');

            $table->index(['status', 'paid_at']);
            $table->index('voided_at');
        });
    }

    public function down(): void
    {
        Schema::table('credit_payments', function (Blueprint $table): void {
            $table->dropIndex(['status', 'paid_at']);
            $table->dropIndex(['voided_at']);
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['status', 'voided_at', 'void_reason']);
        });
    }
};
