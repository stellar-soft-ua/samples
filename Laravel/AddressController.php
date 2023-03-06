<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAddressRequest;
use App\Http\Requests\Admin\UpdateAddressRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\Address;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AddressController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Address::class, 'address');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $client = Auth::user()->client;
        $addresses = (in_array(Auth::user()->role_id,[User::ROLE_ADMIN, User::ROLE_SUPERADMIN]))
            ? Address::whereIn('customer_id', $client->customers->pluck('id') )->orWhere('client_id', $client->id)->paginate()
            : Address::paginate();

        return view('pages.addresses.index', compact('addresses'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Order $order, Customer $customer)
    {
        if ($order->customer_id !== $customer->id){
            abort(404);
        }

        $countries = Country::active()->orderBy('featured','desc')->pluck('name', 'code');
        return view('pages.addresses.create', compact('countries','order', 'customer'));
    }

    public function cardsAddressCreate()
    {
        $countries = Country::active()->orderBy('featured','desc')->pluck('name', 'code');
        return view('pages.addresses.cards_create', compact('countries'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAddressRequest $request, Order $order, Customer $customer)
    {
        if ($order->customer_id !== $customer->id){
            abort(404);
        }
        $address = $customer->addresses()->create($request->validated());

        return redirect()->route("customers.edit",['order'=>$order, 'customer'=>$customer]);
    }

    public function cardsAddressStore(StoreAddressRequest $request)
    {
        $address = Auth::user()->client->addresses()->create($request->validated());

        return redirect()->route('cards.create');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $order, Customer $customer, Address $address)
    {
        if ($customer->id !== $address->customer_id || $order->customer_id !== $customer->id){
            abort(404);
        }

        $countries = Country::active()->orderBy('featured','desc')->pluck('name', 'code');
        return view('pages.addresses.edit', compact('address','countries', 'order', 'customer'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAddressRequest $request, Order $order, Customer $customer, Address $address)
    {
        if ($customer->id !== $address->customer_id || $order->customer_id !== $customer->id){
            abort(404);
        }

        $address->fill($request->validated());
        $address->save();

        return redirect()->route('customers.edit',['order'=>$order, 'customer'=>$customer]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
