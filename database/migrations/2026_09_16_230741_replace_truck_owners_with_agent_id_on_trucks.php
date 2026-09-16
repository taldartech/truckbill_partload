<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('trucks', 'agent_id')) {
            Schema::table('trucks', function (Blueprint $table) {
                $table->foreignId('agent_id')
                    ->nullable()
                    ->after('capacity')
                    ->constrained('agents')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('truck_owners') && Schema::hasColumn('trucks', 'truck_owner_id')) {
            $this->backfillAgentsFromTruckOwners();

            Schema::table('trucks', function (Blueprint $table) {
                $table->dropConstrainedForeignId('truck_owner_id');
            });
        }

        Schema::dropIfExists('truck_owners');
    }

    public function down(): void
    {
        if (! Schema::hasTable('truck_owners')) {
            Schema::create('truck_owners', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('address')->nullable();
                $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
                $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
                $table->string('mobile')->nullable();
                $table->string('email')->nullable();
                $table->string('gst_number')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('trucks', 'truck_owner_id')) {
            Schema::table('trucks', function (Blueprint $table) {
                $table->foreignId('truck_owner_id')
                    ->nullable()
                    ->after('capacity')
                    ->constrained('truck_owners')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasColumn('trucks', 'agent_id')) {
            Schema::table('trucks', function (Blueprint $table) {
                $table->dropConstrainedForeignId('agent_id');
            });
        }
    }

    private function backfillAgentsFromTruckOwners(): void
    {
        $cache = [];

        DB::table('trucks')
            ->whereNotNull('truck_owner_id')
            ->orderBy('id')
            ->get(['id', 'company_id', 'truck_owner_id'])
            ->each(function (object $truck) use (&$cache): void {
                $owner = DB::table('truck_owners')->where('id', $truck->truck_owner_id)->first();

                if ($owner === null) {
                    return;
                }

                $key = $owner->company_id.'|'.$owner->name.'|'.(string) ($owner->mobile ?? '');

                if (! isset($cache[$key])) {
                    $existingAgentId = DB::table('agents')
                        ->where('company_id', $owner->company_id)
                        ->where('name', $owner->name)
                        ->where('mobile', $owner->mobile)
                        ->whereNull('deleted_at')
                        ->value('id');

                    if ($existingAgentId) {
                        $cache[$key] = (int) $existingAgentId;
                    } else {
                        $cache[$key] = (int) DB::table('agents')->insertGetId([
                            'company_id' => $owner->company_id,
                            'name' => $owner->name,
                            'address' => $owner->address,
                            'state_id' => $owner->state_id,
                            'city_id' => $owner->city_id,
                            'mobile' => $owner->mobile,
                            'email' => $owner->email,
                            'gst_no' => $owner->gst_number,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                DB::table('trucks')->where('id', $truck->id)->update([
                    'agent_id' => $cache[$key],
                ]);
            });
    }
};
