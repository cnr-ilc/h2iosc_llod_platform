<?php

namespace App\Jobs;

use App\Facades\SemanticTurkey;
use App\Models\ResourceFile;
use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CreateProjectSemanticTurkey implements ShouldQueue
{
    use Batchable, Queueable;

    public User $user;

    public ResourceFile $resourceFile;

    /**
     * Create a new job instance.
     */
    public function __construct(ResourceFile $resourceFile, User $user)
    {
        $this->resourceFile = $resourceFile;
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->resourceFile->can_be_opened_in_vocbench) {
            return;
        }

        $this->resourceFile->is_synchronising = true;
        $this->resourceFile->save();

        try {
            $adminLogin = SemanticTurkey::login(config('semanticTurkey.admin_email'), config('semanticTurkey.admin_password'));
            $adminCookie = $adminLogin['cookie'];

            $response = SemanticTurkey::createRemoteAccessProject(
                cookies: $adminCookie,
                resource_file: $this->resourceFile
            );

            SemanticTurkey::assignProjectRolesToUser($adminCookie, $response['projectName'], $this->user->email);

            $this->resourceFile->update([
                'st_project_name' => $response['projectName'],
                'graphdb_repository_name' => $response['graphDBRepositoryName'],
                'is_synchronising' => false
            ]);
        } catch (\Exception $e) {
            $this->resourceFile->is_synchronising = false;
            $this->resourceFile->save();
            
            Log::warning('Failed to connect new project with graphdb, retrying in 10 seconds', ['error' => $e->getMessage()]);
            $this->release(10);
        }
    }
}
