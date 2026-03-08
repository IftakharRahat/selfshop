<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Admin;
use DataTables;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $admins = Admin::where('add_by', Auth::guard('admin')->user()->id)->where('type', 'Shop')->get();
        return view('backend.content.admins.index', ['admins' => $admins]);
    }

    public function hrexe()
    {
        $admins = Admin::where('add_by', Auth::guard('admin')->user()->id)->where('type', 'hr')->get();
        return view('backend.content.admins.hrexe', ['admins' => $admins]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $admin = new Admin();
        $admin->name = $request->name;
        $admin->email = $request->email;
        if (isset($request->status)) {
            $admin->status = $request->status;
        }
        if ($request->roles == 2) {
            $admin->type = 'Shop';
        }
        $admin->add_by = Auth::guard('admin')->user()->id;
        $admin->password = Hash::make($request->password);
        $admin->phone = $request->phone;
        $admin->save();
        if ($request->roles) {
            $admin->assignRole($request->roles);
        }
        return redirect()->back()->with('message', 'Admin created successfully');
    }

    public function hrexestore(Request $request)
    {
        $admin = new Admin();
        $admin->name = $request->name;
        $admin->email = $request->email;
        if ($request->roles == 2) {
            $admin->type = 'Shop';
        }
        $admin->add_by = Auth::guard('admin')->user()->id;
        $admin->password = Hash::make($request->password);
        $admin->phone = $request->phone;
        $admin->save();
        if ($request->roles) {
            $admin->assignRole($request->roles);
        }
        return redirect()->back()->with('message', 'Admin created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::where('guard_name', 'admin')->get();
        return view('backend.content.admins.create', ['roles' => $roles]);
    }

    public function hrexecreate()
    {
        $roles = Role::where('guard_name', 'admin')->get();
        return view('backend.content.admins.hrexecreate', ['roles' => $roles]);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $roles = Role::where('guard_name', 'admin')->get();
        $admin = Admin::where('id', $id)->first();
        return view('backend.content.admins.edit', ['roles' => $roles, 'admin' => $admin]);
    }

    public function profile()
    {
        return view('backend.content.profile.profile');
    }

    public function profilebyid($id)
    {
        $shop = Admin::where('id', $id)->first();
        return view('backend.content.profile.shopby', ['shop' => $shop]);
    }

    public function updateprofileby(Request $request, $id)
    {
        $time = microtime('.') * 10000;
        $admin = Admin::where('id', $id)->first();
        $admin->shop_name = $request->shop_name;
        $admin->shop_address = $request->shop_address;
        $admin->shop_contact = $request->shop_contact;
        $admin->delivery_charge = $request->delivery_charge;
        $admin->shop_licence_number = $request->shop_licence_number;
        // home image
        $adminimg_1 = $request->file('shop_icon');

        if ($adminimg_1) {
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $safeName = Str::slug(pathinfo($adminimg_1->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $adminimg_1->getClientOriginalExtension();
            $path = $adminimg_1->storeAs('admin/shops', $safeName, 'r2');
            $admin->shop_icon = $r2BaseUrl . '/' . $path;
        }

        $adminimg_2 = $request->file('trade_licence');

        if ($adminimg_2) {
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $safeName = Str::slug(pathinfo($adminimg_2->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $adminimg_2->getClientOriginalExtension();
            $path = $adminimg_2->storeAs('admin/shops', $safeName, 'r2');
            $admin->trade_licence = $r2BaseUrl . '/' . $path;
        }

        $adminimg_3 = $request->file('national_id');

        if ($adminimg_3) {
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $safeName = Str::slug(pathinfo($adminimg_3->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $adminimg_3->getClientOriginalExtension();
            $path = $adminimg_3->storeAs('admin/shops', $safeName, 'r2');
            $admin->national_id = $r2BaseUrl . '/' . $path;
        }

        $admin->save();
        return redirect()->back()->with('message', 'Shop Profile Update Successfully');
    }

    public function updateprofile(Request $request)
    {

        $time = microtime('.') * 10000;
        $adm_id = Auth::guard('admin')->user()->id;
        $admin = Admin::where('id', $adm_id)->first();

        if (isset($request->password)) {
            if (! $admin || ! Hash::check($request->input('old_password'), $admin->password)) {
                return back()->with('error', 'Current password is incorrect.');
            } else {
                $admin->password = Hash::make($request->password);
            }
        }

        $admin->shop_name = $request->shop_name;
        $admin->shop_address = $request->shop_address;
        $admin->shop_contact = $request->shop_contact;
        $admin->delivery_charge = $request->delivery_charge;
        $admin->shop_licence_number = $request->shop_licence_number;
        // home image
        $adminimg_1 = $request->file('shop_icon');

        if ($adminimg_1) {
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $safeName = Str::slug(pathinfo($adminimg_1->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $adminimg_1->getClientOriginalExtension();
            $path = $adminimg_1->storeAs('admin/shops', $safeName, 'r2');
            $admin->shop_icon = $r2BaseUrl . '/' . $path;
        }

        $adminimg_2 = $request->file('trade_licence');

        if ($adminimg_2) {
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $safeName = Str::slug(pathinfo($adminimg_2->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $adminimg_2->getClientOriginalExtension();
            $path = $adminimg_2->storeAs('admin/shops', $safeName, 'r2');
            $admin->trade_licence = $r2BaseUrl . '/' . $path;
        }

        $adminimg_3 = $request->file('national_id');

        if ($adminimg_3) {
            $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
            $safeName = Str::slug(pathinfo($adminimg_3->getClientOriginalName(), PATHINFO_FILENAME))
                . '_' . Str::random(8) . '.' . $adminimg_3->getClientOriginalExtension();
            $path = $adminimg_3->storeAs('admin/shops', $safeName, 'r2');
            $admin->national_id = $r2BaseUrl . '/' . $path;
        }

        $admin->save();
        return redirect()->back()->with('message', 'Shop Profile Update Successfully');
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $admin = Admin::findOrfail($id);
        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->status = $request->status;
        if ($request->status == 'Inactive') {
            $stafs = Admin::where('add_by', $admin->id)->get();
            foreach ($stafs as $staf) {
                $staf->status = 'Inactive';
                $staf->update();
            }
        } else {
            $stafs = Admin::where('add_by', $admin->id)->get();
            foreach ($stafs as $staf) {
                $staf->status = 'Active';
                $staf->update();
            }
        }
        if ($request->password) {
            $admin->password = Hash::make($request->password);
        }
        $admin->add_by = Auth::guard('admin')->user()->id;
        $admin->phone = $request->phone;
        $admin->save();
        $admin->roles()->detach();
        if ($request->roles) {
            $admin->assignRole($request->roles);
        }

        return redirect()->back()->with('message', 'Admin updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $admin = Admin::where('id', $id)->first();
        if (is_null($admin)) {
            return redirect()->back()->with('error', 'Something went wrong');
        } else {
            $admin->delete();
            return redirect()->back()->with('message', 'Admin Deleted Successfully');
        }
    }
}
