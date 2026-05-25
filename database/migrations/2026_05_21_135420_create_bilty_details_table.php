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
        Schema::create('bilty_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bilty_id')->constrained('bilties')->cascadeOnDelete();
            $table->foreignId('good_id')->constrained('goods')->cascadeOnDelete();
            $table->foreignId('package_type_id')->constrained('package_types')->cascadeOnDelete();
            $table->integer('no_of_packages')->nullable();
            $table->decimal('gross_weight', 10, 2)->nullable();
            $table->decimal('rate', 10, 2)->nullable();
            $table->string('rate_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bilty_details');
    }
};
