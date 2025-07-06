<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\ResourceFile;
use Illuminate\Support\Facades\Auth; // Added for explicit facade usage
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse; // Added for return type hinting

class FileController extends Controller
{
    /**
     * Handle a request to download a resource file.
     *
     * Utilizes implicit route model binding for ResourceFile.
     * The {id} in the route '/file/{id}/download' will be used to fetch the ResourceFile instance.
     *
     * @param  ResourceFile  $id  The ResourceFile instance resolved from the route parameter.
     */
    public function download(ResourceFile $id): StreamedResponse
    {
        // Assign to a more descriptively named variable for clarity within the method.
        $resourceFile = $id;

        // TODO: Check the user (This comment might be better addressed by applying 'auth' middleware to the route)
        // Explicitly check if a user is authenticated.
        // If not, abort with a 403 error. This is crucial if the route is not protected by 'auth' middleware.
        abort_if(! Auth::check(), 403, 'Authentication required to download this file.');

        // At this point, Auth::check() is true, so Auth::user() will return the authenticated user instance.
        $user = Auth::user();

        // Authorization: Check if the authenticated user owns the resource associated with the file,
        // or if the user has an admin role.
        $isOwner = $resourceFile->resource->users->contains($user);
        $isAdmin = $user->hasRole(UserRole::ADMIN->value);

        // If the user is not an admin, they must be the owner of the resource to download the file.
        if (! $isAdmin) {
            abort_if(! $isOwner, 403, 'You are not authorized to download this file.');
        }

        // If all checks pass, stream the file for download from local storage.
        return Storage::disk('local')->download($resourceFile->path);
    }
}
