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
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('address')->nullable()->after('name');
            $table->string('owner_name')->nullable()->after('address');
            $table->string('website')->nullable()->after('owner_name');
            $table->text('notes')->nullable()->after('website');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['address', 'owner_name', 'website', 'notes']);
        });
    }
};
