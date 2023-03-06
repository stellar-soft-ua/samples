<?php

namespace App\Listeners\Klaviyo;

use App\Events\SubscriptionPauseEvent;
use App\Services\Integrations\KlaviyoService;

class SubscriptionPauseKlaviyoListener
{
    public KlaviyoService $klaviyoService;

    public function __construct(KlaviyoService $klaviyoService)
    {
        $this->klaviyoService = $klaviyoService;
    }

    public function handle(SubscriptionPauseEvent $event)
    {
        $subscription = $event->subscription;

        if (!config('integrations.klaviyo.active')){
            return false;
        }

        $this->klaviyoService->subscriptionPause($subscription);
    }
}