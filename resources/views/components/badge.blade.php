{{-- badge anonymous component --}}
@props(['color' => 'secondary', 'label' => ''])

<span class="badge bg-{{ $color }}">{{ $label }}</span>