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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('gst_number')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('short_name')->nullable();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->string('pin_code')->nullable();
            $table->string('gst_scheme')->nullable();
            $table->string('gst_type')->nullable();
            $table->string('pan_no')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('bank')->nullable();
            $table->string('account_number')->nullable();
            $table->string('ifsc')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->string('company_type')->nullable();
            $table->string('company_logo')->nullable();
            $table->text('invoice_template')->nullable();
            $table->text('lr_copy_template')->nullable();
            $table->text('freight_challan_template')->nullable();
            $table->string('gst_slab')->nullable();
            $table->boolean('status')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
