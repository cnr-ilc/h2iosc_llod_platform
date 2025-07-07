<?php

namespace App\Jobs;

use App\Enums\GeneralStatus;
use App\Models\ResourceFile;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class qualityCheckResourceFile implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'quality-check',
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
        $pipelineUrl = config('py-quality-check-service.url').config('py-quality-check-service.endpoints.quality');
        $filePath = $this->resourceFile->getPath();

        try {
            $response = Http::attach(
                'file',
                file_get_contents($filePath),
                basename($filePath)
            )->timeout(config('app.timeout'))->post($pipelineUrl);

            $responseData = $response->json();

            Log::debug('Quality check response', $responseData);

            if (! $response->successful()) {
                $this->resourceFile->quality_assessment_status = GeneralStatus::FAILED;
                $this->resourceFile->quality_assessment_timestamp = now();
                $this->resourceFile->save();

                $errorMessage = 'Quality check API returned HTTP error '.$response->status().' for file: '.basename($filePath);
                Log::error($errorMessage, $responseData ?? []);

                throw new \Exception($errorMessage);
            }

            $this->resourceFile->quality_assessment_status = GeneralStatus::SUCCESS;

            if (isset($responseData['score']) && ! empty($responseData['score'])) {
                $this->resourceFile->quality_score = $responseData['score'];
            }

            if (isset($responseData['report']['suggestions']) && ! empty($responseData['report']['suggestions'])) {
                $this->resourceFile->quality_warnings = $responseData['report']['suggestions'];
                unset($responseData['report']['suggestions']);
            }

            if (isset($responseData['report']) && ! empty($responseData['report'])) {
                $this->resourceFile->quality_result = $responseData['report'];
            }

            $this->resourceFile->quality_assessment_timestamp = now();
            $this->resourceFile->save();
        } catch (\Exception $e) {
            $this->resourceFile->quality_assessment_status = GeneralStatus::FAILED;
            $this->resourceFile->quality_assessment_timestamp = now();
            $this->resourceFile->save();

            Log::error('Error during quality check for file '.basename($filePath).': '.$e->getMessage());
            throw $e;
        }
    }
}
