<div {{ $attributes->class(['fi-form-section-simple-collapsible-section flex flex-col gap-4 w-full']) }}
    x-data="{
        isOpen: @js($isOpen),
        toggle() {
            this.isOpen = !this.isOpen
        }
    }" x-bind:class="isOpen && 'fi-collapsed'">
    <div type="button" class="flex items-center gap-2 cursor-pointer" x-on:click="toggle" aria-expanded="false"
        x-on:keydown.enter="toggle" x-on:keydown.space="toggle" tabindex="0" x-bind:aria-expanded="isOpen.toString()">
        <x-heroicon-o-chevron-down class="h-5 w-5 stroke-2" x-bind:class="{ 'rotate-180': isOpen }" />
        <h5 class="text-lg font-medium">{{ $getTitle() }}</h5>
    </div>
    <div x-cloak x-show="isOpen">
        {{ $getChildComponentContainer() }}
    </div>
</div>
