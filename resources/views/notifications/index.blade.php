@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">

    {{-- Stats Row (Flat UI, no gradients or shadows) --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded-lg p-5 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Notifications</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-1" id="stat-total">0</h3>
            </div>
            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 text-lg">
                <i class="bi bi-bell"></i>
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-5 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Unread</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-1" id="stat-unread">0</h3>
            </div>
            <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 text-lg">
                <i class="bi bi-envelope"></i>
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-5 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Read</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-1" id="stat-read">0</h3>
            </div>
            <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center text-green-600 text-lg">
                <i class="bi bi-envelope-open"></i>
            </div>
        </div>
    </div>

    {{-- Panel Card --}}
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        
        {{-- Panel Header --}}
        <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Notification Panel</h2>
                <p class="text-xs text-gray-500 mt-0.5">Manage and view your system updates</p>
            </div>
            
            <button id="mark-all-btn"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold bg-gray-900 hover:bg-gray-800 text-white rounded-lg transition-colors focus:outline-none disabled:opacity-50">
                <i class="bi bi-check2-all"></i> Mark all read
            </button>
        </div>

        {{-- Filter Tabs (Flat UI) --}}
        <div class="px-6 border-b border-gray-100 bg-gray-50 flex gap-6">
            <button class="filter-tab py-3 text-xs font-bold border-b-2 transition-colors focus:outline-none" data-status="" id="tab-all">
                All (<span class="tab-count-all">0</span>)
            </button>
            <button class="filter-tab py-3 text-xs font-bold border-b-2 transition-colors focus:outline-none" data-status="unread" id="tab-unread">
                Unread (<span class="tab-count-unread">0</span>)
            </button>
            <button class="filter-tab py-3 text-xs font-bold border-b-2 transition-colors focus:outline-none" data-status="read" id="tab-read">
                Read (<span class="tab-count-read">0</span>)
            </button>
        </div>

        {{-- Loading Spinner --}}
        <div id="loading-spinner" class="hidden py-16 text-center text-gray-400 text-sm">
            <svg class="animate-spin h-5 w-5 text-gray-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            Loading notifications...
        </div>

        {{-- Grouped Notification List Container --}}
        <div id="notif-groups-container" class="p-6 divide-y divide-gray-100 space-y-6">
            {{-- Injected dynamically via JS --}}
        </div>

        {{-- Empty State --}}
        <div id="empty-state" class="hidden py-20 text-center">
            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                <i class="bi bi-bell-slash text-xl text-gray-400"></i>
            </div>
            <h4 class="text-sm font-bold text-gray-900">No Notifications</h4>
            <p class="text-xs text-gray-400 mt-1" id="empty-message">You don't have any notifications under this filter.</p>
        </div>

        {{-- Pagination footer --}}
        <div id="notif-pagination" class="border-t border-gray-100 px-6 py-4 flex items-center justify-between hidden bg-gray-50">
            <p class="text-xs text-gray-400" id="pagination-info"></p>
            <div class="flex gap-1" id="pagination-buttons"></div>
        </div>

    </div>
</div>

{{-- Notification Row Template --}}
<template id="row-template">
    <div class="notif-row flex items-start gap-4 p-4 hover:bg-gray-50 transition-colors cursor-pointer relative border border-gray-200 rounded-lg mb-3" data-id="">
        
        {{-- Blue left dot for unread --}}
        <div class="row-dot-container w-2 flex items-center justify-center h-full my-auto">
            <span class="row-dot w-2 h-2 rounded-full bg-blue-600 block" style="display:none;"></span>
        </div>

        {{-- Colored circular avatar --}}
        <div class="row-avatar w-10 h-10 rounded-full flex items-center justify-center text-base font-semibold flex-shrink-0">
            <i class="bi"></i>
        </div>

        {{-- Main body details --}}
        <div class="flex-1 min-w-0">
            <p class="text-sm text-gray-900 leading-snug row-message"></p>
            <p class="text-xs text-gray-500 mt-0.5 row-description"></p>
            
            <div class="flex items-center gap-2 mt-2 flex-wrap">
                <span class="row-category inline-block px-2 py-0.5 text-[10px] font-bold rounded"></span>
                <span class="text-[10px] text-gray-400 flex items-center gap-1">
                    <i class="bi bi-clock"></i>
                    <span class="row-time-text"></span>
                </span>
            </div>
        </div>

        {{-- Status Pill (● Unread / ✓ Read) & Actions --}}
        <div class="flex-shrink-0 flex items-center gap-2">
            <span class="row-status-pill inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"></span>
            
            <button type="button" class="row-delete-btn flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600 transition-colors focus:outline-none" title="Delete notification">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</template>

{{-- Pass config state --}}
<script>
window._notifState = {
    fetchUrl:  "{{ route('notifications.index') }}",
    readUrl:   "/notifications/__ID__/read",
    deleteUrl: "/notifications/__ID__",
    markAllUrl:"{{ route('notifications.markAllRead') }}",
    csrfToken: "{{ csrf_token() }}",
};
</script>
@endsection

@push('scripts')
@vite('resources/js/notifications.js')
@endpush