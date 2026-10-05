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
        Schema::create('crm_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('crm_subscriptions')->cascadeOnDelete();
            $table->foreignId('sequence_step_id')->constrained('crm_sequence_steps')->cascadeOnDelete();
            $table->timestamp('scheduled_for');
            $table->string('status'); // ver Enums\SendStatus
            $table->timestamp('sent_at')->nullable();
            $table->string('brevo_message_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['subscription_id', 'sequence_step_id']);
            $table->index(['status', 'scheduled_for']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_sends');
    }
};
