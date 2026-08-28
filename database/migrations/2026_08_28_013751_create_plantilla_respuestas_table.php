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
        Schema::create('plantilla_respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plantilla_paso_id')->constrained('plantilla_pasos')->cascadeOnDelete();
            $table->unsignedSmallInteger('campo_index'); // índice del campo dentro de campos[] del paso
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'plantilla_paso_id', 'campo_index'], 'plantilla_respuestas_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantilla_respuestas');
    }
};
