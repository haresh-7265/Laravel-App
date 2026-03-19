@php
    $students = $students ?? [];
    $semester = $semester ?? null;
@endphp

{{-- Extend the Layout --}}
@extends('layouts.app')

{{-- Section: title --}}
@section('title', 'Students - ' . config('app.name'))

{{-- Section: header --}}
@section('header')
    <h2>🎓 Student Dashboard</h2>
    <p class="text-muted">Manage and view all enrolled students</p>
@endsection

{{-- Section: content --}}
@section('content')

    {{-- Component: Alert --}}
    <x-alert type="info" message="Showing all registered students for Semester-{{ $semester }}" />

    {{-- Conditional: students existance check --}}
    @if (count($students) > 0)

        <p class="mb-3">Total Students: <strong>{{ count($students) }}</strong></p>

        <div class="row g-3">

            {{-- Loop: foreach --}}
            @foreach ($students as $student)
                <div class="col-md-4">

                    {{-- Component: Student Card --}}
                    <x-student-card :student="$student" />

                </div>
            @endforeach

        </div>

    @else
        <x-alert type="warning" message="No students found." />
    @endif


    {{-- Loop: for --}}
    <h4 class="mt-5">📋 Rank List</h4>
    <ol class="mt-2">
        @for ($i = 0; $i < count($students); $i++)
            <li>{{ $students[$i]['name'] }} — {{ $students[$i]['marks'] }} marks</li>
        @endfor
    </ol>

    {{-- Conditional: Switch --}}
    <h4 class="mt-5">📊 Semester Status</h4>
    @switch($semester)
        @case(1)
            <p>Currently in <strong>Semester 1</strong> — Foundation Year</p>
            @break
        @case(2)
            <p>Currently in <strong>Semester 2</strong> — Core Subjects</p>
            @break
        @default
            <p>Semester information not available.</p>
    @endswitch

@endsection
