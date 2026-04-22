<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'vendorName' => $this->vendor_name,
            'description' => $this->description,
            'categoryId' => $this->category_id,
            'totalAmount' => $this->total_amount,
            'taxAmount' => $this->tax_amount,
            'date' => $this->date?->toDateString(),
            'receiptUri' => $this->receipt_uri,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'deletedAt' => $this->deleted_at?->toISOString(),
        ];
    }
}
