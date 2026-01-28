<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('notification_type'); // e.g., 'domain_renewal', 'invoice_issued', etc.
            $table->boolean('email_enabled')->default(true);
            $table->boolean('dashboard_enabled')->default(true);
            $table->timestamps();
            
            $table->unique(['user_id', 'notification_type']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};
