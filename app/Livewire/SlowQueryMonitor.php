<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class SlowQueryMonitor extends Component
{
    use WithPagination;

    // Filters
    public string $connection = '';

    public string $method = '';

    public string $url = '';

    public int $min_time = 100;

    public int $hours = 24;

    public int $filterKey = 0;

    // Auto-refresh
    public bool $autoRefresh = false;

    // Sort
    public string $sortBy = 'time_ms';

    public string $sortDir = 'desc';

    // Reset pagination when filters change
    public function updatedConnection()
    {
        $this->resetPage();
    }

    public function updatedMethod()
    {
        $this->resetPage();
    }

    public function updatedUrl()
    {
        $this->resetPage();
    }

    public function updatedMinTime()
    {
        $this->resetPage();
    }

    public function updatedHours()
    {
        $this->resetPage();
    }

    public function applySort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'desc';
        }
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('connection', 'method', 'url', 'min_time', 'hours');
        $this->filterKey++;
        $this->resetPage();
    }

    public function render()
    {
        $baseQuery = DB::connection('analytics')
            ->table('slow_queries')
            ->where('created_at', '>=', now()->subHours($this->hours))
            ->where('time_ms', '>=', $this->min_time);

        if ($this->connection) {
            $baseQuery->where('connection', $this->connection);
        }
        if ($this->method) {
            $baseQuery->where('method', $this->method);
        }
        if ($this->url) {
            $baseQuery->where('url', 'like', '%'.$this->url.'%');
        }

        $slowQueries = (clone $baseQuery)
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(20);

        $stats = (clone $baseQuery)
            ->selectRaw('
                COUNT(*)                                    as total,
                ROUND(AVG(time_ms)::numeric, 2)             as avg_time,
                ROUND(MAX(time_ms)::numeric, 2)             as max_time,
                ROUND(MIN(time_ms)::numeric, 2)             as min_time
            ')
            ->first();
            
        $connections = Cache::remember('slow_queries_connections', 3600, function () {
            return DB::connection('analytics')
                ->table('slow_queries')
                ->where('created_at', '>=', now()->subDays(7)) // Look at recent data to find active connections
                ->distinct()
                ->pluck('connection');
        });

        return view('livewire.slow-query-monitor', compact('slowQueries', 'stats', 'connections'));
    }
}
