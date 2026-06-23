<?php

use App\Models\Agency;
use App\Models\Client;
use App\Models\CreditRequest;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credits', function (Blueprint $table): void {
            $table->id();

            $table->foreignIdFor(Agency::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignIdFor(Client::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(CreditRequest::class)
                ->constrained()
                ->cascadeOnDelete()
                ->unique();

            $table->string('code', 40)->unique();
            $table->string('status', 40)->default('approved_pending_disbursement');

            $table->decimal('principal_amount', 12, 2);
            $table->decimal('interest_rate_percent', 5, 2);
            $table->decimal('interest_amount', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->unsignedSmallInteger('term_weeks')->default(4);

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();

            $table->foreignIdFor(User::class, 'created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignIdFor(User::class, 'approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignIdFor(User::class, 'updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('principal_amount');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};
