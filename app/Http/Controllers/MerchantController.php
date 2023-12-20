<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;

class MerchantController extends Controller
{
    public function __construct(
        MerchantService $merchantService
    ) {
    }

    /**
     * Useful order statistics for the merchant API.
     *
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {
        $from = Carbon::parse($request->input('from'));
        $to = Carbon::parse($request->input('to'));

        $orders = Order::whereBetween('created_at', [$from, $to]);

        return response()->json([
            'count' => $orders->count(),
            'commissions_owed' => $orders->where('payout_status', Order::STATUS_UNPAID)->whereHas('affiliate')->sum('commission_owed'),
            'revenue' => $orders->sum('subtotal'),
        ]);
    }
}
