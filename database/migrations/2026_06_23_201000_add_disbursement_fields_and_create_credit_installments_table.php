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
        Schema::table('credits', function (Blueprint $table): void {
            $table->foreignIdFor(User::class, 'disbursed_by')
                ->nullable()
                ->after('approved_by')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::create('credit_installments', function (Blueprint $table): void {
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

            $table->unsignedSmallInteger('number');
            $table->string('status', 30)->default('pending');

            $table->date('due_date');
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('interest_amount', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);

            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();

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

            $table->unique(['credit_id', 'number']);
            $table->index(['agency_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index(['credit_id', 'status']);
            $table->index(['due_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_installments');

        Schema::table('credits', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('disbursed_by');
        });
    }
};
