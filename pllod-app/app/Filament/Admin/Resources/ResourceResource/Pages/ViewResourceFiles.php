<?php

namespace App\Filament\Admin\Resources\ResourceResource\Pages;

use App\Enums\GeneralStatus;
use App\Filament\Admin\Resources\ResourceResource;
use App\Jobs\qualityCheckResourceFile;
use Filament\Actions\Action;
use Filament\Pages\Dashboard;
use Filament\Resources\Pages\ManageRelatedRecords;
use Illuminate\Support\Facades\Bus;

class ViewResourceFiles extends ManageRelatedRecords
{
    protected static string $resource = ResourceResource::class;

    protected static string $relationship = 'files';

    protected static string $view = 'filament.pages.admin.view-resource-files';

    public function getViewData(): array
    {
        return [
            'files' => $this->getRecord()->files,
        ];
    }

    public function getTitle(): string
    {
        return $this->getRecord()->name;
    }

    public function getBreadcrumb(): string
    {
        return $this->getNavigationLabel();
    }

    public static function getNavigationIcon(): null
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __('Available files');
    }

    public function evaluateQualityAction(): Action
    {
        return Action::make(name: 'evaluateQuality')
            ->label(__('Launch evaluation protocol'))
            ->outlined()
            ->disabled(function (array $arguments): bool {
                $resourceFile = $this->getRecord()->files->where('id', $arguments['fileId'])->first();

                return $resourceFile->quality_assessment_status === GeneralStatus::RUNNING->value || $resourceFile->validation_status === GeneralStatus::RUNNING->value || $resourceFile->validation_status === GeneralStatus::FAILED->value;
            })
            ->size('lg')
            ->action(function (array $arguments) {
                $resourceFile = $this->getRecord()->files->where('id', $arguments['fileId'])->first();
                if ($resourceFile) {
                    $resourceFile->quality_assessment_status = GeneralStatus::RUNNING->value;
                    $resourceFile->save();
                    Bus::batch([
                        new qualityCheckResourceFile($resourceFile),
                    ])->dispatch();
                }
            });
    }

    public function getBreadcrumbs(): array
    {
        $baseUrl = Dashboard::getUrl();
        $resourceUrl = ResourceResource::getUrl('index');
        $resource = static::getResource();
        $record = $this->getRecord();
        $breadcrumb = $this->getBreadcrumb();

        return [
            $baseUrl => 'Dashboard',
            $resourceUrl => 'Resources',
            $resource::getUrl('view', ['record' => $record]) => $record->name,
            ...(filled($breadcrumb) ? [$breadcrumb] : []),
        ];
    }
}
