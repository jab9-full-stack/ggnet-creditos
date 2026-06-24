<?php

use App\Models\Agency;
use App\Models\Client;
use App\Models\Credit;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_payments', function (Blueprint $table): void {
            $table->id();

            $table->foreignIdFor(Agency::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignIdFor(Client::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(Credit::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->string('code', 40)->unique();
            $table->string('method', 30);
            $table->string('reference', 160)->nullable();

            $table->unsignedSmallInteger('installments_count');
            $table->decimal('amount', 12, 2);
            $table->timestamp('paid_at');

            $table->text('notes')->nullable();

            $table->foreignIdFor(User::class, 'received_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignIdFor(User::class, 'created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignIdFor(User::class, 'updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'paid_at']);
            $table->index(['client_id', 'paid_at']);
            $table->index(['credit_id', 'paid_at']);
            $table->index(['method', 'paid_at']);
        });

        Schema::table('credit_installments', function (Blueprint $table): void {
            $table->foreignId('credit_payment_id')
                ->nullable()
                ->after('credit_id')
                ->constrained('credit_payments')
                ->nullOnDelete();

            $table->index('credit_payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('credit_installments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('credit_payment_id');
        });

        Schema::dropIfExists('credit_payments');
    }
};
