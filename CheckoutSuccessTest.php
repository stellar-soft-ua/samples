<?php

namespace Tests\Feature;

use App\Models\CheckoutSession;
use App\Models\Currency;
use Tests\TestCase;
use App\Models\Discount;
use Illuminate\Support\Facades\Log;

class CheckoutSuccessTest extends TestCase
{

    private $pageResponse;
    private $pageData;

    public function setUp(): void
    {
        parent::setUp();

        $this->pageResponse = $this->get('/1/checkout/testsuccess/success');
        $this->pageData = $this->pageResponse->getOriginalContent()->getData();

    }

    public function test_success_page()
    {

        $this->pageResponse->assertStatus(200);
    }

    public function test_success_page_data()
    {

        $this->assertArrayHasKey("checkoutSession", $this->pageData);

        $checkoutSessionOrder = $this->pageData['checkoutSession']->order->toArray();

        $this->assertEquals($checkoutSessionOrder['front_facing_id'], 'Jo-1005');

        // check products count
        $this->assertEquals(count($checkoutSessionOrder['products']), 4);

        // check shipping
        $this->assertEquals(count($checkoutSessionOrder['shipping']), 14);
    }


    public function test_success_page_customer()
    {
        $customer = $this->pageData['checkoutSession']->order['customer'];

        // check shipping section
        $shippingFullName = $customer->shipping_address->first_name." ".$customer->shipping_address->last_name;
        $shippingAddress1 = $customer->shipping_address->address_1;
        $shippingAddress2 = $customer->shipping_address->address_2;
        $shippingCity = $customer->shipping_address->city;
        $shippingZip = $customer->shipping_address->zip_code;
        $shippingCountry = $customer->shipping_address->country;

        $this->pageResponse->assertSeeText($shippingFullName);
        $this->pageResponse->assertSeeText($shippingAddress1);
        $this->pageResponse->assertSeeText($shippingAddress2);
        $this->pageResponse->assertSeeText($shippingCity);
        $this->pageResponse->assertSeeText($shippingZip);
        $this->pageResponse->assertSeeText($shippingCountry);


    }


    public function test_success_page_shipping()
    {
        $checkoutSessionOrder = $this->pageData['checkoutSession']->order->toArray();

        $this->pageResponse->assertSeeText($checkoutSessionOrder['shipping']['name'],true);

    }


    public function test_success_page_products()
    {

        $checkoutSessionOrder = $this->pageData['checkoutSession']->order->toArray();

        foreach ($checkoutSessionOrder['products'] as $product){

            if ($product['subscription_products'] && count($product['subscription_products'])){

                $dTrial = $product['subscription_products'][0]['trial_period'] ?? null;
                $dRebill = $product['subscription_products'][0]['rebill_period'] ?? null;
                $productPrice = '$' . number_format($product['subscription_products'][0]['price'],2);

                $productTitle = $product['subscription_products'][0]['name'];
                $productText = $product['subscription_products'][0]['text_overwrite'] ?? "{$dTrial} Day Free Trial";
                $productSubtext = $product['subscription_products'][0]['subtext_overwrite'] ?? "Then {$productPrice} per {$dRebill} days";

            } else {

                $productTitle = $product['name'];
                $productPrice = '$' . number_format($product['price'],2);
                $productText = null;
                $productSubtext = null;

            }

            $this->pageResponse->assertSeeText($productTitle, true);

            if(!$productSubtext) $this->pageResponse->assertSeeText($productPrice, true);

            if($productText) $this->pageResponse->assertSeeText($productText, true);
            if($productSubtext) $this->pageResponse->assertSeeText($productSubtext, true);
        }



    }


    public function test_success_page_discount()
    {
        $productsTotal = collect($this->pageData['checkoutSession']->order['products'])->sum('price');
        $discount = $this->pageData['checkoutSession']->order['discounts'];

        switch ($discount['type']){
            case Discount::TYPE_FIXED:
                $discountAmount = $discount['value'];
                break;
            case Discount::TYPE_PERCENTAGE:
                $discountAmount = ($productsTotal/100)*$discount['value'];
                break;
            default:
                $discountAmount = 0;
                break;
        }

        $this->pageResponse->assertSeeText('FIxedDiscount', true);
        //TODO: some strange with calculate discount amount in testing env
        $this->pageResponse->assertSeeText('- $'.number_format($discountAmount,2), true);

    }

    public function test_success_page_totals()
    {
        $productsTotal = collect($this->pageData['checkoutSession']->order['products'])->sum('price');
        $checkoutSessionOrder = $this->pageData['checkoutSession']->order->toArray();

        $subtotalPrice = "$".number_format($productsTotal,2);
        $totalPrice = "$".number_format($checkoutSessionOrder['total'],2);

        // check subtotal
        $this->pageResponse->assertSeeText("Subtotal{$subtotalPrice}", true);

        // check total
        $this->pageResponse->assertSeeText("TotalUSD {$totalPrice}", true);

    }

    public function test_success_currency()
    {

        $session = CheckoutSession::where('session_id', 'testsuccess')->first();
        $currency = Currency::find(2);

        $session->update([
            'currency_id' => $currency->id
        ]);



        $this->pageResponse = $this->get('/1/checkout/testsuccess/success');


        $this->pageData = $this->pageResponse->getOriginalContent()->getData();

        $price = number_format(collect($this->pageData['data']['products'])->first()['price'],2);

        $this->pageResponse->assertSeeText($currency->symbol . $price);
    }

}
