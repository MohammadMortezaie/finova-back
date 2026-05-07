<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();

        if (!$this->hasValidSignature($payload, (string) $request->header('Stripe-Signature'))) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);
        if (!is_array($event)) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $type = (string) ($event['type'] ?? '');
        $object = $event['data']['object'] ?? [];

        if (in_array($type, ['customer.subscription.updated', 'customer.subscription.deleted'], true)) {
            $this->syncSubscription($object, $type);
        }

        return response()->json(['received' => true]);
    }

    private function syncSubscription(array $subscription, string $eventType): void
    {
        $subscriptionId = (string) ($subscription['id'] ?? '');
        $customerId = (string) ($subscription['customer'] ?? '');
        $email = (string) data_get($subscription, 'metadata.email', '');

        $user = null;
        if ($subscriptionId !== '') {
            $user = User::where('stripe_subscription_id', $subscriptionId)->first();
        }
        if (!$user && $customerId !== '') {
            $user = User::where('stripe_customer_id', $customerId)->first();
        }
        if (!$user && $email !== '') {
            $user = User::where('email', $email)->first();
        }
        if (!$user && $customerId !== '') {
            $customerEmail = $this->customerEmail($customerId);
            if ($customerEmail !== '') {
                $user = User::where('email', $customerEmail)->first();
            }
        }
        if (!$user) {
            return;
        }

        $status = (string) ($subscription['status'] ?? '');
        $cancelAtPeriodEnd = (bool) ($subscription['cancel_at_period_end'] ?? false);
        $periodEnd = $this->timestampToDate($subscription['current_period_end'] ?? null);

        if ($eventType === 'customer.subscription.deleted' || in_array($status, ['canceled', 'unpaid', 'incomplete_expired'], true)) {
            $user->fill([
                'plan' => null,
                'plan_expires_at' => now(),
                'stripe_customer_id' => $customerId ?: $user->stripe_customer_id,
                'stripe_subscription_id' => $subscriptionId ?: $user->stripe_subscription_id,
            ])->save();
            return;
        }

        if (in_array($status, ['active', 'trialing'], true)) {
            $user->fill([
                'plan' => 'pro',
                'plan_expires_at' => $cancelAtPeriodEnd ? $periodEnd : null,
                'stripe_customer_id' => $customerId ?: $user->stripe_customer_id,
                'stripe_subscription_id' => $subscriptionId ?: $user->stripe_subscription_id,
            ])->save();
        }
    }

    private function hasValidSignature(string $payload, string $signatureHeader): bool
    {
        $secret = (string) config('services.stripe.webhook_secret');
        if ($secret === '') {
            return false;
        }

        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, null);
            if ($key === 't') {
                $timestamp = $value;
            }
            if ($key === 'v1' && $value) {
                $signatures[] = $value;
            }
        }

        if (!$timestamp || !$signatures) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    private function customerEmail(string $customerId): string
    {
        if (!config('services.stripe.secret')) {
            return '';
        }

        $response = Http::withToken((string) config('services.stripe.secret'))
            ->get("https://api.stripe.com/v1/customers/{$customerId}");

        return $response->successful() ? (string) $response->json('email', '') : '';
    }

    private function timestampToDate(mixed $timestamp): ?Carbon
    {
        return is_numeric($timestamp) ? Carbon::createFromTimestamp((int) $timestamp) : null;
    }
}
