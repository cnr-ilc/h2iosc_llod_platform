<?php

namespace App\Filament\User\Widgets;

use App\Enums\ResourceStatusSubmission;
use App\Filament\User\Pages\ImportResource;
use App\Models\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\View;
use Filament\Support\Components\Attributes\Unexposed;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserResourcesStatusOverviewWidget extends BaseWidget
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
        return $this->showCreateResourceButton() ? ['md' => 2, 'xl' => 4] : ['md' => 3];
    }

    /**
     * Filament 4+: gli Stat sono componenti di uno Schema, quindi la card-link
     * si aggiunge come componente View accanto agli Stat (ex view custom del widget).
     */
    #[Unexposed]
    public function getSectionContentComponent(): Component
    {
        $components = $this->getCachedStats();

        if ($this->showCreateResourceButton()) {
            $components[] = View::make('filament.widgets.partials.stats-link-card')
                ->viewData([
                    'url' => $this->getCreateResourceButtonURL(),
                    'heading' => __('filament.widgets.user-resources-stats-overview.import-resources'),
                    'description' => __('filament.widgets.user-resources-stats-overview.import-resources-description'),
                ]);
        }

        return parent::getSectionContentComponent()->schema($components);
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
