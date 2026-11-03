<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('Create User'))
                ->extraAttributes([
                    'class' => 'bg-button-gradient hover:bg-button-gradient-hover',
                ])
                ->createAnother(false),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
