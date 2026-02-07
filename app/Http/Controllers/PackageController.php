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
        $package = Package::create($request->all());
        return response()->json($package, 200);
    }


    public function edit($id)
    {
        $package = Package::findOrfail($id);
        return response()->json($package, 200);
    }

    public function update(Request $request, $id)
    {
        $package = Package::findOrfail($id);
        $package->package_name = $request->package_name;
        $package->price = $request->price;
        $package->validity = $request->validity;
        if(isset($request->discount_price)){
            $package->discount_price = $request->discount_price;
        }else{
            $package->discount_price =null;
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
