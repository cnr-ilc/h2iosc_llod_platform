<?php

namespace App\Livewire;

use App\Enums\ResourceStatusSubmission;
use App\Models\Resource;
use App\Models\Submission;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ResourcePublication extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public Resource $resource;

    public int $textAreaMaxLength = 300;

    public string $submitResourceModalId = 'submit-resource-modal';

    public ?array $data = [];

    public function mount(Resource $resource): void
    {
        $this->resource = $resource;
        $this->form->fill([
            'notes' => '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('notes')
                    ->label(__('Additional comment for ILC admins (optional)'))
                    ->autosize()
                    ->maxLength($this->textAreaMaxLength)
                    ->extraAttributes(['class' => '!bg-gray-50 !box-shadow-xs'])
                    ->rows(7),
            ])
            ->disabled(function () {
                return ! $this->resource->can_be_submitted || $this->resource->some_resource_files_has_running_status;
            })
            ->statePath('data');
    }

    public function submit(): void
    {

        $this->closeSubmitResourceModal();

        if (! $this->resource->can_be_submitted || $this->resource->some_resource_files_has_running_status) {
            Notification::make()
                ->title(__('Resource cannot be submitted'))
                ->icon('heroicon-o-x-circle')
                ->iconColor('danger')
                ->body(__('Your resource cannot be submitted because it has failed validation or contains files that are still being processed.'))
                ->danger()
                ->send();

            return;
        }

        $data = $this->form->getState();

        $submission = Submission::create([
            'resource_id' => $this->resource->id,
            'user_id' => filament()->auth()->id(),
            'status' => ResourceStatusSubmission::PENDING->value,
            'user_notes' => $data['notes'],
        ]);

        if ($submission) {
            Notification::make()
                ->title(__('Resource submitted for approval'))
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->body(__('Your resource has been submitted for approval. You will be notified via email when it is approved.'))
                ->send();
        } else {
            Notification::make()
                ->title(__('Resource submission failed'))
                ->icon('heroicon-o-x-circle')
                ->iconColor('danger')
                ->body(__('Your resource submission failed. Please try again.'))
                ->danger()
                ->send();
        }

        $this->dispatch('refresh-resource');
    }

    public function resetForm(): void
    {
        $this->mount($this->resource);
    }

    public function openSubmitResourceModal(): void
    {
        $this->dispatch('open-modal', id: $this->submitResourceModalId);
    }

    public function closeSubmitResourceModal(): void
    {
        $this->dispatch('close-modal', id: $this->submitResourceModalId);
    }

    public function render(): View
    {
        return view('livewire.resource-publication');
    }
}
