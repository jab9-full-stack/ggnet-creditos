<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 80)->default('general');
            $table->string('key', 120)->unique();
            $table->json('value')->nullable();
            $table->string('type', 40)->default('string');
            $table->string('description', 255)->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['group', 'is_public']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
