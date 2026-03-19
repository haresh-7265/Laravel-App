{{-- student-card anonymous component --}}

@props(['student'])

<div class="card shadow-sm h-100">
    <div class="card-body">
        <h5 class="card-title">{{ $student['name'] }}</h5>
        <p class="card-text text-muted">Roll No: {{ $student['roll'] }}</p>

        {{-- Conditional: Pass/Fail Badge --}}
        @if ($student['marks'] >= 33)
            <x-badge color="success" label="Pass" />
        @else
            <x-badge color="danger" label="Fail" />
        @endif

        <p class="mt-2 mb-0">Marks: <strong>{{ $student['marks'] }}</strong></p>
    </div>
</div>