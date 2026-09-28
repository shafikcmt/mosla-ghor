@extends('admin.layout')

@section('title', 'জেনারেল সেটিং')

@section('content')

<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">জেনারেল সেটিং</h1>
</div>

<div class="bg-white rounded shadow">
    <form action="{{ route('admin.general-settings.update') }}" method="POST">
        @csrf

        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">অর্ডার সেটিং</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="minimum_order_amount">
                        ন্যূনতম অর্ডার পরিমাণ (৳)
                        <span class="text-gray-400 font-normal">(গ্রাহকের কার্টের মোট এর চেয়ে কম হলে অর্ডার দেওয়া যাবে না)</span>
                    </label>
                    <input type="number" name="minimum_order_amount" id="minimum_order_amount"
                           value="{{ old('minimum_order_amount', $settings->minimum_order_amount) }}"
                           min="0" step="1"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    @error('minimum_order_amount')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="default_packaging_cost">
                        প্যাকেজিং চার্জ (৳)
                        <span class="text-gray-400 font-normal">(প্রতিটি অর্ডারে যোগ হয়)</span>
                    </label>
                    <input type="number" name="default_packaging_cost" id="default_packaging_cost"
                           value="{{ old('default_packaging_cost', $settings->default_packaging_cost) }}"
                           min="0" step="1"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    @error('default_packaging_cost')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        <div class="px-6 py-5">
            <button type="submit"
                    class="bg-gray-800 text-white px-6 py-2.5 rounded text-sm font-semibold hover:bg-gray-700 transition-colors">
                সেটিং সংরক্ষণ করুন
            </button>
        </div>

    </form>
</div>

{{-- ── Image compression (own form) ─────────────────────────────────── --}}
@php
    $imgErr = $errors->getBag('images');
    $imgOn = old('image_optimize_enabled', \App\Models\WebsiteSetting::get('image_optimize_enabled', '1')) === '1';
    $webp = \App\Support\ImageOptimizer::webpSupported();
