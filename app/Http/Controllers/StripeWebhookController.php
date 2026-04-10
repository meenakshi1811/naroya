<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessStripeWebhookEventJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (! $signature) {
            return response()->json(['message' => 'Missing Stripe signature.'], 400);
        }

        $event = $this->verifyEvent($payload, $signature);

        if (! $event) {
            return response()->json(['message' => 'Invalid webhook payload.'], 400);
        }

        ProcessStripeWebhookEventJob::dispatch($event->toArray());

        return response()->json(['received' => true], 202);
    }

    private function verifyEvent(string $payload, string $signature): ?\Stripe\Event
    {
        $secrets = array_filter([
            config('services.stripe.webhook_secret'),
            config('services.stripe.test_webhook_secret'),
        ]);

        foreach ($secrets as $secret) {
            try {
                return Webhook::constructEvent($payload, $signature, $secret);
            } catch (UnexpectedValueException|SignatureVerificationException $e) {
                continue;
            }
        }

        Log::warning('Stripe webhook signature verification failed.');

        return null;
    }
}
