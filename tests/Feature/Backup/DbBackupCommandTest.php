<?php

namespace Tests\Feature\Backup;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DbBackupCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dbbackup_reports_failure_when_mysqldump_fails(): void
    {
        Process::fake([
            '*' => Process::result(output: '', errorOutput: 'mysqldump failed', exitCode: 1),
        ]);

        $this->artisan('app:dbbackup')->assertFailed();
    }

    public function test_dbbackup_uploads_when_dump_succeeds(): void
    {
        Storage::fake('local');
        config([
            'filesystems.backup.disk' => 'local',
            'filesystems.backup.path' => 'backups',
            'database.connections.mysql.username' => 'test',
            'database.connections.mysql.password' => 'secret',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.database' => 'partload_testing',
        ]);

        $backupDir = storage_path('app/backup');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        Process::fake(function () use ($backupDir) {
            foreach (glob($backupDir.'/*.sql.gz') ?: [] as $existing) {
                @unlink($existing);
            }

            $gzPath = $backupDir.'/'.now()->format('d-m-Y-H-i-s').'.sql.gz';
            file_put_contents($gzPath, 'gzipped-sql-content');

            return Process::result(output: 'ok', exitCode: 0);
        });

        $this->artisan('app:dbbackup')->assertSuccessful();
        Storage::disk('local')->assertExists('backups/'.now()->format('d-m-Y-H-i-s').'.sql.gz');
    }

    public function test_dbbackup_fails_when_local_dump_cannot_be_opened(): void
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('File permissions are not enforced for root.');
        }

        Storage::fake('local');
        config([
            'filesystems.backup.disk' => 'local',
            'filesystems.backup.path' => 'backups',
        ]);

        $backupDir = storage_path('app/backup');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $gzPath = $backupDir.'/'.now()->format('d-m-Y-H-i-s').'.sql.gz';

        Process::fake(function () use ($gzPath) {
            file_put_contents($gzPath, 'gzipped-sql-content');
            chmod($gzPath, 0000);

            return Process::result(output: 'ok', exitCode: 0);
        });

        $previousHandler = set_error_handler(fn (): bool => true);

        try {
            $this->artisan('app:dbbackup')
                ->expectsOutputToContain('Unable to open local backup file.')
                ->assertFailed();
        } finally {
            set_error_handler($previousHandler);
            chmod($gzPath, 0644);
            @unlink($gzPath);
        }
    }
}
