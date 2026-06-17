<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RegistrationRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\RegistrationRequest;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    protected function authenticated(Request $request, $user): ?RedirectResponse
    {
        $hasPendingRegistration = RegistrationRequest::query()
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->where('status', RegistrationRequestStatus::Pending)
            ->exists();

        if (! $hasPendingRegistration) {
            return null;
        }

        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => 'Dein Konto ist angelegt, wartet aber noch auf Freigabe durch den Verein.',
        ]);
    }
}
