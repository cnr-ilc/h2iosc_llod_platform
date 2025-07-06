<x-filament-panels::page wire:poll.5000ms>
    @each('filament.components.file.item', $files, 'file')
</x-filament-panels::page>
