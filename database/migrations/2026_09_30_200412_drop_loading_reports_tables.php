<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public const BACKUP_PATH = 'backups/loading_reports_backup.json';

    /**
     * Parent table first so the restore can insert in this order.
     *
     * @var list<string>
     */
    private const TABLES = ['loading_reports', 'loading_report_details'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->backupTables();

        Schema::dropIfExists('loading_report_details');
        Schema::dropIfExists('loading_reports');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('loading_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('from_station_id')->constrained('stations')->cascadeOnDelete();
            $table->foreignId('to_station_id')->constrained('stations')->cascadeOnDelete();
            $table->text('remark')->nullable();
            $table->integer('no_of_packages')->default(0);
            $table->decimal('gross_weight', 10, 2)->default(0);
            $table->date('date');
            $table->foreignId('truck_id')->constrained()->cascadeOnDelete();
            $table->string('driver_name')->nullable();
            $table->timestamps();
            $table->text('delete_remark')->nullable();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });

        Schema::create('loading_report_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('loading_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bilty_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inward_register_detail_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('good_id')->nullable()->constrained('goods')->cascadeOnDelete();
            $table->foreignId('package_type_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('no_of_packages')->default(0);
            $table->foreignId('consignee_id')->nullable()->constrained('parties')->cascadeOnDelete();
            $table->decimal('gross_weight', 10, 2)->default(0);
            $table->timestamps();
        });

        $this->restoreTables();
    }

    /**
     * Soft-deleted rows are included, since the raw query builder ignores model scopes.
     */
    private function backupTables(): void
    {
        $backup = [];

        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                return;
            }

            $backup[$table] = DB::table($table)->orderBy('id')->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }

        $saved = Storage::disk('local')->put(
            self::BACKUP_PATH,
            json_encode($backup, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        );

        if (! $saved) {
            throw new RuntimeException('Could not write the loading reports backup to storage/app/private/'.self::BACKUP_PATH.'. Tables were not dropped.');
        }
    }

    /**
     * Foreign key checks are disabled so rows are restored exactly as backed up,
     * even if a referenced record was removed after the tables were dropped.
     */
    private function restoreTables(): void
    {
        $disk = Storage::disk('local');

        if (! $disk->exists(self::BACKUP_PATH)) {
            return;
        }

        $backup = json_decode($disk->get(self::BACKUP_PATH), true, flags: JSON_THROW_ON_ERROR);

        Schema::withoutForeignKeyConstraints(function () use ($backup) {
            foreach (self::TABLES as $table) {
                foreach (array_chunk($backup[$table] ?? [], 500) as $rows) {
                    DB::table($table)->insert($rows);
                }
            }
        });
    }
};
