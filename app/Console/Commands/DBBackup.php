<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class DBBackup extends Command
{
    protected $signature = 'app:dbbackup';

    protected $description = 'Create database backup, upload to S3 and remove backups older than 24 hours';

    public function handle()
    {
        $localPath = null;

        try {
            $filename = now()->format('d-m-Y-H-i-s');
            $localDirectory = storage_path('app/backup');

            if (! is_dir($localDirectory)) {
                mkdir($localDirectory, 0755, true);
            }

            $sqlPath = $localDirectory.'/'.$filename.'.sql';
            $localPath = $localDirectory.'/'.$filename.'.sql.gz';

            $command = 'mysqldump'
                .' --user='.escapeshellarg(config('database.connections.mysql.username'))
                .' --password='.escapeshellarg(config('database.connections.mysql.password'))
                .' --host='.escapeshellarg(config('database.connections.mysql.host'))
                .' '.escapeshellarg(config('database.connections.mysql.database'))
                .' > '.escapeshellarg($sqlPath)
                .' && gzip '.escapeshellarg($sqlPath);

            $process = Process::run($command);

            if (! $process->successful()) {
                $this->error('Database backup failed.');
                $this->error($process->errorOutput());

                return self::FAILURE;
            }

            if (! file_exists($localPath) || filesize($localPath) === 0) {
                $this->error('Database backup file was not created or is empty.');

                return self::FAILURE;
            }

            $this->info('Database backup created: '.basename($localPath));

            $disk = config('filesystems.backup.disk');
            $backupPath = config('filesystems.backup.path');

            $s3Path = $backupPath.'/'.basename($localPath);

            $stream = fopen($localPath, 'r');

            if ($stream === false) {
                throw new \RuntimeException('Unable to open local backup file.');
            }

            try {
                $uploaded = Storage::disk($disk)->put($s3Path, $stream);
            } finally {
                fclose($stream);
            }

            if (! $uploaded) {
                throw new \RuntimeException('Failed to upload database backup.');
            }

            $this->info(
                'Database backup uploaded to '.$disk.': '.$s3Path
            );

            if (file_exists($localPath)) {
                unlink($localPath);
                $localPath = null;
            }

            $files = Storage::disk($disk)->files($backupPath);
            $cutoff = now()->subHours(24)->timestamp;

            foreach ($files as $file) {
                if (Storage::disk($disk)->lastModified($file) < $cutoff) {
                    Storage::disk($disk)->delete($file);

                    $this->info(
                        'Deleted old backup: '.basename($file)
                    );
                }
            }

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('Database backup failed.');
            $this->error($e->getMessage());

            if ($localPath && file_exists($localPath)) {
                $this->error(
                    'Local backup kept at: '.$localPath
                );
            }

            return self::FAILURE;
        }
    }
}