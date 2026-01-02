<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drip_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->constrained('drip_streams')->onDelete('cascade');
            $table->string('eventable_type'); // Polymorphic type (e.g., App\Models\Video)
            $table->unsignedBigInteger('eventable_id'); // Polymorphic ID
            $table->string('offset_interval')->default('0'); // ISO 8601 (P1D) or seconds
            $table->boolean('is_visible')->default(true); // Show even when locked
            $table->timestamps();

            // Indexes
            $table->index(['stream_id', 'eventable_type', 'eventable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drip_events');
    }
};
