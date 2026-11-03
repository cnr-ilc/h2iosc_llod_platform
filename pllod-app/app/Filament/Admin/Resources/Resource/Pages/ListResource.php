<?php

namespace App\Filament\Admin\Resources\Resource\Pages;

use App\Filament\Admin\Resources\ResourceResource;
use Filament\Resources\Pages\Page;

class ListResource extends Page
{
    protected static string $resource = ResourceResource::class;

    protected string $view = 'filament.admin.resources.resource.pages.list-resource';

    public function getTitle(): string
    {
        return __('User resources');
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
