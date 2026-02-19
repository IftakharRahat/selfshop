<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;
use DataTables;

class PackageController extends Controller
{
    public function index()
    {
        return view('admin.content.package.package');
    }

    public function packagedata()
    {
        $packages = Package::all();
        return Datatables::of($packages)
            ->addColumn('action', function ($packages) {
                return '<a href="#" type="button" id="editPackageBtn" data-id="' . $packages->id . '"   class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editmainPackages" ><i class="bi bi-pencil-square"></i></a>
                <a href="#" type="button" id="deletePackageBtn" data-id="' . $packages->id . '" class="btn btn-danger btn-sm" ><i class="bi bi-archive" ></i></a>';
            })

            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'package_name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'validity' => ['nullable', 'numeric', 'min:1'],
        ]);

        $package = Package::create([
            'package_name' => $validated['package_name'],
            'price' => $validated['price'],
            'discount_price' => $validated['discount_price'] ?? null,
            'validity' => $validated['validity'] ?? null,
            'status' => 'Active',
        ]);
        return response()->json($package, 200);
    }


    public function edit($id)
    {
        $package = Package::findOrfail($id);
        return response()->json($package, 200);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'package_name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'validity' => ['nullable', 'numeric', 'min:1'],
        ]);

        $package = Package::findOrfail($id);
        $package->package_name = $validated['package_name'];
        $package->price = $validated['price'];
        $package->validity = $validated['validity'] ?? null;
        if (isset($validated['discount_price'])) {
            $package->discount_price = $validated['discount_price'];
        } else {
            $package->discount_price = null;
        }
        $package->save();
        return response()->json($package, 200);
    }


    public function destroy($id)
    {
        $package = Package::findOrfail($id);
        $package->delete();
        return response()->json('delete success', 200);
    }

    public function updatestatus(Request $request)
    {

        $package = Package::Where('id', $request->package_id)->first();
        $package->status = $request->status;
        $package->save();

        return response()->json($package, 200);
    }


}
