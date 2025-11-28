<?php

// https://filamentphp.com/docs/3.x/forms/adding-a-form-to-a-livewire-component

namespace App\Livewire;

use App\Enums\ResourceStatusImport;
use App\Enums\ResourceStatusSubmission;
use App\Facades\DSpace;
use App\Jobs\CreateProjectSemanticTurkey;
use App\Jobs\healthCheckResourceFile;
use App\Jobs\ProcessFiles;
use App\Models\Resource;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Livewire\Component;

class SearchResource extends Component implements HasForms
{
    use InteractsWithForms;

    private const BANNED_FILES_NAMES = ['license.txt'];

    public ?string $itemIdentifier;

    public ?Collection $results = null;

    public bool $loading = false;

    protected array $rules = [
        'itemIdentifier' => 'required|string',
    ];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema());
    }

    private function getFormSchema(): array
    {
        return [
            $this->getItemIdentifierInput(),
        ];
    }

    private function getItemIdentifierInput(): TextInput
    {
        return TextInput::make('itemIdentifier')
            ->required()
            ->regex('/^http:\/\/hdl\.handle\.net\/\d+\.\d+\.\d+\/[\w-]+$/')
            ->placeholder('http://hdl.handle.net/...')
            ->autocomplete(false);
    }

    public function resetForm(): void
    {
        $this->form->fill();
    }

    public function submit(): void
    {
        $this->results = null;
        $validated = $this->validate();

        if (! $validated['itemIdentifier']) {
            Notification::make()
                ->title('Invalid item identifier')
                ->body('The item identifier is invalid')
                ->danger()
                ->send();

            return;
        }

        $this->loading = true;

        DSpace::login();
        $itemByMetadata = DSpace::findItemsByMetadata([
            'key' => 'dc.identifier.uri',
            'language' => '*',
            'value' => $validated['itemIdentifier'],
        ], ['expand' => 'all']);
        DSpace::logout();

        if (! $itemByMetadata || ! $itemByMetadata[0]) {
            Notification::make()
                ->title(__('Item not found'))
                ->body(__('The item identifier is invalid or not found in DSpace'))
                ->danger()
                ->send();
            $this->loading = false;

            return;
        }

        $item = $itemByMetadata[0];

        $submitter = $this->getSubmitter($item['metadata']);

        if (! $submitter) {
            Notification::make()
                ->title(__('Resource submitter not found'))
                ->body(__('The resource submitter was not found'))
                ->danger()
                ->send();
            $this->loading = false;

            return;
        }

        if (! \in_array(app()->environment(), ['local', 'development'])) {
            /** @var User $currentUser */
            $currentUser = filament()->auth()->user();
            $currentUserEmail = $currentUser->email;

            if ($submitter !== $currentUserEmail && ! $currentUser->isAdminOrSuperAdmin()) {
                Notification::make()
                    ->title(__('You are not the submitter of this resource'))
                    ->body(__('You can only import resources that you have submitted on DSpace.'))
                    ->danger()
                    ->send();
                $this->loading = false;

                return;
            }
        }

        $parsedMetadata = $this->parseMetadata($item['metadata']);
        $files = $this->getFiles($item['bitstreams']);

        $resource = [
            'name' => $item['name'],
            'handle_id' => $this->itemIdentifier,
            'internal_id' => $item['id'],
            'files' => $files,
            'submitter' => $submitter,
            ...$parsedMetadata,
        ];

        // Debugging
        if (app()->environment() === 'local') {
            Notification::make()
                ->body(__("The resource submitter email is: <strong>{$submitter}</strong>"))
                ->info()
                ->send();
        }

        $this->results = collect([(object) $resource]);
        $this->loading = false;
    }

    public function importResource(): void
    {

        $currentItem = $this->results->first();

        $resource = [
            'name' => $currentItem->name,
            'handle_id' => $this->itemIdentifier,
            'internal_id' => $currentItem->internal_id,
            'type' => $currentItem->type,
            'description' => $currentItem->description,
            'authors' => $currentItem->authors,
            'item_identifier' => $currentItem->item_identifier,
            'issued_date' => $currentItem->issued_date,
            'publisher' => $currentItem->publisher,
            'status_submission' => ResourceStatusSubmission::NOT_SUBMITTED->value,
            'status_import' => ResourceStatusImport::PENDING->value,
        ];

        $createdResource = (new Resource)->create($resource);

        $attachedFiles = $currentItem->files->toArray();

        if (! $createdResource) {
            Notification::make()
                ->title(__('Resource not imported'))
                ->body(__("Resource <strong>\"{$currentItem->name}\"</strong> not imported"))
                ->icon('heroicon-o-exclamation-triangle')
                ->danger()
                ->send();

            return;
        }

        $createdResource->users()->attach(filament()->auth()->user()->id);

        Notification::make()
            ->title(__('Resource imported'))
            ->body(__("Resource <strong>\"{$createdResource->name}\"</strong> imported successfully"))
            ->icon('heroicon-o-check-circle')
            ->success()
            ->send();

        Bus::batch([
            new ProcessFiles(resource: $createdResource, resourceFiles: $attachedFiles),
        ])
            ->allowFailures(false)
            ->then(function () use ($createdResource) {
                $jobs = [];
                foreach ($createdResource->files as $file) {
                    $jobs[] = new healthCheckResourceFile(resourceFile: $file);
                }
                Bus::batch($jobs)
                    ->allowFailures(false)
                    ->then(function () use ($createdResource) {
                        $jobs = [];
                        foreach ($createdResource->files as $file) {
                            if ($file->can_be_opened_in_vocbench) {
                                $jobs[] = new CreateProjectSemanticTurkey(resourceFile: $file, user: User::query()->find(filament()->auth()->id()));
                            }
                        }
                        Bus::batch($jobs)
                            ->allowFailures(false)
                            ->dispatch();
                    })
                    ->dispatch();
            })
            ->dispatch();

        redirect()->route('filament.user.pages.dashboard');
    }

    private function parseMetadata(array $metadata): array
    {
        $parsedMetadata = array_reduce($metadata, function ($carry, $item) {
            if ($item['key'] === 'dc.contributor.author') {
                $carry['authors'][] = Str::of($item['value'])
                    ->explode(',')
                    ->map(fn ($author) => trim($author))
                    ->join(' ');
            }

            if ($item['key'] === 'dc.type') {
                $carry['type'][] = $item['value'];
            }

            if ($item['key'] === 'dc.identifier.uri') {
                $carry['item_identifier'] = $item['value'];
            }

            if ($item['key'] === 'dc.publisher') {
                $carry['publisher'] = $item['value'];
            }

            if ($item['key'] === 'dc.date.issued') {
                $carry['issued_date'] = $item['value'];
            }

            if ($item['key'] === 'dc.description') {
                $carry['description'] = $item['value'];
            }

            return $carry;
        }, []);

        foreach ($parsedMetadata as $key => $value) {
            if (is_array($value)) {
                $parsedMetadata[$key] = implode(', ', $value);
            }
        }

        return $parsedMetadata;
    }

    // TODO: This is a temporary solution to get the submitter email from the metadata
    private function getSubmitter(array $metadata): string
    {
        if (! isset($metadata) || ! is_array($metadata)) {
            return '';
        }

        $provenanceArray = array_filter($metadata, function ($item) {
            return $item['key'] === 'dc.description.provenance' && Str::startsWith($item['value'], 'Submitted by');
        });

        if (! count($provenanceArray)) {
            return '';
        }

        $lastProvenance = array_pop($provenanceArray);
        $submitter = Str::of($lastProvenance['value'])
            ->match('/\((.*?@.*?)\)/');

        return $submitter ?? '';
    }

    private function getFiles(array $bitstreams): Collection
    {
        return collect($bitstreams)->map(function ($bitstream) {
            if (\in_array($bitstream['name'], self::BANNED_FILES_NAMES)) {
                return null;
            }

            return $bitstream;
        })->filter();
    }

    public function render(): View
    {
        return view('livewire.search-resource');
    }
}
