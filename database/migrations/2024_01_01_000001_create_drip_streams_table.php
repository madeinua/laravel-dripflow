<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drip_streams', function (Blueprint $table) {
            $table->id();
            $table->string('origin_type'); // Polymorphic type (e.g., App\Models\Course)
            $table->unsignedBigInteger('origin_id'); // Polymorphic ID
            $table->boolean('is_public')->default(true); // Public access without subscription
            $table->enum('unlock_mode', ['fixed', 'relative'])->default('relative');
            $table->timestamp('start_date')->nullable(); // For fixed mode
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Index for polymorphic relationship
            $table->index(['origin_type', 'origin_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drip_streams');
    }
};
