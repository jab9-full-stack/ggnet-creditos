<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_references', function (Blueprint $table): void {
            $table->id();

            $table->foreignIdFor(Client::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type', 30)->default('personal');
            $table->string('full_name', 180);
            $table->string('relationship', 120)->nullable();
            $table->string('phone', 30);
            $table->string('secondary_phone', 30)->nullable();
            $table->string('address_line', 255)->nullable();
            $table->string('workplace', 180)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_primary')->default(false);

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

            $table->index(['client_id', 'type']);
            $table->index(['client_id', 'is_primary']);
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_references');
    }
};
