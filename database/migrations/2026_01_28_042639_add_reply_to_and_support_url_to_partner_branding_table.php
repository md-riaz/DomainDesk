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
        Schema::table('partner_branding', function (Blueprint $table) {
            $table->string('reply_to_email')->nullable()->after('email_sender_email');
            $table->string('support_url')->nullable()->after('support_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partner_branding', function (Blueprint $table) {
            $table->dropColumn(['reply_to_email', 'support_url']);
        });
    }
};
