<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.strip_tags((string) $request->input('search')).'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)->orWhere('slug', 'like', $term);
                });
            })
            ->when($request->input('status') === 'active', fn ($q) => $q->active())
            ->when($request->input('status') === 'inactive', fn ($q) => $q->inactive())
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('categories.index', [
            'categories' => $categories,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return view('categories.create', ['category' => new Category()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['is_active'] = true;

        $category = Category::create($data);
        ActivityLogger::log('created', 'Categories', "Created category '{$category->name}'.");

        return redirect()->route('categories.index')->with('success', "Category '{$category->name}' was added.");
    }

    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validateData($request, $category);
        $data['is_active'] = $request->boolean('is_active');

        $category->update($data);
        ActivityLogger::log('updated', 'Categories', "Updated category '{$category->name}'.");

        return redirect()->route('categories.index')->with('success', "Category '{$category->name}' was updated.");
    }

    public function destroy(Category $category)
    {
        $category->update(['is_active' => false]);
        ActivityLogger::log('deactivated', 'Categories', "Marked category '{$category->name}' as inactive.");

        return redirect()->route('categories.index')
            ->with('success', "Category '{$category->name}' was set to inactive. Its products were kept.");
    }

    public function restore(Category $category)
    {
        $category->update(['is_active' => true]);
        ActivityLogger::log('restored', 'Categories', "Restored category '{$category->name}'.");

        return redirect()->route('categories.index')->with('success', "Category '{$category->name}' is active again.");
    }

    private function validateData(Request $request, ?Category $category = null): array
    {
        $uniqueName = Rule::unique('categories', 'name');
        $uniqueSlug = Rule::unique('categories', 'slug');

        if ($category) {
            $uniqueName->ignore($category->id);
            $uniqueSlug->ignore($category->id);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', $uniqueName],
            'slug' => ['nullable', 'string', 'max:100', $uniqueSlug],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Category name is required.',
            'name.unique' => 'This category already exists.',
            'slug.unique' => 'This slug is already used by another category.',
        ]);

        $data['name'] = strip_tags(trim($data['name']));
        $data['slug'] = Str::slug($data['slug'] ?: $data['name']);
        $data['description'] = isset($data['description']) ? strip_tags(trim($data['description'])) : null;

        return $data;
    }
}
