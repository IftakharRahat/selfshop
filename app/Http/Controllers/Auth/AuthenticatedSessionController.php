<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use DB;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Check which route is being accessed
        if (Route::currentRouteName() == 'vendor.login') {
            return view('auth.vendor-login'); // Vendor login page
        }
        
        return view('auth.login'); // Reseller login page
    }


    /**
     * Handle an incoming authentication request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Check if it's vendor login
        $isVendorLogin = str_contains($request->url(), 'vendor-login') || 
                         $request->routeIs('vendor.login.submit');

        if ($isVendorLogin) {
            return $this->handleVendorLogin($request);
        } else {
            return $this->handleResellerLogin($request);
        }
    }

    /**
     * Handle vendor login
     */
    private function handleVendorLogin(Request $request)
    {
        // Vendor login logic (using email only)
        $user = DB::table('users')
            ->where('email', $request->email)
            ->where('user_type', 'vendor') // Assuming you have a user_type column
            ->first();

        if(isset($user)){
            if($user->status == 'Active'){
                if(Auth::guard('web')->attempt(['email' => $user->email, 'password' => $request->password])){
                    // Redirect to vendor dashboard
                    return redirect()->intended(route('vendor.dashboard'));
                } else {
                    return redirect()->back()->with('error', 'Password Does Not Match');
                }
            } else {
                return redirect()->back()->with('error', 'Your vendor account is not active');
            }
        } else {
            return redirect()->back()->with('error', 'Vendor account not found');
        }
    }

    /**
     * Handle reseller login (your existing logic)
     */
    private function handleResellerLogin(Request $request)
    {
        if(strlen($request->email) == '11'){
            $user = DB::table('users')
                ->whereIn('status', ['Active','Inactive'])
                ->where('email', $request->email)
                ->first();
                
            if($user){
                $user = DB::table('users')
                    ->whereIn('status', ['Active','Inactive'])
                    ->where('email', $request->email)
                    ->first();
            } else {
                $ema = '88' . $request->email;
                $user = DB::table('users')
                    ->whereIn('status', ['Active','Inactive'])
                    ->where('email', $ema)
                    ->first();
            }
        } else {
            $user = DB::table('users')
                ->whereIn('status', ['Active','Inactive'])
                ->where('email', $request->email)
                ->first();
        }
        

        if(isset($user)){
            if($user->status == 'Active'){
                if($user->expire_date >= date('Y-m-d')){
                    if(Auth::guard('web')->attempt(['email' => $user->email, 'password' => $request->password])){
                        return redirect()->intended(RouteServiceProvider::HOME);
                    } else {
                        return redirect()->back()->with('error', 'Password Does Not Match');
                    }
                } else {
                    if(isset($user->expire_date)){
                        return redirect()->back()->with('error', 'Your account is expire please contact support');
                    } else {
                        if(Auth::guard('web')->attempt(['email' => $user->email, 'password' => $request->password])){
                            return redirect()->intended(RouteServiceProvider::HOME);
                        }
                    }
                }
            } else {
                if(Auth::guard('web')->attempt(['email' => $user->email, 'password' => $request->password])){
                    return redirect()->intended(RouteServiceProvider::HOME);
                } else {
                     return redirect()->back()->with('error', 'Password Does Not Match');
                }
            }
        } else {
            $user = DB::table('users')
                ->where('email', $request->email)
                ->first();
                
            if(isset($user)){
                if (!Hash::check($request->password, $user->password)) {
                    return redirect()->back()->with('error', 'Password Does Not Match');
                }
                return redirect()->back()->with('error', 'You are blocked by authority');
            } else {
                return redirect()->back()->with('error', 'Information Does Not Match');
            }
        }
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}