@php
    use Filament\Facades\Filament;
    use App\Enums\UserRole;
    use Illuminate\Support\Facades\Auth;

    $user = Auth::user();
    $currentPanel = Filament::getCurrentPanel();
    $currentPanelId = $currentPanel->getId();

    $adminPanel = Filament::getPanel('admin');
    $userPanel = Filament::getPanel('user');

    $adminPanelUrl = $adminPanel->getUrl();
    $userPanelUrl = $userPanel->getUrl();
@endphp

@if ($user->hasExactRoles([UserRole::ADMIN->value, UserRole::USER->value]))
    @if ($currentPanelId === 'admin')
        <x-filament::button
            href="{{ $userPanelUrl }}"
            tag="a"
            class="bg-button-gradient hover:bg-button-gradient-hover text-white"
        >
            {{ __('User Panel') }}
        </x-filament::button>
    @endif
    @if ($currentPanelId === 'user')
        <x-filament::button
            href="{{ $adminPanelUrl }}"
            tag="a"
            class="bg-button-gradient hover:bg-button-gradient-hover text-white"
        >
            {{ __('Admin Panel') }}
        </x-filament::button>
    @endif
@endif