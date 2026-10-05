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
        Schema::create('crm_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('crm_companies')->cascadeOnDelete();
            $table->foreignId('sequence_id')->nullable()->constrained('crm_sequences')->nullOnDelete();
            $table->string('title');
            $table->string('modality'); // ver Enums\CourseModality
            $table->unsignedSmallInteger('hours')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->date('reinforcement_starts_on')->nullable();
            $table->timestamp('reinforcement_activated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_courses');
    }
};
