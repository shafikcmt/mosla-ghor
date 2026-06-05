@props([
    'name',
    'label',
    'help' => null,
    'checked' => false,
    'value' => '1',
])

<label {{ $attributes->merge(['class' => 'flex items-start justify-between gap-3 py-2.5 cursor-pointer']) }}>
    <span class="min-w-0">
        <span class="block text-sm font-medium text-gray-800">{{ $label }}</span>
        @if($help)
            <span class="block text-xs text-gray-400 mt-0.5">{{ $help }}</span>
        @endif
    </span>
    <span class="relative inline-flex flex-shrink-0 mt-0.5">
        <input type="checkbox" name="{{ $name }}" value="{{ $value }}" class="sr-only peer"
               {{ old($name, $checked) ? 'checked' : '' }}>
        <span class="block w-10 h-6 bg-gray-300 rounded-full peer-checked:bg-[#14532d] transition-colors"></span>
        <span class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></span>
    </span>
</label>
