@props(['href', 'label' => 'Back'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'btn btn-outline-secondary btn-sm']) }}>
    &larr; {{ $label }}
</a>
