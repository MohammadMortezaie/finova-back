<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IncomeResource;
use App\Models\Income;
use App\Support\UserProfileHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IncomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Income::query()->orderByDesc('date')->orderByDesc('id');

        $user = UserProfileHelper::resolveFromRequest($request);
        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            return IncomeResource::collection(collect());
        }

        if (!$request->boolean('includeDeleted')) {
            $query->whereNull('deleted_at');
        }

        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->query('to'));
        }

        return IncomeResource::collection($query->get());
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

        $income = Income::create($data);
        UserProfileHelper::syncIncomeExpenseTotals($user);

        return (new IncomeResource($income))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function update(Request $request, Income $income)
    {
        $user = UserProfileHelper::resolveFromRequest($request);
        if (!$user || (int) $income->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $this->validatePayload($request);
        $data = $this->buildPayload($request, true);

        $income->fill($data);
        $income->save();
        UserProfileHelper::syncIncomeExpenseTotals($user);

        return new IncomeResource($income);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Income $income)
    {
        $user = UserProfileHelper::resolveFromRequest($request);
        if (!$user || (int) $income->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $income->delete();
        UserProfileHelper::syncIncomeExpenseTotals($user);

        return response()->noContent();
    }

    private function validatePayload(Request $request): void
    {
        $request->validate([
            'source' => 'sometimes|nullable|string|max:255',
            'sourceName' => 'sometimes|nullable|string|max:255',
            'source_name' => 'sometimes|nullable|string|max:255',
            'amount' => 'sometimes|nullable|numeric',
            'date' => 'sometimes|nullable|date',
            'note' => 'sometimes|nullable|string',
            'description' => 'sometimes|nullable|string',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(Request $request, bool $isUpdate): array
    {
        $data = [];

        if (!$isUpdate || $request->hasAny(['source', 'sourceName', 'source_name'])) {
            $data['source'] = $request->input('source', $request->input('sourceName', $request->input('source_name')));
        }

        if (!$isUpdate || $request->has('amount')) {
            $data['amount'] = $this->normalizePositive($request->input('amount', 0));
        }

        if (!$isUpdate || $request->has('date')) {
            $data['date'] = $request->input('date', now()->toDateString());
        }

        if (!$isUpdate || $request->hasAny(['note', 'description'])) {
            $data['note'] = $request->input('note', $request->input('description'));
        }

        return $data;
    }

    private function normalizePositive(mixed $value): float
    {
        $num = is_numeric($value) ? (float) $value : 0.0;

        return $num < 0 ? abs($num) : $num;
    }
}
