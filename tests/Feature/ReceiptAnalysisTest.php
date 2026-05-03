<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptAnalysisTest extends TestCase
{
    public function test_receipt_analysis_posts_file_to_financial_management_api(): void
    {
        config([
            'services.financial_management.url' => 'https://demo.webpulse.ca/api/financial-management',
            'services.financial_management.token' => 'test-token',
        ]);
        Storage::fake('public');
        Http::fake([
            'https://demo.webpulse.ca/api/financial-management' => Http::response([
                'data' => [
                    'vendor_name' => 'Demo Store',
                    'subtotal' => '10.00',
                    'tax' => '1.20',
                    'total' => '11.20',
                    'date' => '2026-05-03',
                ],
            ]),
        ]);

        $response = $this->post('/receipt/analyze', [
            'file' => UploadedFile::fake()->create('receipt.jpg', 128, 'image/jpeg'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.vendorName', 'Demo Store')
            ->assertJsonPath('data.subtotalAmount', 10)
            ->assertJsonPath('data.taxAmount', 1.2)
            ->assertJsonPath('data.totalAmount', 11.2)
            ->assertJsonPath('data.date', '2026-05-03');

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://demo.webpulse.ca/api/financial-management'
                && $request->method() === 'POST'
                && $request->hasHeader('X-Api-Token', 'test-token')
                && $request->isMultipart()
                && collect($request->data())->contains(fn (array $part) => $part['name'] === 'files[]');
        });
    }

    public function test_receipt_analysis_treats_failed_service_payload_as_error(): void
    {
        config([
            'services.financial_management.url' => 'https://demo.webpulse.ca/api/financial-management',
            'services.financial_management.token' => 'test-token',
        ]);
        Storage::fake('public');
        Http::fake([
            'https://demo.webpulse.ca/api/financial-management' => Http::response([
                'success' => false,
                'message' => 'Unauthorized token.',
            ]),
        ]);

        $response = $this->post('/receipt/analyze', [
            'file' => UploadedFile::fake()->create('receipt.jpg', 128, 'image/jpeg'),
        ]);

        $response
            ->assertStatus(502)
            ->assertJsonPath('message', 'Receipt analysis failed.')
            ->assertJsonPath('error', 'Unauthorized token.');
    }
}
