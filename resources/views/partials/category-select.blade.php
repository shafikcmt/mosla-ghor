{{--
    Grouped category dropdown. Expects:
      $categories — top-level Category models with `children` eager-loaded
      $selected   — currently selected category_id (nullable)
      $ring       — optional Tailwind focus-ring colour class (default brand green)
--}}
@php
    $selected = $selected ?? null;
    $ring = $ring ?? 'focus:ring-[#14532d]';
@endphp
<div class="md:col-span-2">
    <label class="block text-sm font-medium text-gray-700 mb-1" for="category_id">ক্যাটাগরি</label>
    <select name="category_id" id="category_id"
            class="w-full border rounded px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 {{ $ring }}">
        <option value="">— ক্যাটাগরি নির্বাচন করুন —</option>
        @foreach(($categories ?? []) as $parent)
            <optgroup label="{{ $parent->name_bn }}">
                <option value="{{ $parent->id }}"
                    {{ (string) old('category_id', $selected) === (string) $parent->id ? 'selected' : '' }}>
                    {{ $parent->name_bn }} (সব)
                </option>
                @foreach($parent->children as $child)
                    <option value="{{ $child->id }}"
                        {{ (string) old('category_id', $selected) === (string) $child->id ? 'selected' : '' }}>
                        — {{ $child->name_bn }}
                    </option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
    @error('category_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>
