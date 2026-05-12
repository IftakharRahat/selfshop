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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:50',
        ]);

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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:50',
        ]);

        $admin = new Admin();
        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->type = 'hr';
        $admin->add_by = Auth::guard('admin')->user()->id;
        $admin->password = Hash::make($request->password);
        $admin->phone = $request->phone;
        $admin->save();

        // Assign role (as a label / template)
        if ($request->roles) {
            $admin->assignRole($request->roles);
        }

        // Sync direct permissions (granular access)
        if ($request->has('permission')) {
            $admin->syncPermissions($request->permission);
        }

        return redirect('admin/executive')->with('message', 'H.R / Executive created successfully');
    }

    public function hrexeedit($id)
    {
        $admin = Admin::findOrFail($id);
        $roles = Role::where('guard_name', 'admin')->get();
        $allpermissions = Permission::where('guard_name', 'admin')->get();
        $permission_groups = Admin::getPermissionGroups();
        return view('backend.content.admins.hrexeedit', [
            'admin' => $admin,
            'roles' => $roles,
            'allpermissions' => $allpermissions,
            'permission_groups' => $permission_groups,
        ]);
    }

    public function hrexeupdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:50',
        ]);

        $admin = Admin::findOrFail($id);
        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->phone = $request->phone;

        if ($request->filled('password')) {
            if (strlen($request->password) < 8) {
                return redirect()->back()->with('error', 'Password must be at least 8 characters.');
            }
            if ($request->password !== $request->confirmpassword) {
                return redirect()->back()->with('error', 'Password and confirmation do not match.');
            }
            $admin->password = Hash::make($request->password);
        }

        if ($request->has('status')) {
            $admin->status = $request->status;
        }

        $admin->save();

        // Update role
        $admin->roles()->detach();
        if ($request->roles) {
            $admin->assignRole($request->roles);
        }

        // Sync direct permissions
        $admin->syncPermissions($request->permission ?? []);

        return redirect('admin/executive')->with('message', 'H.R / Executive updated successfully');
    }

    /**
     * AJAX: Return permission names for a given role (used to auto-fill checkboxes).
     */
    public function rolePermissions($id)
    {
        $role = Role::findById($id, 'admin');
        $permissions = $role->permissions->pluck('name');
        return response()->json($permissions);
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
        $allpermissions = Permission::where('guard_name', 'admin')->get();
        $permission_groups = Admin::getPermissionGroups();
        return view('backend.content.admins.hrexecreate', [
            'roles' => $roles,
            'allpermissions' => $allpermissions,
            'permission_groups' => $permission_groups,
        ]);
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
        return redirect()->back()->with('message', 'Shop profile updated successfully.');
    }

    public function updateprofile(Request $request)
    {
        $adm_id = Auth::guard('admin')->user()->id;
        $admin = Admin::where('id', $adm_id)->first();

        if (!$admin) {
            return back()->with('error', 'Admin not found.');
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
        return redirect()->back()->with('message', 'Profile updated successfully.');
    }

    /**
     * Change the authenticated admin's password (separate from profile update).
     */
    public function updatepassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required|string',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|string|same:password',
        ], [
            'old_password.required' => 'Current password is required.',
            'password.required' => 'New password is required.',
            'password.min' => 'New password must be at least 8 characters.',
            'password_confirmation.same' => 'Passwords do not match.',
        ]);

        $admin = Admin::find(Auth::guard('admin')->user()->id);

        if (!$admin) {
            return back()->with('password_error', 'Admin not found.');
        }

        if (!Hash::check($request->old_password, $admin->password)) {
            return back()->with('password_error', 'Current password is incorrect.');
        }

        $admin->password = Hash::make($request->password);
        $admin->save();

        return redirect()->back()->with('password_success', 'Password changed successfully.');
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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:50',
            'status' => 'required|in:Active,Inactive',
        ]);

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
        if ($request->filled('password')) {
            if (strlen($request->password) < 8) {
                return redirect()->back()->with('error', 'Password must be at least 8 characters.');
            }
            // Server-side confirmation check
            if ($request->password !== $request->confirmpassword) {
                return redirect()->back()->with('error', 'Password and confirmation do not match.');
            }
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
            return redirect()->back()->with('message', 'Admin deleted successfully.');
        }
    }
}
