@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="max-w-3xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-white flex items-center gap-2">
            <i class="bi bi-bell-fill text-blue-400"></i> Notifications
                </h1>

        @if($notifications->contains(fn($n) => $n->read_at === null))
                <form action="{{ route('notifications.markAllRead') }}" method="POST">
                    @csrf
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-500 text-white text-sm px-4 py-2 rounded-lg transition flex items-center gap-1.5">
                    <i class="bi bi-check2-all"></i> Mark all as read
                    </button>
                </form>
            @endif
        </div>

    {{-- List --}}
        @forelse($notifications as $notification)
            @php
                $data = $notification->data;
                $iconName = $data['icon'] ?? 'default';
                $iconMap = [
                'truck'   => ['icon' => 'bi-truck',        'bg' => '#1e3a5f', 'color' => '#60a5fa'],
                'order'   => ['icon' => 'bi-bag-check',    'bg' => '#1a3a2a', 'color' => '#34d399'],
                'alert'   => ['icon' => 'bi-exclamation-triangle', 'bg' => '#3a2a1a', 'color' => '#fbbf24'],
                'default' => ['icon' => 'bi-bell',         'bg' => '#2d2d3d', 'color' => '#a78bfa'],
                ];
                $ic = $iconMap[$iconName] ?? $iconMap['default'];
            @endphp

            <a href="{{ route('notifications.markAsRead', $notification->id) }}"
               onclick="event.preventDefault(); document.getElementById('notif-form-{{ $notification->id }}').submit();"
           class="flex items-start gap-3 px-4 py-3 rounded-xl mb-2 transition
                  {{ $notification->unread() ? 'bg-gray-800 border border-blue-500/30' : 'bg-gray-800/50 border border-transparent' }}
                  hover:bg-gray-700">

                {{-- Icon --}}
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 text-lg"
                 style="background:{{ $ic['bg'] }}; color:{{ $ic['color'] }};">
                    <i class="bi {{ $ic['icon'] }}"></i>
                </div>

                {{-- Body --}}
            <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-200 leading-snug">
                    {{ $data['message'] ?? 'New notification' }}
                </p>
                <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                            <i class="bi bi-clock"></i>
                            {{ $notification->created_at->diffForHumans() }}
                </p>
                </div>

            {{-- Unread dot --}}
                @if($notification->unread())
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500 flex-shrink-0 mt-1.5"></span>
                @endif
            </a>

            {{-- Hidden PATCH form --}}
            <form id="notif-form-{{ $notification->id }}"
                  action="{{ route('notifications.markAsRead', $notification->id) }}"
                  method="POST" style="display:none;">
                @csrf @method('PATCH')
            </form>
        @empty
        <div class="text-center py-16 text-gray-500">
            <i class="bi bi-bell-slash text-4xl block mb-3 opacity-50"></i>
            <p class="text-lg">No notifications yet</p>
            <p class="text-sm mt-1">You'll see order updates and alerts here.</p>
            </div>
        @endforelse

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
