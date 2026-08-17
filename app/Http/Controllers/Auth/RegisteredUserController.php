<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use App\Models\Wallet;
use App\Services\ReferralService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(private readonly ReferralService $referrals) {}

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register', [
            'referralCode' => request()->query('ref'),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'referral_code' => ['nullable', 'string', 'max:12'],
        ]);

        $referrer = $this->referrals->findReferrer($request->referral_code);

        $user = DB::transaction(function () use ($request, $referrer) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'referred_by' => $referrer?->id,
                'currency_preference' => 'USD',
            ]);

            Profile::create(['user_id' => $user->id]);

            Wallet::create([
                'user_id' => $user->id,
                'currency' => $user->currency_preference,
                'balance' => 0,
                'ledger' => [],
            ]);

            return $user;
        });

        if ($referrer) {
            $this->referrals->rewardSignup($user);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
