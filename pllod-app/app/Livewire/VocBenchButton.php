<?php

namespace App\Livewire;

use App\Models\ResourceFile;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class VocBenchButton extends Component
{
    public ResourceFile $file;

    public function mount(ResourceFile $file): void
    {
        $this->file = $file;
    }

    public function isButtonDisabled(): bool
    {
        return $this->file->validation_status !== \App\Enums\GeneralStatus::SUCCESS->value ||
        ! $this->file->can_be_opened_in_vocbench || $this->file->quality_assessment_status === \App\Enums\GeneralStatus::RUNNING->value;
    }

    #[On('refresh-voc-bench-button')]
    public function refreshVocBenchButton(): void
    {
        //
    }

    public function render(): View
    {
        return view('livewire.voc-bench-button');
    }

    public function openVocBench(): void
    {
        $user = filament()->auth()->user();

        if (! $user) {
            // User not authenticated, should ideally not happen in a Filament context.
            // Silently return, or add logging/notification if this state is critical.
            return;
        }

        $semanticTurkeyUser = $user->semanticTurkeyUser;

        if (! $semanticTurkeyUser || ! $semanticTurkeyUser->has_account) {
            // Semantic Turkey user data is missing or account is not active.
            // Silently return, or add logging/notification.
            // e.g., Log::info("User {$user->id} attempted to open VocBench without a valid ST account or ST account not active.");
            return;
        }

        $stEmail = $user->email; // Assuming the main user email is used for ST authentication.
        $stPassword = $semanticTurkeyUser->password; // Password stored in the SemanticTurkeyUser model.

        if (empty($stEmail) || empty($stPassword)) {
            // Essential Semantic Turkey credentials are not available.
            // Silently return, or add logging/notification.
            // e.g., Log::warning("User {$user->id} has incomplete ST credentials (email: '{$stEmail}', password present: " . !empty($stPassword) . ").");
            return;
        }

        $credentials = "{$stEmail}:{$stPassword}";
        $tokenEncoded = base64_encode($credentials);

        // The original `if (!$tokenEncoded)` check was ineffective because "email:password" (even with empty parts)
        // results in a non-empty string like ":" which base64_encodes to "Og==".
        // The `empty($stEmail) || empty($stPassword)` check above is more appropriate.

        $redirectUrl = $this->buildVocBenchAuthUrl($tokenEncoded);

        $this->dispatch("pllod-vocbench-redirect-uri-{$this->file->id}", $redirectUrl);
    }

    private function buildVocBenchAuthUrl(string $token): string
    {
        $baseUrl = rtrim(config('vocbench.auth_url'), '/');

        return "{$baseUrl}?token={$token}";
    }
}
