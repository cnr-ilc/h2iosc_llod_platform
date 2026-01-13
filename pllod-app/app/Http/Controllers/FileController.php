<?php

namespace App\Http\Controllers;

use App\Models\ResourceFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    /**
     * Handle a request to download a resource file.
     *
     * @param  ResourceFile  $file  The ResourceFile instance resolved from the route parameter.
     */
    public function download(ResourceFile $file): StreamedResponse
    {
        Gate::authorize('view', $file);

        return Storage::disk('local')->download($file->path);
    }

    /**
     * Handle a request to download the suggestions for a resource file.
     *
     * @param  ResourceFile  $file  The ResourceFile instance resolved from the route parameter.
     */
    public function suggestions(ResourceFile $file): StreamedResponse
    {
        Gate::authorize('view', $file);

        $content = "*** SUGGESTIONS ***\n\n\n".collect($file->quality_warnings)->implode("\n\n");

        return response()->streamDownload(
            function () use ($content) {
                echo $content;
            },
            Str::slug($file->name).'_suggestions.txt',
            ['Content-Type' => 'text/plain']
        );
    }
}
