<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `normalized_number` holds the number with spaces, dashes and a leading
     * +94/0 prefix stripped, so duplicates can be found regardless of how the
     * number was typed. It is indexed per organization, not made globally
     * unique, because the same number could legitimately belong to two
     * different organizations.
     */
    public function up(): void
    {
        Schema::create('business_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->string('normalized_number');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['organization_id', 'normalized_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_phones');
    }
};
