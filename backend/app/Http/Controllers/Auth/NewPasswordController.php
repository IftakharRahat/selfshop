<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use App\Models\User;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        return view('auth.reset-password');
    }

    /**
     * Handle an incoming new password request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     * 
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
         
        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        
        if(strlen($request->email)=='11'){
            $user =User::where('email',$request->email)->where('otp',$request->otp)->first();
            if($user){
                $user =User::where('email',$request->email)->where('otp',$request->otp)->first();
            }else{
                $ema='88'.$request->email;
                $user =User::where('email',$ema)->where('otp',$request->otp)->first();
            }
        }else{
            $user =User::where('email',$request->email)->where('otp',$request->otp)->first();
        }
       
        
         
        if(isset($user)){
            $user->password=Hash::make($request->password);
            $user->update();
            $request->session()->forget('phone');
            return redirect('login')->with('success','Password updated ! please login here');
        }else{
            return redirect()->back()->withErrors('OTP not match')->withInput();
        }
    }
}
