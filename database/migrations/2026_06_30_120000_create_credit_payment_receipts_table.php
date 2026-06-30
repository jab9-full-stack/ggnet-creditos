<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_payment_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('agency_id')->nullable()->constrained('agencies')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('credit_id')->nullable()->constrained('credits')->nullOnDelete();
            $table->foreignId('credit_payment_id')->nullable()->constrained('credit_payments')->nullOnDelete();
            $table->foreignId('cash_session_id')->nullable()->constrained('cash_sessions')->nullOnDelete();
            $table->foreignId('cash_movement_id')->nullable()->constrained('cash_movements')->nullOnDelete();
            $table->string('code', 40)->unique();
            $table->string('status', 30)->index();
            $table->string('method', 40);
            $table->string('reference', 180)->nullable();
            $table->unsignedSmallInteger('installments_count')->default(0);
            $table->json('installments_snapshot')->nullable();
            $table->decimal('amount', 12, 2);
            $table->timestamp('issued_at')->nullable()->index();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable()->index();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('credit_payment_id');
            $table->unique('cash_movement_id');
            $table->index(['credit_id', 'status']);
            $table->index(['client_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_payment_receipts');
    }
};
