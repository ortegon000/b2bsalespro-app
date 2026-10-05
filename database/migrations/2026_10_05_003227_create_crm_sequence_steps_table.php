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
        Schema::create('crm_sequence_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('crm_sequences')->cascadeOnDelete();
            $table->unsignedTinyInteger('day');
            $table->unsignedBigInteger('brevo_template_id')->nullable();
            $table->timestamps();

            $table->unique(['sequence_id', 'day']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_sequence_steps');
    }
};
