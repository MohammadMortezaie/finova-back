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

    public function test_receipt_analysis_does_not_fill_form_with_ocr_blocks(): void
    {
        config([
            'services.financial_management.url' => 'https://demo.webpulse.ca/api/financial-management',
            'services.financial_management.token' => 'test-token',
        ]);
        Storage::fake('public');
        Http::fake([
            'https://demo.webpulse.ca/api/financial-management' => Http::response([
                'data' => [
                    'vendor' => "DEMO STORE\nSubtotal 10.00\nTax 1.20\nTotal 11.20",
                    'total' => "DEMO STORE\nSubtotal 10.00\nTax 1.20\nTotal 11.20",
                    'tax' => '1.20',
                    'date' => 'receipt date somewhere in the OCR text',
                    'description' => str_repeat('receipt text ', 30),
                    'category' => "food\nother text",
                ],
            ]),
        ]);

        $response = $this->post('/receipt/analyze', [
            'file' => UploadedFile::fake()->create('camera-receipt.jpg', 128, 'image/jpeg'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.vendorName', null)
            ->assertJsonPath('data.totalAmount', 11.2)
            ->assertJsonPath('data.taxAmount', 1.2)
            ->assertJsonPath('data.date', null)
            ->assertJsonPath('data.description', null)
            ->assertJsonPath('data.categoryId', null);
    }

    public function test_receipt_analysis_extracts_amounts_from_nested_ocr_text(): void
    {
        config([
            'services.financial_management.url' => 'https://demo.webpulse.ca/api/financial-management',
            'services.financial_management.token' => 'test-token',
        ]);
        Storage::fake('public');
        Http::fake([
            'https://demo.webpulse.ca/api/financial-management' => Http::response([
                'success' => true,
                'data' => [
                    [
                        'ocr_text' => "SHOP NAME\nSubtotal $13.45\nTax $1.63\nTotal $15.08\nVisa $15.08",
                        'details' => [
                            'merchant_name' => 'Shop Name',
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->post('/receipt/analyze', [
            'file' => UploadedFile::fake()->create('receipt.jpg', 128, 'image/jpeg'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.vendorName', 'Shop Name')
            ->assertJsonPath('data.subtotalAmount', 13.45)
            ->assertJsonPath('data.taxAmount', 1.63)
            ->assertJsonPath('data.totalAmount', 15.08);
    }

    public function test_receipt_analysis_maps_financial_management_analysis_payload(): void
    {
        config([
            'services.financial_management.url' => 'https://demo.webpulse.ca/api/financial-management',
            'services.financial_management.token' => 'test-token',
        ]);
        Storage::fake('public');
        Http::fake([
            'https://demo.webpulse.ca/api/financial-management' => Http::response([
                'success' => true,
                'message' => 'Analysis completed.',
                'analysis' => [
                    'date' => '2023-10-05 00:00:00',
                    'type' => 'Income',
                    'amount' => 10,
                    'subtotal' => null,
                    'shipping_amount' => null,
                    'pst' => null,
                    'gst' => null,
                    'currency' => 'USD',
                    'description' => 'Shanghai ABC Company Ltd - Textile Hanger Sample (100% Nylon) - Invoice #',
                    'invoice_number' => '',
                    'vendor_name' => 'Shanghai ABC Company Ltd',
                    'po_number' => null,
                    'due_date' => null,
                    'category' => 'Other',
                    'file_type' => 'photo',
                    'file_path' => 'chatbot/files/example.jpg',
                ],
            ]),
        ]);

        $response = $this->post('/receipt/analyze', [
            'file' => UploadedFile::fake()->create('receipt.jpg', 128, 'image/jpeg'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.vendorName', 'Shanghai ABC Company Ltd')
            ->assertJsonPath('data.totalAmount', 10)
            ->assertJsonPath('data.taxAmount', null)
            ->assertJsonPath('data.date', '2023-10-05')
            ->assertJsonPath('data.description', 'Shanghai ABC Company Ltd - Textile Hanger Sample (100% Nylon) - Invoice #')
            ->assertJsonPath('data.categoryId', 'Other');
    }
}
