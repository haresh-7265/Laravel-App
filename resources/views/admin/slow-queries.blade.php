@extends('layouts.app')

@section('title', 'Slow Queries')

@section('content')
    @livewire('slow-query-monitor')
@endsection