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
        Schema::create('partner_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->onDelete('cascade');
            $table->foreignId('tld_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('markup_type', ['fixed', 'percentage']);
            $table->decimal('markup_value', 10, 2);
            $table->unsignedTinyInteger('duration')->nullable(); // specific years, null = all
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['partner_id', 'tld_id', 'duration', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_pricing_rules');
    }
};
