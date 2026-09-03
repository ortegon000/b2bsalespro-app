<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('oc_objections', function (Blueprint $table) {
            $table->json('search_aliases')->nullable()->after('objection');
        });

        $content = File::json(database_path('seeders/data/objecion-cero.json'));

        foreach ($content['FICHAS'] as $ficha) {
            DB::table('oc_objections')
                ->where('number', $ficha['n'])
                ->update([
                    'search_aliases' => json_encode(
                        $ficha['aliases'] ?? [],
                        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
                    ),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oc_objections', function (Blueprint $table) {
            $table->dropColumn('search_aliases');
        });
    }
};
