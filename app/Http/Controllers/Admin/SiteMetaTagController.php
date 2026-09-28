<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteMetaTag;
use App\Support\MetaTagRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** Admin → সাইট ভেরিফিকেশন ও মেটা ট্যাগ. Structured fields only; the site builds the tag. */
class SiteMetaTagController extends Controller
{
    public function index(Request $request)
    {
        $tags = SiteMetaTag::orderBy('sort_order')->orderBy('id')->get();
        $editing = $request->filled('edit') ? $tags->firstWhere('id', (int) $request->query('edit')) : null;

        return view('admin.site-meta-tags', compact('tags', 'editing'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $tag = SiteMetaTag::create($data);
        $this->log($request, 'created', $tag);

        return redirect()->route('admin.site-meta-tags.index')->with('success', 'মেটা ট্যাগ যোগ হয়েছে।');
    }

    public function update(Request $request, SiteMetaTag $tag)
    {
        $tag->update($this->validated($request));
        $this->log($request, 'updated', $tag);

        return redirect()->route('admin.site-meta-tags.index')->with('success', 'মেটা ট্যাগ আপডেট হয়েছে।');
    }

    public function toggle(Request $request, SiteMetaTag $tag)
    {
        $tag->update(['is_active' => ! $tag->is_active]);
        $this->log($request, $tag->is_active ? 'enabled' : 'disabled', $tag);

        return back()->with('success', $tag->is_active ? 'ট্যাগ চালু হয়েছে।' : 'ট্যাগ বন্ধ হয়েছে।');
    }

    public function destroy(Request $request, SiteMetaTag $tag)
    {
        $this->log($request, 'deleted', $tag);
        $tag->delete();

        return redirect()->route('admin.site-meta-tags.index')->with('success', 'মেটা ট্যাগ মুছে ফেলা হয়েছে।');
    }

    /**
     * Smart paste: parse a pasted <meta …> tag on the server and send the admin back
     * to the form with the fields filled in. Nothing is saved; the raw paste is discarded.
     */
    public function parse(Request $request)
    {
        $request->validate(['paste' => ['required', 'string', 'max:1000']], [
            'paste.required' => 'Meta/Google যে ট্যাগটি দিয়েছে সেটি পেস্ট করুন।',
            'paste.max'      => 'পেস্ট করা লেখাটি অনেক বড়।',
        ]);

        $result = MetaTagRules::parsePaste((string) $request->input('paste'));
        $back = redirect()->to(route('admin.site-meta-tags.index', $request->filled('edit') ? ['edit' => (int) $request->input('edit')] : []).'#tag-form');

        if (is_string($result)) {
            return $back->withErrors(['paste' => $result]);
        }

        $preset = MetaTagRules::presetFor($result['name']);
        $label = $preset !== 'custom' ? MetaTagRules::PRESETS[$preset][0] : (string) $request->input('label', '');

        // Only the parsed, structured fields go back to the form (never the raw paste).
        return $back->withInput([
            'label' => $label, 'attribute' => $result['attribute'], 'name' => $result['name'],
            'content' => $result['content'], 'preset' => $preset, 'is_active' => '1',
            'sort_order' => (string) $request->input('sort_order', '0'),
        ])->with('success', 'ট্যাগ থেকে তথ্য নেওয়া হয়েছে — যাচাই করে “সংরক্ষণ” চাপুন।');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate(MetaTagRules::rules(), MetaTagRules::messages());

        return [
            'label'      => trim((string) ($data['label'] ?? '')) ?: null,
            'attribute'  => $data['attribute'],
            'name'       => trim($data['name']),
            'content'    => trim($data['content']),
            'is_active'  => $request->boolean('is_active'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function log(Request $request, string $action, SiteMetaTag $tag): void
    {
        Log::info("Site meta tag {$action}", ['user_id' => $request->user()?->id, 'tag_id' => $tag->id, 'name' => $tag->name]);
    }
}
