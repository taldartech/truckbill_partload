<?php

namespace App\Console\Commands;

use App\Services\SubscriptionCheckoutService;
use Illuminate\Console\Command;

class ApplyUpcomingSubscriptions extends Command
{
    protected $signature = 'subscription:apply-upcoming';

    protected $description = 'Apply pending upcoming subscriptions when the current term has ended';

    public function handle(SubscriptionCheckoutService $checkoutService): int
    {
        $count = $checkoutService->applyDueUpcomingSubscriptions();
        $this->info("Applied {$count} upcoming subscription(s).");

        return self::SUCCESS;
    }
}
