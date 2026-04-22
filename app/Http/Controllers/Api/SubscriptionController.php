<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Support\UserProfileHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Subscription::query()->orderByDesc('id');

        $user = UserProfileHelper::resolveFromRequest($request);
        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            return SubscriptionResource::collection(collect());
        }

        if (!$request->boolean('includeDeleted')) {
            $query->whereNull('deleted_at');
        }

        $limit = (int) $request->query('limit', 0);
        if ($limit > 0) {
            $query->limit($limit);
        }

        return SubscriptionResource::collection($query->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = UserProfileHelper::resolveFromRequest($request);
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $this->validatePayload($request);
        $data = $this->buildPayload($request, false);
        $data['user_id'] = $user->id;

        $subscription = Subscription::create($data);
        UserProfileHelper::syncIncomeExpenseTotals($user);

        return (new SubscriptionResource($subscription))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function update(Request $request, Subscription $subscription)
    {
        $user = UserProfileHelper::resolveFromRequest($request);
        if (!$user || (int) $subscription->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $this->validatePayload($request);
        $data = $this->buildPayload($request, true);

        $subscription->fill($data);
        $subscription->save();
        UserProfileHelper::syncIncomeExpenseTotals($user);

        return new SubscriptionResource($subscription);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Subscription $subscription)
    {
        $user = UserProfileHelper::resolveFromRequest($request);
        if (!$user || (int) $subscription->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $subscription->delete();
        UserProfileHelper::syncIncomeExpenseTotals($user);

        return response()->noContent();
    }

    private function validatePayload(Request $request): void
    {
        $request->validate([
            'name' => 'sometimes|nullable|string|max:255',
            'amount' => 'sometimes|nullable|numeric',
            'monthlyAmount' => 'sometimes|nullable|numeric',
            'billingDay' => 'sometimes|nullable|integer|min:1|max:31',
            'billing_day' => 'sometimes|nullable|integer|min:1|max:31',
            'active' => 'sometimes|nullable|boolean',
            'categoryId' => 'sometimes|nullable|string|max:255',
            'category' => 'sometimes|nullable|string|max:255',
            'category_id' => 'sometimes|nullable|string|max:255',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(Request $request, bool $isUpdate): array
    {
        $data = [];

        if (!$isUpdate || $request->has('name')) {
            $data['name'] = $request->input('name');
        }

        if (!$isUpdate || $request->hasAny(['amount', 'monthlyAmount'])) {
            $amount = $request->input('amount', $request->input('monthlyAmount', 0));
            $data['amount'] = $this->normalizeNegative($amount);
        }

        if (!$isUpdate || $request->hasAny(['billingDay', 'billing_day'])) {
            $data['billing_day'] = (int) $request->input('billingDay', $request->input('billing_day', 1));
        }

        if (!$isUpdate || $request->has('active')) {
            $data['active'] = (bool) $request->input('active', true);
        }

        if (!$isUpdate || $request->hasAny(['categoryId', 'category', 'category_id'])) {
            $data['category_id'] = $request->input('categoryId', $request->input('category', $request->input('category_id')));
        }

        return $data;
    }

    private function normalizeNegative(mixed $value): float
    {
        $num = is_numeric($value) ? (float) $value : 0.0;

        return $num <= 0 ? $num : -abs($num);
    }
}
