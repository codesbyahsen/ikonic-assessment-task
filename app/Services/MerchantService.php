<?php

namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MerchantService
{
    /**
     * Register a new user and associated merchant.
     * Hint: Use the password field to store the API key.
     * Hint: Be sure to set the correct user type according to the constants in the User model.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant
     */
    public function register(array $data): Merchant
    {
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'type' => User::TYPE_MERCHANT,
                'password' => $data['api_key']
            ]);

            return $user->merchant()->create([
                'user_id' => $user->id,
                'domain' => $data['domain'],
                'display_name' => $data['name']
            ]);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * Update the user
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data)
    {
        try {
            $merchant = $user->merchant()->firstOrFail();

            // update merchant
            $merchant->update([
                'domain' => $data['domain'],
                'display_name' => $data['name']
            ]);

            // update user associated with merchant
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['api_key']
            ]);
        } catch (ModelNotFoundException $exception) {
            throw new ModelNotFoundException('Merchant Not Found');
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * Find a merchant by their email.
     * Hint: You'll need to look up the user first.
     *
     * @param string $email
     * @return Merchant|null
     */
    public function findMerchantByEmail(string $email): ?Merchant
    {
        try {
            $user = User::where('email', $email)->first();
            return $user?->merchant()->first();
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * Pay out all of an affiliate's orders.
     * Hint: You'll need to dispatch the job for each unpaid order.
     *
     * @param Affiliate $affiliate
     * @return void
     */
    public function payout(Affiliate $affiliate)
    {
        // TODO: Complete this method
    }
}
