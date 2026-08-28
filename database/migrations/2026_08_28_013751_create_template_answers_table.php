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
        Schema::create('template_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_step_id')->constrained('template_steps')->cascadeOnDelete();
            $table->unsignedSmallInteger('field_index'); // índice del campo dentro de fields[] del paso
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'template_step_id', 'field_index'], 'template_answers_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_answers');
    }
};
