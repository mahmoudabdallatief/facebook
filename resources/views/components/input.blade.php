@props(['type' => 'text', 'name', 'value' => '', 'label' => null, 'required' => false, 'autofocus' => false, 'autocomplete' => null])
@php($id = $attributes->get('id', $name))
<div {{ $attributes->only('class')->merge(['class' => 'space-y-1']) }}>
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-fb-muted dark:text-zinc-300">{{ $label }}</label>
    @endif
    <input
        type="{{ $type }}"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if($required) required @endif
        @if($autofocus) autofocus @endif
        @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        {{ $attributes->except(['class','id'])->merge(['class' => 'w-full rounded-lg border border-fb-border bg-white px-3 py-2 text-sm shadow-sm outline-none ring-fb-blue/30 focus:border-fb-blue focus:ring-4 dark:border-zinc-700 dark:bg-zinc-900']) }}
    >
    @error($name)
        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
