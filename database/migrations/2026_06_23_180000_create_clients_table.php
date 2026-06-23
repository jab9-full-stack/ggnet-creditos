<?php

use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();

            $table->foreignIdFor(Agency::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('code', 30)->unique();

            $table->string('first_name', 120);
            $table->string('middle_name', 120)->nullable();
            $table->string('last_name', 120);
            $table->string('second_last_name', 120)->nullable();
            $table->string('married_name', 120)->nullable();

            $table->string('dpi', 20)->unique();
            $table->string('nit', 20)->nullable()->index();
            $table->date('birth_date')->nullable();
            $table->string('gender', 30)->nullable();

            $table->string('phone', 30);
            $table->string('secondary_phone', 30)->nullable();
            $table->string('email', 160)->nullable();

            $table->string('address_line', 255);
            $table->string('city', 120)->nullable();
            $table->string('department', 120)->nullable();
            $table->string('country', 80)->default('Guatemala');

            $table->string('occupation', 160)->nullable();
            $table->string('workplace', 180)->nullable();

            $table->string('status', 30)->default('active');
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

            $table->index(['agency_id', 'status']);
            $table->index(['last_name', 'first_name']);
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
