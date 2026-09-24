@extends('admin.layout')
@section('title', 'Hero Slides')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-4 mb-5">
    <div><h1 class="text-xl font-bold">Hero Slides</h1><p class="text-sm text-gray-500 mt-2">ছোট ক্রমের স্লাইড আগে দেখাবে। সম্পাদনা করে ক্রম ও সক্রিয় অবস্থা পরিবর্তন করুন।</p></div>
    <a class="bg-gray-800 text-white px-4 py-2 rounded" href="{{ route('admin.hero-slides.create') }}">+ নতুন স্লাইড</a>
</div>
<div class="bg-white rounded shadow overflow-x-auto">
<table class="w-full text-sm">
    <thead><tr class="border-b"><th class="p-4 text-left">ছবি</th><th class="p-4 text-left">শিরোনাম</th><th class="p-4">সক্রিয়</th><th class="p-4">ক্রম</th><th class="p-4">অ্যাকশন</th></tr></thead>
    <tbody>
    @forelse($slides as $slide)
    <tr class="border-b">
        <td class="p-4"><img src="{{ \App\Support\ProductMedia::url($slide->image_path) }}" alt="{{ $slide->title }}" class="w-24 h-16 object-contain"></td>
        <td class="p-4">{{ $slide->title }}</td><td class="p-4 text-center">{{ $slide->is_active ? 'হ্যাঁ' : 'না' }}</td><td class="p-4 text-center">{{ $slide->sort_order }}</td>
        <td class="p-4"><div class="flex gap-4 justify-center">
            <a class="underline" href="{{ route('admin.hero-slides.edit', $slide) }}">সম্পাদনা</a>
            <form method="POST" action="{{ route('admin.hero-slides.destroy', $slide) }}" onsubmit="return confirm('স্লাইডটি মুছে ফেলবেন?')">@csrf @method('DELETE')<button class="text-red-600" type="submit">মুছুন</button></form>
        </div></td>
    </tr>
    @empty<tr><td colspan="5" class="p-8 text-center text-gray-500">কোনো স্লাইড নেই। হোমপেজে আগের হিরো দেখানো হবে।</td></tr>@endforelse
    </tbody>
</table>
</div>
<div class="mt-4">{{ $slides->links() }}</div>
@endsection
