@props(['href', 'label' => 'Back'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'btn btn-outline-danger btn-sm']) }}>
    &larr; {{ $label }}
</a>
