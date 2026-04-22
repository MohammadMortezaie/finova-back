<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExportLink;
use App\Models\Income;
use App\Models\Subscription;
use App\Support\UserProfileHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ExportController extends Controller
{
    public function link(Request $request)
    {
        $data = $this->validatePayload($request);
        $user = UserProfileHelper::resolveFromRequest($request);
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $token = Str::random(48);
        $format = $data['format'] ?? 'csv';
        $from = $data['from'] ?? null;
        $to = $data['to'] ?? null;
        $expiresAt = now()->addHours(24);

        ExportLink::query()->create([
            'user_id' => $user->id,
            'token' => $token,
            'format' => $format,
            'from' => $from,
            'to' => $to,
            'expires_at' => $expiresAt,
        ]);

        $base = rtrim($request->root(), '/');
        $url = $base . '/export/' . $token;

        return response()->json([
            'data' => [
                'url' => $url,
                'token' => $token,
            ],
        ]);
    }

    public function download(Request $request, string $token)
    {
        $link = ExportLink::query()->where('token', $token)->first();
        if (!$link) {
            return response()->json(['message' => 'Export link not found'], Response::HTTP_NOT_FOUND);
        }
        if ($link->expires_at && now()->greaterThan($link->expires_at)) {
            return response()->json(['message' => 'Export link expired'], Response::HTTP_GONE);
        }

        $user = $link->user;
        if (!$user) {
            return response()->json(['message' => 'Export user not found'], Response::HTTP_NOT_FOUND);
        }

        // Ensure any legacy records are associated with this user before exporting.
        UserProfileHelper::syncIncomeExpenseTotals($user);

        $from = $link->from?->toDateString();
        $to = $link->to?->toDateString();
        $format = $link->format ?? 'csv';

        $tables = $this->buildTables($user->id, $from, $to);
        $totals = [
            'total_income' => (float) ($user->total_income ?? 0),
            'total_expense' => (float) ($user->total_expense ?? 0),
            'total_subscription' => (float) ($user->total_subscription ?? 0),
        ];
        $meta = [
            'name' => $user->name ?? '',
            'email' => $user->email ?? '',
        ];

        if ($format === 'pdf') {
            $pdf = $this->renderPdf($tables, $totals, $meta, $from, $to);
            $filename = $this->filename('pdf', $from, $to);

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        $csv = $this->renderCsv($tables, $totals, $meta);
        $filename = $this->filename('csv', $from, $to);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function validatePayload(Request $request, bool $requireFormat = true): array
    {
        return $request->validate([
            'format' => ($requireFormat ? 'required' : 'sometimes') . '|in:csv,pdf',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function buildTables(int $userId, ?string $from, ?string $to): array
    {
        $expenses = Expense::query()
            ->where('user_id', $userId)
            ->whereNull('deleted_at');
        $incomes = Income::query()
            ->where('user_id', $userId)
            ->whereNull('deleted_at');
        $subs = Subscription::query()
            ->where('user_id', $userId)
            ->whereNull('deleted_at');

        // Note: export currently includes all records. Date ranges are shown in the header only.

        $expenseRows = [];
        foreach ($expenses->get() as $expense) {
            $expenseRows[] = [
                'date' => $expense->date?->toDateString() ?? '',
                'vendor' => $expense->vendor_name ?? '',
                'amount' => (float) ($expense->total_amount ?? 0),
                'category' => $expense->category_id ?? '',
                'description' => $expense->description ?? '',
                'tax' => (float) ($expense->tax_amount ?? 0),
            ];
        }

        $incomeRows = [];
        foreach ($incomes->get() as $income) {
            $incomeRows[] = [
                'date' => $income->date?->toDateString() ?? '',
                'source' => $income->source ?? '',
                'amount' => (float) ($income->amount ?? 0),
                'note' => $income->note ?? '',
            ];
        }

        $subscriptionRows = [];
        foreach ($subs->get() as $sub) {
            $subscriptionRows[] = [
                'date' => $sub->created_at?->toDateString() ?? '',
                'name' => $sub->name ?? '',
                'amount' => (float) ($sub->amount ?? 0),
                'billing_day' => (string) ($sub->billing_day ?? ''),
                'active' => $sub->active ? 'yes' : 'no',
                'category' => $sub->category_id ?? '',
            ];
        }

        return [
            'incomes' => $incomeRows,
            'expenses' => $expenseRows,
            'subscriptions' => $subscriptionRows,
        ];
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $tables
     * @param array<string, float> $totals
     */
    private function renderCsv(array $tables, array $totals, array $meta): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Name', $meta['name'] ?? '']);
        fputcsv($handle, ['Email', $meta['email'] ?? '']);
        fputcsv($handle, []);
        fputcsv($handle, ['Totals']);
        fputcsv($handle, ['Total Income', $totals['total_income'] ?? 0]);
        fputcsv($handle, ['Total Expense', $totals['total_expense'] ?? 0]);
        fputcsv($handle, ['Total Subscription', $totals['total_subscription'] ?? 0]);
        fputcsv($handle, []);

        fputcsv($handle, ['Incomes']);
        fputcsv($handle, ['Date', 'Source', 'Amount', 'Note']);
        foreach ($tables['incomes'] ?? [] as $row) {
            fputcsv($handle, [
                $row['date'] ?? '',
                $row['source'] ?? '',
                $row['amount'] ?? 0,
                $row['note'] ?? '',
            ]);
        }
        fputcsv($handle, []);

        fputcsv($handle, ['Expenses']);
        fputcsv($handle, ['Date', 'Vendor', 'Amount', 'Category', 'Description', 'Tax']);
        foreach ($tables['expenses'] ?? [] as $row) {
            fputcsv($handle, [
                $row['date'] ?? '',
                $row['vendor'] ?? '',
                $row['amount'] ?? 0,
                $row['category'] ?? '',
                $row['description'] ?? '',
                $row['tax'] ?? 0,
            ]);
        }
        fputcsv($handle, []);

        fputcsv($handle, ['Subscriptions']);
        fputcsv($handle, ['Created At', 'Name', 'Amount', 'Billing Day', 'Active', 'Category']);
        foreach ($tables['subscriptions'] ?? [] as $row) {
            fputcsv($handle, [
                $row['date'] ?? '',
                $row['name'] ?? '',
                $row['amount'] ?? 0,
                $row['billing_day'] ?? '',
                $row['active'] ?? '',
                $row['category'] ?? '',
            ]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv !== false ? $csv : '';
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $tables
     * @param array<string, float> $totals
     */
    private function renderPdf(array $tables, array $totals, array $meta, ?string $from, ?string $to): string
    {
        $lines = [];
        $lines[] = 'Transactions Export';
        $lines[] = 'Name: ' . ($meta['name'] ?? '');
        $lines[] = 'Email: ' . ($meta['email'] ?? '');
        $lines[] = 'Range: ' . ($from ?? 'all') . ' -> ' . ($to ?? 'all');
        $lines[] = '';
        $lines[] = 'Totals';
        $lines[] = 'Total Income: ' . ($totals['total_income'] ?? 0);
        $lines[] = 'Total Expense: ' . ($totals['total_expense'] ?? 0);
        $lines[] = 'Total Subscription: ' . ($totals['total_subscription'] ?? 0);
        $lines[] = '';

        $lines[] = 'Incomes';
        $lines[] = 'Date | Source | Amount | Note';
        foreach ($tables['incomes'] ?? [] as $row) {
            $lines[] = sprintf(
                '%s | %s | %s | %s',
                $row['date'] ?? '',
                $row['source'] ?? '',
                (string) ($row['amount'] ?? 0),
                $row['note'] ?? ''
            );
        }
        $lines[] = '';

        $lines[] = 'Expenses';
        $lines[] = 'Date | Vendor | Amount | Category | Description | Tax';
        foreach ($tables['expenses'] ?? [] as $row) {
            $lines[] = sprintf(
                '%s | %s | %s | %s | %s | %s',
                $row['date'] ?? '',
                $row['vendor'] ?? '',
                (string) ($row['amount'] ?? 0),
                $row['category'] ?? '',
                $row['description'] ?? '',
                (string) ($row['tax'] ?? 0)
            );
        }
        $lines[] = '';

        $lines[] = 'Subscriptions';
        $lines[] = 'Created At | Name | Amount | Billing Day | Active | Category';
        foreach ($tables['subscriptions'] ?? [] as $row) {
            $lines[] = sprintf(
                '%s | %s | %s | %s | %s | %s',
                $row['date'] ?? '',
                $row['name'] ?? '',
                (string) ($row['amount'] ?? 0),
                $row['billing_day'] ?? '',
                $row['active'] ?? '',
                $row['category'] ?? ''
            );
        }

        return $this->simplePdfFromLines($lines);
    }

    /**
     * @param array<int, string> $lines
     */
    private function simplePdfFromLines(array $lines): string
    {
        $content = "BT\n/F1 12 Tf\n14 TL\n72 760 Td\n";
        foreach ($lines as $line) {
            $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $content .= '(' . $safe . ") Tj\nT*\n";
        }
        $content .= "ET\n";

        $objects = [];
        $objects[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj";
        $objects[] = "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj";
        $objects[] = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj";
        $objects[] = "4 0 obj << /Length " . strlen($content) . " >> stream\n" . $content . "endstream endobj";
        $objects[] = "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj";

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj . "\n";
        }

        $startxref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $startxref . "\n%%EOF";

        return $pdf;
    }

    private function filename(string $ext, ?string $from, ?string $to): string
    {
        $fromPart = $from ?: 'all';
        $toPart = $to ?: 'all';
        return "transactions_{$fromPart}_{$toPart}.{$ext}";
    }
}
