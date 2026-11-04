<?php

namespace App\Jobs;

use App\Facades\SemanticTurkey;
use App\Models\User;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ActivateSemanticTurkeyUser implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Activating Semantic Turkey user', ['user' => $this->user->email]);

            $adminLogin = SemanticTurkey::login(config('semanticTurkey.admin_email'), config('semanticTurkey.admin_password'));
            $adminCookie = $adminLogin['cookie'];

            $activateUser = SemanticTurkey::enableUser($this->user->email, $adminCookie);

            $this->user->semanticTurkeyUser()->update(['is_active' => true]);

            Log::info('Semantic Turkey user activated', ['user' => $this->user->email, 'activateUser' => $activateUser]);
        } catch (Exception $e) {
            Log::warning('Failed to activate Semantic Turkey user, retrying in 10 seconds', ['user' => $this->user->email, 'error' => $e->getMessage()]);
            $this->release(10);
        }
    }
}
