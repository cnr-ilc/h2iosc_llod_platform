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
use Illuminate\Support\Str;

class RegisterSemanticTurkeyUser implements ShouldQueue
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
            Log::info('Registering user in Semantic Turkey with email', ['email' => $this->user->email]);
            $generatedPassword = Str::random(16);
            Log::info('Generated password', ['password' => $generatedPassword]);
            $createdUser = SemanticTurkey::registerUser($this->user->email, $generatedPassword, $this->user->email, $this->user->email);
            Log::info('Created user in Semantic Turkey', ['user' => $createdUser]);

            $this->user->semanticTurkeyUser()->create([
                'has_account' => true,
                'password' => $generatedPassword,
                'user_id' => $this->user->id,
            ]);

        } catch (Exception $e) {
            Log::warning('Failed to register Semantic Turkey user, retrying in 10 seconds', ['user' => $this->user->email, 'error' => $e->getMessage()]);
            $this->release(10);
        }
    }
}
