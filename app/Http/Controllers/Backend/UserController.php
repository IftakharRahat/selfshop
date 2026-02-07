<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use DataTables;
use Illuminate\Support\Facades\Hash;
use App\Imports\UserImport;
use App\Imports\ActiveuserImport;
use App\Models\Resellerinvoice;
use Excel;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('backend.content.users.index');
    }

    public function userdata(Request $request)
    {
        if ($request['phone'] != '') {
            $users = User::where('users.email', 'LIKE', '%' . $request['phone'] . '%');
        } else {
            if ($request['startDate'] != '' && $request['endDate'] != '') {
                $users = User::whereBetween('users.created_at', [$request['startDate'] . ' 00:00:00', $request['endDate'] . ' 23:59:59']);
            }
        }
        return Datatables::of($users)

            ->editColumn('user', function ($users) {
                return '<a href="../../resellerinvoice/user/view-dashboard/' . $users->id . '" target="_blank">' . User::where('id', $users->id)->first()->name . '( <span style="color:#613EEA">' . User::where('id', $users->id)->first()->my_referral_code . ' </span>)</a>';
            })
            ->addColumn('action', function ($users) {
                return '<a href="../admin/users/' . $users->id . '/edit" type="button" class="mt-2 btn btn-primary btn-sm"><i class="bi bi-pencil-square"></i></a>
                <a href="#" type="button" id="deleteBrandBtn" data-id="' . $users->id . '" class="btn btn-danger btn-sm" ><i class="bi bi-archive" ></i></a>';
            })
            ->addColumn('analytics', function ($users) {
                $inv = Resellerinvoice::where('user_id', $users->id)->first();
                if (isset($inv)) {
                    return 'Join Date: ' . $users->created_at->format('Y-m-d h:i a') . '<br>Member Ship: ' . $users->membership_status . '<br>Invoice ID: ' . $inv->invoiceID . '<br>Inv Date: ' . $inv->inviceDate . '<br>Payment Date: ' . $inv->paymentDate . '<br>Payable: ' . $inv->payable_amount . '<br>Paid: ' . $inv->paid_amount . '<br><button class="btn btn-success btn-sm">' . $users->p_system . '</button>';
                } else {
                    return 'Join Date: ' . $users->created_at->format('Y-m-d h:i a') . '<br>Member Ship: ' . $users->membership_status . '';
                }
            })


            ->escapeColumns([])->make(true);
    }

    public function activeuserdata(Request $request)
    {
        $users = User::where('status', 'Active')->where('membership_status', 'paid');


        if ($request['phone'] != '') {
            $users = $users->where('users.email', 'LIKE', '%' . $request['phone'] . '%');
        } else {
            if ($request['startDate'] != '' && $request['endDate'] != '') {
                $users = $users->whereBetween('users.created_at', [$request['startDate'] . ' 00:00:00', $request['endDate'] . ' 23:59:59']);
            }
        }

        return Datatables::of($users)
            ->editColumn('user', function ($users) {
                return '<a href="../../resellerinvoice/user/view-dashboard/' . $users->id . '" target="_blank">' . User::where('id', $users->id)->first()->name . '( <span style="color:#613EEA">' . User::where('id', $users->id)->first()->my_referral_code . ' </span>)</a>';
            })
            ->addColumn('action', function ($users) {
                return '<a href="../users/' . $users->id . '/edit" type="button" class="mt-2 btn btn-primary btn-sm"><i class="bi bi-pencil-square"></i></a>';
            })
            ->addColumn('analytics', function ($users) {
                $inv = Resellerinvoice::where('user_id', $users->id)->first();
                if (isset($inv)) {
                    return 'Join Date: ' . $users->created_at->format('Y-m-d h:i a') . '<br>Member Ship: ' . $users->membership_status . '<br>Invoice ID: ' . $inv->invoiceID . '<br>Inv Date: ' . $inv->inviceDate . '<br>Payment Date: ' . $inv->paymentDate . '<br>Payable: ' . $inv->payable_amount . '<br>Paid: ' . $inv->paid_amount . '<br><button class="btn btn-success btn-sm">' . $users->p_system . '</button>';
                } else {
                    return 'Join Date: ' . $users->created_at->format('Y-m-d h:i a') . '<br>Member Ship: ' . $users->membership_status . '';
                }
            })

            ->escapeColumns([])->make(true);
    }

    public function activeuser()
    {
        return view('backend.content.users.activeuser');
    }

    public function importView()
    {
        return view('backend.content.users.import');
    }

    public function activeimportView()
    {
        return view('backend.content.users.activeimport');
    }

    public function activeimport(Request $request)
    {

        Excel::import(new ActiveuserImport, $request->file);
        return redirect()->back();
    }

    public function import(Request $request)
    {

        Excel::import(new UserImport, $request->file);
        return redirect()->back();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->phone = $request->phone;
        $user->save();
        if ($request->roles) {
            $user->assignRole($request->roles);
        }

        return redirect()->back()->with('message', 'User created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::where('guard_name', 'web')->get();
        return view('backend.content.users.create', ['roles' => $roles]);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $roles = Role::where('guard_name', 'web')->get();
        $user = User::where('id', $id)->first();
        return view('backend.content.users.edit', ['roles' => $roles, 'user' => $user]);
    }

    public function show($id)
    {
        //
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

        $user = User::findOrfail($id);
        $user->name = $request->name;
        $user->email = $request->email;
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        $user->shop_name = $request->shop_name;
        $user->membership_status = $request->membership_status;
        $user->status = $request->status;
        if ($request->expire_date) {
            $user->expire_date = $request->expire_date;
        } else {
            $user->expire_date = '';
        }

        $user->phone = $request->phone;
        $user->save();

        return redirect()->back()->with('message', 'User updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::where('id', $id)->first();
        if (is_null($user)) {
            return redirect()->back()->with('error', 'Something went wrong');
        } else {
            $user->delete();
            return redirect()->back()->with('message', 'User Deleted Successfully');
        }
    }
}