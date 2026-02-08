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
            if (empty($admin->shop_icon)) {
            } else {
                unlink($admin->shop_icon);
            }
            $admin_nameimg_1 = $time . $adminimg_1->getClientOriginalName();
            $uploadPathimg_1 = ('public/images/shop/image/');
            $adminimg_1->move($uploadPathimg_1, $admin_nameimg_1);
            $ImgUrlimg_1 = $uploadPathimg_1 . $admin_nameimg_1;
            $admin->shop_icon = $ImgUrlimg_1;
        }

        $adminimg_2 = $request->file('trade_licence');

        if ($adminimg_2) {
            if (empty($admin->trade_licence)) {
            } else {
                unlink($admin->trade_licence);
            }
            $admin_nameimg_2 = $time . $adminimg_2->getClientOriginalName();
            $uploadPathimg_2 = ('public/images/shop/image/');
            $adminimg_2->move($uploadPathimg_2, $admin_nameimg_2);
            $ImgUrlimg_2 = $uploadPathimg_2 . $admin_nameimg_2;
            $admin->trade_licence = $ImgUrlimg_2;
        }

        $adminimg_3 = $request->file('national_id');

        if ($adminimg_3) {
            if (empty($admin->national_id)) {
            } else {
                unlink($admin->national_id);
            }
            $admin_nameimg_3 = $time . $adminimg_3->getClientOriginalName();
            $uploadPathimg_3 = ('public/images/shop/image/');
            $adminimg_3->move($uploadPathimg_3, $admin_nameimg_3);
            $ImgUrlimg_3 = $uploadPathimg_3 . $admin_nameimg_3;
            $admin->national_id = $ImgUrlimg_3;
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
            if (empty($admin->shop_icon)) {
            } else {
                unlink($admin->shop_icon);
            }
            $admin_nameimg_1 = $time . $adminimg_1->getClientOriginalName();
            $uploadPathimg_1 = ('public/images/shop/image/');
            $adminimg_1->move($uploadPathimg_1, $admin_nameimg_1);
            $ImgUrlimg_1 = $uploadPathimg_1 . $admin_nameimg_1;
            $admin->shop_icon = $ImgUrlimg_1;
        }

        $adminimg_2 = $request->file('trade_licence');

        if ($adminimg_2) {
            if (empty($admin->trade_licence)) {
            } else {
                unlink($admin->trade_licence);
            }
            $admin_nameimg_2 = $time . $adminimg_2->getClientOriginalName();
            $uploadPathimg_2 = ('public/images/shop/image/');
            $adminimg_2->move($uploadPathimg_2, $admin_nameimg_2);
            $ImgUrlimg_2 = $uploadPathimg_2 . $admin_nameimg_2;
            $admin->trade_licence = $ImgUrlimg_2;
        }

        $adminimg_3 = $request->file('national_id');

        if ($adminimg_3) {
            if (empty($admin->national_id)) {
            } else {
                unlink($admin->national_id);
            }
            $admin_nameimg_3 = $time . $adminimg_3->getClientOriginalName();
            $uploadPathimg_3 = ('public/images/shop/image/');
            $adminimg_3->move($uploadPathimg_3, $admin_nameimg_3);
            $ImgUrlimg_3 = $uploadPathimg_3 . $admin_nameimg_3;
            $admin->national_id = $ImgUrlimg_3;
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
