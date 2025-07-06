<?php

namespace App\Console\Commands; // Changed namespace

use App\Enums\UserRole;
use Filament\Facades\Filament;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard; // Keep Guard for broader compatibility, but expect StatefulGuard features
use Illuminate\Contracts\Auth\StatefulGuard; // Import StatefulGuard
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[AsCommand(name: 'make:super-admin')]
class MakeSuperAdminCommand extends Command
{
    protected $description = 'Create a new super admin user';

    protected $signature = 'make:super-admin
                            {--name= : The name of the user}
                            {--last_name= : The last name of the user}
                            {--email= : A valid and unique email address}
                            {--password= : The password for the user (min. 8 characters)}';

    /**
     * @var array{'name': string | null, 'last_name': string | null, 'email': string | null, 'password': string | null}
     */
    protected array $options;

    /**
     * @return array{'name': string, 'last_name': string, 'email': string, 'password': string}
     */
    protected function getUserData(): array
    {
        return [
            'name' => $this->options['name'] ?? text(
                label: 'Name',
                required: true,
            ),

            'last_name' => $this->options['last_name'] ?? text(
                label: 'Last name',
                required: true,
            ),

            'email' => $this->options['email'] ?? text(
                label: 'Email address',
                required: true,
                validate: fn (string $email): ?string => match (true) {
                    ! filter_var($email, FILTER_VALIDATE_EMAIL) => 'The email address must be valid.',
                    static::getUserModel()::where('email', $email)->exists() => 'A user with this email address already exists',
                    default => null,
                },
            ),

            'password' => Hash::make($this->options['password'] ?? password(
                label: 'Password',
                required: true,
            )),
        ];
    }

    protected function createSuperAdmin(): Authenticatable
    {
        /** @var \App\Models\User $user */ // Assuming App\Models\User is the model
        $user = static::getUserModel()::createQuietly($this->getUserData());
        $user->assignRole([UserRole::SUPER_ADMIN->value]);

        return $user;
    }

    protected function sendSuccessMessage(Authenticatable $user): void
    {
        $loginUrl = Filament::getLoginUrl();

        // Assuming the User model has 'email' and 'name' attributes accessible
        $identifier = $user->email ?? $user->name ?? 'You';

        $this->components->info("Success! {$identifier} may now log in at {$loginUrl}");
    }

    protected function getAuthGuard(): StatefulGuard // Changed return type hint
    {
        // Ensure the guard returned implements StatefulGuard
        $guard = Filament::auth();
        if (! $guard instanceof StatefulGuard) {
            // Handle the case where the guard is not stateful if necessary,
            // though Filament typically uses stateful guards.
            // For now, we'll assume it is and let potential errors surface.
            // Alternatively, throw an exception or log a warning.
        }

        /** @var StatefulGuard $guard */
        return $guard;
    }

    protected function getUserProvider(): UserProvider
    {
        // getProvider is available on StatefulGuard
        return $this->getAuthGuard()->getProvider();
    }

    protected function getUserModel(): string
    {
        $provider = $this->getUserProvider();

        if (! $provider instanceof EloquentUserProvider) {
            throw new \RuntimeException('Cannot get user model: The configured user provider is not an Eloquent provider.');
        }

        return $provider->getModel();
    }

    public function handle(): int
    {
        $this->options = $this->options();

        if (! Filament::getCurrentPanel()) {
            $this->error('Filament has not been installed for the current panel context. Run: php artisan filament:install --panels');

            return static::INVALID;
        }

        try {
            $user = $this->createSuperAdmin();
            $this->sendSuccessMessage($user);
        } catch (\Throwable $e) {
            $this->error('Failed to create super admin user: '.$e->getMessage());

            return static::FAILURE;
        }

        return static::SUCCESS;
    }
}
