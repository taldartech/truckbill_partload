<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            $table->dropForeign(['transporter_id']);
            $table->dropColumn('transporter_id');
        });

        Schema::table('inward_register_details', function (Blueprint $table) {
            $table->dropForeign(['transporter_id']);
            $table->dropColumn('transporter_id');
            $table->foreignId('agent_id')->nullable()->after('private_marka')->constrained('agents')->nullOnDelete();
        });

        Schema::dropIfExists('transporters');

        $permissionIds = DB::table('permissions')
            ->whereIn('name', [
                'view transporters',
                'create transporters',
                'edit transporters',
                'delete transporters',
            ])
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }

    public function down(): void
    {
        Schema::create('transporters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('short_name')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('pan_number')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('inward_register_details', function (Blueprint $table) {
            $table->dropForeign(['agent_id']);
            $table->dropColumn('agent_id');
            $table->foreignId('transporter_id')->nullable()->after('private_marka')->constrained('transporters')->nullOnDelete();
        });

        Schema::table('trucks', function (Blueprint $table) {
            $table->foreignId('transporter_id')->nullable()->after('company_id')->constrained('transporters')->nullOnDelete();
        });
    }
};
