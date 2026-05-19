<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.faqs.index', compact('faqs'));
    }

    public function create()
    {
        return view('admin.faqs.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Faq::create($data);

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ তৈরি হয়েছে।');
    }

    public function edit(Faq $faq)
    {
        return view('admin.faqs.edit', compact('faq'));
    }

    public function update(Request $request, Faq $faq)
    {
        $data = $this->validated($request);
        $faq->update($data);

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ আপডেট হয়েছে।');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ মুছে ফেলা হয়েছে।');
    }

    public function toggle(Faq $faq)
    {
        $faq->update(['is_active' => ! $faq->is_active]);

        return back()->with('success', $faq->is_active ? 'FAQ সক্রিয় হয়েছে।' : 'FAQ নিষ্ক্রিয় হয়েছে।');
    }

    private function validated(Request $request): array
    {
        $request->validate([
            'question'   => 'required|string|max:500',
            'answer'     => 'required|string|max:2000',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        return [
            'question'   => $request->question,
            'answer'     => $request->answer,
            'sort_order' => $request->sort_order ?? 0,
            'is_active'  => $request->boolean('is_active'),
        ];
    }
}
