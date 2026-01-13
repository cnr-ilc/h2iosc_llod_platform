<?php

namespace App\Console\Commands;

use App\Jobs\CreateProjectSemanticTurkey;
use App\Jobs\DeleteProjectSemanticTurkey;
use App\Jobs\UpdateResourceFileFromGraphDB;
use App\Models\ResourceFile;
use App\Models\User;
use Illuminate\Console\Command;

class TestSemanticTurkeyJobs extends Command
{
    protected $signature = 'test:semantic-turkey-jobs {--file_id= : ID of the ResourceFile to use} {--user_id= : ID of the User to use} {--job_type=create : The job to test (create/delete/update)}';

    protected $description = 'Test the CreateProjectSemanticTurkey, DeleteProjectSemanticTurkey and UpdateResourceFileFromGraphDB jobs';

    public function handle()
    {
        $fileId = $this->option('file_id');
        $userId = $this->option('user_id');
        $jobType = $this->option('job_type') ?: 'create';

        if (! in_array($jobType, ['create', 'delete', 'update'])) {
            $this->error('Invalid job type. Use create, delete, or update.');
            return 1;
        }

        if ($fileId) {
            $resourceFile = ResourceFile::find($fileId);
            if (! $resourceFile) {
                $this->error("ResourceFile with ID {$fileId} not found.");
                return 1;
            }
        } else {
            $resourceFile = ResourceFile::where('can_be_opened_in_vocbench', true)->first();
            if (! $resourceFile) {
                $this->error('No ResourceFile that can be opened in VocBench found.');
                return 1;
            }
        }

        // User is only needed for create job
        $user = null;
        if ($jobType === 'create') {
            if ($userId) {
                $user = User::find($userId);
                if (! $user) {
                    $this->error("User with ID {$userId} not found.");
                    return 1;
                }
            } else {
                $user = User::first();
                if (! $user) {
                    $this->error('No User found in the database.');
                    return 1;
                }
            }
            $this->info("Using User #{$user->id}: {$user->name} ({$user->email})");
        }

        $this->info("Using ResourceFile #{$resourceFile->id}: {$resourceFile->name}");

        try {
            if ($jobType === 'create') {
                $this->info('Testing CreateProjectSemanticTurkey job...');
                
                // Check if file can be opened in vocbench
                if (!$resourceFile->can_be_opened_in_vocbench) {
                    $this->warn('ResourceFile cannot be opened in VocBench - job will return early');
                    return 0;
                }
                
                $this->info('File can be opened in VocBench, proceeding...');
                $this->info('Setting is_synchronising to true...');
                
                $job = new CreateProjectSemanticTurkey($resourceFile, $user);
                $job->handle();
                
                // Reload the resource file to see changes
                $resourceFile->refresh();
                $this->info('Job completed. Final state:');
                $this->info("- st_project_name: " . ($resourceFile->st_project_name ?? 'null'));
                $this->info("- graphdb_repository_name: " . ($resourceFile->graphdb_repository_name ?? 'null'));
                $this->info("- is_synchronising: " . ($resourceFile->is_synchronising ? 'true' : 'false'));
                
            } elseif ($jobType === 'delete') {
                $this->info('Testing DeleteProjectSemanticTurkey job...');
                $job = new DeleteProjectSemanticTurkey($resourceFile);
                $job->handle();
                $this->info('Delete project job completed successfully.');
            } elseif ($jobType === 'update') {
                $this->info('Testing UpdateResourceFileFromGraphDB job...');
                
                if (!$resourceFile->graphdb_repository_name) {
                    $this->warn('ResourceFile has no GraphDB repository name - job will fail');
                    $this->info("- graphdb_repository_name: " . ($resourceFile->graphdb_repository_name ?? 'null'));
                } else {
                    $this->info("- graphdb_repository_name: " . $resourceFile->graphdb_repository_name);
                }
                
                $job = new UpdateResourceFileFromGraphDB($resourceFile);
                $job->handle();
                
                // Reload the resource file to see changes
                $resourceFile->refresh();
                $this->info('Update job completed. Final state:');
                $this->info("- file_size: " . $resourceFile->size);
                $this->info("- is_synchronising: " . ($resourceFile->is_synchronising ? 'true' : 'false'));
            }
        } catch (\Exception $e) {
            $this->error('Job failed with error: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}