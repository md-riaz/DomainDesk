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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('type'); // domain_registration, domain_renewal, domain_transfer
            $table->string('domain_name');
            $table->foreignId('tld_id')->nullable()->constrained('tlds')->nullOnDelete();
            $table->integer('years')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->integer('quantity')->default(1);
            $table->decimal('total', 10, 2);
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->foreignId('domain_id')->nullable()->constrained('domains')->nullOnDelete(); // Created domain after processing
            $table->json('configuration')->nullable(); // Nameservers, contacts, auto-renew, etc.
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
