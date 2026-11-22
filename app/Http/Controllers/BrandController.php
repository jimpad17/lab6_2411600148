<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $brands = Brand::query()
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

        return view('brands.index', [
            'brands' => $brands,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return view('brands.create', ['brand' => new Brand()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['is_active'] = true;

        $brand = Brand::create($data);
        ActivityLogger::log('created', 'Brands', "Created brand '{$brand->name}'.");

        return redirect()->route('brands.index')->with('success', "Brand '{$brand->name}' was added.");
    }

    public function edit(Brand $brand)
    {
        return view('brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $data = $this->validateData($request, $brand);
        $data['is_active'] = $request->boolean('is_active');

        $brand->update($data);
        ActivityLogger::log('updated', 'Brands', "Updated brand '{$brand->name}'.");

        return redirect()->route('brands.index')->with('success', "Brand '{$brand->name}' was updated.");
    }

    public function destroy(Brand $brand)
    {
        $brand->update(['is_active' => false]);
        ActivityLogger::log('deactivated', 'Brands', "Marked brand '{$brand->name}' as inactive.");

        return redirect()->route('brands.index')
            ->with('success', "Brand '{$brand->name}' was set to inactive. Its products were kept.");
    }

    public function restore(Brand $brand)
    {
        $brand->update(['is_active' => true]);
        ActivityLogger::log('restored', 'Brands', "Restored brand '{$brand->name}'.");

        return redirect()->route('brands.index')->with('success', "Brand '{$brand->name}' is active again.");
    }

    private function validateData(Request $request, ?Brand $brand = null): array
    {
        $uniqueName = Rule::unique('brands', 'name');
        $uniqueSlug = Rule::unique('brands', 'slug');

        if ($brand) {
            $uniqueName->ignore($brand->id);
            $uniqueSlug->ignore($brand->id);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', $uniqueName],
            'slug' => ['nullable', 'string', 'max:100', $uniqueSlug],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Brand name is required.',
            'name.unique' => 'This brand already exists.',
            'slug.unique' => 'This slug is already used by another brand.',
        ]);

        $data['name'] = strip_tags(trim($data['name']));
        $data['slug'] = Str::slug($data['slug'] ?: $data['name']);
        $data['description'] = isset($data['description']) ? strip_tags(trim($data['description'])) : null;

        return $data;
    }
}
