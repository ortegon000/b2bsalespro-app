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
        Schema::create('oc_checklists', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // antes | durante | despues
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('note')->nullable();
            $table->json('blocks'); // [{h: string, items: string[]}]
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oc_checklists');
    }
};
