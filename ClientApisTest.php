<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\SubscriptionOrder;
use App\Services\StickyService;
use Database\Seeders\DevDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SeedDatabase;
use Tests\SeedDatabaseState;
use Tests\TestCase;
use Tests\CreatesApplication;

class ClientApisTest extends TestCase
{


    public function setUp(): void
    {
        parent::setUp();

    }

    /**
     * @test
     */
    public function get_clients()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . env('DISPUTIFIER_API_KEY')
        ])->get('/api/clients');
        $response->assertStatus(200);
        $data = json_decode($response->getContent());

        if (count($data)) {
            foreach ($data as $val) {
                $value = json_decode(json_encode($val), true);
                $this->assertArrayHasKey("id", $value);
                $this->assertArrayHasKey("company_name", $value);
                $this->assertArrayHasKey("website_url", $value);
                $this->assertArrayHasKey("myshopify_url", $value);
            }
        }
    }

    /**
     * @test
     */
    public function get_client_subscriptions()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . env('DISPUTIFIER_API_KEY')
        ])->get('/api/clients/1/subscriptions');
        $response->assertStatus(200);
        $data = json_decode($response->getContent());

        if (count($data)) {
            foreach ($data as $val) {
                $value = json_decode(json_encode($val), true);
                $this->assertArrayHasKey("id", $value);
                $this->assertArrayHasKey("status", $value);
                $this->assertArrayHasKey("price", $value);
                $this->assertArrayHasKey("customer", $value);
                $this->assertArrayHasKey("email", $value['customer'] ?? []);
                $this->assertArrayHasKey("phone", $value['customer'] ?? []);
            }
        }
    }

    /**
     * @test
     */
    public function get_client_subscription()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . env('DISPUTIFIER_API_KEY')
        ])->get('/api/clients/1/subscriptions/Jo-1002-1');
        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);

        if (count($data)) {
            $this->assertArrayHasKey("id", $data);
            $this->assertArrayHasKey("status", $data);
            $this->assertArrayHasKey("price", $data);
            $this->assertArrayHasKey("customer", $data);
            $this->assertArrayHasKey("email", $data['customer'] ?? []);
            $this->assertArrayHasKey("phone", $data['customer'] ?? []);
        }
    }

    /**
     * @test
     */
    public function get_client_subscription_transaction()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . env('DISPUTIFIER_API_KEY')
        ])->get('/api/clients/1/subscriptions/1/transactions');

        $response->assertStatus(200);

        $data = json_decode($response->getContent());

        if (count($data)) {
            foreach ($data as $val) {
                $value = json_decode(json_encode($val), true);
                $this->assertArrayHasKey("id", $value);
                $this->assertArrayHasKey("status", $value);
                $this->assertArrayHasKey("price", $value);
                $this->assertArrayHasKey("customer", $value);
                $this->assertArrayHasKey("email", $value['customer'] ?? []);
                $this->assertArrayHasKey("phone", $value['customer'] ?? []);
            }
        }
    }

    /**
     * @test
     */
    public function stop_client_subscription()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . env('DISPUTIFIER_API_KEY')
        ])->post('/api/clients/1/subscriptions/Jo-1002-1/stop');

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function refund_transaction()
    {
        $client = Client::find(1);
        $sticky = new StickyService($client);
        $paymentToken = $sticky->createTokenizePayment([
            'card_number' => '4242424242424242',
            'cvv' => '111',
            'expiry' => '12-2023',
        ]);
        if ($paymentToken['status'] === 'SUCCESS') {
            $data = [
                'firstName' => 'Ya',
                'lastName' => 'qqq',
                'shippingFirstName' => 'Ya',
                'shippingLastName' => 'qqq',
                'shippingAddress1' => 'The Q, Washington Street, Boston, MA, USA',
                'shippingCity' => 'qw',
                'shippingZip' => '11111',
                'shippingCountry' => 'US',
                'billingSameAsShipping' => 'YES',
                'phone' => '1234567890',
                'email' => 'admin1@example.com',
                'payment_token' => $paymentToken['data']['token'],
                'tranType' => 'Sale',
                'ipAddress' => '127.0.0.1',
                'campaignId' => 172,
                'promoCode' => 'fixed',
                'shippingId' => 2,
                'dynamic_shipping_charge' => '3.13',
                'offers' =>[
                    [
                        'offer_id' => 187,
                        'product_id' => 4528,
                        'billing_model_id' => 64,
                        'quantity' => 1,
                        'price' => 0,
                        'trial' => [
                            'product_id' => 4528,
                            'trial_workflow_id' => 118,
                            'use_workflow' => 1,
                        ],
                    ], [
                        'offer_id' => 187,
                        'product_id' => 4540,
                        'billing_model_id' => 2,
                        'quantity' => 1,
                        'price' => 219.13,
                    ], [
                        'offer_id' => 187,
                        'product_id' => 4573,
                        'billing_model_id' => 62,
                        'quantity' => 1,
                        'price' => 0,
                        'trial' => [
                            'product_id' => 4573,
                            'trial_workflow_id' => 118,
                            'use_workflow' => 1,
                        ],
                    ],[
                        'offer_id' => 187,
                        'product_id' => 4592,
                        'billing_model_id' => 2,
                        'quantity' => 1,
                        'price' => 119.67,
                    ],
                ],
                'shippingState' => 'AL',
            ];
            $stickyOrder = $sticky->postOrder($data);

            if ($stickyOrder['response_code'] == 100){

                $order = Order::create([
                    'front_facing_id' => $client->tiny_company_name ."-".(1000 + $client->orders->where('payment_status', '<>', Order::PAYMENT_STATUS_DECLINED)->count()),
                    'fulfillment_status' => Order::FULFILLMENT_STATUS_UNFULFILLED,
                    'client_id' => $client->id,
                    'customer_id' => 1,

                    'payment_status' => Order::PAYMENT_STATUS_PAID,
                    'checkout_session_id' => 1,

                    'total' => 10000,
                    'products' => '{}',
                    'discounts' => '{}',
                    'shipping' => '{}',

                    'sticky_id' => $stickyOrder['order_id'],
                ]);

                $index = 0;

                foreach ($stickyOrder['line_items'] as $idx => $item){
                    $product = Product::whereHas('subscription_products')->first();
                    $subscriptionProduct = $product->subscription_products->first();
                    $index++;
                    $subscription = Subscription::create([
                        'order_id' => $order->id,
                        'subscription_product_id' => $subscriptionProduct->id,
                        'price' => $subscriptionProduct->price,
                        'status' => 'trial',
                        'sticky_id' => $item['subscription_id'],
                        'front_facing_id' => "{$order->front_facing_id}-{$index}"
                    ]);

                    $numberOfSubscriptionOrders = $subscription->subscription_orders()->count() + 1;
                    $subscription_order = SubscriptionOrder::create([
                        'subscription_id' => $subscription->id,
                        'status' => 'approved',
                        'sticky_id' => $stickyOrder['order_id'],
                        'bill_id' => null,
                        'front_facing_id' => $subscription->front_facing_id . '-' . $numberOfSubscriptionOrders
                    ]);
                }
                $response = $this->withHeaders([
                    'Authorization' => 'Basic ' . env('DISPUTIFIER_API_KEY')
                ])->post(
                    '/api/clients/1/subscriptions/'. $subscription->front_facing_id . '/transactions/'.
                    $subscription_order->front_facing_id .'/refund'
                );

                $response->assertStatus(200);
            } else {
                $this->fail();
            }
        } else {
            $this->fail();
        }
    }
}
