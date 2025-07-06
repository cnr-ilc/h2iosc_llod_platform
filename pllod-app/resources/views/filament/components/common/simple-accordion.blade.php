<div x-data="{ open: false }" class="overflow-hidden">
    <button 
        type="button"
        x-on:click="open = !open"
        class="flex items-center hover:underline gap-1"
        :aria-expanded="open"
    >
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"
            class="w-5 h-5 text-gray-500 transition-transform duration-200"
            x-bind:class="{ 'rotate-180': !open }">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M11.7655 10.1655C11.6155 10.3155 11.4121 10.3998 11.1999 10.3998C10.9878 10.3998 10.7844 10.3155 10.6343 10.1655L7.99994 7.53113L5.36554 10.1655C5.21465 10.3113 5.01257 10.3919 4.80281 10.3901C4.59306 10.3882 4.39241 10.3041 4.24408 10.1558C4.09575 10.0075 4.01162 9.8068 4.0098 9.59705C4.00797 9.38729 4.08861 9.18521 4.23434 9.03433L7.43434 5.83433C7.58436 5.68435 7.7878 5.6001 7.99994 5.6001C8.21207 5.6001 8.41551 5.68435 8.56554 5.83433L11.7655 9.03433C11.9155 9.18435 11.9998 9.3878 11.9998 9.59993C11.9998 9.81206 11.9155 10.0155 11.7655 10.1655Z" fill="#6B7280" />
        </svg>
        <span class="text-gray-600 text-sm leading-none" x-text="open ? '{{ __('Hide details') }}' : '{{ __('Show details') }}'">
        </span>
    </button>
    
    <div 
        x-show="open" 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform -translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform -translate-y-2"
        class="pt-4 pl-6"
    >
        {{ $slot }}
    </div>
</div>