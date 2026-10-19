<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use App\Support\AnnouncementUrl;
use App\Support\UploadErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class HeroSlideController extends Controller
{
    public function index()
    {
        return view('admin.hero-slides.index', ['slides' => HeroSlide::orderBy('sort_order')->orderBy('id')->paginate(20)]);
    }

    public function create()
    {
        return view('admin.hero-slides.form', ['slide' => new HeroSlide]);
    }

    public function edit(HeroSlide $heroSlide)
    {
        return view('admin.hero-slides.form', ['slide' => $heroSlide]);
    }

    public function store(Request $request)
    {
        return $this->save($request, new HeroSlide);
    }

    public function update(Request $request, HeroSlide $heroSlide)
    {
        return $this->save($request, $heroSlide);
    }

    private function save(Request $request, HeroSlide $slide)
    {
        $urlRule = function ($attribute, $value, $fail) {
            if (! AnnouncementUrl::isValid($value)) {
                $fail('Use an internal path beginning with / or a valid http/https URL.');
            }
        };
        UploadErrors::guard($request, ['image' => 'হিরো ছবি', 'mobile_image' => 'মোবাইলের ছবি']); // failed uploads → one clear Bangla message
        $data = $request->validate([
            'title' => 'required|string|max:200', 'subtitle' => 'nullable|string|max:500',
            'eyebrow' => 'nullable|string|max:100', 'is_active' => 'nullable|boolean',
            'sort_order' => 'required|integer|min:0|max:10000',
            'primary_label' => 'nullable|required_with:primary_url|string|max:60',
            'primary_url' => ['nullable', 'required_with:primary_label', 'string', 'max:300', $urlRule],
            'secondary_label' => 'nullable|required_with:secondary_url|string|max:60',
            'secondary_url' => ['nullable', 'required_with:secondary_label', 'string', 'max:300', $urlRule],
            'image' => ['bail', $slide->exists ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'display_mode' => 'nullable|in:image,text',
            'link_url' => ['nullable', 'string', 'max:300', $urlRule],
            'mobile_image' => ['bail', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'remove_mobile_image' => 'nullable|boolean',
        ], UploadErrors::imageMessages(['image', 'mobile_image']), ['image' => 'হিরো ছবি', 'mobile_image' => 'মোবাইলের ছবি']);
        unset($data['image'], $data['mobile_image'], $data['remove_mobile_image']);
        $data['is_active'] = $request->boolean('is_active');
        $data['display_mode'] = $data['display_mode'] ?? ($slide->display_mode ?: 'text');
        $old = $slide->image_path;
        $oldMobile = $slide->mobile_image_path;
        $stored = null;
        $storedMobile = null;
        try {
            if ($request->hasFile('image')) {
                $stored = \App\Support\ImageOptimizer::store($request->file('image'), 'hero-slides', 'hero');
                if (! $stored) {
                    throw ValidationException::withMessages(['image' => 'Image could not be stored. Please try again.']);
                }
                $data['image_path'] = 'storage/'.$stored;
            }
            if ($request->hasFile('mobile_image')) {
                $storedMobile = \App\Support\ImageOptimizer::store($request->file('mobile_image'), 'hero-slides', 'hero');
                if (! $storedMobile) {
                    throw ValidationException::withMessages(['mobile_image' => 'মোবাইলের ছবি সংরক্ষণ করা যায়নি। আবার চেষ্টা করুন।']);
                }
                $data['mobile_image_path'] = 'storage/'.$storedMobile;
            } elseif ($request->boolean('remove_mobile_image')) {
                $data['mobile_image_path'] = null;
            }
            DB::transaction(fn () => $slide->fill($data)->save());
        } catch (\Throwable $e) {
            if ($stored) { $this->removeOwned('storage/'.$stored); }
            if ($storedMobile) { $this->removeOwned('storage/'.$storedMobile); }
            throw $e;
        }
        if ($stored && $old) { $this->removeOwned($old); }
        if ($oldMobile && $oldMobile !== $slide->mobile_image_path) { $this->removeOwned($oldMobile); }

        return redirect()->route('admin.hero-slides.index')->with('success', 'Hero slide saved.');
    }

    public function destroy(HeroSlide $heroSlide)
    {
        $old = $heroSlide->image_path;
        $oldMobile = $heroSlide->mobile_image_path;
        DB::transaction(fn () => $heroSlide->delete());
        $this->removeOwned($old);
        $this->removeOwned($oldMobile);

        return redirect()->route('admin.hero-slides.index')->with('success', 'Hero slide deleted.');
    }

    private function removeOwned(?string $path): void
    {
        if (! $path || ! preg_match('~^storage/hero-slides/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$~D', $path)
            || HeroSlide::where('image_path', $path)->orWhere('mobile_image_path', $path)->exists()) {
            return;
        }
        try {
            if (! Storage::disk('public')->delete(substr($path, 8))) {
                \Illuminate\Support\Facades\Log::warning('Hero slide image cleanup failed.');
            }
        } catch (\Throwable $e) { report($e); }
    }
}
