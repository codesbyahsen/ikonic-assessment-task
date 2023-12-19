<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\Merchant;
use App\Models\Affiliate;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected AffiliateService $affiliateService
    ) {
    }

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        $existingOrder = Order::where('external_order_id', $data['order_id'])->first();

        if ($existingOrder) {
            return;
        }

        // Find or create an affiliate based on customer email
        $affiliate = Affiliate::where('discount_code', $data['discount_code'])->first();

        if (!$affiliate) {
            // Create a new affiliate if not found
            $merchant = Merchant::where('domain', $data['merchant_domain'])->first();
            $affiliate = $this->affiliateService->register(
                $merchant,
                $data['customer_email'],
                $data['customer_name'],
                $merchant->default_commission_rate
            );
        }
        $merchant = Merchant::where('domain', $data['merchant_domain'])->first();

        Order::create([
            'affiliate_id' => $affiliate?->id,
            'merchant_id' => $merchant?->id,
            'subtotal' => $data['subtotal_price'],
            'commission_owed' => $data['subtotal_price'] * $affiliate->commission_rate,
            'payout_status' => Order::STATUS_UNPAID,
            'external_order_id' => $data['order_id']
        ]);
    }
}
