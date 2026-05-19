<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function create()
    {
        return view('admin.reviews.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Review::create($data);

        return redirect()->route('admin.reviews.index')->with('success', 'রিভিউ তৈরি হয়েছে।');
    }

    public function edit(Review $review)
    {
        return view('admin.reviews.edit', compact('review'));
    }

    public function update(Request $request, Review $review)
    {
        $data = $this->validated($request);
        $review->update($data);

        return redirect()->route('admin.reviews.index')->with('success', 'রিভিউ আপডেট হয়েছে।');
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return redirect()->route('admin.reviews.index')->with('success', 'রিভিউ মুছে ফেলা হয়েছে।');
    }

    public function toggle(Review $review)
    {
        $review->update(['is_active' => ! $review->is_active]);

        return back()->with('success', $review->is_active ? 'রিভিউ সক্রিয় হয়েছে।' : 'রিভিউ নিষ্ক্রিয় হয়েছে।');
    }

    private function validated(Request $request): array
    {
        $request->validate([
            'customer_name'     => 'required|string|max:100',
            'customer_location' => 'nullable|string|max:100',
            'rating'            => 'required|integer|min:1|max:5',
            'review_text'       => 'required|string|max:1000',
            'image'             => 'nullable|string|max:500',
            'sort_order'        => 'nullable|integer|min:0',
        ]);

        return [
            'customer_name'     => $request->customer_name,
            'customer_location' => $request->customer_location ?: null,
            'rating'            => $request->rating,
            'review_text'       => $request->review_text,
            'image'             => $request->image ?: null,
            'sort_order'        => $request->sort_order ?? 0,
            'is_active'         => $request->boolean('is_active'),
        ];
    }
}
