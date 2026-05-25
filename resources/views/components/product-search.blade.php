{{-- Search bar --}}
<div class="sticky top-16 z-20">
    <form method="GET" action="{{ route('products.index') }}" id="search-form">
        {{-- preserve existing filters --}}
        @foreach(request()->except('q', 'page') as $key => $value)
            @if(is_array($value))
                @foreach($value as $v)
                    <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach

        <div class="flex gap-2 mb-3">
            <div class="relative flex-1">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text"
                       name="q"
                       value="{{ request('q') }}"
                       placeholder="{{ __('products.search_placeholder', ['default' => 'Search products...']) }}"
                       class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-gray-400">
            </div>
            <button type="submit"
                    class="px-4 py-2 text-sm font-medium bg-gray-900 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <i class="bi bi-search"></i>
            </button>
            @if(request('q'))
                <a href="{{ route('products.index', request()->except('q', 'page')) }}"
                   class="px-3 py-2 text-sm border border-gray-200 rounded-lg text-gray-500 hover:bg-gray-50">
                    <i class="bi bi-x-lg"></i>
                </a>
            @endif
        </div>

        @if(request('q'))
            <p class="text-sm text-gray-400 mb-2">
                Results for <span class="font-medium text-gray-700">"{{ request('q') }}"</span>
            </p>
        @endif
    </form>
</div>