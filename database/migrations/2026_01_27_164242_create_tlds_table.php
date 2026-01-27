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
        Schema::create('tlds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registrar_id')->constrained()->onDelete('cascade');
            $table->string('extension'); // e.g., "com", "net", "org"
            $table->unsignedTinyInteger('min_years')->default(1);
            $table->unsignedTinyInteger('max_years')->default(10);
            $table->boolean('supports_dns')->default(true);
            $table->boolean('supports_whois_privacy')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['registrar_id', 'extension']);
            $table->index('extension');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tlds');
    }
};
