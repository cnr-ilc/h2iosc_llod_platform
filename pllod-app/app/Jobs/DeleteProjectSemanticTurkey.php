<?php

namespace App\Jobs;

use App\Facades\SemanticTurkey;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DeleteProjectSemanticTurkey implements ShouldQueue
{
    use Queueable;

    protected array $filesData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $filesData)
    {
        $this->filesData = $filesData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $adminLogin = SemanticTurkey::login(config('semanticTurkey.admin_email'), config('semanticTurkey.admin_password'));
            $adminCookie = $adminLogin['cookie'];

            foreach ($this->filesData as $fileData) {
                SemanticTurkey::deleteRemoteAccessProject(
                    cookies: $adminCookie,
                    projectName: $fileData['st_project_name'],
                    repositoryGraphDBId: $fileData['graphdb_repository_name']
                );
            }
        } catch (\Exception $e) {
            Log::warning('Failed to DELETE project', [
                'error' => $e,
                'projectName' => $fileData['st_project_name'],
                'repositoryGraphDBId' => $fileData['graphdb_repository_name'],
            ]);
            $this->release(10);
        }
    }
}
