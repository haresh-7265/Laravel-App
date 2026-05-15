@props([
    'title'       => '',
    'open'        => false,
    'titleClass'  => 'text-lg font-semibold',
])

<div {{ $attributes->merge(['class' => 'mb-1']) }} x-data="{ open: {{ $open ? 'true' : 'false' }} }">

    {{-- Header / Toggle --}}
    <div class="flex items-center gap-2 mb-3 cursor-pointer select-none w-fit"
         @click="open = !open">

        <h3 class="{{ $titleClass }}">{{ $title }}</h3>

        {{-- Arrow next to title --}}
        <span class="text-sm transition-transform duration-300"
              :class="open ? 'rotate-180' : 'rotate-0'">
            ▼
        </span>
    </div>

    {{-- Collapsible Content --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2" class="flex overflow-x-auto gap-3 pb-2 snap-x snap-mandatory scrollbar-hide">
        {{ $slot }}
    </div>

</div>