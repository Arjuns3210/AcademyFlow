@props(['label', 'name', 'type' => 'text', 'value' => null, 'required' => false])

<div class="mb-3">
    <x-input-label :for="$name" :value="$label" />
    <x-text-input
        :id="$name"
        :name="$name"
        :type="$type"
        :value="old($name, $value)"
        :required="$required"
        {{ $attributes }}
    />
    <x-input-error :messages="$errors->get($name)" />
</div>
