<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\WithoutOnboardingInformation;
use App\Http\Requests\Admin\ClientOnboardingRequest;
use App\Http\Requests\Admin\WelcomeBillingRequest;
use App\Jobs\StripeRegisterClient;
use App\Models\Address;
use App\Models\Country;
use App\Services\StripeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use App\Jobs\StickyClientInitial;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use LVR\CreditCard\CardCvc;
use LVR\CreditCard\CardExpirationDate;
use LVR\CreditCard\CardNumber;
use Stripe\Exception\CardException;


class WelcomeController extends Controller
{
    /**
     * Instantiate a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(WithoutOnboardingInformation::class)->except('billing','updateBilling');
    }

    /**
     * Display a welcome page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Auth::user()->hasOnboardingInformation() && !Auth::user()->client->stripe_id){
            return redirect()->route('welcome.billing');
        }

        $roleId = Auth::user()->role_id;
        return view('welcome')->with('roleId', $roleId);
    }

    /**
     * Display a billing page.
     *
     * @return \Illuminate\Http\Response
     */
    public function billing()
    {
        if (Auth::user()->client->stripe_id){
            return redirect()->route('subscriptions.index');
        }
        $countries = Country::active()->orderBy('featured','desc')->pluck('name','code');
        return view('pages.welcome.billing', compact('countries'));
    }

    public function updateBilling(WelcomeBillingRequest $request)
    {
        $client = Auth::user()->client;

        if ($client->stripe_id){
            return redirect()->route('welcome.index');
        }

        $billing_address = Address::create(array_merge($request->validated()['address'],['client_id' => $client->id]));

        $date = Carbon::createFromFormat('m/y',$request->get('expire'));

        try {

            StripeRegisterClient::dispatchSync($client, [
                'number' => $request->get('cc'),
                'exp_month' => $date->format('m'),
                'exp_year' => $date->format("Y"),
                'cvc' => $request->get('cvc')
            ], $billing_address);

        } catch (CardException $e){
            // Looks some wrong with card
            $client = $client->fresh();

            // remove Stripe customer cause we make creation over Job and some processes of setup is goes step by step
            StripeService::deleteCustomer($client->stripe_id);
            $client->update(['stripe_id' => null]);

            return redirect()->back()->withErrors(['message' => 'Please try another credit card.']);
        }


        return redirect()->route('subscriptions.index');

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\ClientOnboardingRequest  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(ClientOnboardingRequest $request)
    {
        $request->validate($request->updateRules());

        /** @var \App\Models\User */
        $user = auth()->user();

        // Update Onboarding Information for auth client
        $user->client->fill($request->validated());
        $user->client->onboarding_information = true;
        $user->client->save();

        /**
         * Run Job's chain to prepear clients data
         */
        /*
        Bus::chain([
            new ShopifyInitialImport(Auth::user()),
            new StickyClientInitial(Auth::user()->client)
        ])->dispatch();
        */

        return redirect()
            ->route('welcome.billing')
            ->withSuccess('Information saved!');
    }
}
