<?php

namespace Tests\Feature\Backup;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class DbBackupEdgeCasesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'filesystems.backup.disk' => 'local',
            'filesystems.backup.path' => 'backups',
            'database.connections.mysql.username' => 'test',
            'database.connections.mysql.password' => 'secret',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.database' => 'partload_testing',
        ]);

        $this->cleanBackupDirectory();
    }

    protected function tearDown(): void
    {
        $this->cleanBackupDirectory();

        parent::tearDown();
    }

    private function cleanBackupDirectory(): void
    {
        foreach (glob(storage_path('app/backup').'/*') ?: [] as $file) {
            @unlink($file);
        }
    }

    /**
     * Fake mysqldump so it writes a gzip file of the given contents next to the requested sql path.
     */
    private function fakeDump(string $contents): void
    {
        Process::fake(function (PendingProcess $process) use ($contents) {
            preg_match("/> '([^']+\\.sql)'/", (string) $process->command, $matches);
            file_put_contents($matches[1].'.gz', $contents);

            return Process::result(output: 'ok', exitCode: 0);
        });
    }

    public function test_empty_dump_file_is_reported_as_failure(): void
    {
        $this->fakeDump('');

        $this->artisan('app:dbbackup')
            ->expectsOutputToContain('Database backup file was not created or is empty.')
            ->assertFailed();
    }

    public function test_backup_directory_is_created_and_old_remote_backups_are_pruned(): void
    {
        $directory = storage_path('app/backup');
        @rmdir($directory);

        Storage::fake('local');
        Storage::disk('local')->put('backups/old.sql.gz', 'old');
        Storage::disk('local')->put('backups/recent.sql.gz', 'recent');
        touch(Storage::disk('local')->path('backups/old.sql.gz'), now()->subDays(2)->timestamp);

        $this->fakeDump('gzipped');

        $this->artisan('app:dbbackup')
            ->expectsOutputToContain('Deleted old backup: old.sql.gz')
            ->assertSuccessful();

        Storage::disk('local')->assertMissing('backups/old.sql.gz');
        Storage::disk('local')->assertExists('backups/recent.sql.gz');
        $this->assertSame([], glob($directory.'/*.sql.gz') ?: []);
    }

    public function test_failed_upload_keeps_local_backup_and_fails(): void
    {
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);

        $this->fakeDump('gzipped');

        $this->artisan('app:dbbackup')
            ->expectsOutputToContain('Failed to upload database backup.')
            ->expectsOutputToContain('Local backup kept at:')
            ->assertFailed();
    }
}
