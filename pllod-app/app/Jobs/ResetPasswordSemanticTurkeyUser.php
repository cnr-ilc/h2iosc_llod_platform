<?php

namespace App\Jobs;

use App\Facades\SemanticTurkey;
use App\Models\User;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ResetPasswordSemanticTurkeyUser implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, Queueable, SerializesModels;

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
            Log::info('Resetting password for user in Semantic Turkey with email', ['email' => $this->user->email]);
            $adminLogin = SemanticTurkey::login(config('semanticTurkey.admin_email'), config('semanticTurkey.admin_password'));
            $adminCookie = $adminLogin['cookie'];
            $newPassword = Str::random(16);
            $resetPassword = SemanticTurkey::forcePassword($this->user->email, $newPassword, $adminCookie);

            $semanticTurkeyUser = $this->user->semanticTurkeyUser;
            $semanticTurkeyUser->password = $newPassword;
            $semanticTurkeyUser->save();

            Log::info('Password has been changed for user in Semantic Turkey', ['email' => $this->user->email, 'resetPassword' => $resetPassword]);
        } catch (Exception $e) {
            Log::warning('Failed to reset password for user in Semantic Turkey, retrying in 10 seconds', ['email' => $this->user->email, 'error' => $e->getMessage()]);
            $this->release(10);
        }
    }
}
