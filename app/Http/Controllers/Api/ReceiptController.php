<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Support\PublicStorageUrl;
use App\Support\UserProfileHelper;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $user = UserProfileHelper::resolveFromRequest($request);
        if (!$user) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'currentPage' => 1,
                    'lastPage' => 1,
                    'perPage' => 24,
                    'total' => 0,
                    'hasMore' => false,
                ],
            ]);
        }

        $perPage = max(1, min((int) $request->integer('perPage', 24), 60));

        $receipts = Expense::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->whereNotNull('receipt_uri')
            ->where('receipt_uri', '!=', '')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => $receipts->getCollection()->map(fn (Expense $expense) => [
                'id' => (string) $expense->id,
                'expenseId' => (string) $expense->id,
                'uri' => $expense->receipt_uri,
                'vendorName' => $expense->vendor_name,
                'description' => $expense->description,
                'totalAmount' => $expense->total_amount,
                'date' => $expense->date?->toDateString(),
                'createdAt' => $expense->created_at?->toISOString(),
            ])->values(),
            'meta' => [
                'currentPage' => $receipts->currentPage(),
                'lastPage' => $receipts->lastPage(),
                'perPage' => $receipts->perPage(),
                'total' => $receipts->total(),
                'hasMore' => $receipts->hasMorePages(),
            ],
        ]);
    }

    public function analyze(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20480',
        ]);

        $file = $request->file('file');
        $path = $file->store('receipts', 'public');
        $receiptUri = PublicStorageUrl::fromPath($request, $path);

        $apiUrl = config('services.financial_management.url');
        $apiToken = config('services.financial_management.token');

        if (!$apiToken) {
            return response()->json([
                'message' => 'Financial management API token is not configured.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $stream = fopen($file->getRealPath(), 'r');

        try {
            $response = Http::withHeaders([
                'X-Api-Token' => $apiToken,
            ])
                ->timeout(60)
                ->attach('files[]', $stream, $file->getClientOriginalName())
                ->post($apiUrl)
                ->throw();
        } catch (RequestException $exception) {
            return response()->json([
                'message' => 'Receipt analysis failed.',
                'error' => $exception->response?->json('message') ?? $exception->getMessage(),
            ], Response::HTTP_BAD_GATEWAY);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $payload = $response->json();
        if (is_array($payload) && Arr::get($payload, 'success') === false) {
            return response()->json([
                'message' => 'Receipt analysis failed.',
                'error' => Arr::get($payload, 'message', 'The receipt analysis service rejected the request.'),
                'errors' => Arr::get($payload, 'errors'),
            ], Response::HTTP_BAD_GATEWAY);
        }

        $analysis = $this->normalizeAnalysis($payload, $receiptUri, $file->getClientOriginalName());

        return response()->json([
            'data' => $analysis,
        ]);
    }

    public function file(string $path)
    {
        if (!Storage::disk('public')->exists($path)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return Storage::disk('public')->response($path);
    }

    /**
     * @param array<string, mixed>|null $payload
     *
     * @return array<string, mixed>
     */
    private function normalizeAnalysis(?array $payload, string $receiptUri, string $originalName): array
    {
        $data = $this->analysisData($payload ?? []);

        return [
            'vendorName' => $this->cleanShortText($this->recursiveFirstValue($data, [
                'vendorName',
                'vendor_name',
                'merchantName',
                'merchant_name',
                'storeName',
                'store_name',
                'businessName',
                'business_name',
                'supplierName',
                'supplier_name',
            ]), 80),
            'subtotalAmount' => $this->amountValue($data, [
                'subtotalAmount',
                'subtotal_amount',
                'subtotal',
                'sub_total',
            ], ['subtotal', 'sub total']),
            'totalAmount' => $this->amountValue($data, [
                'totalAmount',
                'total_amount',
                'total',
                'grandTotal',
                'grand_total',
                'amount',
            ], ['total', 'grand total', 'amount due', 'balance due']),
            'taxAmount' => $this->taxAmountValue($data),
            'date' => $this->cleanDate($this->recursiveFirstValue($data, [
                'date',
                'receiptDate',
                'receipt_date',
                'transactionDate',
                'transaction_date',
            ])),
            'description' => $this->cleanDescription($this->recursiveFirstValue($data, [
                'description',
                'summary',
                'note',
            ])),
            'categoryId' => $this->cleanShortText($this->recursiveFirstValue($data, [
                'categoryId',
                'category_id',
                'category',
            ]), 60),
            'apiSuccess' => $this->apiSuccess($payload ?? []),
            'apiMessage' => $this->apiMessage($payload ?? []),
            'receiptUri' => $receiptUri,
            'raw' => $payload,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function apiSuccess(array $payload): ?bool
    {
        $value = Arr::get($payload, 'results.0.success', Arr::get($payload, 'success'));

        return is_bool($value) ? $value : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function apiMessage(array $payload): ?string
    {
        return $this->cleanShortText(
            Arr::get($payload, 'results.0.message', Arr::get($payload, 'message')),
            160
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function taxAmountValue(array $data): ?float
    {
        $direct = $this->amountValue($data, [
                'taxAmount',
                'tax_amount',
                'tax',
                'gstAmount',
                'gst_amount',
                'gst',
                'hst',
                'pst',
            ], ['tax', 'gst', 'hst', 'pst']);
        if ($direct !== null) {
            return $direct;
        }

        $components = [];
        foreach (['gst', 'pst', 'hst'] as $key) {
            $value = $this->numericValue($this->recursiveFirstValue($data, [$key]));
            if ($value !== null) {
                $components[] = $value;
            }
        }

        return $components ? array_sum($components) : null;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function analysisData(array $payload): array
    {
        $candidates = [
            Arr::get($payload, 'analysis'),
            Arr::get($payload, 'data'),
            Arr::get($payload, 'result'),
            Arr::get($payload, 'results.0'),
            $payload,
        ];

        foreach ($candidates as $candidate) {
            if (is_array($candidate)) {
                return $candidate;
            }
        }

        return [];
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $keys
     */
    private function recursiveFirstValue(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = Arr::get($data, $key);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $found = $this->recursiveFirstValue($value, $keys);
                if ($found !== null && $found !== '') {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $keys
     * @param array<int, string> $labels
     */
    private function amountValue(array $data, array $keys, array $labels): ?float
    {
        $direct = $this->numericValue($this->recursiveFirstValue($data, $keys));
        if ($direct !== null) {
            return $direct;
        }

        return $this->labeledAmount($data, $labels);
    }

    private function numericValue(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            if (str_contains($value, "\n") || str_contains($value, "\r") || strlen($value) > 40) {
                return null;
            }

            $normalized = preg_replace('/[^0-9.-]/', '', $value);

            return is_numeric($normalized) ? (float) $normalized : null;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $labels
     */
    private function labeledAmount(array $data, array $labels): ?float
    {
        $matches = [];
        foreach ($this->stringValues($data) as $text) {
            $lines = preg_split('/\R+/', $text) ?: [$text];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                foreach ($labels as $label) {
                    if (!preg_match('/\b' . preg_quote($label, '/') . '\b/i', $line)) {
                        continue;
                    }

                    preg_match_all('/-?\$?\s*([0-9]+(?:[,.][0-9]{2})?)/', $line, $amounts);
                    $last = end($amounts[1]);
                    if ($last !== false) {
                        $matches[] = (float) str_replace(',', '.', $last);
                    }
                }
            }
        }

        return $matches ? end($matches) : null;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<int, string>
     */
    private function stringValues(array $data): array
    {
        $values = [];
        foreach ($data as $value) {
            if (is_string($value)) {
                $values[] = $value;
            } elseif (is_numeric($value)) {
                $values[] = (string) $value;
            } elseif (is_array($value)) {
                $values = array_merge($values, $this->stringValues($value));
            }
        }

        return $values;
    }

    private function cleanShortText(mixed $value, int $maxLength): ?string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        if (str_contains($text, "\n") || str_contains($text, "\r")) {
            return null;
        }

        $text = preg_replace('/\s+/', ' ', $text);
        if (!$text || strlen($text) > $maxLength) {
            return null;
        }

        return $text;
    }

    private function cleanDescription(mixed $value): ?string
    {
        $text = $this->cleanShortText($value, 500);
        if (!$text) {
            return null;
        }

        $words = preg_split('/\s+/', strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($words) > 30) {
            $uniqueRatio = count(array_unique($words)) / count($words);
            if ($uniqueRatio < 0.35) {
                return null;
            }
        }

        return $text;
    }

    private function cleanDate(mixed $value): ?string
    {
        $text = $this->cleanShortText($value, 40);
        if (!$text) {
            return null;
        }

        if (!preg_match('/\d{4}|\d{1,2}[\/.-]\d{1,2}[\/.-]\d{2,4}/', $text)) {
            return null;
        }

        $timestamp = strtotime($text);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }
}
