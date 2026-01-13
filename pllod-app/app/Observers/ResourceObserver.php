<?php

namespace App\Observers;

use App\Jobs\DeleteProjectSemanticTurkey;
use App\Models\Resource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ResourceObserver
{
    public function created(Resource $resource)
    {
        //
    }

    public function updated(Resource $resource)
    {
        //
    }

    public function deleting(Resource $resource)
    {
        $filesData = $resource->files()
            ->select('st_project_name', 'graphdb_repository_name')
            ->get()
            ->map(function ($file) {
                return [
                    'st_project_name' => $file->st_project_name,
                    'graphdb_repository_name' => $file->graphdb_repository_name,
                ];
            })
            ->toArray();

        DeleteProjectSemanticTurkey::dispatch($filesData);
        if ($firstFile = $resource->files()->first()) {
            try {
                Log::info('Deleting resource files folder: '.dirname($firstFile->path));
                Storage::disk('local')->deleteDirectory(dirname($firstFile->path));
            } catch (\Throwable $th) {
                Log::error('Error deleting resource files: '.$th->getMessage());
            }
        }
    }

    public function deleted(Resource $resource)
    {
        //
    }

    public function restored(Resource $resource)
    {
        //
    }
}
