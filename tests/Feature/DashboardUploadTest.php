<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardUploadController;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DashboardUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_has_an_upload_menu(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('aria-label="Dashboard"', false)
            ->assertSee('>History<', false)
            ->assertSee('aria-label="Upload"', false)
            ->assertSee('>Report<', false)
            ->assertSee('>Profile<', false)
            ->assertSee('Upload a financial file')
            ->assertSee('Take photo')
            ->assertSee('Choose file')
            ->assertSee('View history')
            ->assertSee('capture="environment"', false);
    }

    public function test_uploading_a_financial_file_saves_an_expense(): void
    {
        $this->fakeAnalysis([
            'success' => true,
            'analysis' => [
                'type' => 'Expense',
                'amount' => 11.2,
                'tax' => 1.2,
                'date' => '2026-05-03',
                'vendor_name' => 'Demo Store',
                'category' => 'Meals',
                'description' => 'Lunch receipt',
            ],
        ]);

        $user = User::factory()->create([
            'is_active' => true,
            'currency' => 'CAD',
        ]);

        $this->actingAs($user)
            ->post(route('dashboard.uploads.store'), [
                'file' => UploadedFile::fake()->create('receipt.jpg', 128, 'image/jpeg'),
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('open_screen', 'upload');

        $expense = Expense::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($expense);
        $this->assertSame('Demo Store', $expense->vendor_name);
        $this->assertSame(-11.2, $expense->total_amount);
        $this->assertSame('Meals', $expense->category_id);
        $this->assertSame('2026-05-03', $expense->date->toDateString());
        $this->assertNotNull($expense->receipt_uri);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://demo.webpulse.ca/api/financial-management'
                && $request->isMultipart()
                && collect($request->data())->contains(fn (array $part) => $part['name'] === 'files[]');
        });

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Demo Store')
            ->assertSee('Saved as Expense');
    }

    public function test_uploading_an_income_file_saves_income(): void
    {
        $this->fakeAnalysis([
            'success' => true,
            'analysis' => [
                'type' => 'Income',
                'amount' => 10,
                'date' => '2023-10-05',
                'vendor_name' => 'Shanghai ABC Company Ltd',
                'description' => 'Textile hanger sample',
            ],
        ]);

        $user = User::factory()->create(['is_active' => true, 'currency' => 'CAD']);

        $this->actingAs($user)
            ->post(route('dashboard.uploads.store'), [
                'file' => UploadedFile::fake()->create('invoice.pdf', 128, 'application/pdf'),
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(0, Expense::query()->count());
        $income = Income::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($income);
        $this->assertSame('Shanghai ABC Company Ltd', $income->source);
        $this->assertSame(10.0, $income->amount);
        $this->assertStringContainsString('/receipt/file/', (string) $income->note);
    }

    public function test_a_failed_reading_does_not_create_a_record(): void
    {
        $this->fakeAnalysis([
            'success' => false,
            'message' => 'Unauthorized token.',
        ]);

        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->from('/dashboard')
            ->post(route('dashboard.uploads.store'), [
                'file' => UploadedFile::fake()->create('receipt.jpg', 128, 'image/jpeg'),
            ])
            ->assertRedirect('/dashboard')
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Expense::query()->count());
        $this->assertSame(0, Income::query()->count());
    }

    public function test_upload_reads_the_file_with_openai_when_that_key_is_set(): void
    {
        config([
            'services.openai.key' => 'test-openai-key',
            'services.openai.model' => 'gpt-4o-mini',
            'services.financial_management.token' => null,
        ]);
        Storage::fake('public');
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'type' => 'Expense',
                            'vendor_name' => 'Cafe',
                            'amount' => 12.5,
                            'tax' => 1.5,
                            'date' => '2026-10-02',
                            'category' => 'dining',
                            'description' => 'Lunch',
                        ]),
                    ],
                ]],
            ]),
        ]);

        $user = User::factory()->create(['is_active' => true, 'currency' => 'CAD']);

        $this->actingAs($user)
            ->post(route('dashboard.uploads.store'), [
                'file' => UploadedFile::fake()->create('receipt.jpg', 40, 'image/jpeg'),
            ])
            ->assertRedirect(route('dashboard'));

        $expense = Expense::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($expense);
        $this->assertSame('Cafe', $expense->vendor_name);
        $this->assertSame(-12.5, $expense->total_amount);
        $this->assertSame('dining', $expense->category_id);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-openai-key');
        });
    }

    public function test_an_oversized_php_upload_explains_the_limit(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $path = tempnam(sys_get_temp_dir(), 'upload');
        $file = new UploadedFile($path, 'photo.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true);

        $this->actingAs($user)
            ->from('/dashboard')
            ->post(route('dashboard.uploads.store'), ['file' => $file])
            ->assertRedirect('/dashboard')
            ->assertSessionHasErrors('file');

        $this->assertStringContainsString('too large', session('errors')->first('file'));
    }

    public function test_recent_files_keep_only_the_latest_five(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        foreach (range(1, 6) as $number) {
            Expense::query()->create([
                'user_id' => $user->id,
                'vendor_name' => 'Shop '.$number,
                'category_id' => 'food',
                'total_amount' => -$number,
                'date' => sprintf('2026-10-%02d', $number),
                'receipt_uri' => 'https://example.test/receipt/file/receipts/'.$number.'.jpg',
            ]);
        }

        $recent = DashboardUploadController::recentFor($user, 5);

        $this->assertCount(5, $recent);
        $this->assertSame(['Shop 6', 'Shop 5', 'Shop 4', 'Shop 3', 'Shop 2'], array_column($recent, 'title'));
    }

    public function test_guests_cannot_upload(): void
    {
        $this->post(route('dashboard.uploads.store'), [])
            ->assertRedirect('/login');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function fakeAnalysis(array $payload): void
    {
        config([
            'services.financial_management.url' => 'https://demo.webpulse.ca/api/financial-management',
            'services.financial_management.token' => 'test-token',
        ]);
        Storage::fake('public');
        Http::fake([
            'https://demo.webpulse.ca/api/financial-management' => Http::response($payload),
        ]);
    }
}
