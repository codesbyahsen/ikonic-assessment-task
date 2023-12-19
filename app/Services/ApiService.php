<?php

namespace App\Services;

use App\Models\Order;
use RuntimeException;
use App\Models\Merchant;
use App\Models\Affiliate;
use Illuminate\Support\Str;
use App\Jobs\PayoutOrderJob;

/**
 * You don't need to do anything here. This is just to help
 */
class ApiService
{
    /**
     * Create a new discount code for an affiliate
     *
     * @param Merchant $merchant
     *
     * @return array{id: int, code: string}
     */
    public function createDiscountCode(Merchant $merchant): array
    {
        return [
            'id' => rand(0, 100000),
            'code' => Str::uuid()
        ];
    }

    /**
     * Send a payout to an email
     *
     * @param  string $email
     * @param  float $amount
     * @return void
     * @throws RuntimeException
     */
    public function sendPayout(string $email, float $amount)
    {
        $orders = Order::where('payout_status', Order::STATUS_UNPAID)->get();

        foreach ($orders as $order) {
            // Dispatching job with instance of the order
            PayoutOrderJob::dispatch($order);
        }
    }
}
