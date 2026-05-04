<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_gallery_returns_paginated_user_receipts_newest_first(): void
    {
        $user = User::factory()->create(['email' => 'moemortezaie@gmail.com']);
        $otherUser = User::factory()->create(['email' => 'other@example.com']);

        Expense::query()->create([
            'user_id' => $user->id,
            'vendor_name' => 'Old Store',
            'description' => 'Old receipt',
            'category_id' => 'food',
            'total_amount' => -12.50,
            'date' => '2026-04-01',
            'receipt_uri' => 'https://static.vecteezy.com/system/resources/thumbnails/008/363/349/small/paper-cash-sell-receipt-vector.jpg',
        ]);
        Expense::query()->create([
            'user_id' => $user->id,
            'vendor_name' => 'New Store',
            'description' => 'New receipt',
            'category_id' => 'gas',
            'total_amount' => -34.75,
            'date' => '2026-05-01',
            'receipt_uri' => 'https://img.magnific.com/free-vector/realistic-receipt-template_23-2147938550.jpg',
        ]);
        Expense::query()->create([
            'user_id' => $user->id,
            'vendor_name' => 'No Receipt',
            'description' => null,
            'category_id' => 'other',
            'total_amount' => -1,
            'date' => '2026-05-02',
            'receipt_uri' => null,
        ]);
        Expense::query()->create([
            'user_id' => $otherUser->id,
            'vendor_name' => 'Other User',
            'description' => null,
            'category_id' => 'other',
            'total_amount' => -99,
            'date' => '2026-05-03',
            'receipt_uri' => 'https://img.magnific.com/free-vector/realistic-receipt-template_23-2147938550.jpg',
        ]);

        $response = $this->getJson('/receipts?email=moemortezaie@gmail.com&perPage=1&page=1');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.vendorName', 'New Store')
            ->assertJsonPath('data.0.uri', 'https://img.magnific.com/free-vector/realistic-receipt-template_23-2147938550.jpg')
            ->assertJsonPath('meta.currentPage', 1)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.hasMore', true);

        $this->getJson('/receipts?email=moemortezaie@gmail.com&perPage=1&page=2')
            ->assertOk()
            ->assertJsonPath('data.0.vendorName', 'Old Store')
            ->assertJsonPath('meta.currentPage', 2)
            ->assertJsonPath('meta.hasMore', false);
    }
}
