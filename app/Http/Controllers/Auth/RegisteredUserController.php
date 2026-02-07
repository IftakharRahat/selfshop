<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Admin;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.register');
    }

    public function vendorcreate()
    {
        return view('auth.shopreg');
    }

    public function referralcreate($code)
    {
        $user = User::where('my_referral_code', $code)->first();
        if (isset($user)) {
            $referralID = $code;
            return view('auth.registerlink', ['referralID' => $referralID]);
        } else {
            abort(404);
        }
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        if(strlen($request->email)=='11'){
            $olduser = User::where('email', $request->email)->first();
            if($olduser){
               $olduseremail=$olduser;
            }else{
                $ema='88'.$request->email;
                $olduser = User::where('email',$ema)->first();
                $olduseremail=$olduser;
            }
        }else{
            $olduser = User::where('email', $request->email)->first(); 
            $olduseremail=$olduser;
        }
        
        
        if (isset($request->refer_by)) {
            $validity = User::where('my_referral_code', $request->refer_by)->first();
        } else {
            $validity = User::where('my_referral_code', 'Admin123')->first();
        }

        if (isset($validity)) {
            if (isset($olduseremail)) {
                return redirect()->back()->with('error', 'Email or phone already exist !');
            } else {
                $user = new User();
                $user->name = $request->name;
                $user->email = $request->email;
                $user->phone = $request->phone;
                $string = str_replace(' ', '', $request->name);
                $code = substr($string, 0, 3);

                $user->my_referral_code = strtoupper($code) . $this->uniqueID();
                if (isset($request->refer_by)) {
                    $user->refer_by = $request->refer_by;
                } else {
                    $user->refer_by = 'Admin123';
                }
                $otp = random_int(100000, 999999);
                $user->otp = $otp;
                $otppass = $otp;
                $user->password = Hash::make($request->password);
                $success = $user->save();

                if ($success) {
                    if (isset($request->refer_by)) {
                        $createreferral = User::where('my_referral_code', $request->refer_by)->first();
                        if (isset($createreferral)) {
                            $createreferral->my_referral = $createreferral->my_referral + 1;
                            $createreferral->update();
                        }
                    }
                }
            }
        } else {
            return redirect()->back()->with('error', 'Refer Code is not valid. Please enter a valid Refer code.');
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }

    public function storeven(Request $request)
    {
        $olduseremail = Admin::where('email', $request->email)->first();

        if (isset($olduseremail)) {
            return redirect()->back()->with('error', 'Email already exist !');
        } else {
            $admin = new Admin();
            $admin->name = $request->name;
            $admin->email = $request->email;
            $admin->status = 'Inactive';
            $admin->type = 'Shop';
            $admin->add_by = 1;
            $admin->password = Hash::make($request->password);
            $admin->phone = $request->phone;
            $admin->save();
            $admin->assignRole([2]);
            return redirect('login')->with('success', 'আপনার একাউন্টটি সফলভাবে রেজিস্ট্রেশন হয়েছে | অনুগ্রহপূর্বক অপেক্ষা করুন , 24 থেকে 48 ঘন্টার মধ্যে আমাদের সাপোর্ট টিম থেকে কল করে প্রয়োজনীয় তথ্য নিয়ে আপনার একাউন্টটি একটিভ করে দিবে | আমাদের পাশে থাকার জন্য আপনাকে ধন্যবাদ');
        }
    }

    public function uniqueID()
    {
        $lastReseller = User::latest('id')->first();
        if ($lastReseller) {
            $resellerID = $lastReseller->id + 1;
        } else {
            $resellerID = 1;
        }

        return 'SS00' . $resellerID;
    }
}
