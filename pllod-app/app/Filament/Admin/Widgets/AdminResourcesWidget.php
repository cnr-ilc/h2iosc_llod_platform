<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\ResourceStatusSubmission;
use App\Enums\UserRole;
use App\Models\Resource;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class AdminResourcesWidget extends Widget
{
    protected static ?int $sort = 4;

    protected string $view = 'filament.widgets.admin-resources-widget';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '10s';

    public string $statusFilter = 'all';

    public static function canView(): bool
    {
        return Auth::user()->hasRole([UserRole::ADMIN->value]);
    }

    protected function getPollingInterval(): ?string
    {
        return $this->pollingInterval;
    }

    private function getAllSubmittedResources(): Collection
    {
        $submittedStatuses = [
            ResourceStatusSubmission::PENDING->value,
            ResourceStatusSubmission::APPROVED->value,
            ResourceStatusSubmission::REJECTED->value,
        ];

        $resources = Resource::with('submission')
            ->whereHas('submission', function ($query) use ($submittedStatuses) {
                $query->whereIn('status', $submittedStatuses);
            })
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        return $resources;
    }

    protected function getViewData(): array
    {
        $resources = $this->getAllSubmittedResources();

        return [
            'resources' => $resources,
        ];
    }
}
