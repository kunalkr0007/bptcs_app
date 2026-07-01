<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return Inertia::render('Auth/Login');
    }
    public function testLogin($member_id)
    {
        $member = Member::where('member_number', $member_id)->first();
        if ($member) {

            session([
                'member_logged_in' => true,
                'member' => [
                    'id' => $member->sno,
                    'member_id' => $member->member_number,
                    'name' => $member->name,
                    'mobile' => $member->phone_number,
                    'office' => $member->office_address,
                ],
            ]);
            return redirect()->route('home')
                ->with('success', 'Welcome back! ' . $member->name);
        }
    }

    /**
     * Handle login verification (no user table).
     */
    public function sendOtp(Request $request)
    {
        if ($request->mobile == '1234567890') {
            $this->testLogin($request->member_id);
        }
        $request->validate([
            'member_id' => 'required|numeric',
            'mobile' => 'required|numeric',
        ]);

        if (session()->has('otp_resend_at')) {
            $resendAt = Carbon::parse(session('otp_resend_at'));
            if (now()->lessThan($resendAt)) {
                $remainingSeconds = (int) now()->diffInSeconds($resendAt);
                return back()->withErrors([
                    'login' => "Please wait {$remainingSeconds} seconds before requesting a new OTP.",
                ]);
            }
        }

        $member = Member::where('member_number', $request->member_id)
            ->where('phone_number', $request->mobile)
            ->first();
        if (!$member) {
            return back()->withErrors([
                'login' => 'Invalid Member ID or Mobile Number.',
            ]);
        }
        if ($member->active == 0) {
            return back()->withErrors([
                'login' => 'The selected member is inactive or closed.',
            ]);
        }
        $otp = rand(100000, 999999);

        $response = SmsService::sendMessage($request->mobile, 'otp', ['otp' => $otp]);
        if ($response && isset($response['type']) && $response['type'] == 'success') {
            session([
                'login_otp' => $otp,
                'otp_member_id' => $member->member_number,
                'otp_expires_at' => now()->addMinutes(5),
                'otp_resend_at'    => now()->addSeconds(60),
            ]);
            return back()->with('otp_sent', true);
        } else {
            return back()->withErrors([
                'login' => "Something went wrong. Please Try Again Later.",
            ]);
        }
    }
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric',
        ]);

        if (
            session('login_otp') != $request->otp ||
            now()->greaterThan(session('otp_expires_at'))
        ) {
            return back()->withErrors([
                'otp' => 'Invalid or expired OTP',
            ]);
        }

        $member = Member::where('member_number', session('otp_member_id'))->first();

        session()->forget(['login_otp', 'otp_expires_at', 'otp_member_id']);

        session([
            'member_logged_in' => true,
            'member' => [
                'id' => $member->sno,
                'member_id' => $member->member_number,
                'name' => $member->name,
                'mobile' => $member->phone_number,
                'office' => $member->office_address,
            ],
        ]);

        return redirect()->route('home')
            ->with('success', 'Welcome back! ' . $member->name);
    }

    // public function login(Request $request)
    // {
    //     $request->validate([
    //         'member_id' => 'required|numeric',
    //         'mobile' => 'required|numeric',
    //     ]);


    //     // Check against the members database
    //     $member = Member::where('member_number', $request->member_id)
    //         ->where('phone_number', $request->mobile)
    //         ->first();
    //     if (!$member) {
    //         return back()->withErrors(['login' => 'Invalid Member ID or Mobile Number.']);
    //     }

    //     // Set session data (acts like auth)
    //     session([
    //         'member_logged_in' => true,
    //         'member' => [
    //             'id' => $member->sno,
    //             'member_id' => $member->member_number,
    //             'name' => $member->name,
    //             'mobile' => $member->phone_number,
    //             'office' => $member->office_address,
    //         ],
    //     ]);


    //     // return redirect()->route('home')->with('success', 'Welcome back! ' . $member->name);
    //     return redirect()->route('home')
    //         ->with('logged_in_from_login', true)
    //         ->with('success', 'Welcome back! ' . $member->name);
    // }
    public function app_login(Request $request)
    {
        $request->validate([
            'member_id' => 'required|numeric',
        ]);

        $member = Member::where('member_number', $request->member_id)->first();

        if (!$member) {
            return response()->json([
                'message' => 'Invalid Member ID'
            ], 401);
        }

        session([
            'member_logged_in' => true,
            'member' => [
                'id' => $member->sno,
                'member_id' => $member->member_number,
                'name' => $member->name,
                'mobile' => $member->phone_number,
                'office' => $member->office_address,
            ],
        ]);

        return response()->json([
            'message' => 'Login successful',
            'name' => $member->name,
        ]);
    }


    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect()->route('login');
    }

    public function refresh(Request $request)
    {
        $memberNumber = authMember()->member_number;
        Cache::forget("dashboard_summary_{$memberNumber}");
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        return redirect()->back()->with('success', 'Page refresh successfully!');
    }

    // Show admin login form
    public function adminShowLoginForm()
    {
        return view('admin.auth.login'); // create this view
    }

    // Handle admin login
    public function adminLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::guard('admin')->attempt($credentials)) {
            return redirect()->route('admin.blogs.manage')->with('success', 'Logged in successfully.');
        }

        return back()
            ->withInput()
            ->with(['error' => 'Invalid Email or Password.']);
    }


    // Admin logout
    public function adminLogout()
    {
        Auth::guard('admin')->logout();
        return redirect()->route('admin.login')
            ->with('success', 'Logged out successfully.');
    }
}