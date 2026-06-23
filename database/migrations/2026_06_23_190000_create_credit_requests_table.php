<?php

use App\Models\Agency;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_requests', function (Blueprint $table): void {
            $table->id();

            $table->foreignIdFor(Agency::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignIdFor(Client::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->string('code', 40)->unique();
            $table->string('status', 30)->default('draft');

            $table->decimal('requested_amount', 12, 2);
            $table->unsignedSmallInteger('requested_term_weeks')->nullable();

            $table->text('purpose')->nullable();
            $table->string('income_source', 180)->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->text('review_notes')->nullable();
            $table->text('decision_notes')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->foreignIdFor(User::class, 'created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignIdFor(User::class, 'updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignIdFor(User::class, 'reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignIdFor(User::class, 'approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignIdFor(User::class, 'rejected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignIdFor(User::class, 'cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('requested_amount');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_requests');
    }
};
