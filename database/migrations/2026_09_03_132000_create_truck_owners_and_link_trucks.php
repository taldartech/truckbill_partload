<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        Schema::table('trucks', function (Blueprint $table) {
            $table->foreignId('truck_owner_id')
                ->nullable()
                ->after('capacity')
                ->constrained('truck_owners')
                ->nullOnDelete();
        });

        $this->backfillTruckOwners();

        Schema::table('trucks', function (Blueprint $table) {
            $table->dropColumn([
                'owner_name',
                'owner_mobile',
                'owner_email',
                'owner_address',
                'owner_city',
                'owner_state',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            $table->string('owner_name')->nullable();
            $table->string('owner_mobile')->nullable();
            $table->string('owner_email')->nullable();
            $table->string('owner_address')->nullable();
            $table->string('owner_city')->nullable();
            $table->string('owner_state')->nullable();
        });

        Schema::table('trucks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('truck_owner_id');
        });

        Schema::dropIfExists('truck_owners');
    }

    private function backfillTruckOwners(): void
    {
        $cache = [];

        DB::table('trucks')
            ->whereNotNull('owner_name')
            ->where('owner_name', '!=', '')
            ->orderBy('id')
            ->get()
            ->each(function (object $truck) use (&$cache): void {
                $key = $truck->company_id.'|'.$truck->owner_name.'|'.(string) ($truck->owner_mobile ?? '');

                if (! isset($cache[$key])) {
                    $cache[$key] = DB::table('truck_owners')->insertGetId([
                        'company_id' => $truck->company_id,
                        'name' => $truck->owner_name,
                        'address' => $truck->owner_address,
                        'mobile' => $truck->owner_mobile,
                        'email' => $truck->owner_email,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('trucks')->where('id', $truck->id)->update([
                    'truck_owner_id' => $cache[$key],
                ]);
            });
    }
};
