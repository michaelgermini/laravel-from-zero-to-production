<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show', 'search']);
        $this->middleware('can:manage-products')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = Product::with(['category', 'brand', 'tags']);

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->active();
            }
        } else {
            $query->active();
        }

        // Filter by category
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Filter by brand
        if ($request->has('brand')) {
            $query->byBrand($request->brand);
        }

        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Filter by stock
        if ($request->has('in_stock')) {
            $query->inStock();
        }

        // Filter by sale
        if ($request->has('on_sale')) {
            $query->onSale();
        }

        // Search functionality
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Sort by
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate(12);
        $categories = Category::all();
        $brands = Brand::all();
        $featuredProducts = Product::active()->featured()->latest()->take(6)->get();

        return view('products.index', compact('products', 'categories', 'brands', 'featuredProducts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $categories = Category::all();
        $brands = Brand::all();
        $tags = Tag::all();

        return view('products.create', compact('categories', 'brands', 'tags'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'cost_price' => 'nullable|numeric|min:0',
            'sku' => 'required|string|unique:products,sku|max:100',
            'stock_quantity' => 'required|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'gallery.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:active,inactive,draft',
            'featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
        ]);

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('products', 'public');
            $validated['featured_image'] = $path;
        }

        // Handle gallery images upload
        if ($request->hasFile('gallery')) {
            $gallery = [];
            foreach ($request->file('gallery') as $image) {
                $path = $image->store('products/gallery', 'public');
                $gallery[] = $path;
            }
            $validated['gallery'] = $gallery;
        }

        $product = Product::create($validated);

        // Attach tags
        if (isset($validated['tags'])) {
            $product->tags()->attach($validated['tags']);
        }

        return redirect()->route('products.show', $product)
            ->with('success', 'Product created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): View
    {
        // Check if product is active or user has permission
        if ($product->status !== 'active' && !auth()->user()?->can('manage-products')) {
            abort(404);
        }

        $product->load(['category', 'brand', 'tags', 'reviews.user']);

        // Increment view count
        $product->increment('views');

        $relatedProducts = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->latest()
            ->take(4)
            ->get();

        return view('products.show', compact('product', 'relatedProducts'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        $categories = Category::all();
        $brands = Brand::all();
        $tags = Tag::all();

        return view('products.edit', compact('product', 'categories', 'brands', 'tags'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'cost_price' => 'nullable|numeric|min:0',
            'sku' => 'required|string|unique:products,sku,' . $product->id . '|max:100',
            'stock_quantity' => 'required|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'gallery.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:active,inactive,draft',
            'featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
        ]);

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            // Delete old image
            if ($product->featured_image) {
                Storage::disk('public')->delete($product->featured_image);
            }
            
            $path = $request->file('featured_image')->store('products', 'public');
            $validated['featured_image'] = $path;
        }

        // Handle gallery images upload
        if ($request->hasFile('gallery')) {
            // Delete old gallery images
            if ($product->gallery) {
                foreach ($product->gallery as $image) {
                    Storage::disk('public')->delete($image);
                }
            }
            
            $gallery = [];
            foreach ($request->file('gallery') as $image) {
                $path = $image->store('products/gallery', 'public');
                $gallery[] = $path;
            }
            $validated['gallery'] = $gallery;
        }

        $product->update($validated);

        // Sync tags
        $product->tags()->sync($validated['tags'] ?? []);

        return redirect()->route('products.show', $product)
            ->with('success', 'Product updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        // Delete featured image
        if ($product->featured_image) {
            Storage::disk('public')->delete($product->featured_image);
        }

        // Delete gallery images
        if ($product->gallery) {
            foreach ($product->gallery as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully!');
    }

    /**
     * Search products
     */
    public function search(Request $request): View
    {
        $query = $request->get('q');
        
        $products = Product::active()
            ->search($query)
            ->with(['category', 'brand'])
            ->latest()
            ->paginate(12);

        $categories = Category::all();
        $brands = Brand::all();

        return view('products.search', compact('products', 'categories', 'brands', 'query'));
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $product->update(['featured' => !$product->featured]);

        $status = $product->featured ? 'featured' : 'unfeatured';
        
        return redirect()->back()
            ->with('success', "Product {$status} successfully!");
    }

    /**
     * Get products by category
     */
    public function byCategory(Request $request, $categorySlug): View
    {
        $category = Category::where('slug', $categorySlug)->firstOrFail();
        
        $products = Product::active()
            ->byCategory($category->id)
            ->with(['brand', 'tags'])
            ->latest()
            ->paginate(12);

        $categories = Category::all();
        $brands = Brand::all();

        return view('products.category', compact('products', 'categories', 'brands', 'category'));
    }

    /**
     * Get products by brand
     */
    public function byBrand(Request $request, $brandSlug): View
    {
        $brand = Brand::where('slug', $brandSlug)->firstOrFail();
        
        $products = Product::active()
            ->byBrand($brand->id)
            ->with(['category', 'tags'])
            ->latest()
            ->paginate(12);

        $categories = Category::all();
        $brands = Brand::all();

        return view('products.brand', compact('products', 'categories', 'brands', 'brand'));
    }
}