@endphp
<div class="bg-white rounded shadow mt-6" id="image-settings">
    <form action="{{ route('admin.general-settings.images') }}" method="POST">
        @csrf
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-1">ছবি কম্প্রেশন</h3>
            <p class="text-xs text-gray-400 mb-4">আপলোডের সময় ছবি নিজে থেকেই ছোট ও হালকা হয় (ব্রাউজারে ও সার্ভারে) — TinyPNG লাগবে না। KYC ডকুমেন্ট কখনো বদলানো হয় না।</p>

            @if($imgErr->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded px-4 py-3 mb-4">
                    @foreach($imgErr->all() as $e)<p>{{ $e }}</p>@endforeach
                </div>
            @endif

            <input type="hidden" name="image_optimize_enabled" value="0">
            <label class="flex items-start gap-3 cursor-pointer mb-5">
                <input type="checkbox" name="image_optimize_enabled" value="1" class="mt-0.5 rounded border-gray-300" @checked($imgOn)>
                <span>
                    <span class="block text-sm font-medium text-gray-700">অটো কম্প্রেশন চালু</span>
                    <span class="block text-xs text-gray-400 mt-0.5">বন্ধ করলে ছবি আগের মতো হুবহু সংরক্ষণ হবে।</span>
                </span>
            </label>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="image_max_product_px">পণ্যের ছবির সর্বোচ্চ মাপ (px, লম্বা দিক)</label>
                    <input type="number" name="image_max_product_px" id="image_max_product_px" min="600" max="4000" step="100"
                           value="{{ old('image_max_product_px', \App\Models\WebsiteSetting::get('image_max_product_px', '1600')) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <p class="text-xs text-gray-400 mt-1">প্রস্তাবিত ১৬০০। ছোট ছবি কখনো বড় করা হয় না।</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="image_quality">কোয়ালিটি (৪০–৯৫)</label>
                    <input type="number" name="image_quality" id="image_quality" min="40" max="95" step="1"
                           value="{{ old('image_quality', \App\Models\WebsiteSetting::get('image_quality', '80')) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <p class="text-xs text-gray-400 mt-1">প্রস্তাবিত ৮০ — পণ্য, হিরো ও ব্যানার ছবিতে প্রযোজ্য। লোগো, পেমেন্ট স্ক্রিনশট, রিভিউ ও শেয়ার ছবির মান নির্দিষ্ট।</p>
                </div>
            </div>

            @php $imgLib = \App\Support\ImageOptimizer::libraryInstalled(); @endphp
            <p class="text-xs mt-4 {{ $imgLib ? 'text-green-700' : 'text-red-600' }}" data-image-library="{{ $imgLib ? 'yes' : 'no' }}">
                Image library: {{ $imgLib ? '✅ ইনস্টল আছে' : '❌ নেই — ব্রাউজারে ছোট করা চালু আছে' }}
            </p>
            <p class="text-xs mt-1 {{ $webp ? 'text-green-700' : 'text-gray-500' }}">
                সার্ভার WebP: {{ $webp ? 'সমর্থিত' : 'নেই — সার্ভার JPEG/PNG ব্যবহার করবে; ব্রাউজার থেকে WebP আপলোড আগের মতোই কাজ করবে' }}
            </p>

            {{-- ── Live server upload limits (no shell needed) ───────────── --}}
            @php
                $L = \App\Support\ServerLimits::class;
                $measured = $L::measured();
                $measuredAt = \App\Models\WebsiteSetting::get($L::MEASURED_AT_KEY);
                $free = $L::freeDiskBytes();
            @endphp
            <div class="mt-5 border-t border-gray-100 pt-4" id="upload-limits">
                <h4 class="text-xs font-semibold text-gray-600 mb-2">সার্ভারের আপলোড সীমা (এই মুহূর্তে লাইভ)</h4>
                <table class="w-full text-xs" data-limits>
                    <tbody class="divide-y divide-gray-100">
                        <tr><td class="py-1 text-gray-500">upload_max_filesize (প্রতি ফাইল)</td><td class="py-1 font-mono text-right">{{ ini_get('upload_max_filesize') }}</td></tr>
                        <tr><td class="py-1 text-gray-500">post_max_size (পুরো সেভ)</td><td class="py-1 font-mono text-right">{{ ini_get('post_max_size') }}</td></tr>
                        <tr><td class="py-1 text-gray-500">max_file_uploads</td><td class="py-1 font-mono text-right">{{ ini_get('max_file_uploads') }}</td></tr>
                        <tr><td class="py-1 text-gray-500">memory_limit</td><td class="py-1 font-mono text-right">{{ ini_get('memory_limit') }}</td></tr>
                        <tr><td class="py-1 text-gray-500">আপলোডের temp ফোল্ডার</td><td class="py-1 text-right {{ $L::tempDirWritable() ? 'text-green-700' : 'text-red-600' }}">{{ $L::tempDirWritable() ? '✅ লেখা যায়' : '❌ লেখা যায় না' }}</td></tr>
                        <tr><td class="py-1 text-gray-500">স্টোরেজে খালি জায়গা</td><td class="py-1 font-mono text-right">{{ $L::human($free) }}</td></tr>
                        <tr><td class="py-1 text-gray-500">পরীক্ষায় পাওয়া আসল সীমা (nginx + PHP)</td><td class="py-1 font-mono text-right" data-measured>{{ $measured ? $L::human($measured).' ('.\Illuminate\Support\Carbon::parse($measuredAt)->timezone('Asia/Dhaka')->format('d M, h:i A').')' : 'এখনও পরীক্ষা হয়নি' }}</td></tr>
                        <tr><td class="py-1 text-gray-500">চলমান কোড (commit)</td><td class="py-1 font-mono text-right">{{ \App\Support\BuildInfo::commit() }}</td></tr>
                        @php $persist = \App\Support\StoragePersistence::status(); @endphp
                        <tr data-persistence="{{ $persist['survived'] ? 'survived' : ($persist['ok'] ? 'waiting' : 'error') }}">
                            <td class="py-1 text-gray-500">প্রাইভেট স্টোরেজ (deploy-এর পরও থাকে কি?)</td>
                            <td class="py-1 text-right {{ $persist['survived'] ? 'text-green-700' : ($persist['ok'] ? 'text-gray-500' : 'text-red-600') }}">
                                @if(! $persist['ok'])
                                    ❌ লেখা যায়নি
                                @elseif($persist['survived'])
                                    ✅ টিকে আছে — {{ $persist['created_commit'] }} থেকে ({{ \Illuminate\Support\Carbon::parse($persist['created_at'])->timezone('Asia/Dhaka')->format('d M, h:i A') }})
                                @else
                                    ⏳ মার্কার তৈরি ({{ $persist['created_commit'] }}, {{ \Illuminate\Support\Carbon::parse($persist['created_at'])->timezone('Asia/Dhaka')->format('d M, h:i A') }}) — পরের deploy-এর পর দেখুন
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" data-upload-probe
                        data-probe-url="{{ route('admin.diagnostics.upload-probe') }}" data-result-url="{{ route('admin.diagnostics.upload-result') }}"
                        class="mt-3 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded text-sm font-medium hover:bg-gray-50">আপলোড সীমা পরীক্ষা</button>
                <p class="text-xs text-gray-400 mt-1">০.৫, ১, ২, ৫ ও ১০ MB পাঠিয়ে দেখে কোনটা পৌঁছায় — কোনো ফাইল সংরক্ষণ হয় না।</p>
                <div data-probe-output class="text-xs mt-2"></div>
            </div>
        </div>
        <div class="px-6 py-5">
            <button type="submit" class="bg-gray-800 text-white px-6 py-2.5 rounded text-sm font-semibold hover:bg-gray-700 transition-colors">ছবির সেটিং সংরক্ষণ</button>
        </div>
    </form>
</div>

<script>
(function () {
    var btn = document.querySelector('[data-upload-probe]');
    if (!btn) return;
    var out = document.querySelector('[data-probe-output]');
    var csrf = document.querySelector('input[name=_token]');
    var steps = [0.5, 1, 2, 5, 10];
    var label = function (mb) { return (mb < 1 ? (mb * 1024) + ' KB' : mb + ' MB'); };
    var row = function (text, ok) {
        var p = document.createElement('p');
        p.textContent = text;
        p.style.color = ok ? '#047857' : '#b91c1c';
        out.appendChild(p);
    };
    btn.addEventListener('click', async function () {
        btn.disabled = true;
        out.textContent = '';
        var largest = 0;
        for (var i = 0; i < steps.length; i++) {
            var bytes = Math.round(steps[i] * 1024 * 1024);
            var fd = new FormData();
            fd.append('probe', new Blob([new Uint8Array(bytes)], { type: 'application/octet-stream' }), 'probe.bin');
            var why = '';
            try {
                var res = await fetch(btn.dataset.probeUrl, {
                    method: 'POST', body: fd, credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf ? csrf.value : '' }
                });
                var json = null;
                try { json = await res.clone().json(); } catch (e) { json = null; }
                if (res.ok && json && json.ok) {
                    largest = bytes;
                    row('✅ ' + label(steps[i]) + ' পৌঁছেছে', true);
                    continue;
                }
                if (res.status === 413 && !json) why = 'nginx (client_max_body_size) আটকে দিয়েছে';
                else if (res.status === 413) why = 'PHP post_max_size আটকে দিয়েছে';
                else if (json && json.layer === 'php_upload_max_filesize') why = 'PHP upload_max_filesize আটকে দিয়েছে';
                else why = 'ব্যর্থ (HTTP ' + res.status + ')';
            } catch (e) {
                why = 'সংযোগ বিচ্ছিন্ন — সম্ভবত সার্ভার/প্রক্সি আটকে দিয়েছে';
            }
            row('❌ ' + label(steps[i]) + ': ' + why, false);
            break;
        }
        try {
            var save = await fetch(btn.dataset.resultUrl, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf ? csrf.value : '' },
                body: JSON.stringify({ largest_ok_bytes: largest })
            });
            var sj = await save.json();
            var m = document.querySelector('[data-measured]');
            if (m && sj.saved) m.textContent = sj.saved + ' (এইমাত্র)';
            row('সবচেয়ে বড় যেটা পৌঁছেছে: ' + (largest ? (largest / 1048576).toFixed(1) + ' MB' : 'কিছুই না') + ' — সেভ করার সময় এই সীমা মানা হবে।', largest > 0);
        } catch (e) { /* result not saved; table still shows the run */ }
        btn.disabled = false;
    });
})();
</script>

@endsection
