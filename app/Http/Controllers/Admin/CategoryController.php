<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        // Tree: top-level parents each followed by their (ordered) children.
        $parents = Category::whereNull('parent_id')
            ->with(['children' => fn($q) => $q->withCount('products')])
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.categories.index', compact('parents'));
    }

    public function create()
    {
        $parents = Category::whereNull('parent_id')->orderBy('sort_order')->orderBy('name_bn')->get();

        return view('admin.categories.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Category::create($data);

        return redirect()->route('admin.categories.index')->with('success', 'ক্যাটাগরি তৈরি হয়েছে।');
    }

    public function edit(Category $category)
    {
        // Only top-level categories may be parents, and a category cannot be its own parent.
        $parents = Category::whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->orderBy('sort_order')
            ->orderBy('name_bn')
            ->get();

        return view('admin.categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validated($request, $category);
        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'ক্যাটাগরি আপডেট হয়েছে।');
    }

    public function destroy(Category $category)
    {
        // Detaching children to top-level keeps the FK valid (nullOnDelete also handles it).
        $category->children()->update(['parent_id' => null]);
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'ক্যাটাগরি মুছে ফেলা হয়েছে।');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $request->validate([
            'name_bn'    => 'required|string|max:255',
            'name_en'    => 'nullable|string|max:255',
            'slug'       => ['nullable', 'string', 'max:255',
                             Rule::unique('categories', 'slug')->ignore($category?->id)],
            'parent_id'  => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $slug = $request->slug ?: Str::slug($request->name_en ?: $request->name_bn);
        if ($slug === '') {
            $slug = 'category-' . Str::random(6);
        }
        // Guarantee uniqueness if auto-generated slug collides.
        $base = $slug;
        $i = 2;
        while (Category::where('slug', $slug)->when($category, fn($q) => $q->where('id', '!=', $category->id))->exists()) {
            $slug = $base . '-' . $i++;
        }

        // A category cannot be its own parent, and children cannot be parents (one level deep).
        $parentId = $request->parent_id ?: null;
        if ($category && $parentId === $category->id) {
            $parentId = null;
        }

        return [
            'name_bn'    => $request->name_bn,
            'name_en'    => $request->name_en ?: null,
            'slug'       => $slug,
            'parent_id'  => $parentId,
            'sort_order' => $request->sort_order ?? 0,
            'is_active'  => $request->boolean('is_active'),
        ];
    }
}
