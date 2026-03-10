<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PromotionalSection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PromotionalSectionController extends Controller
{
    /**
     * List all promotional sections.
     */
    public function index()
    {
        $sections = PromotionalSection::withCount('products')->orderBy('sort_order')->get();
        return view('backend.content.promotional_sections.index', compact('sections'));
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $products = Product::where('status', 'Active')
            ->select('id', 'ProductName', 'ViewProductImage')
            ->orderBy('ProductName')
            ->get();

        return view('backend.content.promotional_sections.create', compact('products'));
    }

    /**
     * Store a new promotional section.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'banner_image' => 'nullable|image|max:5120',
        ]);

        $section = new PromotionalSection();
        $section->title = $request->title;
        $section->slug = Str::slug($request->title, '_');

        // Handle banner upload to R2
        if ($request->hasFile('banner_image')) {
            $section->banner_image = $this->uploadBanner($request->file('banner_image'));
        }

        $section->sort_order = $request->input('sort_order', PromotionalSection::max('sort_order') + 1);
        $section->layout_type = $request->input('layout_type', 'card');
        $section->bg_color = $request->input('bg_color');
        $section->is_active = $request->has('is_active');
        $section->save();

        // Attach products
        if ($request->product_ids) {
            $syncData = [];
            foreach ($request->product_ids as $i => $productId) {
                $syncData[$productId] = ['sort_order' => $i];
            }
            $section->products()->sync($syncData);
        }

        return redirect()->route('admin.promotional-sections.index')
            ->with('message', 'Promotional section created successfully.');
    }

    /**
     * Show edit form.
     */
    public function edit($id)
    {
        $section = PromotionalSection::with('products')->findOrFail($id);
        $products = Product::where('status', 'Active')
            ->select('id', 'ProductName', 'ViewProductImage')
            ->orderBy('ProductName')
            ->get();

        $selectedProductIds = $section->products->pluck('id')->toArray();

        return view('backend.content.promotional_sections.edit', compact('section', 'products', 'selectedProductIds'));
    }

    /**
     * Update a promotional section.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'banner_image' => 'nullable|image|max:5120',
        ]);

        $section = PromotionalSection::findOrFail($id);
        $section->title = $request->title;

        // Only update slug if title changed and slug is not manually overridden
        if ($section->isDirty('title')) {
            $newSlug = Str::slug($request->title, '_');
            if (!PromotionalSection::where('slug', $newSlug)->where('id', '!=', $id)->exists()) {
                $section->slug = $newSlug;
            }
        }

        // Handle banner upload
        if ($request->hasFile('banner_image')) {
            $section->banner_image = $this->uploadBanner($request->file('banner_image'));
        }

        $section->is_active = $request->has('is_active');
        $section->sort_order = $request->input('sort_order', $section->sort_order);
        $section->layout_type = $request->input('layout_type', $section->layout_type);
        $section->bg_color = $request->input('bg_color');
        $section->save();

        // Sync products
        if ($request->has('product_ids')) {
            $syncData = [];
            foreach ($request->product_ids as $i => $productId) {
                $syncData[$productId] = ['sort_order' => $i];
            }
            $section->products()->sync($syncData);
        } else {
            $section->products()->detach();
        }

        return redirect()->route('admin.promotional-sections.index')
            ->with('message', 'Promotional section updated successfully.');
    }

    /**
     * Delete a promotional section.
     */
    public function destroy($id)
    {
        $section = PromotionalSection::findOrFail($id);
        $section->delete();

        return redirect()->route('admin.promotional-sections.index')
            ->with('message', 'Promotional section deleted successfully.');
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus($id)
    {
        $section = PromotionalSection::findOrFail($id);
        $section->is_active = !$section->is_active;
        $section->save();

        return redirect()->back()->with('message', 'Section status updated successfully.');
    }

    /**
     * Update sort order via AJAX.
     */
    public function updateOrder(Request $request)
    {
        $order = $request->input('order', []);
        foreach ($order as $position => $id) {
            PromotionalSection::where('id', $id)->update(['sort_order' => $position]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Move a section up or down.
     */
    public function moveOrder($id, $direction)
    {
        $section = PromotionalSection::findOrFail($id);

        if ($direction === 'up') {
            $swap = PromotionalSection::where('sort_order', '<', $section->sort_order)
                ->orderBy('sort_order', 'desc')->first();
        } else {
            $swap = PromotionalSection::where('sort_order', '>', $section->sort_order)
                ->orderBy('sort_order', 'asc')->first();
        }

        if ($swap) {
            $tempOrder = $section->sort_order;
            $section->sort_order = $swap->sort_order;
            $swap->sort_order = $tempOrder;
            $section->save();
            $swap->save();
        }

        return redirect()->route('admin.promotional-sections.index');
    }

    /**
     * Upload banner image to R2 storage.
     */
    private function uploadBanner($file)
    {
        $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
        $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('admin/promotional-sections', $safeName, 'r2');
        return $r2BaseUrl . '/' . $path;
    }
}
