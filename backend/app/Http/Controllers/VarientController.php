<?php

namespace App\Http\Controllers;

use App\Models\Varient;
use App\Models\VariantSize;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VarientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'qty' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'extra_delivery_charge' => 'nullable|numeric|min:0',
            'color_name' => 'nullable|string|max:100',
            'color_code' => ['nullable', 'string', 'regex:/^#?[A-Fa-f0-9]{3}([A-Fa-f0-9]{3})?$/'],
        ]);

        $variant = new Varient();
        $variant->product_id = (int) $data['product_id'];
        $variant->title = trim((string) ($data['title'] ?? $data['color_name'] ?? 'Variant'));
        $variant->qty = (int) $data['qty'];
        $variant->price = (float) $data['price'];
        $variant->extra_delivery_charge = (float) ($data['extra_delivery_charge'] ?? 0);
        $variant->color_name = $this->normalizeColorName($data['color_name'] ?? null);
        $variant->color_code = $this->normalizeColorCode($data['color_code'] ?? null);
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products/variants', 'public');
            $variant->image = 'storage/' . $path;
        }
        $variant->save();

        return response()->json($variant, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Varient  $varient
     * @return \Illuminate\Http\Response
     */
    public function show(Varient $varient)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Varient  $varient
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $category = Varient::findOrfail($id);
        return response()->json($category, 200);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Varient  $varient
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'qty' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'extra_delivery_charge' => 'nullable|numeric|min:0',
            'status' => 'required|in:Active,Inactive',
            'color_name' => 'nullable|string|max:100',
            'color_code' => ['nullable', 'string', 'regex:/^#?[A-Fa-f0-9]{3}([A-Fa-f0-9]{3})?$/'],
        ]);

        $variant = Varient::findOrfail($id);
        $variant->title = trim((string) ($data['title'] ?? $data['color_name'] ?? $variant->title));
        $variant->qty = (int) $data['qty'];
        $variant->price = (float) $data['price'];
        $variant->extra_delivery_charge = (float) ($data['extra_delivery_charge'] ?? 0);
        $variant->status = $data['status'];
        $variant->color_name = $this->normalizeColorName($data['color_name'] ?? null);
        $variant->color_code = $this->normalizeColorCode($data['color_code'] ?? null);
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products/variants', 'public');
            $variant->image = 'storage/' . $path;
        }
        $variant->save();

        return response()->json($variant, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Varient  $varient
     * @return \Illuminate\Http\Response
     */
    public function destroy(Varient $varient)
    {
        $varient->delete();
        return response()->json('success', 200);
    }

    public function sizes($id)
    {
        $sizes = VariantSize::where('varient_id', $id)->orderBy('id')->get();
        return response()->json($sizes, 200);
    }

    public function storeSize(Request $request, $id)
    {
        $data = $request->validate([
            'size_name' => 'required|string|max:50',
            'qty' => 'required|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:Active,Inactive',
        ]);
        
        $size = new VariantSize();
        $size->varient_id = $id;
        $size->size_name = trim($data['size_name']);
        $size->qty = (int) $data['qty'];
        $size->price = array_key_exists('price', $data) && $data['price'] !== null ? (float) $data['price'] : null;
        $size->status = $request->input('status', 'Active');
        $size->save();
        
        return response()->json($size, 201);
    }

    public function updateSize(Request $request, $id, $sizeId)
    {
        $data = $request->validate([
            'size_name' => 'required|string|max:50',
            'qty' => 'required|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'status' => 'required|in:Active,Inactive',
        ]);
        
        $size = VariantSize::where('varient_id', $id)->findOrFail($sizeId);
        $size->size_name = trim($data['size_name']);
        $size->qty = (int) $data['qty'];
        $size->price = array_key_exists('price', $data) && $data['price'] !== null ? (float) $data['price'] : null;
        $size->status = $data['status'];
        $size->save();
        
        return response()->json($size, 200);
    }

    public function destroySize($id, $sizeId)
    {
        $size = VariantSize::where('varient_id', $id)->findOrFail($sizeId);
        $size->delete();
        return response()->json('success', 200);
    }

    private function normalizeColorCode(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $clean = trim($value);
        if ($clean === '') {
            return null;
        }

        $clean = Str::upper(ltrim($clean, '#'));
        return '#' . $clean;
    }

    private function normalizeColorName(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $clean = trim($value);
        return $clean === '' ? null : $clean;
    }
}
