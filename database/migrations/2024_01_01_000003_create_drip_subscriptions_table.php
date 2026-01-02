<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drip_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscriber_type'); // Polymorphic type (e.g., App\Models\User)
            $table->unsignedBigInteger('subscriber_id'); // Polymorphic ID
            $table->foreignId('stream_id')->constrained('drip_streams')->onDelete('cascade');
            $table->timestamp('joined_at'); // When user subscribed
            $table->timestamps();

            // Indexes
            $table->index(['subscriber_type', 'subscriber_id']);
            $table->unique(['subscriber_type', 'subscriber_id', 'stream_id'], 'unique_subscription');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drip_subscriptions');
    }
};
