<?php

namespace App\Console\Commands;

use App\Jobs\healthCheckResourceFile;
use App\Jobs\qualityCheckResourceFile;
use App\Models\ResourceFile;
use Illuminate\Console\Command;

class TestResourceFileJobs extends Command
{
    protected $signature = 'test:resource-file-jobs {--file_id= : ID of the ResourceFile to use} {--job_type=both : The job to test (health/quality/both)}';

    protected $description = 'Test the healthCheckResourceFile and qualityCheckResourceFile jobs with a ResourceFile';

    public function handle()
    {
        $fileId = $this->option('file_id');
        $jobType = $this->option('job_type') ?: 'both';

        if (! in_array($jobType, ['health', 'quality', 'both'])) {
            $this->error('Invalid job type. Use health, quality, or both.');

            return 1;
        }

        if ($fileId) {
            $resourceFile = ResourceFile::find($fileId);

            if (! $resourceFile) {
                $this->error("ResourceFile with ID {$fileId} not found.");

                return 1;
            }
        } else {
            // Get the first ResourceFile
            $resourceFile = ResourceFile::first();

            if (! $resourceFile) {
                $this->error('No ResourceFile found in the database.');

                return 1;
            }
        }

        $this->info("Using ResourceFile #{$resourceFile->id}: {$resourceFile->name} (path: {$resourceFile->path})");

        if ($jobType === 'health' || $jobType === 'both') {
            $this->info('Dispatching healthCheckResourceFile job...');
            dispatch(new healthCheckResourceFile($resourceFile));
            $this->info('Health check job dispatched.');
        }

        if ($jobType === 'quality' || $jobType === 'both') {
            $this->info('Dispatching qualityCheckResourceFile job...');
            dispatch(new qualityCheckResourceFile($resourceFile));
            $this->info('Quality check job dispatched.');
        }

        $this->info('Jobs have been dispatched. Check the queue worker output or database for results.');

        return 0;
    }
}
