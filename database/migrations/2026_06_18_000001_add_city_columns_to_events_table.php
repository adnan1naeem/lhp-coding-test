<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('city_slug')->nullable()->after('longitude');
            $table->string('city_label')->nullable()->after('city_slug');

            $table->index('city_slug');
            $table->index(['status', 'created_time']);
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_time']);
            $table->dropIndex(['city_slug']);
            $table->dropColumn(['city_slug', 'city_label']);
        });
    }
};
