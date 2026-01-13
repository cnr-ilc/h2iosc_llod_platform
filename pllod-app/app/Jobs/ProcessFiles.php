<?php

namespace App\Jobs;

use App\Enums\ResourceStatusImport;
use App\Models\Resource;
use App\Models\ResourceFile;
use App\Services\DSpace\DSpaceClient;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ProcessFiles implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Resource $resource;

    protected string $dirName;

    protected array $resourceFiles;

    protected DSpaceClient $client;

    protected string $tempPath = '';

    protected array $processedFiles = [];

    /** @var array<string, bool> Flipped array for O(1) lookup */
    protected array $acceptedZipMimeTypeMap;

    /** @var array<string, bool> Flipped array for O(1) lookup */
    protected array $acceptedGzipMimeTypeMap;

    /** @var array<string, bool> Flipped array for O(1) lookup */
    protected array $acceptedMimeTypeMap;

    protected array $acceptedZipMimeType = [
        'application/zip',
        'application/x-compressed',
    ];

    protected array $acceptedGzipMimeType = [
        'application/gzip',
        'application/x-gzip',
    ];

    protected array $acceptedMimeType = [
        'application/rdf+xml',
        'text/turtle',
        'application/n-triples',
        'application/n-quads',
        'application/trig',
        'application/ld+json',
        'text/n3',
    ];

    protected array $excludedDirectories = [
        '__MACOSX',
        '.DS_Store',
        'Thumbs.db',
        '.git',
        'node_modules',
        '.svn',
    ];

    public function __construct(Resource $resource, array $resourceFiles)
    {
        $this->resource = $resource;
        $this->dirName = $this->resource->id.date('Y-m-d_H-i-s');
        $this->resourceFiles = $resourceFiles;
        $this->processedFiles = [];

        // Pre-compute flipped arrays for O(1) lookups
        $this->acceptedZipMimeTypeMap = array_flip($this->acceptedZipMimeType);
        $this->acceptedGzipMimeTypeMap = array_flip($this->acceptedGzipMimeType);
        $this->acceptedMimeTypeMap = array_flip($this->acceptedMimeType);
    }

    /**
     * Execute the job and return processed files
     */
    public function handle(): void
    {
        $resourceId = $this->resource->id;
        $fileCount = count($this->resourceFiles);

        Log::info("📁 [ProcessFiles] Starting job for resource #{$resourceId} with {$fileCount} file(s)");

        $this->client = app(DSpaceClient::class);

        Log::info("🔄 [ProcessFiles] Setting status to PENDING for resource #{$resourceId}");
        $this->resource->update(['status_import' => ResourceStatusImport::PENDING]);

        $this->createBaseDirectory();

        try {
            $this->processAllFiles();

            if ($this->processedFiles) {
                $this->saveProcessedFiles();
            } else {
                Log::warning("⚠️ [ProcessFiles] No valid files processed for resource #{$resourceId}");
                $this->cleanBaseDirectory();
            }

            Log::info("✅ [ProcessFiles] Setting status to IMPORTED for resource #{$resourceId}");
            $this->resource->update(['status_import' => ResourceStatusImport::IMPORTED]);

            Log::info("🎉 [ProcessFiles] Job completed successfully for resource #{$resourceId}");

        } catch (Exception $e) {
            Log::error("❌ [ProcessFiles] Job failed for resource #{$resourceId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->resource->update(['status_import' => ResourceStatusImport::FAILED]);
        }
    }

    /**
     * Process all resource files
     */
    private function processAllFiles(): void
    {
        foreach ($this->resourceFiles as $index => $resourceFile) {
            $fileNumber = $index + 1;
            $totalFiles = count($this->resourceFiles);
            Log::info("📄 [ProcessFiles] Processing file {$fileNumber}/{$totalFiles}");

            $this->processFile($resourceFile);
        }
    }

    private function processFile(array $resourceFile): void
    {
        $fileName = $resourceFile['name'] ?? 'unknown';
        Log::debug("🔍 [ProcessFiles] Fetching file: {$fileName}");

        try {
            $file = $this->getFile($resourceFile);
        } catch (GuzzleException $e) {
            Log::error("❌ [ProcessFiles] Failed to fetch file: {$fileName}", [
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $mimeType = $file['mime_type'];
        Log::debug("📋 [ProcessFiles] File MIME type: {$mimeType}");

        if ($this->isZipMimeType($mimeType)) {
            Log::info("📦 [ProcessFiles] Processing ZIP archive: {$file['name']}");
            $this->tempPath = 'temp/'.uniqid('zip_', true).'.zip';
            $this->handleZipFile($file, $resourceFile);

            return;
        }

        if ($this->isGzipMimeType($mimeType)) {
            Log::info("📦 [ProcessFiles] Processing GZIP file: {$file['name']}");
            $this->handleGzipFile($file, $resourceFile);

            return;
        }

        if ($this->isAcceptedMimeType($mimeType)) {
            Log::info("📝 [ProcessFiles] Processing single file: {$file['name']}");
            $this->saveFile($file['name'], $file['content']);
            $this->handleSingleFile($file['name'], $resourceFile, $mimeType);

            return;
        }

        Log::warning("⏭️ [ProcessFiles] Skipping file with unsupported MIME type: {$mimeType}");
    }

    /**
     * Normalize MIME type by removing charset and other parameters
     * e.g., 'application/gzip;charset=UTF-8' becomes 'application/gzip'
     */
    private function normalizeMimeType(string $mimeType): string
    {
        $semicolonPos = strpos($mimeType, ';');
        if ($semicolonPos !== false) {
            return trim(substr($mimeType, 0, $semicolonPos));
        }

        return trim($mimeType);
    }

    /**
     * Check if MIME type is a ZIP format (O(1) lookup)
     */
    private function isZipMimeType(string $mimeType): bool
    {
        $normalized = $this->normalizeMimeType($mimeType);

        return isset($this->acceptedZipMimeTypeMap[$normalized]);
    }

    /**
     * Check if MIME type is a GZIP format (O(1) lookup)
     */
    private function isGzipMimeType(string $mimeType): bool
    {
        $normalized = $this->normalizeMimeType($mimeType);

        return isset($this->acceptedGzipMimeTypeMap[$normalized]);
    }

    /**
     * Check if MIME type is accepted (O(1) lookup)
     */
    private function isAcceptedMimeType(string $mimeType): bool
    {
        $normalized = $this->normalizeMimeType($mimeType);

        return isset($this->acceptedMimeTypeMap[$normalized]);
    }

    /**
     * Save all processed files using batch insert for better performance
     */
    private function saveProcessedFiles(): void
    {
        $count = count($this->processedFiles);
        Log::info("💾 [ProcessFiles] Saving {$count} processed file(s) to database");

        $records = array_map(fn (array $item) => [
            'resource_id' => $this->resource->id,
            'path' => $item['path'],
            'name' => $item['filename'],
            'size' => $item['size'],
            'mime_type' => $item['mime_type'],
            'description' => $item['description'],
            'hash_md5' => $item['checksum'],
            'created_at' => now(),
            'updated_at' => now(),
        ], $this->processedFiles);

        // Batch insert for better performance
        ResourceFile::insert($records);

        Log::info("✅ [ProcessFiles] Successfully saved {$count} file record(s)");
    }

    private function cleanBaseDirectory(): void
    {
        Log::debug("🧹 [ProcessFiles] Checking if base directory needs cleanup: {$this->dirName}");

        if (count(Storage::allFiles($this->dirName)) === 0) {
            Log::info("🗑️ [ProcessFiles] Removing empty directory: {$this->dirName}");
            Storage::deleteDirectory($this->dirName);
        }
    }

    private function createBaseDirectory(): void
    {
        Log::debug("📂 [ProcessFiles] Creating base directory: {$this->dirName}");
        Storage::disk('local')->makeDirectory($this->dirName);
    }

    private function handleZipFile(array $file, array $bitstream): void
    {
        Log::debug("📦 [ProcessFiles] Saving temporary ZIP file: {$this->tempPath}");
        $this->saveTemporaryZip($file['content']);

        $zip = $this->openZipFile();
        if ($zip === null) {
            Log::error('❌ [ProcessFiles] Failed to open ZIP file');
            $this->cleanupTemporaryFile();

            return;
        }

        Log::info("📦 [ProcessFiles] ZIP contains {$zip->numFiles} entries");
        $this->extractZipContents($zip, $bitstream);
        $zip->close();

        $this->cleanupTemporaryFile();
    }

    /**
     * Handle GZIP compressed files (.gz)
     * GZIP files contain a single compressed file, not an archive
     */
    private function handleGzipFile(array $file, array $bitstream): void
    {
        $gzipFilename = $file['name'];
        Log::debug("📦 [ProcessFiles] Decompressing GZIP file: {$gzipFilename}");

        // Decompress the content
        $decompressedContent = @gzdecode($file['content']);

        if ($decompressedContent === false) {
            Log::error("❌ [ProcessFiles] Failed to decompress GZIP file: {$gzipFilename}");

            return;
        }

        // Remove .gz extension to get the original filename
        $originalFilename = preg_replace('/\.gz$/i', '', $gzipFilename);
        $finalFilename = $this->getUniqueFilename($originalFilename);

        Log::debug("📦 [ProcessFiles] Decompressed to: {$finalFilename} (".strlen($decompressedContent).' bytes)');

        // Save the decompressed file
        $this->saveFile($finalFilename, $decompressedContent);

        // Get MIME type of the decompressed file
        $filePath = $this->dirName.'/'.$finalFilename;
        $mimeType = Storage::mimeType($filePath);
        $size = Storage::size($filePath);

        Log::debug("📋 [ProcessFiles] Decompressed file MIME type: {$mimeType}");

        // Check if the decompressed file has an accepted MIME type
        if (! $this->isAcceptedMimeType($mimeType)) {
            Log::warning("⏭️ [ProcessFiles] Deleting decompressed file with unsupported MIME type: {$mimeType}");
            Storage::delete($filePath);

            return;
        }

        Log::info("✅ [ProcessFiles] Successfully processed GZIP file: {$finalFilename}");

        $bitstream['sizeBytes'] = $size;
        $this->handleSingleFile($finalFilename, $bitstream, $mimeType);
    }

    private function saveTemporaryZip(string $content): void
    {
        Storage::put($this->tempPath, $content);
    }

    private function openZipFile(): ?ZipArchive
    {
        if (! Storage::exists($this->tempPath)) {
            Log::error("❌ [ProcessFiles] Temporary ZIP file not found: {$this->tempPath}");

            return null;
        }

        $zip = new ZipArchive;

        try {
            $result = $zip->open(Storage::disk('local')->path($this->tempPath));
            if ($result === true) {
                Log::debug('✅ [ProcessFiles] Successfully opened ZIP file');

                return $zip;
            }

            Log::error("❌ [ProcessFiles] Failed to open ZIP, error code: {$result}");
        } catch (Exception $e) {
            Log::error('❌ [ProcessFiles] Exception opening ZIP file', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function isExcludedPath(string $filepath): bool
    {
        foreach ($this->excludedDirectories as $excludedDir) {
            if (str_contains($filepath, $excludedDir.'/') || $filepath === $excludedDir) {
                return true;
            }
        }

        return false;
    }

    private function extractZipContents(ZipArchive $zip, array $bitstream): void
    {
        $extractedCount = 0;
        $skippedCount = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            if ($this->isDirectory($filename)) {
                continue;
            }

            if ($this->isExcludedPath($filename)) {
                Log::debug("⏭️ [ProcessFiles] Skipping excluded path: {$filename}");
                $skippedCount++;

                continue;
            }

            $extractedFile = $this->extractSingleZipEntry($zip, $i, $filename, $bitstream);
            if ($extractedFile) {
                $extractedCount++;
            } else {
                $skippedCount++;
            }
        }

        Log::info("📊 [ProcessFiles] ZIP extraction complete: {$extractedCount} extracted, {$skippedCount} skipped");
    }

    /**
     * Extract a single entry from the ZIP file
     */
    private function extractSingleZipEntry(
        ZipArchive $zip,
        int $index,
        string $filename,
        array $bitstream
    ): bool {
        $baseFilename = basename($filename);
        $finalFilename = $this->getUniqueFilename($baseFilename);

        $fileContent = $zip->getFromIndex($index);
        if ($fileContent === false) {
            Log::warning("⚠️ [ProcessFiles] Failed to read content for: {$filename}");

            return false;
        }

        $this->saveFile($finalFilename, $fileContent);

        $filePath = $this->dirName.'/'.$finalFilename;
        $mimeType = Storage::mimeType($filePath);
        $size = Storage::size($filePath);

        if (! $this->isAcceptedMimeType($mimeType)) {
            Log::debug("⏭️ [ProcessFiles] Deleting file with unsupported MIME type: {$mimeType}");
            Storage::delete($filePath);

            return false;
        }

        Log::debug("✅ [ProcessFiles] Extracted: {$finalFilename} ({$mimeType}, {$size} bytes)");

        $bitstream['sizeBytes'] = $size;
        $this->handleSingleFile($finalFilename, $bitstream, $mimeType);

        return true;
    }

    private function getUniqueFilename(string $filename): string
    {
        $basePath = $this->dirName.'/';

        if (! Storage::disk('local')->exists($basePath.$filename)) {
            return $filename;
        }

        $originalName = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $counter = 1;

        do {
            $newFilename = "{$originalName}_{$counter}.{$extension}";
            $counter++;
        } while (Storage::disk('local')->exists($basePath.$newFilename));

        Log::debug("📝 [ProcessFiles] Renamed duplicate file: {$filename} -> {$newFilename}");

        return $newFilename;
    }

    private function isDirectory(string $filename): bool
    {
        return str_ends_with($filename, '/');
    }

    private function saveFile(string $filename, string $content): void
    {
        $path = $this->dirName.'/'.$filename;
        Storage::disk('local')->put($path, $content);
        Log::debug("💾 [ProcessFiles] Saved file: {$path}");
    }

    private function handleSingleFile(string $filename, array $bitstream, string $mimeType): void
    {
        $this->processedFiles[] = [
            'path' => $this->dirName.'/'.$filename,
            'filename' => $filename,
            'size' => $bitstream['sizeBytes'] ?? 0,
            'mime_type' => $mimeType,
            'description' => data_get($bitstream, 'metadata.dc.description.0.value'),
            'checksum' => data_get($bitstream, 'checkSum.value'),
            'checksum_algorithm' => data_get($bitstream, 'checkSum.checkSumAlgorithm'),
        ];
    }

    private function cleanupTemporaryFile(): void
    {
        if (empty($this->tempPath)) {
            return;
        }

        if (Storage::exists($this->tempPath)) {
            Log::debug("🧹 [ProcessFiles] Cleaning up temporary file: {$this->tempPath}");
            Storage::delete($this->tempPath);
        }
    }

    /**
     * @throws GuzzleException
     */
    protected function getFile(array $bitstream): array
    {
        $contentUrl = data_get($bitstream, '_links.content.href');
        $filename = $bitstream['name'] ?? basename($contentUrl);

        Log::debug("🌐 [ProcessFiles] Downloading file from: {$contentUrl}");

        $response = $this->client->getAbsolute($contentUrl);
        $mimeType = $response->header('Content-Type');

        Log::debug("✅ [ProcessFiles] Downloaded: {$filename} ({$mimeType})");

        return [
            'content' => $response->getBody()->getContents(),
            'mime_type' => $mimeType,
            'name' => $filename,
        ];
    }
}
