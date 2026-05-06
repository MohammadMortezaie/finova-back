<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'language' => $this->language,
            'currency' => $this->currency,
            'isActive' => (bool) $this->is_active,
            'plan' => $this->plan,
            'plan_expires_at' => $this->plan_expires_at?->toISOString(),
            'total_income' => $this->total_income ?? 0,
            'total_expense' => $this->total_expense ?? 0,
            'total_subscription' => $this->total_subscription ?? 0,
        ];
    }
}
