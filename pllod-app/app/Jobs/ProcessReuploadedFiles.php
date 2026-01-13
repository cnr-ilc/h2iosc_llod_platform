<?php

namespace App\Jobs;

use App\Models\Resource;
use App\Models\ResourceFile;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessReuploadedFiles implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Resource $resource;

    protected string $dirName;

    protected string $newResourceFilePath; // Path to the temporary file uploaded by the user

    protected ResourceFile $currentFile;   // The ResourceFile record to be updated

    protected ?array $processedFile = null; // Data for updating the ResourceFile record

    protected array $acceptedMimeType = [
        'application/rdf+xml',    // Standard RDF/XML
        'text/xml',               // XML detected as text/xml (common for RDF files)
        'application/xml',        // Alternative XML MIME type
        'text/turtle',            // Turtle format (TTL)
        'application/n-triples',  // N-Triples format
        'application/n-quads',    // N-Quads format
        'application/trig',       // TriG format
        'application/ld+json',    // JSON-LD
        'text/n3',                // Notation3 (N3)
        'text/plain',             // Some RDF formats detected as plain text
    ];

    public function __construct(Resource $resource, string $newResourceFilePath, ResourceFile $currentFile)
    {
        $this->resource = $resource;
        $this->newResourceFilePath = $newResourceFilePath;
        $this->currentFile = $currentFile;
        // Create a unique directory name for this specific reupload operation
        $this->dirName = $this->resource->id.'_REUPLOAD_'.date('Y-m-d_H-i-s').'_'.$this->currentFile->id;
    }

    /**
     * Execute the job to process the reuploaded file.
     */
    public function handle(): void
    {
        $this->createBaseDirectory();

        try {
            // Process the temporary uploaded file: validate, copy to new location, prepare metadata.
            // Throws exception on failure (e.g., file not found, invalid MIME type).
            $this->processFile($this->newResourceFilePath);

            // If processFile succeeded, $this->processedFile will be populated.
            if ($this->processedFile) {
                // Delete the old physical file and update the ResourceFile record with new file details.
                $this->saveProcessedFile();
            } else {
                // This state should not be reached if processFile is robust.
                // It indicates that processFile completed without error but failed to populate $this->processedFile.
                throw new Exception('File processing logic error: processedFile data was not set.');
            }
        } catch (Exception $e) {
            Log::error("Failed to process reuploaded file for resource {$this->resource->id}, file {$this->currentFile->id}: ".$e->getMessage(), [
                'exception' => $e,
                'resource_id' => $this->resource->id,
                'current_file_id' => $this->currentFile->id,
                'new_resource_file_path' => $this->newResourceFilePath,
                'target_dir_name' => $this->dirName,
            ]);
            // Re-throw the exception to mark the job (and batch) as failed.
            throw $e;
        } finally {
            // Always attempt to clean up the temporary uploaded file from its initial 'temp/' location.
            $this->cleanupTemporaryUploadedFile($this->newResourceFilePath);

            // Attempt to clean up the base directory created for this operation.
            // This will only delete $this->dirName if it's empty.
            // If processing failed after copying the file to $this->dirName, the directory (and file) will remain for inspection.
            // If processing succeeded, $this->dirName contains the new live file and should not be deleted.
            $this->cleanBaseDirectory();
        }
    }

    /**
     * Processes the temporary uploaded file: validates MIME type, copies to permanent storage, and prepares metadata.
     */
    private function processFile(string $temporaryFilePath): void
    {
        if (! Storage::disk('local')->exists($temporaryFilePath)) {
            throw new Exception("Temporary file for reupload does not exist at path: {$temporaryFilePath}");
        }

        $fileContents = $this->getFileContents($temporaryFilePath);
        $mimeType = Storage::disk('local')->mimeType($temporaryFilePath);

        if (in_array($mimeType, $this->acceptedMimeType)) {
            // Save the new file content into $this->dirName, using the logical name of the file being replaced.
            $this->saveFileToOperationDirectory($this->currentFile->name, $fileContents);

            // Populate $this->processedFile with metadata of the newly saved file.
            $this->prepareProcessedFileData($this->currentFile->name, $mimeType);
        } else {
            throw new Exception("Invalid MIME type '{$mimeType}' for reuploaded file. Accepted types: ".implode(', ', $this->acceptedMimeType));
        }
    }

    /**
     * Deletes the old physical file and updates the ResourceFile database record.
     */
    private function saveProcessedFile(): void
    {
        // Delete the old physical folder.
        if ($this->currentFile->path && Storage::disk('local')->exists($this->currentFile->path)) {
            Storage::disk('local')->deleteDirectory(dirname($this->currentFile->path));
        }
        // Update the ResourceFile record with the new file's details.
        $this->currentFile->update($this->processedFile);
    }

    /**
     * Deletes the base directory for this operation if it's empty.
     */
    private function cleanBaseDirectory(): void
    {
        if (Storage::disk('local')->exists($this->dirName) && count(Storage::disk('local')->allFiles($this->dirName)) === 0) {
            Storage::disk('local')->deleteDirectory($this->dirName);
            Log::info("Cleaned up empty base directory: {$this->dirName}");
        }
    }

    /**
     * Creates the base directory for storing the reuploaded file.
     */
    private function createBaseDirectory(): void
    {
        Storage::disk('local')->makeDirectory($this->dirName);
    }

    /**
     * Saves the file content to the operation-specific directory.
     */
    private function saveFileToOperationDirectory(string $filename, string $content): void
    {
        Storage::disk('local')->put($this->dirName.'/'.$filename, $content);
    }

    /**
     * Prepares the array of data for updating the ResourceFile model.
     */
    private function prepareProcessedFileData(string $filename, string $mimeType): void
    {
        $newPath = $this->dirName.'/'.$filename;
        $size = Storage::disk('local')->size($newPath);
        $checksum = md5_file(Storage::disk('local')->path($newPath));

        $this->processedFile = [
            'name' => $this->currentFile->name, // Keep original logical name
            'description' => $this->currentFile->description, // Keep original description
            'path' => $newPath,
            'filename' => $filename, // The actual filename in storage
            'size' => $size,
            'mime_type' => $mimeType,
            'checksum' => $checksum,
            'checksum_algorithm' => 'md5',
        ];
    }

    /**
     * Deletes the temporary file that was initially uploaded by the user.
     */
    private function cleanupTemporaryUploadedFile(string $filePathToDelete): void
    {
        if ($filePathToDelete && Storage::disk('local')->exists($filePathToDelete)) {
            Storage::disk('local')->delete($filePathToDelete);
            Log::info("Cleaned up temporary uploaded file: {$filePathToDelete}");
        }
    }

    /**
     * Gets the content of a file from local storage.
     */
    protected function getFileContents(string $path): string
    {
        return Storage::disk('local')->get($path);
    }
}
