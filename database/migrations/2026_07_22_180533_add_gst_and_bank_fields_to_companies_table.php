<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'has_gst')) {
                $table->boolean('has_gst')->default(false)->after('pan_no');
            }
            if (! Schema::hasColumn('companies', 'gst_number')) {
                $table->string('gst_number')->nullable()->after('has_gst');
            }
            if (! Schema::hasColumn('companies', 'gst_type')) {
                $table->string('gst_type')->nullable()->after('gst_number');
            }
            if (! Schema::hasColumn('companies', 'gst_scheme')) {
                $table->string('gst_scheme')->nullable()->after('gst_type');
            }
            if (! Schema::hasColumn('companies', 'gst_slab')) {
                $table->decimal('gst_slab', 5, 2)->nullable()->after('gst_scheme');
            }
            if (! Schema::hasColumn('companies', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('gst_slab');
            }
            if (! Schema::hasColumn('companies', 'account_number')) {
                $table->string('account_number')->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('companies', 'ifsc_code')) {
                $table->string('ifsc_code')->nullable()->after('account_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $columns = [
                'has_gst',
                'gst_number',
                'gst_type',
                'gst_scheme',
                'gst_slab',
                'bank_name',
                'account_number',
                'ifsc_code',
            ];

            $existing = array_values(array_filter(
                $columns,
                fn (string $column) => Schema::hasColumn('companies', $column)
            ));

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
