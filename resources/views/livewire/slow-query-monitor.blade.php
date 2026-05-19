<div class="min-h-screen bg-gray-50 p-6">

    {{-- Auto Refresh --}}
    @if($autoRefresh)
        <div wire:poll.10000ms="$refresh"></div>
    @endif

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <i class="bi bi-speedometer2 text-blue-600 text-2xl"></i>
                <h1 class="text-xl font-semibold text-gray-900">Slow Query Monitor</h1>
            </div>
            <p class="text-sm text-gray-500">
                Queries exceeding <span class="font-medium text-gray-700">{{ $min_time }}ms</span>
                in the last <span class="font-medium text-gray-700">{{ $hours }}</span> hours
            </p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            {{-- Auto Refresh Toggle --}}
            <label class="flex items-center gap-2 cursor-pointer select-none text-sm text-gray-600">
                <div class="relative">
                    <input type="checkbox" wire:model.live="autoRefresh" class="sr-only peer">
                    <div class="w-10 h-5 bg-gray-300 peer-checked:bg-blue-500 rounded-full transition-colors duration-200"></div>
                    <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200 peer-checked:translate-x-5"></div>
                </div>
                Auto-refresh (10s)
            </label>

            {{-- Loading Indicator --}}
            <div wire:loading class="flex items-center gap-1.5 text-blue-600 text-sm">
                <i class="bi bi-arrow-clockwise animate-spin text-base"></i>
                Loading…
            </div>

            {{-- Manual Refresh --}}
            <button wire:click="$refresh"
                class="inline-flex items-center gap-1.5 text-sm font-medium px-3 py-1.5 rounded-lg border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors">
                <i class="bi bi-arrow-clockwise"></i>
                Refresh
            </button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        <div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
            <p class="text-xs uppercase tracking-wide text-gray-400 font-medium mb-1 flex items-center gap-1">
                <i class="bi bi-stack text-blue-400"></i> Total
            </p>
            <p class="text-3xl font-semibold text-blue-600">{{ number_format($stats->total) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
            <p class="text-xs uppercase tracking-wide text-gray-400 font-medium mb-1 flex items-center gap-1">
                <i class="bi bi-clock text-amber-400"></i> Avg time
            </p>
            <p class="text-3xl font-semibold text-amber-500">
                {{ number_format($stats->avg_time, 2) }}<span class="text-base font-normal text-gray-400">ms</span>
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
            <p class="text-xs uppercase tracking-wide text-gray-400 font-medium mb-1 flex items-center gap-1">
                <i class="bi bi-arrow-up text-red-400"></i> Max time
            </p>
            <p class="text-3xl font-semibold text-red-500">
                {{ number_format($stats->max_time) }}<span class="text-base font-normal text-gray-400">ms</span>
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
            <p class="text-xs uppercase tracking-wide text-gray-400 font-medium mb-1 flex items-center gap-1">
                <i class="bi bi-arrow-down text-green-400"></i> Min time
            </p>
            <p class="text-3xl font-semibold text-green-500">
                {{ number_format($stats->min_time) }}<span class="text-base font-normal text-gray-400">ms</span>
            </p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 px-5 py-4 mb-5">
        <div class="flex flex-wrap gap-3 items-end" wire:key="filters-{{ $filterKey }}">

            {{-- Time Range --}}
            <div class="min-w-[140px] flex-1">
                <label class="block text-xs font-medium text-gray-400 uppercase tracking-wide mb-1.5">
                    <i class="bi bi-clock-history mr-1"></i>Time range
                </label>
                <select wire:model.live="hours"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-blue-400 transition">
                    <option value="1">Last 1 hour</option>
                    <option value="6">Last 6 hours</option>
                    <option value="12">Last 12 hours</option>
                    <option value="24">Last 24 hours</option>
                    <option value="48">Last 48 hours</option>
                </select>
            </div>

            {{-- Min Time --}}
            <div class="min-w-[110px] flex-1">
                <label class="block text-xs font-medium text-gray-400 uppercase tracking-wide mb-1.5">
                    <i class="bi bi-stopwatch mr-1"></i>Min time (ms)
                </label>
                <input type="number"
                    wire:model.live.debounce.500ms="min_time"
                    placeholder="100"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-blue-400 transition">
            </div>

            {{-- Connection --}}
            <div class="min-w-[130px] flex-1">
                <label class="block text-xs font-medium text-gray-400 uppercase tracking-wide mb-1.5">
                    <i class="bi bi-server mr-1"></i>Connection
                </label>
                <select wire:model.live="connection"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-blue-400 transition">
                    <option value="">All connections</option>
                    @foreach($connections as $conn)
                        <option value="{{ $conn }}">{{ $conn }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Method --}}
            <div class="min-w-[110px] flex-1">
                <label class="block text-xs font-medium text-gray-400 uppercase tracking-wide mb-1.5">
                    <i class="bi bi-signpost-split mr-1"></i>Method
                </label>
                <select wire:model.live="method"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-blue-400 transition">
                    <option value="">All methods</option>
                    @foreach(['GET','POST','PUT','PATCH','DELETE'] as $m)
                        <option value="{{ $m }}">{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            {{-- URL --}}
            <div class="min-w-[160px] flex-[2]">
                <label class="block text-xs font-medium text-gray-400 uppercase tracking-wide mb-1.5">
                    <i class="bi bi-link-45deg mr-1"></i>URL contains
                </label>
                <input type="text"
                    wire:model.live.debounce.500ms="url"
                    placeholder="/api/users"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-blue-400 transition">
            </div>

            {{-- Clear --}}
            <button type="button"
                wire:click="clearFilters"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-gray-200 bg-gray-50 text-gray-600 text-sm font-medium hover:bg-gray-100 transition-colors">
                <i class="bi bi-x-lg"></i>
                Clear
            </button>

        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" wire:loading.class="opacity-50 pointer-events-none">

        @if($slowQueries->isEmpty())
            <div class="text-center py-20">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-green-50 mb-4">
                    <i class="bi bi-check-lg text-green-500 text-2xl"></i>
                </div>
                <p class="text-gray-700 font-medium text-lg">No slow queries found</p>
                <p class="text-gray-400 text-sm mt-1">All queries are performing within the threshold.</p>
            </div>
        @else

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">

                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left w-[110px]">
                            <button wire:click="applySort('time_ms')"
                                class="inline-flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-gray-400 hover:text-gray-700 transition-colors {{ $sortBy === 'time_ms' ? 'text-blue-600' : '' }}">
                                <i class="bi bi-clock"></i> Time
                                @if($sortBy === 'time_ms')
                                    <i class="bi bi-arrow-{{ $sortDir === 'desc' ? 'down' : 'up' }}"></i>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                            <span class="inline-flex items-center gap-1"><i class="bi bi-code-square"></i> SQL</span>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                            <span class="inline-flex items-center gap-1"><i class="bi bi-link-45deg"></i> URL / Route</span>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400 w-[85px]">
                            <span class="inline-flex items-center gap-1"><i class="bi bi-signpost-split"></i> Method</span>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400 w-[100px]">
                            <span class="inline-flex items-center gap-1"><i class="bi bi-server"></i> Connection</span>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400 w-[80px]">
                            <span class="inline-flex items-center gap-1"><i class="bi bi-person"></i> User</span>
                        </th>
                        <th class="px-4 py-3 text-left w-[120px]">
                            <button wire:click="applySort('created_at')"
                                class="inline-flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-gray-400 hover:text-gray-700 transition-colors {{ $sortBy === 'created_at' ? 'text-blue-600' : '' }}">
                                <i class="bi bi-calendar3"></i> At
                                @if($sortBy === 'created_at')
                                    <i class="bi bi-arrow-{{ $sortDir === 'desc' ? 'down' : 'up' }}"></i>
                                @endif
                            </button>
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-50">
                    @foreach($slowQueries as $q)
                    <tr class="hover:bg-gray-50 transition-colors">

                        {{-- Time --}}
                        <td class="px-4 py-3 whitespace-nowrap font-semibold tabular-nums">
                            @if($q->time_ms >= 1000)
                                <span class="inline-flex items-center gap-1 text-red-600">
                                    <i class="bi bi-exclamation-circle text-xs"></i>
                                    {{ number_format($q->time_ms) }} ms
                                </span>
                            @elseif($q->time_ms >= 500)
                                <span class="inline-flex items-center gap-1 text-orange-500">
                                    <i class="bi bi-exclamation-triangle text-xs"></i>
                                    {{ number_format($q->time_ms) }} ms
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-amber-500">
                                    <i class="bi bi-clock text-xs"></i>
                                    {{ number_format($q->time_ms) }} ms
                                </span>
                            @endif
                        </td>

                        {{-- SQL --}}
                        <td class="px-4 py-3 max-w-xs">
                            <code class="block bg-gray-100 text-gray-700 rounded px-2 py-1 text-xs truncate font-mono leading-relaxed"
                                title="{{ $q->sql }}">
                                {{ $q->sql }}
                            </code>
                        </td>

                        {{-- URL --}}
                        <td class="px-4 py-3 max-w-[180px]">
                            <span class="block truncate text-xs text-gray-500 font-mono" title="{{ $q->url }}">
                                {{ $q->url ?? '—' }}
                            </span>
                        </td>

                        {{-- Method --}}
                        <td class="px-4 py-3">
                            @if($q->method)
                                @php
                                    $methodStyles = [
                                        'GET'    => 'bg-blue-50 text-blue-700 border border-blue-100',
                                        'POST'   => 'bg-green-50 text-green-700 border border-green-100',
                                        'PUT'    => 'bg-amber-50 text-amber-700 border border-amber-100',
                                        'PATCH'  => 'bg-orange-50 text-orange-700 border border-orange-100',
                                        'DELETE' => 'bg-red-50 text-red-700 border border-red-100',
                                    ];
                                    $style = $methodStyles[$q->method] ?? 'bg-gray-100 text-gray-600 border border-gray-200';
                                @endphp
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $style }}">
                                    {{ $q->method }}
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Connection --}}
                        <td class="px-4 py-3 text-xs text-gray-400">
                            @if($q->connection)
                                <span class="inline-flex items-center gap-1">
                                    <i class="bi bi-hdd-network text-gray-300 text-xs"></i>
                                    {{ $q->connection }}
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- User --}}
                        <td class="px-4 py-3 text-xs">
                            @if($q->user_id)
                                <span class="inline-flex items-center gap-1 text-gray-500">
                                    <i class="bi bi-person-circle text-gray-300 text-xs"></i>
                                    #{{ $q->user_id }}
                                </span>
                            @else
                                <span class="text-gray-400 italic">Guest</span>
                            @endif
                        </td>

                        {{-- Time ago --}}
                        <td class="px-4 py-3 text-xs text-gray-400 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1">
                                <i class="bi bi-clock-history text-gray-300 text-xs"></i>
                                {{ \Carbon\Carbon::parse($q->created_at)->diffForHumans() }}
                            </span>
                        </td>

                    </tr>
                    @endforeach
                </tbody>

            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between flex-wrap gap-3">
            <p class="text-xs text-gray-400">
                Showing
                <span class="font-medium text-gray-600">{{ $slowQueries->firstItem() }}–{{ $slowQueries->lastItem() }}</span>
                of
                <span class="font-medium text-gray-600">{{ number_format($slowQueries->total()) }}</span>
                results
            </p>
            <div>
                {{ $slowQueries->links() }}
            </div>
        </div>

        @endif
    </div>

</div>