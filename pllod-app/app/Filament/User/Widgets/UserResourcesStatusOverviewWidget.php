<?php

namespace App\Filament\User\Widgets;

use App\Enums\ResourceStatusSubmission;
use App\Filament\User\Pages\ImportResource;
use App\Models\Resource;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserResourcesStatusOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static string $view = 'filament.widgets.user-resources-stats-overview-widget';

    protected static ?string $pollingInterval = '10s';

    protected function getPollingInterval(): ?string
    {
        return static::$pollingInterval;
    }

    protected function getColumns(): int
    {
        $showCreateResourceButton = $this->showCreateResourceButton();

        return $showCreateResourceButton ? 4 : 3;
    }

    protected function showCreateResourceButton(): bool
    {
        return true;
    }

    protected function getCreateResourceButtonURL(): string
    {
        return ImportResource::getUrl();
    }

    private function getResourceCountByStatus(ResourceStatusSubmission $status): int
    {
        $user = filament()->auth()->user();

        $resources = Resource::with('submission')
            ->whereHas('submission', function ($query) use ($status, $user) {
                $query->where('status', $status->value)
                    ->where('user_id', $user->id);
            })
            ->orderByDesc('created_at')
            ->get();

        return $resources->count();
    }

    /**
     * Get the stats for the widget.
     *
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        return [
            Stat::make(
                __('Pending approval submissions'),
                $this->getResourceCountByStatus(ResourceStatusSubmission::PENDING),
            ),
            Stat::make(
                __('Approved submissions'),
                $this->getResourceCountByStatus(ResourceStatusSubmission::APPROVED),
            ),
            Stat::make(
                __('Rejected submissions'),
                $this->getResourceCountByStatus(ResourceStatusSubmission::REJECTED),
            ),
        ];
    }
}
