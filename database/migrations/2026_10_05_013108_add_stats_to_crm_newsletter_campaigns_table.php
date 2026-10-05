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
        Schema::table('crm_newsletter_campaigns', function (Blueprint $table) {
            $table->json('stats')->nullable()->after('scheduled_for');
            $table->timestamp('stats_synced_at')->nullable()->after('stats');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_newsletter_campaigns', function (Blueprint $table) {
            $table->dropColumn(['stats', 'stats_synced_at']);
        });
    }
};
