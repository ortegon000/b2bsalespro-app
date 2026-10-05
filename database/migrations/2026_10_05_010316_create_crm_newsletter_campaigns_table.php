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
        Schema::create('crm_newsletter_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('brevo_template_id');
            $table->unsignedBigInteger('brevo_campaign_id');
            $table->timestamp('scheduled_for')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_newsletter_campaigns');
    }
};
