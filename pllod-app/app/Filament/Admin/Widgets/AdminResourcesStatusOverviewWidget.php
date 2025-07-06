<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\ResourceStatusSubmission;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\ResourceResource;
use App\Models\Resource;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AdminResourcesStatusOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static string $view = 'filament.widgets.admin-resources-stats-overview-widget';

    protected static ?string $pollingInterval = '10s';

    protected function getPollingInterval(): ?string
    {
        return static::$pollingInterval;
    }

    protected function getColumns(): int
    {
        $showManageUsersResourceButton = $this->showManageUsersResourceButton();

        return $showManageUsersResourceButton ? 4 : 3;
    }

    protected function showManageUsersResourceButton(): bool
    {
        return Auth::user()->hasRole([UserRole::ADMIN->value]);
    }

    protected function getManageUsersResourceButtonURL(): string
    {
        return ResourceResource::getUrl('index');
    }

    private function getAllResourcesCountByStatus(ResourceStatusSubmission $status): int
    {
        $resources = Resource::with('submission')
            ->whereHas('submission', function ($query) use ($status) {
                $query->where('status', $status->value);
            })
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
                $this->getAllResourcesCountByStatus(ResourceStatusSubmission::PENDING),
            ),
            Stat::make(
                __('Approved submissions'),
                $this->getAllResourcesCountByStatus(ResourceStatusSubmission::APPROVED),
            ),
            Stat::make(
                __('Rejected submissions'),
                $this->getAllResourcesCountByStatus(ResourceStatusSubmission::REJECTED),
            ),
        ];
    }
}
