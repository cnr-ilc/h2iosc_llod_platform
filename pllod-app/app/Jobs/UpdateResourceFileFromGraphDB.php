<?php

namespace App\Jobs;

use App\Models\ResourceFile;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateResourceFileFromGraphDB implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'graphdb-update',
            'resource-file id: '.$this->resourceFile->id,
            'resource id: '.$this->resourceFile->resource_id,
            'file:'.$this->resourceFile->name,
        ];
    }

    protected ResourceFile $resourceFile;

    /**
     * Create a new job instance.
     */
    public function __construct(ResourceFile $resourceFile)
    {
        $this->resourceFile = $resourceFile;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->resourceFile->is_synchronising = true;
        $this->resourceFile->save();

        if (! $this->resourceFile->graphdb_repository_name) {
            Log::error('GraphDB repository name not found for ResourceFile ID: '.$this->resourceFile->id);
            throw new Exception('GraphDB repository name is required');
        }

        $graphdbUrl = config('graphdb.host.host_url', 'http://localhost:7200');
        $repositoryName = $this->resourceFile->graphdb_repository_name;
        $statementsUrl = "{$graphdbUrl}/repositories/{$repositoryName}/statements";

        try {
            $response = Http::withHeaders([
                'Accept' => $this->resourceFile->mime_type,
            ])->timeout(config('app.timeout', 30))->get($statementsUrl);

            if (! $response->successful()) {
                $errorMessage = 'GraphDB API returned HTTP error '.$response->status().' for repository: '.$repositoryName;
                Log::error($errorMessage, [
                    'resource_file_id' => $this->resourceFile->id,
                    'repository_name' => $repositoryName,
                    'response_body' => $response->body(),
                ]);

                throw new Exception($errorMessage);
            }

            $content = $response->body();
            $filePath = $this->resourceFile->getPath();

            if (file_put_contents($filePath, $content) === false) {
                $errorMessage = 'Failed to write content to file: '.$filePath;
                Log::error($errorMessage, [
                    'resource_file_id' => $this->resourceFile->id,
                    'file_path' => $filePath,
                ]);

                throw new Exception($errorMessage);
            }

            $this->resourceFile->size = strlen($content);
            $this->resourceFile->is_synchronising = false;
            $this->resourceFile->save();

            Log::info('Successfully updated ResourceFile from GraphDB', [
                'resource_file_id' => $this->resourceFile->id,
                'repository_name' => $repositoryName,
                'file_size' => $this->resourceFile->size,
            ]);

        } catch (Exception $e) {
            $this->resourceFile->is_synchronising = false;
            $this->resourceFile->save();

            Log::error('Error updating ResourceFile from GraphDB: '.$e->getMessage(), [
                'resource_file_id' => $this->resourceFile->id,
                'repository_name' => $repositoryName,
            ]);
            throw $e;
        }
    }
}
