<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('code', 40)->unique();
            $table->string('status', 30)->default('open')->index();

            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->timestamp('opened_at');
            $table->foreignIdFor(User::class, 'opened_by')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('expected_cash_amount', 12, 2)->default(0);
            $table->decimal('counted_cash_amount', 12, 2)->nullable();
            $table->decimal('difference_amount', 12, 2)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignIdFor(User::class, 'closed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();

            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'user_id', 'status']);
            $table->index(['opened_at', 'closed_at']);
        });

        Schema::create('cash_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cash_session_id')->nullable()->constrained('cash_sessions')->nullOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('credit_id')->nullable()->constrained('credits')->nullOnDelete();
            $table->foreignId('credit_payment_id')->nullable()->constrained('credit_payments')->nullOnDelete();

            $table->string('code', 40)->unique();
            $table->string('status', 30)->default('active')->index();
            $table->string('type', 40)->index();
            $table->string('method', 30)->index();

            $table->decimal('amount', 12, 2);
            $table->string('reference', 180)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('movement_at')->index();

            $table->timestamp('voided_at')->nullable()->index();
            $table->foreignIdFor(User::class, 'voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();

            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'status', 'method']);
            $table->index(['cash_session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('cash_sessions');
    }
};
