<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReceiptController extends Controller
{
    public function analyze(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20480',
        ]);

        $file = $request->file('file');
        $path = $file->store('receipts', 'public');
        $receiptUri = Storage::disk('public')->url($path);

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

    /**
     * @param array<string, mixed>|null $payload
     *
     * @return array<string, mixed>
     */
    private function normalizeAnalysis(?array $payload, string $receiptUri, string $originalName): array
    {
        $data = $this->analysisData($payload ?? []);
        return [
            'vendorName' => $this->cleanShortText($this->firstValue($data, [
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
            'subtotalAmount' => $this->numericValue($this->firstValue($data, [
                'subtotalAmount',
                'subtotal_amount',
                'subtotal',
                'sub_total',
            ])),
            'totalAmount' => $this->numericValue($this->firstValue($data, [
                'totalAmount',
                'total_amount',
                'total',
                'grandTotal',
                'grand_total',
                'amount',
            ])),
            'taxAmount' => $this->numericValue($this->firstValue($data, [
                'taxAmount',
                'tax_amount',
                'tax',
                'gstAmount',
                'gst_amount',
                'gst',
                'hst',
                'pst',
            ])),
            'date' => $this->cleanDate($this->firstValue($data, [
                'date',
                'receiptDate',
                'receipt_date',
                'transactionDate',
                'transaction_date',
            ])),
            'description' => $this->cleanShortText($this->firstValue($data, [
                'description',
                'summary',
                'note',
            ]), 140),
            'categoryId' => $this->cleanShortText($this->firstValue($data, [
                'categoryId',
                'category_id',
                'category',
            ]), 60),
            'receiptUri' => $receiptUri,
            'raw' => $payload,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function analysisData(array $payload): array
    {
        $candidates = [
            Arr::get($payload, 'data'),
            Arr::get($payload, 'result'),
            Arr::get($payload, 'results.0'),
            Arr::get($payload, 'analysis'),
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
    private function firstValue(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = Arr::get($data, $key);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
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
