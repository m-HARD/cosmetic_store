<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ProductBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'batch_code',
        'expiration_date',
        'quantity',
        'remaining_quantity',
        'cost_price',
    ];

    protected $casts = [
        'expiration_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'batch_id');
    }

    public function refundItems(): HasMany
    {
        return $this->hasMany(RefundItem::class, 'batch_id');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('remaining_quantity', '>', 0);
    }

    public function scopeFefo(Builder $query): Builder
    {
        return $query->orderBy('expiration_date')->orderBy('id');
    }
}
