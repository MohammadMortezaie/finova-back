<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $currency = $user->currency ?: 'CAD';
        $today = now()->startOfDay();
        $currentYear = (int) $today->year;

        $years = $this->availableYears((int) $user->id, $currentYear);
        $requested = $request->query('year');
        $year = is_numeric($requested) ? (int) $requested : $currentYear;
        if (! in_array($year, $years, true)) {
            $year = $currentYear;
        }

        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $groups = $this->monthGroups((int) $user->id, $monthStart, $monthEnd);
        $percents = $this->percents($groups);

        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($year, 12, 31)->startOfDay();
        $monthly = $this->monthlyUploads((int) $user->id, $year);
        $incomeTotal = array_sum($monthly['income']);
        $expenseTotal = array_sum($monthly['expense']);

        return view('dashboard', [
            'user' => $user,
            'initial' => $this->initial($user->name),
            'currency' => strtoupper($currency),
            'plan' => $user->plan ? ucfirst((string) $user->plan) : 'Free',
            'year' => $year,
            'years' => $years,
            'rangeLabel' => $yearStart->format('D d M').' – '.$yearEnd->format('D d M'),
            'incomeLabel' => $this->money($incomeTotal, $currency),
            'expenseLabel' => $this->money($expenseTotal, $currency),
            'chart' => $this->monthlyChart(
                $monthly,
                $year === $currentYear ? (int) $today->month : null,
            ),
            'monthRangeLabel' => $monthStart->format('D d M').' – '.$monthEnd->format('D d M'),
            'spendLabel' => $this->money(array_sum($groups), $currency),
            'slices' => [
                ['key' => 'essentials', 'label' => 'Essentials', 'percent' => $percents['essentials'], 'color' => '#e6c34a'],
                ['key' => 'treat', 'label' => 'Treat Yourself', 'percent' => $percents['treat'], 'color' => '#7ed06a'],
                ['key' => 'finance', 'label' => 'Finance & Repayments', 'percent' => $percents['finance'], 'color' => '#5b93f5'],
            ],
            'bills' => $this->bills((int) $user->id, $currency, $today),
            'uploads' => DashboardUploadController::recentFor($user, 5),
            'records' => DashboardUploadController::recentFor($user, null),
        ]);
    }

    /**
     * @return list<int>
     */
    private function availableYears(int $userId, int $current): array
    {
        $years = array_merge(
            [$current],
            $this->yearsFrom('incomes', $userId),
            $this->yearsFrom('expenses', $userId),
        );
        $years = array_values(array_unique(array_map('intval', $years)));
        rsort($years);

        return $years;
    }

    /**
     * @return list<int>
     */
    private function yearsFrom(string $table, int $userId): array
    {
        $expr = DB::getDriverName() === 'sqlite'
            ? "CAST(strftime('%Y', date) AS INTEGER)"
            : 'YEAR(date)';

        return DB::table($table)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->selectRaw($expr.' as year')
            ->distinct()
            ->pluck('year')
            ->all();
    }

    /**
     * @return array{income: array<int, float>, expense: array<int, float>}
     */
    private function monthlyUploads(int $userId, int $year): array
    {
        $income = array_fill(1, 12, 0.0);
        $expense = array_fill(1, 12, 0.0);

        Income::query()
            ->where('user_id', $userId)
            ->whereYear('date', $year)
            ->where('note', 'like', '%/receipt/file/%')
            ->get(['amount', 'date'])
            ->each(function (Income $row) use (&$income): void {
                $income[$row->date->month] += (float) $row->amount;
            });

        Expense::query()
            ->where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereNotNull('receipt_uri')
            ->where('receipt_uri', '!=', '')
            ->get(['total_amount', 'date'])
            ->each(function (Expense $row) use (&$expense): void {
                $expense[$row->date->month] += abs((float) $row->total_amount);
            });

        return [
            'income' => array_values($income),
            'expense' => array_values($expense),
        ];
    }

    /**
     * @param  array{income: array<int, float>, expense: array<int, float>}  $monthly
     * @return array{bars: list<array{label: string, income: float, expense: float, incomeAmount: float, expenseAmount: float, current: bool}>, yLabels: list<string>, empty: bool}
     */
    private function monthlyChart(array $monthly, ?int $markerMonth): array
    {
        $peak = max(max($monthly['income']), max($monthly['expense']));
        $empty = $peak <= 0;
        $scale = $empty ? 1 : $peak;
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $bars = [];

        foreach ($labels as $index => $label) {
            $bars[] = [
                'label' => $label,
                'income' => round(($monthly['income'][$index] / $scale) * 100, 2),
                'expense' => round(($monthly['expense'][$index] / $scale) * 100, 2),
                'incomeAmount' => $monthly['income'][$index],
                'expenseAmount' => $monthly['expense'][$index],
                'current' => $markerMonth === $index + 1,
            ];
        }

        return [
            'bars' => $bars,
            'yLabels' => $empty
                ? ['', '', '0']
                : [$this->axisLabel($peak), $this->axisLabel($peak / 2), '0'],
            'empty' => $empty,
        ];
    }

    private function axisLabel(float $value): string
    {
        $abs = abs($value);
        if ($abs >= 1000) {
            $thousands = $abs / 1000;
            $text = $thousands >= 10 || abs($thousands - round($thousands)) < 0.05
                ? (string) (int) round($thousands)
                : rtrim(rtrim(number_format($thousands, 1, '.', ''), '0'), '.');

            return $text.'k';
        }

        return number_format($abs, 0, '.', '');
    }

    /**
     * @return array{essentials: float, treat: float, finance: float}
     */
    private function monthGroups(int $userId, Carbon $start, Carbon $end): array
    {
        $groups = [
            'essentials' => 0.0,
            'treat' => 0.0,
            'finance' => 0.0,
        ];

        Expense::query()
            ->where('user_id', $userId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['total_amount', 'category_id'])
            ->each(function (Expense $expense) use (&$groups): void {
                $groups[$this->spendingGroup((string) $expense->category_id)] += abs((float) $expense->total_amount);
            });

        return $groups;
    }

    /**
     * @param  array<string, float>  $amounts
     * @return array<string, int>
     */
    private function percents(array $amounts): array
    {
        $total = array_sum($amounts);
        $shares = [];
        foreach ($amounts as $key => $amount) {
            $shares[$key] = 0;
        }
        if ($total <= 0) {
            return $shares;
        }

        $remainders = [];
        foreach ($amounts as $key => $amount) {
            $exact = ($amount / $total) * 100;
            $shares[$key] = (int) floor($exact);
            $remainders[$key] = $exact - $shares[$key];
        }

        $left = 100 - array_sum($shares);
        arsort($remainders);
        foreach (array_keys($remainders) as $key) {
            if ($left <= 0) {
                break;
            }
            $shares[$key]++;
            $left--;
        }

        return $shares;
    }

    /**
     * @return list<array{name: string, amount: string, category: string, due: string, initial: string}>
     */
    private function bills(int $userId, string $currency, Carbon $today): array
    {
        return Subscription::query()
            ->where('user_id', $userId)
            ->where('active', true)
            ->get()
            ->map(function (Subscription $subscription) use ($currency, $today): array {
                $due = $this->nextBillingDate((int) $subscription->billing_day, $today);
                $days = (int) round($today->diffInDays($due));
                $name = trim((string) $subscription->name) ?: 'Bill';

                return [
                    'name' => $name,
                    'amount' => $this->money(abs((float) $subscription->amount), $currency, true),
                    'category' => $this->label((string) ($subscription->category_id ?: 'Recurring')),
                    'due' => $this->dueLabel($days),
                    'initial' => $this->initial($name),
                    'sort' => $due->timestamp,
                ];
            })
            ->sortBy('sort')
            ->map(function (array $bill): array {
                unset($bill['sort']);

                return $bill;
            })
            ->values()
            ->all();
    }

    private function nextBillingDate(int $billingDay, Carbon $today): Carbon
    {
        $day = max(1, min(31, $billingDay));
        $candidate = $today->copy()->day(min($day, $today->daysInMonth));
        if ($candidate->lt($today)) {
            $nextMonth = $today->copy()->addMonthNoOverflow()->startOfMonth();
            $candidate = $nextMonth->day(min($day, $nextMonth->daysInMonth));
        }

        return $candidate;
    }

    private function dueLabel(int $days): string
    {
        if ($days <= 0) {
            return 'Due today';
        }
        if ($days === 1) {
            return 'Due in 1 Day';
        }

        return 'Due in '.$days.' Days';
    }

    private function spendingGroup(string $category): string
    {
        $key = strtolower(str_replace(['&', '_', '-'], ' ', trim($category)));
        $finance = ['finance', 'debt', 'loan', 'credit', 'repay', 'bank', 'fee', 'tax', 'invest', 'insurance', 'mortgage'];
        $treat = ['entertain', 'shop', 'dining', 'restaurant', 'coffee', 'travel', 'hobby', 'cloth', 'game', 'treat', 'leisure', 'fun', 'beauty', 'gift', 'personal', 'stream'];

        foreach ($finance as $needle) {
            if (str_contains($key, $needle)) {
                return 'finance';
            }
        }
        foreach ($treat as $needle) {
            if (str_contains($key, $needle)) {
                return 'treat';
            }
        }

        return 'essentials';
    }

    private function money(float $amount, string $currency, bool $cents = false): string
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
        $negative = $amount < 0;
        $abs = abs($amount);
        $decimals = $code === 'JPY' ? 0 : ($cents || abs($abs - round($abs)) >= 0.005 ? 2 : 0);

        return ($negative ? '-' : '').$symbol.number_format($abs, $decimals, '.', ',');
    }

    private function label(string $value): string
    {
        $clean = trim(str_replace(['_', '-'], ' ', $value));
        if ($clean === '') {
            return 'Recurring';
        }

        return mb_convert_case($clean, MB_CASE_TITLE, 'UTF-8');
    }

    private function initial(string $name): string
    {
        $trimmed = trim($name);

        return mb_strtoupper(mb_substr($trimmed !== '' ? $trimmed : 'A', 0, 1));
    }
}
