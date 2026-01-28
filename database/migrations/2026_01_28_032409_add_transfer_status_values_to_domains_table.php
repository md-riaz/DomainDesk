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
        // SQLite doesn't support modifying check constraints easily
        // Since we use PHP enums (DomainStatus), the database-level constraint
        // is not strictly necessary. Laravel's enum casting will handle validation.
        
        // For new installations, the enum will include all transfer statuses
        // For existing installations, we just add the transfer fields
        
        // The domains table already has a status field, no modification needed
        // Application-level enum (DomainStatus) will enforce valid values
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No changes to reverse
    }
};
