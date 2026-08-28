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
        Schema::create('checklist_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checklist_id')->constrained('checklists')->cascadeOnDelete();
            $table->string('item_key'); // "{bloqueIndex}-{itemIndex}" dentro del checklist
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'checklist_id', 'item_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_progress');
    }
};
