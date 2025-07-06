<footer
    class="flex flex-col md:flex-row gap-y-4 md:gap-y-0 items-start justify-between gap-x-4 bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-700 dark:ring-white/10 md:px-6 lg:px-8 w-full">
    <div class="flex gap-4 flex-col items-center md:items-start">
        <img src="{{ Vite::asset('resources/images/h2iosc-logo-colored.png') }}" alt="H2IOSC Logo" width="220"
            height="100" fetchpriority="low" loading="lazy">
        <p class="text-sm max-w-screen-md">
            {{ __('filament.footer.text') }}
        </p>
    </div>
    <div class="flex items-center">
        <p class="text-sm">
            {{ __('filament.footer.copyright', ['year' => now()->year]) }}
        </p>
    </div>
</footer>
