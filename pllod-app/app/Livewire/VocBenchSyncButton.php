<?php

namespace App\Livewire;

use App\Jobs\UpdateResourceFileFromGraphDB;
use App\Models\ResourceFile;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class VocBenchSyncButton extends Component
{
    public ResourceFile $file;

    public function mount(ResourceFile $file): void
    {
        $this->file = $file;
    }

    #[On('refresh-voc-bench-sync-button')]
    public function refreshVocBenchSyncButton(): void
    {
        $this->file->refresh();
    }

    public function render(): View
    {
        return view('livewire.voc-bench-sync-button');
    }

    public function syncWithVocBench(): void
    {
        Log::info('syncWithVocBench method called', ['file_id' => $this->file->id]);

        // Check if sync is possible
        if (empty($this->file->graphdb_repository_name) || $this->file->is_synchronising) {
            Log::warning('Sync not possible', [
                'file_id' => $this->file->id,
                'repository_name' => $this->file->graphdb_repository_name,
                'is_synchronising' => $this->file->is_synchronising,
            ]);
        }

        Log::info('Dispatching UpdateResourceFileFromGraphDB job', ['file_id' => $this->file->id]);

        // Mark as synchronising
        $this->file->is_synchronising = true;
        $this->file->save();

        try {
            // Dispatch the UpdateResourceFileFromGraphDB job
            dispatch(new UpdateResourceFileFromGraphDB($this->file));

            // Show success notification
            Notification::make()
                ->success()
                ->title(__('Sync Completed'))
                ->body(__('Sync with VocBench completed successfully!'))
                ->send();

            // Wait minimum 2 seconds to show animation
            sleep(2);

            // Refresh file to check final status
            $this->file->refresh();

            // Refresh both VocBench components
            $this->dispatch('refresh-voc-bench-button');
            $this->dispatch('refresh-voc-bench-sync-button');

        } catch (\Exception $e) {
            Log::error('Sync with VocBench failed', [
                'file_id' => $this->file->id,
                'error' => $e->getMessage(),
            ]);

            // Show error notification
            Notification::make()
                ->danger()
                ->title(__('Sync Failed'))
                ->body(__('Sync with VocBench failed: ').$e->getMessage())
                ->send();
        } finally {
            $this->file->is_submittable = false;
            $this->file->save();
        }
    }
}
