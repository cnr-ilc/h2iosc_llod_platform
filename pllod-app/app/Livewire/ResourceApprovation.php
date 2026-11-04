<?php

namespace App\Livewire;

use App\Enums\ResourceStatusSubmission;
use App\Models\Resource;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea as TextArea;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ResourceApprovation extends Component implements HasActions, HasSchemas
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
                Radio::make('status')
                    ->label(false)
                    ->required()
                    ->inline()
                    ->extraAttributes(['class' => 'justify-center w-full sm:gap-8'])
                    ->extraFieldWrapperAttributes(['class' => 'fi-fo-field-centered-wrp'])
                    ->validationMessages([
                        'required' => __('You must select an option'),
                    ])
                    ->options([
                        ResourceStatusSubmission::APPROVED->value => __('Approved submission'),
                        ResourceStatusSubmission::REJECTED->value => __('Rejected submission'),
                    ]),
                TextArea::make('notes')
                    ->label(__('Additional comment (optional)'))
                    ->autosize()
                    ->maxLength($this->textAreaMaxLength)
                    ->extraAttributes(['class' => '!bg-gray-50 !box-shadow-xs'])
                    ->rows(7),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {

        $this->closeSubmitResourceModal();

        $data = $this->form->getState();

        $status = ResourceStatusSubmission::from($data['status'])->value;

        $this->resource->submission->update([
            'admin_id' => filament()->auth()->id(),
            'status' => $status,
            'admin_notes' => $data['notes'],
            'updated_at' => now(),
        ]);

        if ($status === ResourceStatusSubmission::APPROVED->value) {
            Notification::make()
                ->title(__('Resource approved'))
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->body(__('The resource has been approved. You will be notified via email when it is published.'))
                ->send();
        }

        if ($status === ResourceStatusSubmission::REJECTED->value) {
            Notification::make()
                ->title(__('Resource rejected'))
                ->icon('heroicon-o-x-circle')
                ->iconColor('danger')
                ->body(__('The resource has been rejected.'))
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
        return view('livewire.resource-approvation');
    }
}
