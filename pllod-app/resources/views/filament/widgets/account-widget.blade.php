@php
    $user = filament()->auth()->user();
@endphp

<x-filament-widgets::widget class="fi-account-widget">
    <div class="flex items-center gap-x-3 p-6 bg-white rounded-xl ring-1 ring-gray-950/5 shadow-md">
        <x-filament-panels::avatar.user size="lg" :user="$user" />
        <div class="flex-1 flex flex-col gap-y-1">
            <h2 class="grid flex-1 text-base font-semibold leading-6 text-gray-950 dark:text-white">
                {{ __('filament-panels::widgets/account-widget.welcome', ['app' => config('app.name')]) }},
                {{ filament()->getUserName($user) }}
            </h2>
            <form action="{{ filament()->getLogoutUrl() }}" method="post">
                @csrf
                <button type="submit" class="text-sm text-gray-500 dark:text-gray-400 hover:underline"
                    aria-label="{{ __('filament-panels::widgets/account-widget.actions.logout.label') }}">
                    {{ __('filament-panels::widgets/account-widget.actions.logout.label') }}
                </button>
            </form>
        </div>
    </div>
</x-filament-widgets::widget>
