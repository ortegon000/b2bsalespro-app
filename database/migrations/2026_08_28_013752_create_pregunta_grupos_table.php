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
        Schema::create('pregunta_grupos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo'); // Precio, Confianza, Competencia, Urgencia, Autoridad, Tiempo
            $table->json('items'); // array de preguntas del grupo (20 c/u)
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pregunta_grupos');
    }
};
