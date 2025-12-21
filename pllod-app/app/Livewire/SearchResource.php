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

    private const array BANNED_FILES_NAMES = ['license.txt'];

    private const string FILE_TYPE = 'ORIGINAL';

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

        try {
            $item = DSpace::findItemByPid($validated['itemIdentifier'], true);
        } catch (\Throwable $th) {
            Notification::make()
                ->title(__('Error'))
                ->body(fn () => app()->isLocal() ? $th->getMessage() : __('An error occurred while searching for the item, please try again later.'))
                ->danger()
                ->send();
            $this->loading = false;
            $this->results = null;

            return;
        }

        if (! $item) {
            Notification::make()
                ->title(__('Item not found'))
                ->body(__('The item identifier is invalid or not found in DSpace'))
                ->danger()
                ->send();
            $this->loading = false;
            $this->results = null;

            return;
        }

        $submitter = $this->getSubmitter($item['metadata']);

        if (! $submitter) {
            Notification::make()
                ->title(__('Resource submitter not found'))
                ->body(__('The resource submitter was not found'))
                ->danger()
                ->send();
            $this->loading = false;
            $this->results = null;

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
                $this->results = null;

                return;
            }
        }

        $parsedMetadata = $this->parseMetadata($item['metadata']);
        $files = $this->getFiles($item);

        $resource = [
            'name' => $item['name'],
            'handle_id' => $this->itemIdentifier,
            'internal_id' => $item['id'],
            'files' => $files,
            'submitter' => $submitter,
            ...$parsedMetadata,
        ];

        // Debugging
        if (app()->isLocal()) {
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
        $parsedMetadata = [];

        // Authors
        if (isset($metadata['dc.contributor.author'])) {
            $parsedMetadata['authors'] = collect($metadata['dc.contributor.author'])
                ->pluck('value')
                ->map(fn ($author) => Str::of($author)->explode(',')->map(fn ($part) => trim($part))->join(' '))
                ->join(', ');
        }

        // Type
        if (isset($metadata['dc.type'])) {
            $parsedMetadata['type'] = collect($metadata['dc.type'])->pluck('value')->join(', ');
        }

        // Identifier URI
        if (isset($metadata['dc.identifier.uri'][0]['value'])) {
            $parsedMetadata['item_identifier'] = $metadata['dc.identifier.uri'][0]['value'];
        }

        // Publisher
        if (isset($metadata['dc.publisher'][0]['value'])) {
            $parsedMetadata['publisher'] = $metadata['dc.publisher'][0]['value'];
        }

        // Issued Date
        if (isset($metadata['dc.date.issued'][0]['value'])) {
            $parsedMetadata['issued_date'] = $metadata['dc.date.issued'][0]['value'];
        }

        // Description
        if (isset($metadata['dc.description'][0]['value'])) {
            $parsedMetadata['description'] = $metadata['dc.description'][0]['value'];
        }

        return $parsedMetadata;
    }

    private function getSubmitter(array $metadata): string
    {
        if (! isset($metadata['dc.description.provenance']) || ! \is_array($metadata['dc.description.provenance'])) {
            return '';
        }
        $provenanceArray = \array_filter($metadata['dc.description.provenance'], function ($item) {
            return isset($item['value']) && Str::startsWith($item['value'], 'Submitted by');
        });
        if (empty($provenanceArray)) {
            return '';
        }
        $lastProvenance = \array_pop($provenanceArray);

        return (string) Str::of($lastProvenance['value'])
            ->match('/\(([^)]*@[^)]*)\)/');
    }

    private function getFiles(array $item): Collection
    {
        $bundles = data_get($item, '_embedded.bundles', []);

        return collect($bundles)
            ->filter(fn ($bundle) => $bundle['name'] === self::FILE_TYPE)
            ->flatMap(fn ($bundle) => data_get($bundle, '_embedded.bitstreams._embedded.bitstreams', []))
            ->filter(fn ($bitstream) => ! \in_array($bitstream['name'], self::BANNED_FILES_NAMES));
    }

    public function render(): View
    {
        return view('livewire.search-resource');
    }
}
