<?php

/**
 * Example Job - Process Payment
 */

namespace App\Jobs;

class ProcessPaymentJob
{
    public function handle(array $data): void
    {
        $orderId = $data['order_id'] ?? null;
        $amount = $data['amount'] ?? 0;
        $currency = $data['currency'] ?? 'USD';

        logger("Processing payment for order {$orderId}: {$amount} {$currency}");

        // Simulate payment processing
        sleep(2);

        // In production, integrate with payment gateway
        // Stripe, PayPal, etc.

        logger("Payment processed successfully for order {$orderId}");
    }
}
