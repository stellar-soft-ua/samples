<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait ConvertPriceTrait
{

    private $primaryCurrency;
    private $allCurrencies;
    private $clientSettings;



    public function convertedRoundPrice($currency = "USD")
    {
        if (empty($currency)){
            $currency = "USD";
        }

        $price = $this->convertPriceTo($currency);

        // leave price as is if currencies same
        if (strtoupper($this->primaryCurrency) == strtoupper($currency)){
            return $this->attributes[$this->priceField];
        }

        if (!$this->clientSettings){
            if (!empty($this->isVariant)){
                $this->client = $this->product->client;
            }
            $this->clientSettings = $this->client->settings;
        }

        //implement new rounding rule
        $roundTo = ($this->clientSettings && $this->clientSettings->currencies_round_to) ? $this->clientSettings->currencies_round_to/100 : 1;
        if (intval($this->clientSettings->currencies_round_to)){
            $flawedNumber = floor($price);
            $roundingInterval = $roundTo;
            $roundedNumber = $flawedNumber;
            while ($roundedNumber <= $price){
                $roundedNumber += $roundingInterval;
            }
        } else {
            $roundedNumber = ceil($price);
        }


//        Log::debug(get_class($this));
//        Log::debug("round from {$this->primaryCurrency} to {$currency} and round");
//        Log::debug("{$this->attributes[$this->priceField]} -> {$price} -> {$roundedNumber}\n");

        return round($roundedNumber,2);
    }


    /**
     * Convert price to needed currency
     * @param $to
     * @return float
     */
    public function convertPriceTo($to = 'USD', $from = null)
    {
        $price = $this->convertPriceFrom($from);
//Log::debug("convert price {$price} to {$to}");

        switch (strtoupper($to)) {
            case 'USD':
                return $price;
                break;
            case 'EUR':
                return $price * $this->allCurrencies['EUR'];
                break;
            case 'GBP':
                return $price * $this->allCurrencies['GBP'];
                break;
            default:
                return 0;
                break;
        }
    }

    /**
     * Return price in USD
     * @return float
     */
    public function convertPriceFrom($from=null)
    {
        if (!empty($this->isVariant)){
            $this->client = $this->product->client;
        }

        if (empty($this->priceField)){
            $this->priceField = 'price';
        }

        $this->allCurrencies = $this->client->allCurrencies()->pluck("exchange_rate", "currency_code");
        if ($from){
            $this->primaryCurrency = $from;
        } else {
            $this->primaryCurrency = ($this->client->currency) ? $this->client->currency->currency_code : "USD";
        }

//        Log::debug("convert from {$this->primaryCurrency}");

        switch (strtoupper($this->primaryCurrency)) {
            case 'USD':
                return $this->attributes[$this->priceField];
                break;
            case 'EUR':
                return $this->attributes[$this->priceField] / $this->allCurrencies['EUR'];
                break;
            case 'GBP':
                return $this->attributes[$this->priceField] / $this->allCurrencies['GBP'];
                break;
            default:
                return 0;
                break;
        }
    }
}
