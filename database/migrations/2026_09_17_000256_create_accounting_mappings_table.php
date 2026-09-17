<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('event_key');
            $table->json('rules');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'event_key']);
            $table->index(['event_key', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_mappings');
    }
};
