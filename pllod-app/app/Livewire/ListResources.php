<?php

namespace App\Livewire;

use App\Enums\ResourceStatusSubmission;
use App\Models\Resource;
use App\Models\Submission; // Assuming the Submission model is in App\Models
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ListResources extends Component
{
    use WithPagination;

    public string $status = '';

    public int|string $perPage = 5;

    public function updatedPage($page)
    {
        $this->dispatch('pageUpdated', page: $page);
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * This method is called via wire:change="updateStatus" in the Blade view.
     * When the public $status property (bound with wire:model) changes,
     * Livewire automatically triggers a component re-render.
     * Resetting the page ensures pagination starts from the beginning with new filters.
     */
    public function updateStatus(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $submittedStatusValues = [
            ResourceStatusSubmission::PENDING->value,
            ResourceStatusSubmission::APPROVED->value,
            ResourceStatusSubmission::REJECTED->value,
        ];

        // 1. Determine the list of unique statuses present in relevant resources for the filter dropdown.
        // This query fetches resources that have submissions with any of the specified statuses.
        // We select only necessary columns from 'submission' for this specific task to optimize.
        $resourcesForStatusDropdown = Resource::whereHas('submission', function ($query) use ($submittedStatusValues) {
            $query->whereIn('status', $submittedStatusValues);
        })->with('submission:id,resource_id,status') // Eager load only necessary submission fields
            ->get();

        // Pluck statuses, filter out potential nulls, get unique values, sort them, and reset keys.
        $availableFilterStatuses = $resourcesForStatusDropdown
            ->pluck('submission.status')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // 2. Build the main query for fetching resources to display.
        $resourcesQuery = Resource::query()
            // Eager load the 'submission' relation for use in the view and sorting.
            ->with('submission')
            // Base condition: resources must have a submission with one of the $submittedStatusValues.
            ->whereHas('submission', function ($query) use ($submittedStatusValues) {
                $query->whereIn('status', $submittedStatusValues);
            });

        // Apply filtering based on the selected status, if any.
        if (! empty($this->status)) {
            $resourcesQuery->whereHas('submission', function ($query) {
                $query->where('status', $this->status);
            });
        }

        $resourcesQuery->orderByDesc(
            Submission::select('updated_at')
                ->whereColumn('submissions.resource_id', 'resources.id') // Correlate with the outer query
                ->latest('updated_at') // Get the most recent submission matching criteria
                ->limit(1)
        );

        // 'all' è una delle opzioni del selettore "per page" di <x-filament::pagination>
        $perPage = $this->perPage === 'all' ? max(1, (clone $resourcesQuery)->count()) : (int) $this->perPage;

        $paginatedResources = $resourcesQuery->paginate($perPage);

        return view('livewire.list-resources', [
            'resources' => $paginatedResources, // Pass the paginator instance directly to the view.
            'resourceCount' => $paginatedResources->total(), // Get total count from the paginator.
            'resourcesStatus' => $availableFilterStatuses,
        ]);
    }
}
