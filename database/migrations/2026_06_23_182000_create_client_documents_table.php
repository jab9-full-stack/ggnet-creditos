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
        Schema::create('client_documents', function (Blueprint $table): void {
            $table->id();

            $table->foreignIdFor(Client::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type', 40);
            $table->string('title', 180);
            $table->string('original_name', 255);
            $table->string('disk', 40)->default('local');
            $table->string('path', 500);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);

            $table->string('status', 30)->default('pending');
            $table->text('notes')->nullable();

            $table->foreignIdFor(User::class, 'uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignIdFor(User::class, 'updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignIdFor(User::class, 'verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'type']);
            $table->index(['client_id', 'status']);
            $table->index(['uploaded_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_documents');
    }
};
