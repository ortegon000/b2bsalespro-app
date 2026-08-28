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
        Schema::create('fichas', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('numero')->unique();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $table->string('tipo'); // real | duda | excusa — ver Enums\TipoObjecion
            $table->string('objecion');
            $table->text('confirmar');
            $table->text('significa');
            $table->json('peor')->nullable();
            $table->json('dialogo')->nullable();
            $table->text('pregunta')->nullable();
            $table->text('cierre')->nullable();
            $table->text('error')->nullable();
            $table->text('consejo')->nullable();
            $table->json('ramas')->nullable();
            $table->timestamps();

            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fichas');
    }
};
