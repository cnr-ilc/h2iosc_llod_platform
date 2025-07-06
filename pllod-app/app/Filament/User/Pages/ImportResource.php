<?php

namespace App\Filament\User\Pages;

use Filament\Pages\Dashboard;
use Filament\Pages\Page;

class ImportResource extends Page
{
    // Configuration
    protected static string $view = 'filament.pages.import-resource';

    protected static ?string $slug = 'import-resource';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationParentItem = 'Dashboard';

    protected array $extraBodyAttributes = ['class' => 'pllod-import-resource-page'];

    public function getTitle(): string
    {
        return __('filament.pages.import-resource.title');
    }

    public function getBreadcrumbs(): array
    {
        return [
            Dashboard::getUrl() => 'Dashboard',
            $this->getUrl() => $this->getTitle(),
        ];
    }
}
