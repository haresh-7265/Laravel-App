@extends('layouts.app')

    @push('styles')
    <style>
        .priority-radio:checked + .priority-card { border-width: 2px; }
        .priority-radio:checked + .priority-card.card-low    { border-color: #16a34a; background-color: #f0fdf4; }
        .priority-radio:checked + .priority-card.card-medium { border-color: #d97706; background-color: #fffbeb; }
        .priority-radio:checked + .priority-card.card-high   { border-color: #ea580c; background-color: #fff7ed; }
        .priority-radio:checked + .priority-card.card-urgent { border-color: #dc2626; background-color: #fef2f2; }
        .priority-radio:focus-visible + .priority-card { outline: 2px solid #3b82f6; outline-offset: 2px; }
    </style>
    @endpush


@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-12">

        {{-- Left aside --}}
        <aside class="lg:col-span-1 space-y-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 mb-2">Get help fast</h1>
                <p class="text-sm text-gray-500 leading-relaxed">
                    Submit your issue and our support team is notified instantly via Slack.
                    We triage every ticket and respond based on priority.
                </p>
            </div>

            {{-- Response times --}}
            <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100 overflow-hidden">
                <div class="px-4 py-3 bg-gray-50">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Response times</p>
                </div>
                @foreach([
                    ['color' => 'bg-red-500',    'label' => 'Urgent', 'time' => 'Within 1 hour'],
                    ['color' => 'bg-orange-500',  'label' => 'High',   'time' => 'Within 4 hours'],
                    ['color' => 'bg-amber-500',   'label' => 'Medium', 'time' => 'Within 1 business day'],
                    ['color' => 'bg-green-500',   'label' => 'Low',    'time' => 'Within 3 business days'],
                ] as $rt)
                    <div class="px-4 py-3 flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full {{ $rt['color'] }} flex-shrink-0"></span>
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $rt['label'] }}</p>
                            <p class="text-xs text-gray-400">{{ $rt['time'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Info note --}}
            <div class="flex gap-2.5 bg-blue-50 border border-blue-100 rounded-xl p-4">
                <i class="bi bi-slack text-blue-500 text-base flex-shrink-0 mt-0.5"></i>
                <p class="text-xs text-blue-700 leading-relaxed">
                    Your ticket is posted to our internal Slack channel so the right person picks it up immediately.
                </p>
            </div>
        </aside>

        {{-- Form card --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

                {{-- Card header --}}
                <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-ticket-perforated-fill text-white text-base"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Submit a support ticket</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Fields marked <span class="text-red-500">*</span> are required</p>
                    </div>
                </div>

                {{-- Alerts --}}
                <div class="px-6 pt-5 space-y-3">
                    @if(session('success'))
                        <div class="flex items-start gap-3 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">
                            <i class="bi bi-check-circle-fill text-green-500 text-base flex-shrink-0 mt-0.5"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm">
                            <i class="bi bi-exclamation-circle-fill text-red-500 text-base flex-shrink-0 mt-0.5"></i>
                            <span>Please fix the errors below and try again.</span>
                        </div>
                    @endif
                </div>

                {{-- Form --}}
                <form
                    id="ticket-form"
                    action="{{ route('support.tickets.store') }}"
                    method="POST"
                    novalidate
                    class="px-6 pb-6 pt-5 space-y-5"
                >
                    @csrf

                    {{-- Name + Email --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Name --}}
                        <div class="space-y-1.5">
                            <label for="customer_name" class="block text-sm font-medium text-gray-700">
                                Your name <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <i class="bi bi-person text-gray-400 text-sm"></i>
                                </div>
                                <input
                                    type="text"
                                    id="customer_name"
                                    name="customer_name"
                                    value="{{ old('customer_name') }}"
                                    placeholder="Jane Smith"
                                    autocomplete="name"
                                    class="block w-full pl-9 pr-3 py-2.5 text-sm rounded-lg border transition
                                        focus:outline-none focus:ring-2 focus:ring-opacity-20
                                        {{ $errors->has('customer_name')
                                            ? 'border-red-400 bg-red-50 focus:ring-red-400 focus:border-red-400'
                                            : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }}"
                                >
                            </div>
                            @error('customer_name')
                                <p class="flex items-center gap-1 text-xs text-red-600">
                                    <i class="bi bi-exclamation-circle"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="space-y-1.5">
                            <label for="customer_email" class="block text-sm font-medium text-gray-700">
                                Email address <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <i class="bi bi-envelope text-gray-400 text-sm"></i>
                                </div>
                                <input
                                    type="email"
                                    id="customer_email"
                                    name="customer_email"
                                    value="{{ old('customer_email') }}"
                                    placeholder="jane@example.com"
                                    autocomplete="email"
                                    class="block w-full pl-9 pr-3 py-2.5 text-sm rounded-lg border transition
                                        focus:outline-none focus:ring-2 focus:ring-opacity-20
                                        {{ $errors->has('customer_email')
                                            ? 'border-red-400 bg-red-50 focus:ring-red-400 focus:border-red-400'
                                            : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }}"
                                >
                            </div>
                            @error('customer_email')
                                <p class="flex items-center gap-1 text-xs text-red-600">
                                    <i class="bi bi-exclamation-circle"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>

                    </div>

                    {{-- Subject --}}
                    <div class="space-y-1.5">
                        <label for="subject" class="block text-sm font-medium text-gray-700">
                            Subject <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="bi bi-chat-text text-gray-400 text-sm"></i>
                            </div>
                            <input
                                type="text"
                                id="subject"
                                name="subject"
                                value="{{ old('subject') }}"
                                placeholder="Brief description of your issue"
                                class="block w-full pl-9 pr-3 py-2.5 text-sm rounded-lg border transition
                                    focus:outline-none focus:ring-2 focus:ring-opacity-20
                                    {{ $errors->has('subject')
                                        ? 'border-red-400 bg-red-50 focus:ring-red-400 focus:border-red-400'
                                        : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }}"
                            >
                        </div>
                        @error('subject')
                            <p class="flex items-center gap-1 text-xs text-red-600">
                                <i class="bi bi-exclamation-circle"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Body --}}
                    <div class="space-y-1.5">
                        <label for="body" class="block text-sm font-medium text-gray-700">
                            Details
                            <span class="text-gray-400 font-normal ml-1">(optional)</span>
                        </label>
                        <textarea
                            id="body"
                            name="body"
                            rows="5"
                            placeholder="Describe your issue in detail — steps to reproduce, expected vs actual behaviour, error messages…"
                            class="block w-full px-3 py-2.5 text-sm rounded-lg border resize-y transition leading-relaxed
                                focus:outline-none focus:ring-2 focus:ring-opacity-20
                                {{ $errors->has('body')
                                    ? 'border-red-400 bg-red-50 focus:ring-red-400 focus:border-red-400'
                                    : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }}"
                        >{{ old('body') }}</textarea>
                        @error('body')
                            <p class="flex items-center gap-1 text-xs text-red-600">
                                <i class="bi bi-exclamation-circle"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Priority --}}
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            Priority <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @php
                                $priorities = [
                                    'low'    => ['icon' => 'bi-arrow-down-circle',  'color' => 'text-green-600'],
                                    'medium' => ['icon' => 'bi-dash-circle',         'color' => 'text-amber-500'],
                                    'high'   => ['icon' => 'bi-arrow-up-circle',     'color' => 'text-orange-500'],
                                    'urgent' => ['icon' => 'bi-exclamation-circle',  'color' => 'text-red-600'],
                                ];
                            @endphp

                            @foreach($priorities as $level => $meta)
                                <div>
                                    <input
                                        type="radio"
                                        id="priority_{{ $level }}"
                                        name="priority"
                                        value="{{ $level }}"
                                        class="sr-only priority-radio"
                                        {{ old('priority', 'medium') === $level ? 'checked' : '' }}
                                    >
                                    <label
                                        for="priority_{{ $level }}"
                                        class="priority-card card-{{ $level }} flex flex-col items-center gap-2 py-3.5 px-2 rounded-xl border border-gray-200 cursor-pointer hover:border-gray-400 transition-all text-center select-none"
                                    >
                                        <i class="bi {{ $meta['icon'] }} {{ $meta['color'] }} text-xl"></i>
                                        <span class="text-xs font-semibold text-gray-700 capitalize">{{ $level }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('priority')
                            <p class="flex items-center gap-1 text-xs text-red-600">
                                <i class="bi bi-exclamation-circle"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Submit --}}
                    <div class="border-t border-gray-100 pt-4 space-y-3">
                        <button
                            type="submit"
                            id="submit-btn"
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl
                                bg-blue-600 hover:bg-blue-700 active:scale-[.98]
                                disabled:opacity-60 disabled:cursor-not-allowed
                                text-white text-sm font-semibold transition-all duration-150
                                focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2"
                        >
                            <i class="bi bi-send-fill text-sm" id="btn-icon"></i>
                            <span id="btn-text">Submit ticket</span>
                        </button>

                    </div>

                </form>
            </div>
        </div>

    </div>

@push('scripts')
<script>
    document.getElementById('ticket-form').addEventListener('submit', function () {
        const btn  = document.getElementById('submit-btn');
        const icon = document.getElementById('btn-icon');
        const text = document.getElementById('btn-text');
        btn.disabled     = true;
        icon.className   = 'bi bi-arrow-repeat animate-spin text-sm';
        text.textContent = 'Submitting…';
    });
</script>
@endpush


@endsection