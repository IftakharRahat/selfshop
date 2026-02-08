<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Session;
use DB;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        if(strlen($request->phone)=='11'){
            $user =User::where('email',$request->phone)->first();
            if($user){
                $user =User::where('email',$request->phone)->first();
            }else{
                $ema='88'.$request->phone;
                $user =User::where('email',$ema)->first();
            }
        }else{
            $user =User::where('email',$request->phone)->first();
        }
        
        if(isset($user)){
            $otp = random_int(100000, 999999);
            $user->otp = $otp;
            $user->update();
            $otpcode=$otp;
            Session::put('phone',$request->phone);
            Http::get('http://bulksmsbd.net/api/smsapi?api_key=PwokJ9JcGrHVqm0Vmqp9&type=text&number='.$user->email.'&senderid=8809604902839&message=Dear '.$user->name.' Your password reset OTP is : '.$otpcode.'');
            return redirect('reset-password');
        }else{
            return redirect()->back()->withErrors('OPPS ! something went wrong. Please try again.');
        }
    }
}
