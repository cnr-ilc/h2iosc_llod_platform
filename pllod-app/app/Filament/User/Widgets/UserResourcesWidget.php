<?php

namespace App\Filament\User\Widgets;

use App\Enums\ResourceStatusImport;
use App\Models\Resource;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class UserResourcesWidget extends Widget
{
    protected static ?int $sort = 4;

    protected static string $view = 'filament.widgets.user-resources-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '10s';

    public string $statusFilter = 'all';

    protected function getPollingInterval(): ?string
    {
        return static::$pollingInterval;
    }

    private function getUserResources(): Collection
    {
        $user = filament()->auth()->user();
        $data = $user->resources()->with('files')->get();
        $data = $data->sortByDesc('created_at');

        return $data;
    }

    public function deleteResource(int $resourceId): void
    {
        $resource = Resource::find($resourceId);

        if (! $resource) {
            Notification::make()
                ->title(__('Resource not found'))
                ->body(__("The resource <strong>\"{$resource->name}\"</strong> has not been found."))
                ->danger()
                ->send();
        }

        $resource->delete();

        Notification::make()
            ->title(__('Resource deleted'))
            ->body(__("The resource <strong>\"{$resource->name}\"</strong> has been deleted successfully."))
            ->success()
            ->send();
    }

    private function filterResources(Collection $resources): Collection
    {
        if ($this->statusFilter !== 'all') {
            $resources = $resources->where('status_import', $this->statusFilter);
        }

        return $resources;
    }

    protected function getViewData(): array
    {
        $resources = $this->getUserResources();
        $filterResourceIfNeeded = $this->filterResources($resources);

        return [
            'resources' => $filterResourceIfNeeded,
            'statuses' => $resources->pluck('status_import')
                ->unique()
                ->values()
                ->map(function ($status): object {
                    return (object) [
                        'value' => $status,
                        'label' => ResourceStatusImport::from($status)->getLabel(),
                    ];
                }),
        ];
    }
}
