<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerificationController extends Controller
{
    use VerifiesEmails;

    /**
     * Where to redirect authenticated users after a resend or notice.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except('verify');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    /**
     * Confirm a signed verification link without requiring a browser session.
    */
    public function verify(Request $request): View
    {
        $authenticatedUser = $request->user();
        $user = $authenticatedUser instanceof User
            && hash_equals((string) $request->route('id'), (string) $authenticatedUser->getKey())
                ? $authenticatedUser
                : User::query()->findOrFail($request->route('id'));

        abort_unless(
            hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification())),
            403,
        );

        $alreadyVerified = $user->hasVerifiedEmail();

        if (! $alreadyVerified && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return view('auth.verification-confirmed', [
            'alreadyVerified' => $alreadyVerified,
            'awaitingApproval' => $user->hasPendingRegistrationApproval(),
        ]);
    }
}
