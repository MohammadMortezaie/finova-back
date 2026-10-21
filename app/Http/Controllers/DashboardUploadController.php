<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\ReceiptController;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use App\Support\UserProfileHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class DashboardUploadController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if ($failure = $this->rejectInvalidUpload($request)) {
            return $failure;
        }

        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
        ], [
            'file.required' => 'Choose a financial file to upload.',
            'file.uploaded' => 'That file is too large to upload. Please use a smaller photo.',
            'file.max' => 'That file is larger than 20 MB.',
        ]);

        $response = app(ReceiptController::class)->analyze($request);
        $payload = $response->getData(true);
        $analysis = $payload['data'] ?? null;

        if ($response->getStatusCode() >= 400 || ! is_array($analysis)) {
            return back()->withErrors([
                'file' => $payload['error'] ?? $payload['message'] ?? 'Could not read that file.',
            ]);
        }

        $user = $request->user();
        $saved = $this->isIncome($analysis)
            ? $this->saveIncome($user, $analysis)
            : $this->saveExpense($user, $analysis);

        UserProfileHelper::syncIncomeExpenseTotals($user);

        return redirect()
            ->route('dashboard')
            ->with('open_screen', 'upload')
            ->with('upload_result', $saved);
    }

    private function rejectInvalidUpload(Request $request): ?RedirectResponse
    {
        $upload = $request->file('file');
        if (! $upload instanceof UploadedFile || $upload->isValid()) {
            return null;
        }

        $limit = ini_get('upload_max_filesize') ?: '2M';
        $message = match ($upload->getError()) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "That file is too large. Please use a photo smaller than {$limit}.",
            UPLOAD_ERR_PARTIAL => 'The upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE => 'Choose a financial file to upload.',
            default => 'The file could not be uploaded. Please try a smaller photo.',
        };

        return back()->withErrors(['file' => $message]);
    }

    /**
     * @return list<array{kind: string, title: string, amount: string, date: string, detail: string, fileUrl: ?string}>
     */
    public static function recentFor(User $user, ?int $limit = 8): array
    {
        $currency = $user->currency ?: 'CAD';
        $rows = [];

        Expense::query()
            ->where('user_id', $user->id)
            ->whereNotNull('receipt_uri')
            ->where('receipt_uri', '!=', '')
            ->latest('date')
            ->latest('id')
            ->get()
            ->each(function (Expense $expense) use (&$rows, $currency): void {
                $rows[] = [
                    'kind' => 'Expense',
                    'title' => $expense->vendor_name ?: 'Receipt',
                    'amount' => self::money(abs((float) $expense->total_amount), $currency),
                    'date' => $expense->date?->format('D d M Y') ?? '',
                    'detail' => self::label((string) ($expense->category_id ?: 'Expense')),
                    'fileUrl' => $expense->receipt_uri,
                    'sort' => ($expense->created_at?->timestamp ?? 0).sprintf('%06d', $expense->id),
                ];
            });

        Income::query()
            ->where('user_id', $user->id)
            ->where('note', 'like', '%/receipt/file/%')
            ->latest('date')
            ->latest('id')
            ->get()
            ->each(function (Income $income) use (&$rows, $currency): void {
                $rows[] = [
                    'kind' => 'Income',
                    'title' => $income->source ?: 'Income',
                    'amount' => self::money(abs((float) $income->amount), $currency),
                    'date' => $income->date?->format('D d M Y') ?? '',
                    'detail' => 'Income',
                    'fileUrl' => self::fileUrl((string) $income->note),
                    'sort' => ($income->created_at?->timestamp ?? 0).sprintf('%06d', $income->id),
                ];
            });

        usort($rows, fn (array $a, array $b) => strcmp($b['sort'], $a['sort']));

        $rows = array_map(function (array $row): array {
            unset($row['sort']);

            return $row;
        }, $rows);

        return $limit === null ? $rows : array_slice($rows, 0, $limit);
    }

    /**
     * @param  array<string, mixed>  $analysis
     * @return array{kind: string, title: string, amount: string, date: string, detail: string, fileUrl: ?string}
     */
    private function saveExpense(User $user, array $analysis): array
    {
        $amount = abs((float) ($analysis['totalAmount'] ?? 0));
        $category = $analysis['categoryId'] ?: 'other';
        $date = $this->date($analysis['date'] ?? null);

        $expense = Expense::query()->create([
            'user_id' => $user->id,
            'vendor_name' => $analysis['vendorName'] ?: null,
            'description' => $analysis['description'] ?: null,
            'category_id' => $category,
            'total_amount' => $amount === 0.0 ? 0 : -$amount,
            'tax_amount' => $analysis['taxAmount'] ?? null,
            'date' => $date,
            'receipt_uri' => $analysis['receiptUri'] ?? null,
        ]);

        return $this->present(
            'Expense',
            $expense->vendor_name ?: 'Receipt',
            $amount,
            $date,
            $this->label((string) $category),
            $expense->receipt_uri,
            $user->currency ?: 'CAD',
        );
    }

    /**
     * @param  array<string, mixed>  $analysis
     * @return array{kind: string, title: string, amount: string, date: string, detail: string, fileUrl: ?string}
     */
    private function saveIncome(User $user, array $analysis): array
    {
        $amount = abs((float) ($analysis['totalAmount'] ?? 0));
        $date = $this->date($analysis['date'] ?? null);
        $title = $analysis['vendorName'] ?: 'Uploaded income';
        $fileUrl = $analysis['receiptUri'] ?? null;
        $note = trim(implode("\n", array_filter([
            $analysis['description'] ?? null,
            $fileUrl,
        ])));

        Income::query()->create([
            'user_id' => $user->id,
            'source' => $title,
            'amount' => $amount,
            'date' => $date,
            'note' => $note !== '' ? $note : null,
        ]);

        return $this->present('Income', $title, $amount, $date, 'Income', $fileUrl, $user->currency ?: 'CAD');
    }

    /**
     * @param  array<string, mixed>  $analysis
     */
    private function isIncome(array $analysis): bool
    {
        $type = strtolower(trim((string) (
            data_get($analysis, 'raw.analysis.type')
            ?? data_get($analysis, 'raw.data.type')
            ?? data_get($analysis, 'raw.results.0.type')
            ?? data_get($analysis, 'raw.type')
            ?? ''
        )));

        return $type === 'income';
    }

    private function date(mixed $value): string
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return now()->toDateString();
    }

    /**
     * @return array{kind: string, title: string, amount: string, date: string, detail: string, fileUrl: ?string}
     */
    private function present(string $kind, string $title, float $amount, string $date, string $detail, ?string $fileUrl, string $currency): array
    {
        return [
            'kind' => $kind,
            'title' => $title,
            'amount' => self::money($amount, $currency),
            'date' => Carbon::parse($date)->format('D d M Y'),
            'detail' => $detail,
            'fileUrl' => $fileUrl,
        ];
    }

    private static function fileUrl(string $note): ?string
    {
        if (preg_match('#https?://\S+/receipt/file/\S+#', $note, $matches) !== 1) {
            return null;
        }

        return $matches[0];
    }

    private static function label(string $value): string
    {
        $clean = trim(str_replace(['_', '-'], ' ', $value));

        return $clean === '' ? 'Expense' : mb_convert_case($clean, MB_CASE_TITLE, 'UTF-8');
    }

    private static function money(float $amount, string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'CAD' => '$',
            'AUD' => '$',
            'NZD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'INR' => '₹',
        ];
        $code = strtoupper($currency);
        $symbol = $symbols[$code] ?? $code.' ';
        $decimals = $code === 'JPY' ? 0 : (abs($amount - round($amount)) >= 0.005 ? 2 : 0);

        return $symbol.number_format(abs($amount), $decimals, '.', ',');
    }
}
