<?php

namespace App\Repositories;

use App\Models\Refund;
use App\Repositories\Contracts\RefundRepositoryInterface;
use Illuminate\Support\Collection;

class RefundRepository implements RefundRepositoryInterface
{
    public function findById(int $id): ?Refund
    {
        return Refund::query()->with('items')->find($id);
    }

    public function findByRefundNo(string $refundNo): ?Refund
    {
        return Refund::query()
            ->with('items')
            ->where('refund_no', $refundNo)
            ->first();
    }

    public function latest(int $limit = 20): Collection
    {
        return Refund::query()
            ->latest('refunded_at')
            ->limit($limit)
            ->get();
    }

    public function create(array $data): Refund
    {
        return Refund::query()->create($data);
    }
}
