<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class StripeUpgradeController extends Controller
{
    private const PLANS = [
        'weekly' => [
            'label' => 'Weekly Pro',
            'price' => '$2.99 / week',
            'config_key' => 'weekly_price_id',
        ],
        'monthly' => [
            'label' => 'Monthly Pro',
            'price' => '$19.99 / month',
            'config_key' => 'monthly_price_id',
        ],
    ];

    public function show(Request $request): View
    {
        $plan = $this->normalizePlan($request->query('plan'));

        return view('upgrade', [
            'email' => (string) $request->query('email', ''),
            'name' => (string) $request->query('name', ''),
            'returnUrl' => (string) $request->query('return_url', 'finova://upgrade-success'),
            'selectedPlan' => $plan,
            'plans' => self::PLANS,
            'stripeReady' => $this->isStripeConfigured(),
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'name' => ['nullable', 'string', 'max:255'],
            'plan' => ['required', 'in:weekly,monthly'],
            'return_url' => ['nullable', 'string', 'max:500'],
        ]);

        if (!$this->isStripeConfigured()) {
            return back()
                ->withInput()
                ->withErrors(['stripe' => 'Stripe is not configured yet. Add STRIPE_SECRET and price IDs to .env.']);
        }

        $priceId = $this->priceIdForPlan($data['plan']);
        if (!$priceId) {
            return back()
                ->withInput()
                ->withErrors(['stripe' => 'Stripe price ID is missing for this plan.']);
        }

        $returnUrl = $data['return_url'] ?: 'finova://upgrade-success';
        $successUrl = route('upgrade.success', [
            'return_url' => $returnUrl,
            'plan' => $data['plan'],
        ]) . '&session_id={CHECKOUT_SESSION_ID}';

        $response = Http::asForm()
            ->withToken((string) config('services.stripe.secret'))
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'subscription',
                'line_items[0][price]' => $priceId,
                'line_items[0][quantity]' => 1,
                'customer_email' => $data['email'],
                'client_reference_id' => $data['email'],
                'metadata[email]' => $data['email'],
                'metadata[name]' => $data['name'] ?? '',
                'metadata[plan]' => $data['plan'],
                'subscription_data[metadata][email]' => $data['email'],
                'subscription_data[metadata][name]' => $data['name'] ?? '',
                'subscription_data[metadata][plan]' => $data['plan'],
                'success_url' => $successUrl,
                'cancel_url' => route('upgrade.show', [
                    'email' => $data['email'],
                    'name' => $data['name'] ?? '',
                    'plan' => $data['plan'],
                    'return_url' => $returnUrl,
                ]),
            ]);

        if (!$response->successful()) {
            return back()
                ->withInput()
                ->withErrors(['stripe' => $response->json('error.message') ?: 'Stripe checkout could not be created.']);
        }

        return redirect()->away((string) $response->json('url'));
    }

    public function success(Request $request): View
    {
        $sessionId = (string) $request->query('session_id', '');
        $returnUrl = (string) $request->query('return_url', 'finova://upgrade-success');
        $plan = $this->normalizePlan($request->query('plan'));
        $email = '';

        if ($sessionId !== '' && config('services.stripe.secret')) {
            $response = Http::withToken((string) config('services.stripe.secret'))
                ->get("https://api.stripe.com/v1/checkout/sessions/{$sessionId}");

            if ($response->successful() && $response->json('payment_status') === 'paid') {
                $email = (string) ($response->json('customer_details.email') ?: $response->json('metadata.email') ?: '');
                $paidPlan = $this->normalizePlan($response->json('metadata.plan') ?: $plan);

                if ($email !== '') {
                    User::where('email', $email)->update([
                        'plan' => 'pro',
                        'plan_expires_at' => $paidPlan === 'monthly' ? now()->addMonth() : now()->addWeek(),
                        'stripe_customer_id' => $response->json('customer'),
                        'stripe_subscription_id' => $response->json('subscription'),
                    ]);
                }
            }
        }

        return view('upgrade-success', [
            'email' => $email,
            'returnUrl' => $returnUrl,
        ]);
    }

    private function normalizePlan(mixed $plan): string
    {
        return array_key_exists((string) $plan, self::PLANS) ? (string) $plan : 'weekly';
    }

    private function priceIdForPlan(string $plan): ?string
    {
        return config('services.stripe.' . self::PLANS[$plan]['config_key']);
    }

    private function isStripeConfigured(): bool
    {
        return filled(config('services.stripe.secret'))
            && filled(config('services.stripe.weekly_price_id'))
            && filled(config('services.stripe.monthly_price_id'));
    }
}
