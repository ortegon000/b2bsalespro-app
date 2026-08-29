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
        Schema::create('oc_question_groups', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Precio, Confianza, Competencia, Urgencia, Autoridad, Tiempo
            $table->json('items'); // array de preguntas del grupo (20 c/u)
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oc_question_groups');
    }
};
