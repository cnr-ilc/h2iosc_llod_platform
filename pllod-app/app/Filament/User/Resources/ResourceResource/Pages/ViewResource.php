<?php

namespace App\Filament\User\Resources\ResourceResource\Pages;

use App\Filament\User\Resources\ResourceResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\On;

class ViewResource extends ViewRecord
{
    protected static string $resource = ResourceResource::class;

    protected string $view = 'filament.pages.view-resource';

    #[On('refresh-resource')]
    public function refreshResource(): void
    {
        //
    }

    public static function getNavigationLabel(): string
    {
        return __('Resource details');
    }

    public static function getNavigationIcon(): string|Htmlable|null
    {
        return null;
    }

    public function getTitle(): string
    {
        return $this->getRecord()->name;
    }

    public function getBreadcrumb(): string
    {
        return $this->getTitle();
    }

    public function getBreadcrumbs(): array
    {
        $baseUrl = Dashboard::getUrl();
        $breadcrumb = $this->getBreadcrumb();

        return [
            $baseUrl => 'Dashboard',
            ...(filled($breadcrumb) ? [$breadcrumb] : []),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getResourceMetadataSection(),
            ]);
    }

    private function getResourceMetadataSection(): Section
    {
        return Section::make('Resource Metadata')
            ->columnSpanFull()
            ->collapsible()
            ->schema([
                TextEntry::make('type')
                    ->translateLabel(),
                TextEntry::make('authors')
                    ->translateLabel(),
                TextEntry::make('item_identifier')
                    ->translateLabel(),
                TextEntry::make('issued_date')
                    ->label('Date issued')
                    ->dateTime('Y')
                    ->translateLabel(),
                TextEntry::make('description')
                    ->translateLabel(),
                TextEntry::make('publisher')
                    ->translateLabel(),
            ]);
    }

    protected function getFooterActionsAlignment(): string
    {
        return 'center';
    }

    public function getFormActions(): array
    {
        return [
            // Filament 4+: l'azione va registrata sul componente, poi mountAction('delete') la risolve via deleteAction()
            $this->cacheAction($this->deleteAction()),
        ];
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label('Remove resource from profile')
            ->translateLabel()
            ->size('lg')
            ->color('danger')
            ->action(function () {
                $resource = $this->getRecord();
                $resource->delete();
                Notification::make()
                    ->title('Resource deleted successfully')
                    ->body("The resource <strong>\"{$resource->name}\"</strong> has been deleted successfully.")
                    ->success()
                    ->send();

                return redirect()->route('filament.user.pages.dashboard');
            })
            ->requiresConfirmation();
    }
}
