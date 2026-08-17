<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Applies referral codes at signup and rewards both the referrer and the
 * referee once the referee's account is confirmed active.
 */
class ReferralService
{
    public function __construct(
        private readonly WalletService $wallets,
    ) {}

    public function findReferrer(?string $code): ?User
    {
        if (empty($code)) {
            return null;
        }

        return User::query()->where('referral_code', $code)->first();
    }

    /**
     * Reward the referrer and the new referee. Both receive the configured
     * reward amount credited straight to their wallets.
     */
    public function rewardSignup(User $referee): ?Referral
    {
        if (! $referee->referred_by) {
            return null;
        }

        $referrer = User::find($referee->referred_by);

        if (! $referrer) {
            return null;
        }

        return DB::transaction(function () use ($referrer, $referee) {
            $rewardAmount = (string) config('platform.referral_reward_amount');
            $currency = config('platform.base_currency');

            $referral = Referral::create([
                'referrer_id' => $referrer->id,
                'referee_id' => $referee->id,
                'reward_amount' => $rewardAmount,
                'currency' => $currency,
                'status' => 'pending',
            ]);

            $this->wallets->credit($referrer, $rewardAmount, $currency, 'referral_reward', "Referral reward for inviting {$referee->name}");
            $this->wallets->credit($referee, $rewardAmount, $currency, 'referral_reward', 'Welcome referral reward');

            $referral->update(['status' => 'rewarded', 'rewarded_at' => now()]);

            return $referral;
        });
    }
}
