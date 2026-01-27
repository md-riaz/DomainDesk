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
        Schema::create('tld_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tld_id')->constrained()->onDelete('cascade');
            $table->enum('action', ['register', 'renew', 'transfer']);
            $table->unsignedTinyInteger('years'); // 1-10
            $table->decimal('price', 10, 2);
            $table->date('effective_date');
            $table->timestamps();

            $table->index(['tld_id', 'action', 'years', 'effective_date']);
            $table->index('effective_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tld_prices');
    }
};
