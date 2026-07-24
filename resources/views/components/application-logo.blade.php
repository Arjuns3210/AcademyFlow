@props(['size' => 'fs-3', 'variant' => 'primary'])

@php
    $colorClass = $variant === 'light' ? 'text-white' : 'text-primary';
@endphp

<span {{ $attributes->merge(['class' => "{$size} fw-bold {$colorClass} d-inline-flex align-items-center gap-2"]) }}>
    <i class="bi bi-mortarboard-fill"></i>
    <span>AcademyFlow</span>
</span>
