<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

        $originalName = $file->getClientOriginalName();
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $vendorName = $baseName ? Str::title(str_replace(['-', '_'], ' ', $baseName)) : null;

        return response()->json([
            'data' => [
                'vendorName' => $vendorName,
                'totalAmount' => null,
                'taxAmount' => null,
                'date' => null,
                'description' => null,
                'categoryId' => null,
                'receiptUri' => $receiptUri,
            ],
        ]);
    }
}
