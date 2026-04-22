<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'vendor_name',
        'description',
        'category_id',
        'total_amount',
        'tax_amount',
        'date',
        'receipt_uri',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'date' => 'date',
        'total_amount' => 'float',
        'tax_amount' => 'float',
    ];
}
