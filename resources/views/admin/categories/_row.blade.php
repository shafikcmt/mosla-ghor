<tr class="hover:bg-gray-50">
    <td class="px-5 py-3">
        <div class="flex items-center gap-2 {{ $child ? 'pl-6' : '' }}">
            @if($child)
                <span class="text-gray-300">↳</span>
            @endif
            <div>
                <div class="font-medium text-gray-800 leading-snug {{ $child ? '' : 'font-semibold' }}">{{ $category->name_bn }}</div>
                @if($category->name_en)
                    <div class="text-xs text-gray-400 leading-snug">{{ $category->name_en }}</div>
                @endif
            </div>
        </div>
    </td>
    <td class="px-5 py-3 text-gray-400 text-xs font-mono">{{ $category->slug }}</td>
    <td class="px-5 py-3 text-center text-gray-500">{{ $category->products_count ?? 0 }}</td>
    <td class="px-5 py-3 text-center text-gray-400">{{ $category->sort_order }}</td>
    <td class="px-5 py-3 text-center">
        <span class="px-2.5 py-1 rounded text-xs font-medium
                     {{ $category->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
            {{ $category->is_active ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
        </span>
    </td>
    <td class="px-5 py-3 text-right">
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.categories.edit', $category) }}"
               class="text-xs text-gray-600 hover:underline">সম্পাদনা</a>
            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST"
                  onsubmit="return confirm('ক্যাটাগরি মুছে ফেলবেন?{{ $child ? '' : ' সাব-ক্যাটাগরিগুলো টপ-লেভেলে চলে যাবে।' }}')">
                @csrf @method('DELETE')
                <button type="submit" class="text-xs text-red-500 hover:underline">মুছুন</button>
            </form>
        </div>
    </td>
</tr>
