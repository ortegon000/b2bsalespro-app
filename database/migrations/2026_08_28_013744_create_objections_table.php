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
        Schema::create('objections', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('number')->unique();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('type'); // real | duda | excusa — ver Enums\TipoObjecion
            $table->string('objection');
            $table->text('confirm');
            $table->text('meaning');
            $table->json('worst_case')->nullable();
            $table->json('dialogue')->nullable();
            $table->text('question')->nullable();
            $table->text('closing')->nullable();
            $table->text('error')->nullable();
            $table->text('tip')->nullable();
            $table->json('branches')->nullable();
            $table->timestamps();

            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('objections');
    }
};
