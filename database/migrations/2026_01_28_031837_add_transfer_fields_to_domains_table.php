<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->text('auth_code')->nullable()->after('auto_renew');
            $table->timestamp('transfer_initiated_at')->nullable()->after('auth_code');
            $table->timestamp('transfer_completed_at')->nullable()->after('transfer_initiated_at');
            $table->string('transfer_status_message')->nullable()->after('transfer_completed_at');
            $table->json('transfer_metadata')->nullable()->after('transfer_status_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn([
                'auth_code',
                'transfer_initiated_at',
                'transfer_completed_at',
                'transfer_status_message',
                'transfer_metadata',
            ]);
        });
    }
};
