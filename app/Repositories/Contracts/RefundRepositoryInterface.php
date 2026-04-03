<?php

namespace App\Repositories\Contracts;

use App\Models\Refund;
use Illuminate\Support\Collection;

interface RefundRepositoryInterface
{
    public function findById(int $id): ?Refund;

    public function findByRefundNo(string $refundNo): ?Refund;

    public function latest(int $limit = 20): Collection;

    public function create(array $data): Refund;
}
