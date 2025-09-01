<?php

namespace App\Jobs;

use App\Enums\GeneralStatus;
use App\Enums\RdfFormat;
use App\Models\ResourceFile;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class healthCheckResourceFile implements ShouldQueue
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
            'health-check',
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
        $pipelineUrl = config('py-quality-check-service.url').config('py-quality-check-service.endpoints.health');
        $filePath = $this->resourceFile->getPath();

        $this->resourceFile->validation_status = GeneralStatus::RUNNING;
        $this->resourceFile->save();

        try {
            $format = RdfFormat::toValidationAPIFormat($this->resourceFile->mime_type)->value;
            
            $response = Http::attach(
                'file',
                file_get_contents($filePath),
                basename($filePath)
            )->attach(
                'format',
                $format
            )->timeout(config('app.timeout'))->post($pipelineUrl);

            $responseData = $response->json();

            if (! $response->successful()) {
                $this->resourceFile->validation_status = GeneralStatus::FAILED;
                $this->resourceFile->validation_timestamp = now();
                $this->resourceFile->save();

                $errorMessage = 'Health check API returned HTTP error '.$response->status().' for file: '.basename($filePath);
                Log::error($errorMessage, $responseData ?? []);

                throw new \Exception($errorMessage);
            }

            if (isset($responseData['is_valid']) && $responseData['is_valid'] === true) {
                $this->resourceFile->validation_status = GeneralStatus::SUCCESS;
            } else {
                $this->resourceFile->validation_status = GeneralStatus::FAILED;
            }

            if (isset($responseData['issues'])) {
                $this->resourceFile->health_issues = $responseData['issues'];
            }

            if (isset($responseData['warnings'])) {
                $this->resourceFile->health_warnings = $responseData['warnings'];
            }

            if (empty($responseData['issues']) && empty($responseData['warnings'])) {
                $this->resourceFile->can_be_opened_in_vocbench = true;
            }

            if (isset($responseData['main_uri']) && !empty($responseData['main_uri'])) {
                $this->resourceFile->graphdb_uri = $responseData['main_uri'];
            }

            $this->resourceFile->validation_timestamp = now();
            $this->resourceFile->save();
        } catch (\Exception $e) {
            $this->resourceFile->validation_status = GeneralStatus::FAILED;
            $this->resourceFile->validation_timestamp = now();
            $this->resourceFile->save();

            Log::error('Error during health check for file '.basename($filePath).': '.$e->getMessage());
            throw $e;
        }
    }
}
