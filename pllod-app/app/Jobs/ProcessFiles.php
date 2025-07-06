<?php

namespace App\Jobs;

use App\Enums\ResourceStatusImport;
use App\Models\Resource;
use App\Models\ResourceFile;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ProcessFiles implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Resource $resource;

    protected string $dirName;

    protected array $resourceFiles;

    protected Client $client;

    protected string $tempPath;

    protected array $processedFiles = [];

    protected array $acceptedCompressedMimeType = [
        'application/zip',
    ];

    protected array $acceptedMimeType = [
        'application/rdf+xml',    // Standard RDF/XML
        'text/turtle',            // Turtle format (TTL)
        'application/n-triples',  // N-Triples format
        'application/n-quads',    // N-Quads format
        'application/trig',       // TriG format
        'application/ld+json',    // JSON-LD
        'text/n3',           // Notation3 (N3)
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
    }

    /**
     * Execute the job and return processed files
     */
    public function handle(): void
    {
        $this->client = new Client(['verify' => false]);

        $this->resource->update(['status_import' => ResourceStatusImport::PENDING]);

        $this->createBaseDirectory();

        try {
            foreach ($this->resourceFiles as $resourceFile) {
                $this->processFile($resourceFile);
            }

            if ($this->processedFiles) {
                $this->saveProcessedFile();
            } else {
                $this->cleanBaseDirectory();
            }

            $this->resource->update(['status_import' => ResourceStatusImport::IMPORTED]);

            return;

        } catch (Exception $e) {
            $this->resource->update(['status_import' => ResourceStatusImport::FAILED]);

            return;
        }
    }

    private function processFile($resourceFile): void
    {
        $file = $this->getFile($resourceFile);
        if (in_array($file['mime_type'], $this->acceptedCompressedMimeType)) {
            $this->tempPath = 'temp/'.uniqid().'.zip';
            $this->handleZipFile($file, $resourceFile);
        } elseif (in_array($file['mime_type'], $this->acceptedMimeType)) {
            $this->saveFile($file['name'], $file['content']);
            $this->handleSingleFile($file['name'], $resourceFile, $file['mime_type']);
        }
    }

    private function saveProcessedFile(): void
    {
        foreach ($this->processedFiles as $currentItem) {
            ResourceFile::create([
                'resource_id' => $this->resource->id,
                'path' => $currentItem['path'],
                'name' => $currentItem['filename'],
                'size' => $currentItem['size'],
                'mime_type' => $currentItem['mime_type'],
                'description' => $currentItem['description'],
                'hash_md5' => $currentItem['checksum'],
            ]);
        }
    }

    private function cleanBaseDirectory(): void
    {
        if (count(Storage::allFiles($this->dirName)) === 0) {
            Storage::deleteDirectory($this->dirName);
        }
    }

    private function createBaseDirectory(): void
    {
        Storage::disk('local')->makeDirectory($this->dirName);
    }

    private function handleZipFile(array $file, array $bitstream): void
    {
        $this->saveTemporaryZip($file['content']);

        $zip = $this->openZipFile();
        if ($zip) {
            $this->extractZipContents($zip, $bitstream);
            $zip->close();
        }

        $this->cleanupTemporaryFile();
    }

    private function saveTemporaryZip(string $content): void
    {
        Storage::put($this->tempPath, $content);
    }

    private function openZipFile(): ?ZipArchive
    {
        $zip = new ZipArchive;

        if (! Storage::exists($this->tempPath)) {
            return null;
        }

        try {
            if ($zip->open(Storage::disk('local')->path($this->tempPath)) === true) {
                return $zip;
            }
        } catch (Exception $e) {
            return null;
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
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if ($this->isDirectory($filename) || $this->isExcludedPath($filename)) {
                continue;
            }

            $baseFilename = basename($filename);
            $finalFilename = $this->getUniqueFilename($baseFilename);

            $fileContent = $zip->getFromIndex($i);

            $this->saveFile($finalFilename, $fileContent);

            $mimeType = Storage::mimeType($this->dirName.'/'.$finalFilename);
            $size = Storage::size($this->dirName.'/'.$finalFilename);

            if (! in_array($mimeType, $this->acceptedMimeType)) {
                Storage::delete($this->dirName.'/'.$finalFilename);

                continue;
            }

            $bitstream['sizeBytes'] = $size;
            $this->handleSingleFile($finalFilename, $bitstream, $mimeType);
        }
    }

    private function getUniqueFilename(string $filename): string
    {
        $counter = 1;
        $originalName = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $newFilename = $filename;
        while (Storage::disk('local')->exists($this->dirName.'/'.$newFilename)) {
            $newFilename = $originalName.'_'.$counter.'.'.$extension;
            $counter++;
        }

        return $newFilename;
    }

    private function isDirectory(string $filename): bool
    {
        return str_ends_with($filename, '/');
    }

    private function saveFile(string $filename, string $content): void
    {
        Storage::disk('local')->put($this->dirName.'/'.$filename, $content);
    }

    private function handleSingleFile(string $filename, array $bitstream, string $mimeType): void
    {
        $this->processedFiles[] = [
            'path' => $this->dirName.'/'.$filename,
            'filename' => $filename,
            'size' => $bitstream['sizeBytes'],
            'mime_type' => $mimeType,
            'description' => $bitstream['description'] ?? null,
            'checksum' => $bitstream['checkSum']['value'] ?? null,
            'checksum_algorithm' => $bitstream['checkSum']['checkSumAlgorithm'] ?? null,
        ];
    }

    private function cleanupTemporaryFile(): void
    {
        if (Storage::exists($this->tempPath)) {
            Storage::delete($this->tempPath);
        }
    }

    protected function getFile(array $bitstream): array
    {
        $response = $this->client->get('https://dspace-clarin-it.ilc.cnr.it/repository/rest'.$bitstream['retrieveLink']);
        $mimeType = $response->getHeaderLine('Content-Type');
        $filename = $bitstream['name'] ?? basename($bitstream['retrieveLink']);

        return [
            'content' => $response->getBody()->getContents(),
            'mime_type' => $mimeType,
            'name' => $filename,
        ];
    }
}
