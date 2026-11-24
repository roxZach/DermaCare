<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Use cases: Register Account (<<include>> Verify Email / Phone (OTP)), Login, Logout.
 */
class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required']);
        if (! Auth::attempt($data, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email or password is incorrect.'])->onlyInput('email');
        }
        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'Your account has been deactivated. Please contact the clinic.']);
        }
        if (! $user->email_verified_at) {
            Auth::logout();
            $request->session()->put('otp_user_id', $user->id);
            $this->sendOtp($user);
            return redirect()->route('otp.show')->with('status', 'Please verify your email first. A new code has been sent.');
        }
        $request->session()->regenerate();
        AuditLog::record('login', $user->email);
        return redirect()->intended(route($user->homeRoute()));
    }

    public function logout(Request $request)
    {
        AuditLog::record('logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'phone' => 'required|string|max:30',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $user = User::create([
            'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'],
            'password' => $data['password'], 'role' => 'patient',
        ]);
        Patient::create([
            'user_id' => $user->id, 'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'],
            'date_of_birth' => $data['date_of_birth'], 'gender' => $data['gender'],
        ]);
        $request->session()->put('otp_user_id', $user->id);
        $this->sendOtp($user);
        return redirect()->route('otp.show')->with('status', 'Account created. Enter the 6-digit code we sent to your email.');
    }

    public function showOtp(Request $request)
    {
        $user = User::find($request->session()->get('otp_user_id'));
        if (! $user) {
            return redirect()->route('login');
        }
        return view('auth.otp', ['user' => $user, 'demoCode' => session('demo_otp')]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);
        $user = User::find($request->session()->get('otp_user_id'));
        if (! $user || ! $user->otp_code || $user->otp_expires_at?->isPast()) {
            return back()->withErrors(['code' => 'The code has expired. Please request a new one.']);
        }
        if (! Hash::check($request->code, $user->otp_code)) {
            return back()->withErrors(['code' => 'The code is not correct.']);
        }
        $user->forceFill(['email_verified_at' => now(), 'otp_code' => null, 'otp_expires_at' => null])->save();
        $request->session()->forget(['otp_user_id', 'demo_otp']);
        Auth::login($user);
        $request->session()->regenerate();
        AuditLog::record('email_verified', $user->email);
        return redirect()->route($user->homeRoute())->with('success', 'Welcome to DermaCare AI, ' . $user->name . '!');
    }

    public function resendOtp(Request $request)
    {
        $user = User::find($request->session()->get('otp_user_id'));
        if ($user) {
            $this->sendOtp($user);
        }
        return back()->with('status', 'A new code has been sent.');
    }

    /** Generate a 6-digit OTP, store its hash and email it (MAIL_MAILER=log writes it to storage/logs). */
    private function sendOtp(User $user): void
    {
        $code = (string) random_int(100000, 999999);
        $user->forceFill(['otp_code' => Hash::make($code), 'otp_expires_at' => now()->addMinutes(10)])->save();
        try {
            Mail::raw("Your DermaCare AI verification code is: $code\nIt expires in 10 minutes.", function ($m) use ($user) {
                $m->to($user->email)->subject('DermaCare AI verification code');
            });
        } catch (\Throwable $e) {
            report($e);
        }
        // Demo convenience: when no real mail server is configured, show the code on screen.
        if (config('mail.default') === 'log' || config('app.debug')) {
            session()->flash('demo_otp', $code);
        }
    }
}
