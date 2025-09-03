<?php

namespace App\Filament\User\Resources\ResourceResource\Pages;

use App\Enums\GeneralStatus;
use App\Enums\ResourceStatusImport;
use App\Enums\ResourceStatusSubmission;
use App\Filament\User\Resources\ResourceResource;
use App\Jobs\CreateProjectSemanticTurkey;
use App\Jobs\healthCheckResourceFile;
use App\Jobs\ProcessReuploadedFiles;
use App\Jobs\qualityCheckResourceFile;
use App\Models\ResourceFile;
use App\Models\User;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard;
use Filament\Resources\Pages\ManageRelatedRecords;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

class ViewResourceFiles extends ManageRelatedRecords
{
    protected static string $resource = ResourceResource::class;

    protected static string $relationship = 'files';

    protected static string $view = 'filament.pages.view-resource-files';

    #[On('refresh-resource')]
    public function refreshResource(): void
    {
        //
    }

    protected function authorizeAccess(): void
    {
        abort_unless(filament()->auth()->user()->can('view', $this->getRecord()) || $this->getRecord()->status_import === ResourceStatusImport::IMPORTED->value, 403);
    }

    public function getViewData(): array
    {
        $this->dispatch('refresh-voc-bench-button');

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

    public function reuploadAction(): Action
    {
        return Action::make('reupload')
            ->label(__('Re-upload file'))
            ->outlined()
            ->disabled(function (array $arguments): bool {
                $record = $this->getRecord();
                if (! $record) {
                    return true; // Disable if record is not available
                }
                $currentFile = $record->files->where('id', $arguments['currentFileId'])->first();
                if (! $currentFile) {
                    return true; // Disable if the specific file is not found
                }

                $submissionsStatus = [
                    ResourceStatusSubmission::PENDING->value,
                    ResourceStatusSubmission::APPROVED->value,
                    ResourceStatusSubmission::REJECTED->value,
                ];

                $isResourceSubmitted = in_array($this->getRecord()?->submission?->status, $submissionsStatus);

                return $currentFile->quality_assessment_status === GeneralStatus::RUNNING->value || $isResourceSubmitted || $currentFile->validation_status === GeneralStatus::RUNNING->value;
            })
            ->size('lg')
            ->icon('heroicon-o-document-arrow-up')
            ->modalHeading(__('Reupload file'))
            ->modalDescription(__('Please select the file you want to reupload.'))
            ->modalCancelActionLabel(__('Cancel'))
            ->modalSubmitActionLabel(__('Reupload'))
            ->form([
                FileUpload::make('fileForReupload')
                    ->label(__('File'))
                    ->disk('local')
                    ->directory('temp')
                    ->preserveFilenames()
                    ->required()
                    ->rules([
                        function (): Closure { // Changed from arrow function
                            return function (string $attribute, $value, Closure $fail): void {
                                $extension = Str::lower($value->getClientOriginalExtension());
                                $allowedExtensions = ['ttl', 'nt', 'nq', 'trig', 'jsonld', 'n3', 'rdf'];
                                if (! in_array($extension, $allowedExtensions, true)) {
                                    $fail(__('The file is invalid. Only TTL, NT, NQ, TRIG, JSONLD, N3 and RDF files are allowed.'));
                                }
                            };
                        },
                    ]),
            ])
            ->action(function (array $arguments, array $data) {

                $currentFileId = $arguments['currentFileId'];
                $currentFile = ResourceFile::find($currentFileId); // Removed first() as find already returns a single model or null

                if (! $currentFile) {
                    Notification::make()
                        ->title(__('File not found'))
                        ->body(__('The file you are trying to replace was not found.'))
                        ->danger()
                        ->send();

                    return;
                }

                $newResourceFiles = $data['fileForReupload'];
                $currentFile->update([
                    'graphdb_uri' => null,
                    'health_issues' => null,
                    'health_warnings' => null,
                    'quality_result' => null,
                    'quality_warnings' => null,
                    'can_be_opened_in_vocbench' => false,
                    'is_submittable' => false,
                    'quality_score' => null,
                    'quality_assessment_status' => null,
                    'validation_status' => GeneralStatus::RUNNING->value,
                ]);

                $currentFile = $currentFile->refresh();

                Bus::chain([
                    new ProcessReuploadedFiles(resource: $this->getRecord(), newResourceFilePath: $newResourceFiles, currentFile: $currentFile),
                    new healthCheckResourceFile($currentFile),
                    new CreateProjectSemanticTurkey($currentFile->refresh(), User::query()->find(filament()->auth()->id())),
                ])
                    ->dispatch();
                Notification::make()
                    ->title(__('File reuploaded'))
                    ->body(__('The file has been reuploaded successfully and will be checked again.'))
                    ->success()
                    ->send();
            })->after(function () {
                $this->dispatch('refresh-resource');
            });
    }

    public function evaluateQualityAction(): Action
    {
        return Action::make(name: 'evaluateQuality')
            ->label(__('Launch evaluation protocol'))
            ->outlined()
            ->disabled(function (array $arguments): bool {
                $record = $this->getRecord();
                if (! $record) {
                    return true; // Disable if record is not available
                }
                $resourceFile = $record->files->where('id', $arguments['fileId'])->first();
                if (! $resourceFile) {
                    return true; // Disable if the specific file is not found
                }

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
        $resource = static::getResource();
        $record = $this->getRecord();
        $breadcrumb = $this->getBreadcrumb();

        return [
            $baseUrl => 'Dashboard',
            $resource::getUrl('view', ['record' => $record]) => $record->name,
            ...(filled($breadcrumb) ? [$breadcrumb] : []),
        ];
    }
}
