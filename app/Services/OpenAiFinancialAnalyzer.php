<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiFinancialAnalyzer
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(UploadedFile $file): array
    {
        $key = config('services.openai.key');
        if (! is_string($key) || $key === '') {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $contents = file_get_contents($file->getRealPath());
        if ($contents === false) {
            throw new RuntimeException('Could not read the uploaded file.');
        }

        $response = Http::withToken($key)
            ->timeout(60)
            ->acceptJson()
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You read receipts, invoices, and other financial files. Reply with one JSON object only. Use null for unknown values. type is "Income" when the document shows money received, and "Expense" when it shows money spent. date is YYYY-MM-DD. amount, subtotal, and tax are numbers. category is a short word such as food, rent, gas, entertainment, finance, or other. description is one short sentence.',
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => 'Extract vendor_name, date, type, amount, subtotal, tax, category, description, and currency.'],
                            $this->filePart($file->getClientOriginalName() ?: 'financial-file', $mime, base64_encode($contents)),
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            $message = $response->json('error.message');
            throw new RuntimeException(is_string($message) && $message !== '' ? $message : 'OpenAI request failed.');
        }

        $decoded = json_decode((string) $response->json('choices.0.message.content'), true);
        if (! is_array($decoded)) {
            throw new RuntimeException('The file could not be read.');
        }

        return [
            'success' => true,
            'message' => 'Analysis completed.',
            'analysis' => $decoded,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function filePart(string $name, string $mime, string $base64): array
    {
        if (str_starts_with($mime, 'image/') && ! in_array($mime, ['image/heic', 'image/heif'], true)) {
            return [
                'type' => 'image_url',
                'image_url' => ['url' => "data:{$mime};base64,{$base64}"],
            ];
        }

        return [
            'type' => 'file',
            'file' => [
                'filename' => $name,
                'file_data' => "data:{$mime};base64,{$base64}",
            ],
        ];
    }
}
