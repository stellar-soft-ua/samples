<?php

namespace App\Http\Controllers\Api;

use App\Events\SubscriptionCancelEvent;
use App\Events\SubscriptionRefundedEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiClientRefundTransactionRequest;
use App\Http\Requests\Api\ApiGetClientSubscriptionRequest;
use App\Http\Requests\Api\ApiGetClientSubscriptionsRequest;
use App\Http\Requests\Api\ApiGetClientSubscriptionTransactionsRequest;
use App\Jobs\StickySubscriptionOrderRefund;
use App\Models\Client;
use App\Models\Subscription;
use App\Models\SubscriptionOrder;
use App\Services\StickyService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiClientsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['integer'],
            'company_name' => ['string'],
            'website_url' => ['string', 'url'],
            'myshopify_url' => ['string', 'url'],
        ]);
        $clients = Client::query()->where(function ($query) use ($data) {
            foreach($data as $key => $select) {
                $query->orWhere($key, '=', $select);
            }
        })->select(['id', 'company_name', 'website_url', 'myshopify_url'])->get();

        return response()->json($clients->toArray());
    }

    public function clientSubscriptions(ApiGetClientSubscriptionsRequest $request, int $client_id): JsonResponse
    {
        $data = $request->validated();
        $ordersQuery = Subscription::query()
            ->whereHas('order', function($query) use ($client_id) {
                $query->where('client_id', $client_id);
            });
        if (isset($data['start_datetime'])) {
            $ordersQuery->where('created_at', '>',
                (new Carbon($data['start_datetime']))->startOfDay()
            );
        }
        if (isset($data['end_datetime'])) {
            $ordersQuery->where('created_at', '<',
                (new Carbon($data['end_datetime']))->endOfDay()
            );
        }
        $subscriptions = $ordersQuery->orderBy('created_at')->get();

        $response = [];

        foreach ($subscriptions as $subscription) {
            $response[] = [
                'id' => $subscription->front_facing_id,
                'status' => $subscription->status,
                'price' => $subscription->price,
                'customer' => [
                    'email' => $subscription->order->customer->email,
                    'phone' => $subscription->order->customer->phone,
                ]
            ];
        }

        return response()->json($response);
    }

    public function getClientSubscription(ApiGetClientSubscriptionRequest $request, int $client_id, $subscription_id): JsonResponse
    {
        $subscription = Subscription::query()->where('front_facing_id', $subscription_id)
            ->whereHas('order', function($query) use ($client_id) {
                $query->where('client_id', $client_id);
            })->first();

        if (!$subscription) {
            abort(404);
        }

        return response()->json([
            'id' => $subscription->front_facing_id,
            'status' => $subscription->status,
            'price' => $subscription->price,
            'created_at' => $subscription->created_at->toDateTimeString(),
            'updated_at' => $subscription->updated_at->toDateTimeString(),
            'customer' => [
                'email' => $subscription->order->customer->email,
                'phone' => $subscription->order->customer->phone,
            ]
        ]);
    }

    public function clientSubscriptionTransactions(ApiGetClientSubscriptionTransactionsRequest $request, int $client_id, string $subscription_id): JsonResponse
    {
        $subscriptionOrders = SubscriptionOrder::query()
            ->whereHas('subscription', function($query) use ($client_id, $subscription_id) {
                $query->where('front_facing_id', $subscription_id);
                $query->whereHas('subscription_product', function($query) use ($client_id) {
                    $query->where('client_id', $client_id);
                });
            })->get();
        $response = [];

        foreach ($subscriptionOrders as $subscriptionOrder) {
            $response[] = [
                'id' => $subscriptionOrder->front_facing_id,
                'status' => $subscriptionOrder->status,
                'price' => $subscriptionOrder->subscription->price,
                'customer' => [
                    'email' => $subscriptionOrder->subscription->order->customer->email,
                    'phone' => $subscriptionOrder->subscription->order->customer->phone,
                ]
            ];
        }
        return response()->json($response);
    }

    public function stopSubscription(ApiGetClientSubscriptionRequest $request, int $client_id, $subscription_id): JsonResponse
    {
        $subscription = Subscription::query()->where('front_facing_id', $subscription_id)
            ->whereHas('order', function($query) use ($client_id) {
                $query->where('client_id', $client_id);
            })->first();

        if (!$subscription) {
            abort(404);
        }

        $sticky = new StickyService($subscription->order->client);

        $subscription->status = Subscription::STATUS_STOPPED;
        $result = $sticky->subscriptionStop($subscription->sticky_id);
        SubscriptionCancelEvent::dispatch($subscription);
        $status = "Subscription stopped successfully!";

        if ($subscription->isDirty()) {
            $subscription->save();
        }

        return response()->json(['msg' => $status]);
    }

    public function refundTransaction(ApiClientRefundTransactionRequest $request, int $client_id, $subscription_id, $transaction_id): JsonResponse
    {
        $subscriptionOrder = SubscriptionOrder::query()->where('front_facing_id', $transaction_id)
            ->whereHas('subscription', function($query) use ($client_id, $subscription_id) {
                $query->where('front_facing_id', $subscription_id);
                $query->whereHas('subscription_product', function($query) use ($client_id) {
                    $query->where('client_id', $client_id);
                });
            })->first();

        if (!$subscriptionOrder) {
            abort(404);
        }

        if ($subscriptionOrder->status == SubscriptionOrder::STATUS_APPROVED){
            try{
                StickySubscriptionOrderRefund::dispatchSync($subscriptionOrder->sticky_id, $subscriptionOrder->subscription->price);
            } catch (\Exception $e){
                return response()->json([
                    'message' => 'Transaction refund failed. Please contact support!'
                ], 400);
            }

            SubscriptionRefundedEvent::dispatch($subscriptionOrder->subscription->order->client, $subscriptionOrder->subscription, [$subscriptionOrder]);
        }

        $subscriptionOrder = SubscriptionOrder::where('sticky_id', $subscriptionOrder->sticky_id)->first();

        if ($subscriptionOrder->status == SubscriptionOrder::STATUS_REFUNDED){
            return response()->json([
                'message' => 'Subscription transactions refunded!'
            ]);
        }

        return response()->json([
            'message' => 'The subscription transaction status is not approved!'
        ], 400);
    }
}
