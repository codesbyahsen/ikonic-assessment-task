<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\Merchant;
use App\Models\Affiliate;
use App\Mail\AffiliateCreated;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Exceptions\AffiliateCreateException;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {
    }

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        $merchantUserEmail = $merchant->user->email;

        // get user as merchant
        $userAsMerchant = User::where('email', $email)->where('type', User::TYPE_MERCHANT)->first();
        // get user as affiliate
        $userAsAffiliate = User::where('email', $email)->where('type', User::TYPE_AFFILIATE)->first();

        // throw exception if email already use as merchant
        if ($userAsMerchant) {
            throw new AffiliateCreateException('Email is already in use as a merchant.');
        }

        // throw exception if email already use as affiliate
        if ($userAsAffiliate) {
            throw new AffiliateCreateException('Email is already in use as an affiliate.');
        }

        // check if email use as merchant
        if ($merchantUserEmail == $email) {
            $userId = $merchant->user->id;
            $merchantId = $merchant->id;
        } else {
            $user = $this->createNewUser($merchant->id, $name, $email); // creating new user
            $userId = $user->id;
            $merchantId = $merchant->id;
        }

        $affiliate = Affiliate::where('user_id', $userId)
            ->where('merchant_id', $merchantId)
            ->where('commission_rate', $commissionRate)
            ->first();

        if (!$affiliate) {
            $affiliate = Affiliate::create([
                'user_id' => $userId,
                'merchant_id' => $merchantId,
                'commission_rate' => $commissionRate,
                'discount_code' => $this->apiService->createDiscountCode($merchant)['code']
            ]);

            if ($affiliate) {
                Mail::to($email)->send(new AffiliateCreated($affiliate));
            }
        }

        return $affiliate;
    }

    /**
     * Create new user as affiliate
     *
     * @param  string $merchantId
     * @param  string $name
     * @param  string $email
     */
    public function createNewUser($merchantId, $name, $email): User
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            return $user;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'type' => User::TYPE_AFFILIATE
        ]);

        $merchantUser = Merchant::find($merchantId);

        if ($merchantUser) {
            $merchantUser->increment('turn_customers_into_affiliates');
        }
        return $user;
    }
}
