<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('agency_id')
                ->nullable()
                ->after('id')
                ->constrained('agencies')
                ->nullOnDelete();

            $table->string('status', 30)
                ->default('active')
                ->after('password');

            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->timestamp('blocked_at')->nullable()->after('last_login_ip');
            $table->foreignId('blocked_by')->nullable()->after('blocked_at')->constrained('users')->nullOnDelete();
            $table->string('blocked_reason', 255)->nullable()->after('blocked_by');

            $table->index(['agency_id', 'status']);
            $table->index('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agency_id');
            $table->dropConstrainedForeignId('blocked_by');
            $table->dropIndex(['last_login_at']);
            $table->dropIndex(['agency_id', 'status']);
            $table->dropColumn([
                'status',
                'last_login_at',
                'last_login_ip',
                'blocked_at',
                'blocked_reason',
            ]);
        });
    }
};
