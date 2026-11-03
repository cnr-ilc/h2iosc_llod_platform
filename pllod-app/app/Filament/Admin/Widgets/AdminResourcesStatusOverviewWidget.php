<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\ResourceStatusSubmission;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\ResourceResource;
use App\Models\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\View;
use Filament\Support\Components\Attributes\Unexposed;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AdminResourcesStatusOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected ?string $pollingInterval = '10s';

    /**
     * Stessi breakpoint della vecchia view custom (md:grid-cols-2 xl:grid-cols-4 / md:grid-cols-3).
     *
     * @return array<string, int>
     */
    protected function getColumns(): array
    {
        return $this->showManageUsersResourceButton() ? ['md' => 2, 'xl' => 4] : ['md' => 3];
    }

    /**
     * Filament 4+: gli Stat sono componenti di uno Schema, quindi la card-link
     * si aggiunge come componente View accanto agli Stat (ex view custom del widget).
     */
    #[Unexposed]
    public function getSectionContentComponent(): Component
    {
        $components = $this->getCachedStats();

        if ($this->showManageUsersResourceButton()) {
            $components[] = View::make('filament.widgets.partials.stats-link-card')
                ->viewData([
                    'url' => $this->getManageUsersResourceButtonURL(),
                    'heading' => __('filament.widgets.admin-resources-stats-overview.manage-users-resources'),
                    'description' => __('filament.widgets.admin-resources-stats-overview.manage-users-resources-description'),
                ]);
        }

        return parent::getSectionContentComponent()->schema($components);
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
