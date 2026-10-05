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
        Schema::table('crm_sends', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
            $table->timestamp('opened_at')->nullable()->after('delivered_at');
            $table->timestamp('clicked_at')->nullable()->after('opened_at');
            $table->unsignedSmallInteger('opens_count')->default(0)->after('clicked_at');
            $table->unsignedSmallInteger('clicks_count')->default(0)->after('opens_count');

            $table->index('brevo_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_sends', function (Blueprint $table) {
            $table->dropIndex(['brevo_message_id']);
            $table->dropColumn(['delivered_at', 'opened_at', 'clicked_at', 'opens_count', 'clicks_count']);
        });
    }
};
