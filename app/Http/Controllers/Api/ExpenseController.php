<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Support\PublicStorageUrl;
use App\Support\UserProfileHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Expense::query()->orderByDesc('date')->orderByDesc('id');

        $user = UserProfileHelper::resolveFromRequest($request);
        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            return ExpenseResource::collection(collect());
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

        return ExpenseResource::collection($query->get());
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

        $expense = Expense::create($data);
        UserProfileHelper::syncIncomeExpenseTotals($user);

        return (new ExpenseResource($expense))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function update(Request $request, Expense $expense)
    {
        $user = UserProfileHelper::resolveFromRequest($request);
        if (!$user || (int) $expense->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $this->validatePayload($request);
        $data = $this->buildPayload($request, true);

        $expense->fill($data);
        $expense->save();
        UserProfileHelper::syncIncomeExpenseTotals($user);

        return new ExpenseResource($expense);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Expense $expense)
    {
        $user = UserProfileHelper::resolveFromRequest($request);
        if (!$user || (int) $expense->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $expense->delete();
        UserProfileHelper::syncIncomeExpenseTotals($user);

        return response()->noContent();
    }

    private function validatePayload(Request $request): void
    {
        $request->validate([
            'vendorName' => 'sometimes|nullable|string|max:255',
            'vendor' => 'sometimes|nullable|string|max:255',
            'vendor_name' => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string',
            'categoryId' => 'sometimes|nullable|string|max:255',
            'category' => 'sometimes|nullable|string|max:255',
            'category_id' => 'sometimes|nullable|string|max:255',
            'totalAmount' => 'sometimes|nullable|numeric',
            'amount' => 'sometimes|nullable|numeric',
            'total_amount' => 'sometimes|nullable|numeric',
            'taxAmount' => 'sometimes|nullable|numeric',
            'tax_amount' => 'sometimes|nullable|numeric',
            'gstAmount' => 'sometimes|nullable|numeric',
            'gst_amount' => 'sometimes|nullable|numeric',
            'date' => 'sometimes|nullable|date',
            'receiptUri' => 'sometimes|nullable|string|max:2048',
            'receipt_uri' => 'sometimes|nullable|string|max:2048',
            'receipt' => 'sometimes|file|mimes:jpg,jpeg,png,webp|max:20480',
            'file' => 'sometimes|file|mimes:jpg,jpeg,png,webp|max:20480',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(Request $request, bool $isUpdate): array
    {
        $data = [];

        if (!$isUpdate || $request->hasAny(['vendorName', 'vendor', 'vendor_name'])) {
            $data['vendor_name'] = $request->input('vendorName', $request->input('vendor', $request->input('vendor_name')));
        }

        if (!$isUpdate || $request->has('description')) {
            $data['description'] = $request->input('description');
        }

        if (!$isUpdate || $request->hasAny(['categoryId', 'category', 'category_id'])) {
            $data['category_id'] = $request->input('categoryId', $request->input('category', $request->input('category_id', 'other')));
        }

        if (!$isUpdate || $request->hasAny(['totalAmount', 'amount', 'total_amount'])) {
            $amount = $request->input('totalAmount', $request->input('amount', $request->input('total_amount', 0)));
            $data['total_amount'] = $this->normalizeNegative($amount);
        }

        if (!$isUpdate || $request->hasAny(['taxAmount', 'tax_amount', 'gstAmount', 'gst_amount'])) {
            $tax = $request->input(
                'taxAmount',
                $request->input('tax_amount', $request->input('gstAmount', $request->input('gst_amount')))
            );
            $data['tax_amount'] = $tax !== null ? (float) $tax : null;
        }

        if (!$isUpdate || $request->has('date')) {
            $data['date'] = $request->input('date', now()->toDateString());
        }

        $storedReceipt = $this->storeReceiptFile($request);
        if ($storedReceipt !== null) {
            $data['receipt_uri'] = $storedReceipt;
        } elseif (!$isUpdate || $request->hasAny(['receiptUri', 'receipt_uri'])) {
            $data['receipt_uri'] = $request->input('receiptUri', $request->input('receipt_uri'));
        }

        return $data;
    }

    private function storeReceiptFile(Request $request): ?string
    {
        $file = null;
        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt');
        } elseif ($request->hasFile('file')) {
            $file = $request->file('file');
        }

        if (!$file) {
            return null;
        }

        $user = UserProfileHelper::resolveFromRequest($request);
        $userId = (string) ($user?->id ?? 'unknown');
        $folder = $userId !== '' ? $userId : 'unknown';

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ($extension === '') {
            $extension = strtolower((string) $file->extension());
        }
        $filename = Str::uuid()->toString();
        if ($extension !== '') {
            $filename .= '.' . $extension;
        }

        $path = $file->storeAs($folder, $filename, 'public');

        return PublicStorageUrl::fromPath($request, $path);
    }

    private function normalizeNegative(mixed $value): float
    {
        $num = is_numeric($value) ? (float) $value : 0.0;

        return $num <= 0 ? $num : -abs($num);
    }
}
